<?php

namespace Tests\Feature;

use App\Models\DocumentAccessRequest;
use App\Models\DocumentAccessToken;
use App\Models\DocumentEvent;
use App\Models\DocumentSession;
use App\Models\SecureDocument;
use App\Models\User;
use App\Services\SecureDocument\DocumentAccessService;
use App\Services\SecureDocument\SecureDocumentService;
use App\Support\SecureDocument as Flow;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * Spec 56: GASQ keeps viewer IP and user-agent data for 12 months.
 *
 * The rule being tested is the one that makes the window safe to apply: the
 * history is never deleted. After the sweep a document activity report still
 * says who opened it and when, and no longer says from where.
 */
class SecureDocumentRetentionTest extends TestCase
{
    use RefreshDatabase;

    private function sentDocument(): SecureDocument
    {
        Storage::fake(SecureDocumentService::DISK);
        $admin = User::factory()->create(['user_type' => 'admin', 'nda_accepted_at' => now()]);
        $service = app(SecureDocumentService::class);

        $document = $service->create($admin, [
            'document_type' => 'procurement_appraisal',
            'title' => 'Atlanta Portfolio Security Services',
        ]);
        $service->addVersion($document, UploadedFile::fake()->create('a.pdf', 10, 'application/pdf'), $admin);
        $service->addRecipient($document, ['email' => 'john@company.test'], $admin);
        $service->send($document->refresh(), $admin);

        return $document->refresh();
    }

    /**
     * Build one old trail and one recent one. Returns the ids of the rows that
     * are past the window.
     *
     * @return array{document: SecureDocument, old: array<string, int>, recent: array<string, int>}
     */
    private function trail(): array
    {
        $document = $this->sentDocument();
        $recipient = $document->recipients()->first();
        $access = app(DocumentAccessService::class);
        $old = [];
        $recent = [];

        foreach (['old' => now()->subMonths(14), 'recent' => now()->subMonths(2)] as $age => $when) {
            $session = $access->startSession($document, $recipient);
            $session->forceFill(['started_at' => $when, 'ip_address' => '203.0.113.7'])->save();

            $event = $access->log($document, Flow::EVENT_OPENED, [], recipient: $recipient, session: $session);
            $event->forceFill([
                'created_at' => $when,
                'ip_address' => '203.0.113.7',
                'user_agent' => 'Mozilla/5.0 (Macintosh)',
            ])->save();

            $token = $access->issueLink($document, $recipient)['token'];
            $token->forceFill(['last_used_at' => $when, 'last_used_ip' => '203.0.113.7'])->save();

            $requestRow = DocumentAccessRequest::create([
                'document_id' => $document->id,
                'requester_email' => "{$age}@elsewhere.test",
                'status' => DocumentAccessRequest::PENDING,
                'ip_address' => '203.0.113.7',
            ]);
            $requestRow->forceFill(['created_at' => $when])->save();

            $bucket = [
                'session' => $session->id,
                'event' => $event->id,
                'token' => $token->id,
                'request' => $requestRow->id,
            ];
            $age === 'old' ? $old = $bucket : $recent = $bucket;
        }

        return ['document' => $document, 'old' => $old, 'recent' => $recent];
    }

    public function test_addresses_older_than_the_window_are_cleared_and_newer_ones_are_kept(): void
    {
        ['document' => $document, 'old' => $old, 'recent' => $recent] = $this->trail();
        $eventsBefore = DocumentEvent::count();

        $this->artisan('documents:purge-personal-data')
            ->expectsOutputToContain('Retention sweep cleared personal data')
            ->assertSuccessful();

        // Past the window: forgotten.
        $this->assertNull(DocumentEvent::find($old['event'])->ip_address);
        $this->assertNull(DocumentEvent::find($old['event'])->user_agent);
        $this->assertNull(DocumentSession::find($old['session'])->ip_address);
        $this->assertNull(DocumentAccessToken::find($old['token'])->last_used_ip);
        $this->assertNull(DocumentAccessRequest::find($old['request'])->ip_address);

        // Inside the window: untouched.
        $this->assertSame('203.0.113.7', DocumentSession::find($recent['session'])->ip_address);
        $this->assertSame('203.0.113.7', DocumentAccessRequest::find($recent['request'])->ip_address);

        // The trail itself survives: nothing deleted, and the old event still
        // says who did what and when.
        $this->assertSame($eventsBefore, DocumentEvent::count());
        $cleared = DocumentEvent::find($old['event']);
        $this->assertSame(Flow::EVENT_OPENED, $cleared->event_type);
        $this->assertSame('john@company.test', $cleared->recipient->email);
        $this->assertTrue($cleared->created_at->lessThan(now()->subYear()));
        $this->assertSame($document->id, $cleared->document_id);
    }

    public function test_a_dry_run_reports_without_changing_anything(): void
    {
        ['old' => $old] = $this->trail();

        $this->artisan('documents:purge-personal-data --dry-run')
            ->expectsOutputToContain('would clear')
            ->assertSuccessful();

        $this->assertSame('203.0.113.7', DocumentEvent::find($old['event'])->ip_address);
        $this->assertSame('203.0.113.7', DocumentSession::find($old['session'])->ip_address);
    }

    public function test_a_zero_window_switches_the_sweep_off(): void
    {
        config(['secure-documents.personal_data_retention_months' => 0]);
        ['old' => $old] = $this->trail();

        $this->artisan('documents:purge-personal-data')
            ->expectsOutputToContain('switched off')
            ->assertSuccessful();

        $this->assertSame('203.0.113.7', DocumentEvent::find($old['event'])->ip_address);
    }

    public function test_the_window_is_configurable(): void
    {
        config(['secure-documents.personal_data_retention_months' => 24]);
        ['old' => $old] = $this->trail();

        // The 14-month-old row is inside a 24-month window, so it stays.
        $this->artisan('documents:purge-personal-data')->assertSuccessful();

        $this->assertSame('203.0.113.7', DocumentEvent::find($old['event'])->ip_address);
    }
}

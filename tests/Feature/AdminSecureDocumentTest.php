<?php

namespace Tests\Feature;

use App\Mail\SecureDocumentSentMail;
use App\Models\DocumentAccessRequest;
use App\Models\SecureDocument;
use App\Models\User;
use App\Services\SecureDocument\DocumentAccessService;
use App\Services\SecureDocument\SecureDocumentService;
use App\Support\SecureDocument as Flow;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/** The GASQ side: create and issue a document, watch it, and stop it. */
class AdminSecureDocumentTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->create([
            'user_type' => 'admin',
            'nda_accepted_at' => now(),
            'phone_verified' => true,
        ]);
    }

    /** @return array<string, mixed> */
    private function payload(array $overrides = []): array
    {
        return array_merge([
            'title' => 'Atlanta Portfolio Security Services',
            'document_type' => 'procurement_appraisal',
            'buyer_organization' => 'ABC Property Management',
            'file' => UploadedFile::fake()->create('appraisal.pdf', 200, 'application/pdf'),
            'recipient_email' => 'john@company.test',
            'recipient_name' => 'John Smith',
            'security_level' => Flow::LEVEL_OTP,
            'stakeholder_policy' => Flow::POLICY_GASQ_APPROVAL,
            'expires_in_days' => 30,
            'send_now' => 1,
        ], $overrides);
    }

    public function test_an_admin_creates_and_sends_a_document(): void
    {
        Mail::fake();
        Storage::fake(SecureDocumentService::DISK);
        $admin = $this->admin();

        $this->actingAs($admin)
            ->post(route('admin.secure-documents.store'), $this->payload())
            ->assertRedirect();

        $document = SecureDocument::first();
        $this->assertNotNull($document);
        $this->assertSame(Flow::SENT, $document->status);
        $this->assertSame(now()->addDays(30)->toDateString(), $document->expires_at->toDateString());
        $this->assertSame(1, $document->versions()->count());

        // The delivery email carries a link, never the document itself.
        Mail::assertSent(SecureDocumentSentMail::class, function ($mail) {
            return $mail->hasTo('john@company.test')
                && str_contains($mail->url, '/document/')
                && $mail->attachments === []; // a link, not the document
        });

        $this->assertDatabaseHas('document_events', ['event_type' => Flow::EVENT_SENT]);
        // The file is on the private disk, not the public one.
        Storage::disk(SecureDocumentService::DISK)->assertExists($document->currentVersion->storage_key);
    }

    public function test_the_document_can_be_saved_without_sending(): void
    {
        Mail::fake();
        Storage::fake(SecureDocumentService::DISK);

        $this->actingAs($this->admin())
            ->post(route('admin.secure-documents.store'), $this->payload(['send_now' => 0]))
            ->assertRedirect();

        $this->assertSame(Flow::READY, SecureDocument::first()->status);
        Mail::assertNothingSent();
    }

    public function test_only_an_admin_reaches_the_document_centre(): void
    {
        $vendor = User::factory()->create(['user_type' => 'vendor', 'nda_accepted_at' => now(), 'phone_verified' => true]);

        $this->actingAs($vendor)->get(route('admin.secure-documents.index'))->assertForbidden();
        // The platform's admin middleware refuses guests outright rather than
        // redirecting them, which is how every other admin route behaves.
        $this->get(route('admin.secure-documents.index'))->assertForbidden();
        $this->actingAs($this->admin())->get(route('admin.secure-documents.index'))->assertOk();
    }

    public function test_the_dashboard_shows_engagement_without_changing_the_document(): void
    {
        Mail::fake();
        Storage::fake(SecureDocumentService::DISK);
        $admin = $this->admin();
        $this->actingAs($admin)->post(route('admin.secure-documents.store'), $this->payload());

        $document = SecureDocument::first();
        $recipient = $document->recipients()->first();
        $access = app(DocumentAccessService::class);

        $session = $access->startSession($document, $recipient);
        $access->heartbeat($session, 45, page: 1, totalPages: 12);
        $access->heartbeat($session, 70, page: 4);
        $access->endSession($session);

        $this->actingAs($admin)
            ->get(route('admin.secure-documents.show', $document))
            ->assertOk()
            ->assertSee($document->public_id)
            ->assertSee('1m 55s')              // total active reading
            ->assertSee('john@company.test')
            // Spec 28: which pages held them, and how much of the document.
            ->assertSee('Reading by page')
            ->assertSee('0m 45s')
            ->assertSee('1m 10s')
            ->assertSee('2 / 12');

        $this->assertSame(Flow::SENT, $document->fresh()->status);
    }

    public function test_an_admin_approves_a_stakeholder_and_they_get_their_own_link(): void
    {
        Mail::fake();
        Storage::fake(SecureDocumentService::DISK);
        $admin = $this->admin();
        $this->actingAs($admin)->post(route('admin.secure-documents.store'), $this->payload());

        $document = SecureDocument::first();
        app(DocumentAccessService::class)->requestStakeholderAccess($document, 'mary@company.test', 'Mary Jones');
        $request = DocumentAccessRequest::first();

        $this->actingAs($admin)
            ->post(route('admin.secure-documents.requests.decide', $request), ['decision' => 'approve'])
            ->assertRedirect();

        $this->assertSame(DocumentAccessRequest::APPROVED, $request->fresh()->status);
        $this->assertNotNull($document->recipientFor('mary@company.test'));
        Mail::assertSent(SecureDocumentSentMail::class, fn ($mail) => $mail->hasTo('mary@company.test'));
    }

    public function test_an_admin_denies_a_request(): void
    {
        Mail::fake();
        Storage::fake(SecureDocumentService::DISK);
        $admin = $this->admin();
        $this->actingAs($admin)->post(route('admin.secure-documents.store'), $this->payload());
        $document = SecureDocument::first();
        app(DocumentAccessService::class)->requestStakeholderAccess($document, 'rival@elsewhere.test');

        $this->actingAs($admin)
            ->post(route('admin.secure-documents.requests.decide', DocumentAccessRequest::first()), [
                'decision' => 'deny',
                'note' => 'Not part of the buying organisation',
            ])
            ->assertRedirect();

        $this->assertSame(DocumentAccessRequest::DENIED, DocumentAccessRequest::first()->status);
        $this->assertNull($document->recipientFor('rival@elsewhere.test'));
        $this->assertDatabaseHas('document_events', ['event_type' => Flow::EVENT_STAKEHOLDER_DENIED]);
    }

    public function test_revoke_and_restore(): void
    {
        Mail::fake();
        Storage::fake(SecureDocumentService::DISK);
        $admin = $this->admin();
        $this->actingAs($admin)->post(route('admin.secure-documents.store'), $this->payload());
        $document = SecureDocument::first();

        $this->actingAs($admin)
            ->post(route('admin.secure-documents.revoke', $document), ['reason' => 'Superseded'])
            ->assertRedirect();

        $document->refresh();
        $this->assertSame(Flow::REVOKED, $document->status);
        $this->assertNotNull($document->revoked_at);

        $this->actingAs($admin)->post(route('admin.secure-documents.restore', $document))->assertRedirect();

        $this->assertSame(Flow::SENT, $document->fresh()->status);
        $this->assertNull($document->fresh()->revoked_at);
    }

    public function test_expiry_sweep_closes_documents_whose_time_has_run_out(): void
    {
        Mail::fake();
        Storage::fake(SecureDocumentService::DISK);
        $admin = $this->admin();
        $this->actingAs($admin)->post(route('admin.secure-documents.store'), $this->payload());

        $document = SecureDocument::first();
        $document->forceFill(['expires_at' => now()->subHour()])->save();

        $this->artisan('documents:expire')->assertExitCode(0);

        $this->assertSame(Flow::EXPIRED, $document->fresh()->status);
        $this->assertDatabaseHas('document_events', ['event_type' => Flow::EVENT_ACCESS_EXPIRED]);

        // A document still inside its window is left alone.
        $this->assertSame(1, SecureDocument::where('status', Flow::EXPIRED)->count());
    }

    public function test_a_non_pdf_upload_is_rejected(): void
    {
        Storage::fake(SecureDocumentService::DISK);

        $this->actingAs($this->admin())
            ->post(route('admin.secure-documents.store'), $this->payload([
                'file' => UploadedFile::fake()->create('notes.txt', 10, 'text/plain'),
            ]))
            ->assertSessionHasErrors('file');

        $this->assertSame(0, SecureDocument::count());
    }
}

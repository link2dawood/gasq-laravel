<?php

namespace Tests\Feature;

use App\Mail\SecureDocumentCodeMail;
use App\Models\DocumentAccessRequest;
use App\Models\DocumentSession;
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

/**
 * The acceptance tests from the Secure Document plan, section 64, plus the
 * rules from section 65 that the whole thing rests on: no raw file URL, no
 * sequential identifiers, and a forwarded link authorises nobody.
 */
class SecureDocumentTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->create(['user_type' => 'admin', 'nda_accepted_at' => now()]);
    }

    /** A document uploaded, given a primary recipient and issued. */
    private function sentDocument(array $attributes = [], string $email = 'john@company.test'): SecureDocument
    {
        Storage::fake(SecureDocumentService::DISK);
        $admin = $this->admin();
        $service = app(SecureDocumentService::class);

        $document = $service->create($admin, array_merge([
            'document_type' => 'procurement_appraisal',
            'title' => 'Atlanta Portfolio Security Services',
            'buyer_organization' => 'ABC Property Management',
            'expires_at' => now()->addDays(30),
        ], $attributes));

        $service->addVersion($document, UploadedFile::fake()->create('appraisal.pdf', 120, 'application/pdf'), $admin);
        $service->addRecipient($document, ['email' => $email, 'name' => 'John Smith'], $admin);
        $service->send($document->refresh(), $admin);

        return $document->refresh();
    }

    private function linkFor(SecureDocument $document, ?string $email = null): string
    {
        $recipient = $email ? $document->recipientFor($email) : $document->recipients()->first();

        return app(DocumentAccessService::class)->issueLink($document, $recipient)['plain'];
    }

    /** Pull the code out of the mail the recipient was sent. */
    private function codeFromMail(): string
    {
        $code = null;
        Mail::assertSent(SecureDocumentCodeMail::class, function ($mail) use (&$code) {
            $code = $mail->code;

            return true;
        });

        return (string) $code;
    }

    public function test_the_public_number_is_readable_and_not_a_database_id(): void
    {
        $document = $this->sentDocument();

        $this->assertMatchesRegularExpression('/^GASQ-APP-\d{4}-\d{5}$/', $document->public_id);
        $this->assertSame('GASQ-APP-'.now()->format('Y').'-00001', $document->public_id);
    }

    public function test_only_the_token_hash_is_stored(): void
    {
        $document = $this->sentDocument();
        $plain = $this->linkFor($document);

        $this->assertDatabaseMissing('document_access_tokens', ['token_hash' => $plain]);
        $this->assertDatabaseHas('document_access_tokens', ['token_hash' => hash('sha256', $plain)]);
    }

    /** Test 1 and 4: the invited recipient verifies and reaches the viewer. */
    public function test_an_authorised_recipient_verifies_and_opens_the_document(): void
    {
        Mail::fake();
        $document = $this->sentDocument();
        $token = $this->linkFor($document);

        $this->get(route('secure-documents.open', $token))
            ->assertOk()
            ->assertSee('Verify your identity')
            ->assertSee('j***@company.test');

        $this->post(route('secure-documents.send-code'))->assertRedirect();
        $code = $this->codeFromMail();
        $this->assertMatchesRegularExpression('/^\d{6}$/', $code);

        $this->post(route('secure-documents.verify'), ['code' => $code])
            ->assertRedirect(route('secure-documents.view'));

        $this->get(route('secure-documents.view'))
            ->assertOk()
            ->assertSee($document->public_id)
            ->assertSee('john@company.test');

        $this->assertDatabaseHas('document_events', ['event_type' => Flow::EVENT_VERIFIED]);
        $this->assertDatabaseHas('document_events', ['event_type' => Flow::EVENT_OPENED]);
        $this->assertNotNull($document->recipients()->first()->fresh()->first_viewed_at);
    }

    /** Test 3: a wrong code is refused and recorded. */
    public function test_a_wrong_code_is_refused_and_logged(): void
    {
        Mail::fake();
        $document = $this->sentDocument();
        $token = $this->linkFor($document);
        $this->get(route('secure-documents.open', $token));
        $this->post(route('secure-documents.send-code'));

        $this->post(route('secure-documents.verify'), ['code' => '000000'])
            ->assertSessionHasErrors('code');

        $this->get(route('secure-documents.view'))->assertRedirect();
        $this->assertDatabaseHas('document_events', ['event_type' => Flow::EVENT_VERIFICATION_FAILED]);
    }

    /** Test 5 and 6: a first view, then a return visit, are separate sessions. */
    public function test_a_return_visit_is_recorded_as_a_revisit(): void
    {
        Mail::fake();
        $document = $this->sentDocument();
        $this->verifyAs($document);

        $this->get(route('secure-documents.view'))->assertOk();
        $this->get(route('secure-documents.view'))->assertOk();

        $recipient = $document->recipients()->first()->fresh();
        $this->assertSame(2, (int) $recipient->session_count);
        $this->assertSame(2, DocumentSession::count());
        $this->assertDatabaseHas('document_events', ['event_type' => Flow::EVENT_OPENED]);
        $this->assertDatabaseHas('document_events', ['event_type' => Flow::EVENT_REVISITED]);
    }

    /** Test 8: idle time is not counted as reading time. */
    public function test_idle_time_is_not_counted_as_active_viewing(): void
    {
        Mail::fake();
        $document = $this->sentDocument();
        $this->verifyAs($document);
        $this->get(route('secure-documents.view'));

        $this->postJson(route('secure-documents.heartbeat'), ['seconds' => 20])->assertOk();
        // An hour away from the screen arrives as one long gap; only the idle
        // ceiling counts, not the whole gap.
        $this->postJson(route('secure-documents.heartbeat'), ['seconds' => 300])->assertOk();

        $session = DocumentSession::first();
        $this->assertSame(20 + DocumentAccessService::IDLE_SECONDS, (int) $session->active_seconds);
        $this->assertLessThan(300, (int) $session->active_seconds);
    }

    /** Test 9: with downloads off, the viewer offers none and the file needs the session. */
    public function test_the_file_is_never_reachable_without_a_verified_session(): void
    {
        Mail::fake();
        $document = $this->sentDocument();

        // No session at all.
        $this->get(route('secure-documents.file'))->assertRedirect();

        // Link clicked but not verified.
        $this->get(route('secure-documents.open', $this->linkFor($document)));
        $this->get(route('secure-documents.file'))->assertForbidden();

        $this->verifyAs($document);
        $response = $this->get(route('secure-documents.file'));
        $response->assertOk()->assertHeader('content-type', 'application/pdf');

        $this->get(route('secure-documents.view'))->assertOk()->assertDontSee('>Download<', false);
    }

    /** Test 11: the watermark carries the viewer's identity. */
    public function test_the_viewer_is_watermarked_with_the_reader_identity(): void
    {
        Mail::fake();
        $document = $this->sentDocument();
        $this->verifyAs($document);

        $this->get(route('secure-documents.view'))
            ->assertOk()
            ->assertSee('CONFIDENTIAL')
            ->assertSee('Viewed by john@company.test')
            ->assertSee('ABC Property Management')
            ->assertSee($document->public_id);
    }

    /** Test 12: expiry closes access. */
    public function test_an_expired_document_cannot_be_opened(): void
    {
        $document = $this->sentDocument();
        $token = $this->linkFor($document);
        $document->forceFill(['expires_at' => now()->subDay()])->save();

        $this->get(route('secure-documents.open', $token))
            ->assertOk()
            ->assertSee('This document is no longer available');
    }

    /** Test 13: revocation takes effect on the next request. */
    public function test_revocation_blocks_access_immediately(): void
    {
        Mail::fake();
        $document = $this->sentDocument();
        $this->verifyAs($document);
        $this->get(route('secure-documents.view'))->assertOk();

        app(SecureDocumentService::class)->revoke($document, $this->admin(), 'Superseded by a revised appraisal');

        $this->get(route('secure-documents.view'))->assertRedirect();
        $this->get(route('secure-documents.file'))->assertRedirect();
        $this->assertDatabaseHas('document_events', ['event_type' => Flow::EVENT_ACCESS_REVOKED]);
    }

    /** Test 14 and 15: a forwarded link authorises nobody, and asks who they are. */
    public function test_a_forwarded_link_does_not_authorise_the_new_reader(): void
    {
        Mail::fake();
        $document = $this->sentDocument();
        $token = $this->linkFor($document);

        // Mary opens John's link. She is offered verification for John's
        // address, which she cannot receive, plus her own request route.
        $this->get(route('secure-documents.open', $token))
            ->assertOk()
            ->assertSee('Verify your identity')
            ->assertSee('Not j***@company.test?')
            ->assertDontSee('sdv-stage', false);

        $this->post(route('secure-documents.request-access', $token), [
            'email' => 'mary@company.test',
            'name' => 'Mary Jones',
        ])->assertOk()->assertSee('Access request submitted');

        $this->assertDatabaseHas('document_access_requests', [
            'requester_email' => 'mary@company.test',
            'status' => DocumentAccessRequest::PENDING,
        ]);
        $this->assertNull($document->recipientFor('mary@company.test'));
        $this->assertDatabaseHas('document_events', ['event_type' => Flow::EVENT_STAKEHOLDER_REQUESTED]);
    }

    /** Test 16: an approved stakeholder gets their own access, not John's. */
    public function test_an_approved_stakeholder_verifies_on_their_own_address(): void
    {
        Mail::fake();
        $document = $this->sentDocument();
        $access = app(DocumentAccessService::class);
        $access->requestStakeholderAccess($document, 'mary@company.test', 'Mary Jones');

        $recipient = $access->approveStakeholder(DocumentAccessRequest::first(), $this->admin());

        $this->assertSame(Flow::RECIPIENT_STAKEHOLDER, $recipient->recipient_type);
        $this->assertSame(2, $document->recipients()->count());

        $this->get(route('secure-documents.open', $this->linkFor($document, 'mary@company.test')))
            ->assertOk()
            ->assertSee('m***@company.test');

        $this->post(route('secure-documents.send-code'));
        Mail::assertSent(SecureDocumentCodeMail::class, fn ($mail) => $mail->hasTo('mary@company.test'));
    }

    public function test_same_domain_policy_admits_a_colleague_without_waiting(): void
    {
        $document = $this->sentDocument(['stakeholder_policy' => Flow::POLICY_SAME_DOMAIN]);

        $outcome = app(DocumentAccessService::class)
            ->requestStakeholderAccess($document, 'mary@company.test', 'Mary Jones');

        $this->assertSame('granted', $outcome['outcome']);
        $this->assertNotNull($document->recipientFor('mary@company.test'));

        // A stranger from another company still waits for a decision.
        $other = app(DocumentAccessService::class)
            ->requestStakeholderAccess($document, 'rival@elsewhere.test');
        $this->assertSame('pending', $other['outcome']);
    }

    public function test_revoking_one_recipient_leaves_the_others_working(): void
    {
        Mail::fake();
        $document = $this->sentDocument();
        $access = app(DocumentAccessService::class);
        $mary = app(SecureDocumentService::class)->addRecipient(
            $document, ['email' => 'mary@company.test'], null, Flow::RECIPIENT_STAKEHOLDER,
        );
        $maryToken = $access->issueLink($document, $mary)['plain'];
        $johnToken = $this->linkFor($document, 'john@company.test');

        $access->revokeRecipient($mary->fresh(), $this->admin(), 'Left the company');

        $this->get(route('secure-documents.open', $maryToken))
            ->assertOk()->assertSee('Your access has been withdrawn');
        $this->get(route('secure-documents.open', $johnToken))
            ->assertOk()->assertSee('Verify your identity');
    }

    /** Test 17: a revision keeps the earlier version and its history. */
    public function test_a_new_version_supersedes_without_erasing_the_old_one(): void
    {
        Storage::fake(SecureDocumentService::DISK);
        $document = $this->sentDocument();
        $admin = $this->admin();

        $second = app(SecureDocumentService::class)->addVersion(
            $document,
            UploadedFile::fake()->create('appraisal-v2.pdf', 140, 'application/pdf'),
            $admin,
            'Wage assumption updated',
        );

        $this->assertSame(2, (int) $second->version_number);
        $this->assertSame($second->id, $document->fresh()->current_version_id);
        $this->assertSame(2, $document->versions()->count());

        $first = $document->versions()->where('version_number', 1)->first();
        $this->assertNotNull($first->superseded_at);
        $this->assertDatabaseHas('document_events', ['event_type' => Flow::EVENT_VERSION_CREATED]);
    }

    public function test_lifecycle_status_is_never_moved_by_reading(): void
    {
        Mail::fake();
        $document = $this->sentDocument();
        $this->verifyAs($document);

        $this->get(route('secure-documents.view'))->assertOk();
        $this->get(route('secure-documents.view'))->assertOk();

        // Engagement lives in the events; the document is still simply "sent".
        $this->assertSame(Flow::SENT, $document->fresh()->status);
        $this->assertGreaterThan(0, $document->events()->count());

        $this->expectException(\RuntimeException::class);
        app(SecureDocumentService::class)->transition($document, Flow::DRAFT, $this->admin());
    }

    public function test_a_document_cannot_be_sent_without_a_file(): void
    {
        $admin = $this->admin();
        $document = app(SecureDocumentService::class)->create($admin, [
            'document_type' => 'service_estimate',
            'title' => 'Estimate without a file',
        ]);

        $this->expectException(\RuntimeException::class);
        app(SecureDocumentService::class)->send($document, $admin);
    }

    /** Verify as the primary recipient and leave the session in place. */
    private function verifyAs(SecureDocument $document, string $email = 'john@company.test'): void
    {
        $this->get(route('secure-documents.open', $this->linkFor($document, $email)));
        $this->post(route('secure-documents.send-code'));
        $this->post(route('secure-documents.verify'), ['code' => $this->codeFromMail()]);
    }
}

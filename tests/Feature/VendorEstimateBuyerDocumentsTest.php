<?php

namespace Tests\Feature;

use App\Jobs\SendVendorEstimateDelivery;
use App\Models\User;
use App\Models\VendorBuyerLead;
use App\Models\VendorEstimateAttachment;
use App\Models\VendorEstimateDelivery;
use App\Models\VendorLeadEstimate;
use App\Models\VendorLeadLink;
use App\Support\BuyerQualificationQuestionnaire;
use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * A contract, scope of work or risk assessment reaching the buyer.
 *
 * The condition the client set was that it stay password protected until the
 * review. An emailed attachment cannot do that, so these are served from the
 * verified access page instead, under the same gate as the estimate PDF.
 */
class VendorEstimateBuyerDocumentsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Http::preventStrayRequests();
        $this->withoutMiddleware(ValidateCsrfToken::class);
        $this->travelTo(now()->setDate(2026, 11, 18)->setTime(20, 4));
        config(['queue.default' => 'database']);
        Mail::fake();
        Storage::fake('local');
    }

    private function readyEstimate(): array
    {
        $vendor = User::factory()->create(['user_type' => 'vendor', 'nda_accepted_at' => now(), 'currency' => 'USD']);
        $this->actingAs($vendor)->get(route('vendor-buyer-leads.index'))->assertOk();
        $link = VendorLeadLink::where('vendor_id', $vendor->id)->firstOrFail();
        auth()->forgetGuards();
        $this->post(route('buyer-enquiries.store', $link->token), [
            'company' => 'Buyer Company', 'contact_name' => 'Avery Buyer', 'contact_title' => 'Facilities Director',
            'email' => 'avery@example.test', 'phone' => '+14045550123', 'site_address' => '100 Main Street',
            'decision_maker_name' => 'Jordan Chief', 'decision_maker_phone' => '+14045550124', 'decision_maker_email' => 'jordan@example.test',
            'approved_budget' => 12000, 'approved_budget_basis' => 'monthly',
            'service_types' => ['unarmed'], 'industry' => 'Commercial Office Buildings', 'preferred_contact_method' => 'email',
            'scope' => 'Lobby security.',
            'start_date' => now()->addDays(30)->toDateString(), 'service_end_date' => now()->addDays(395)->toDateString(),
            'hours_per_day' => 8, 'days_per_week' => 5, 'officers_per_shift' => 2, 'number_of_weeks' => 52,
            'answers' => array_fill_keys(array_keys(BuyerQualificationQuestionnaire::questions()), 'yes'),
        ])->assertRedirect()->assertSessionHasNoErrors();
        $lead = VendorBuyerLead::firstOrFail();
        $this->actingAs($vendor);
        $this->post(route('vendor-calendar.settings'), ['enabled' => 1, 'timezone' => 'America/New_York', 'meeting_details' => 'https://example.test/meeting'])->assertRedirect();
        $this->post(route('vendor-estimates.store', $lead), ['title' => 'Lobby estimate', 'scope' => 'Lobby security.', 'hourly_rate' => 64.29, 'weekly_hours' => 336, 'weeks_per_year' => 52, 'one_time_cost' => 1123.27, 'valid_until' => now()->addDays(30)->toDateString(), 'status' => 'ready'])->assertRedirect();

        return ['vendor' => $vendor, 'estimate' => VendorLeadEstimate::firstOrFail()];
    }

    private function attach(User $vendor, VendorLeadEstimate $estimate, string $name): VendorEstimateAttachment
    {
        $this->actingAs($vendor)->post(route('vendor-estimates.attachments.store', $estimate), [
            'attachments' => [UploadedFile::fake()->create($name, 30, 'application/pdf')],
        ])->assertRedirect()->assertSessionHasNoErrors();

        return $estimate->attachments()->where('original_name', $name)->firstOrFail();
    }

    private function send(User $vendor, VendorLeadEstimate $estimate): VendorEstimateDelivery
    {
        $this->actingAs($vendor)->post(route('vendor-estimates.send', $estimate), [
            'request_key' => (string) Str::uuid(), 'pdf_password' => 'WorkflowPassword2026',
        ])->assertRedirect()->assertSessionHasNoErrors();

        return VendorEstimateDelivery::latest('id')->firstOrFail();
    }

    public function test_files_are_private_until_the_vendor_includes_them(): void
    {
        ['vendor' => $vendor, 'estimate' => $estimate] = $this->readyEstimate();
        $contract = $this->attach($vendor, $estimate, 'contract.pdf');
        $this->assertFalse($contract->share_with_buyer);

        $delivery = $this->send($vendor, $estimate);
        $this->assertSame(0, $delivery->documents()->count());
    }

    public function test_an_included_contract_is_copied_onto_the_delivery_and_named_in_the_email(): void
    {
        ['vendor' => $vendor, 'estimate' => $estimate] = $this->readyEstimate();
        $contract = $this->attach($vendor, $estimate, 'contract.pdf');
        $this->attach($vendor, $estimate, 'risk-assessment.pdf');

        $this->actingAs($vendor)->patch(route('vendor-estimates.attachments.share', [$estimate, $contract]), ['share_with_buyer' => 1])
            ->assertRedirect()->assertSessionHasNoErrors();

        $delivery = $this->send($vendor, $estimate);
        $this->assertSame(1, $delivery->documents()->count());
        $document = $delivery->documents()->firstOrFail();
        $this->assertSame('contract.pdf', $document->original_name);
        // Its own copy, not a pointer at the vendor's file.
        $this->assertNotSame($contract->path, $document->path);
        $this->assertStringStartsWith('vendor-estimate-delivery-documents/'.$delivery->id.'/', $document->path);
        Storage::disk('local')->assertExists($document->path);
        $this->assertSame($contract->sha256, $document->sha256);

        $body = view('emails.vendor-lead-estimate', ['delivery' => $delivery->load('documents'), 'snapshot' => $delivery->snapshot])->render();
        $this->assertStringContainsString('contract.pdf', $body);
        $this->assertStringContainsString('not attached to this email', $body);
        $this->assertStringNotContainsString('risk-assessment.pdf', $body);
    }

    public function test_the_buyer_cannot_download_a_document_before_verifying_their_email(): void
    {
        ['vendor' => $vendor, 'estimate' => $estimate] = $this->readyEstimate();
        $contract = $this->attach($vendor, $estimate, 'contract.pdf');
        $this->actingAs($vendor)->patch(route('vendor-estimates.attachments.share', [$estimate, $contract]), ['share_with_buyer' => 1])->assertRedirect();
        $delivery = $this->send($vendor, $estimate);
        (new SendVendorEstimateDelivery($delivery->id))->handle();
        $document = $delivery->documents()->firstOrFail();
        auth()->forgetGuards();

        // Before the vendor releases access at all.
        $this->post(route('vendor-pdf.document', [$delivery->fresh()->scheduling_token, $document]))->assertStatus(403);

        // The access page gives nothing away either.
        $this->get($delivery->fresh()->accessUrl())->assertOk()->assertDontSee('contract.pdf');
    }

    public function test_unsharing_does_not_retract_a_document_already_sent(): void
    {
        ['vendor' => $vendor, 'estimate' => $estimate] = $this->readyEstimate();
        $contract = $this->attach($vendor, $estimate, 'contract.pdf');
        $this->actingAs($vendor)->patch(route('vendor-estimates.attachments.share', [$estimate, $contract]), ['share_with_buyer' => 1])->assertRedirect();
        $delivery = $this->send($vendor, $estimate);
        $document = $delivery->documents()->firstOrFail();

        // Unshare, then delete the vendor's own copy entirely.
        $this->actingAs($vendor)->patch(route('vendor-estimates.attachments.share', [$estimate, $contract]), ['share_with_buyer' => 0])->assertRedirect();
        $this->actingAs($vendor)->delete(route('vendor-estimates.attachments.destroy', [$estimate, $contract]))->assertRedirect();

        // What was sent is still what was sent.
        $this->assertSame(1, $delivery->documents()->count());
        Storage::disk('local')->assertExists($document->fresh()->path);
    }

    public function test_a_later_send_reflects_what_is_marked_at_that_moment(): void
    {
        ['vendor' => $vendor, 'estimate' => $estimate] = $this->readyEstimate();
        $contract = $this->attach($vendor, $estimate, 'contract.pdf');
        $first = $this->send($vendor, $estimate);
        $this->assertSame(0, $first->documents()->count());

        $this->actingAs($vendor)->patch(route('vendor-estimates.attachments.share', [$estimate, $contract]), ['share_with_buyer' => 1])->assertRedirect();
        $second = $this->send($vendor, $estimate);

        $this->assertSame(0, $first->documents()->count());
        $this->assertSame(1, $second->documents()->count());
    }

    public function test_another_vendor_cannot_change_what_is_sent_to_this_buyer(): void
    {
        ['vendor' => $vendor, 'estimate' => $estimate] = $this->readyEstimate();
        $contract = $this->attach($vendor, $estimate, 'contract.pdf');
        $other = User::factory()->create(['user_type' => 'vendor', 'nda_accepted_at' => now()]);

        $this->actingAs($other)->patch(route('vendor-estimates.attachments.share', [$estimate, $contract]), ['share_with_buyer' => 1])
            ->assertNotFound();

        $this->assertFalse($contract->fresh()->share_with_buyer);
    }

    public function test_the_vendor_screen_shows_whether_a_file_goes_to_the_buyer(): void
    {
        ['vendor' => $vendor, 'estimate' => $estimate] = $this->readyEstimate();
        $contract = $this->attach($vendor, $estimate, 'contract.pdf');

        $this->actingAs($vendor)->get(route('vendor-estimates.edit', $estimate))->assertOk()->assertSee('Not included');

        $this->actingAs($vendor)->patch(route('vendor-estimates.attachments.share', [$estimate, $contract]), ['share_with_buyer' => 1])->assertRedirect();

        $this->actingAs($vendor)->get(route('vendor-estimates.edit', $estimate))->assertOk()->assertSee('Included');
    }
}

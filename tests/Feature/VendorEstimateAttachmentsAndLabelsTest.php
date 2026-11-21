<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\VendorBuyerLead;
use App\Models\VendorEstimateAttachment;
use App\Models\VendorLeadEstimate;
use App\Models\VendorLeadLink;
use App\Support\BuyerQualificationQuestionnaire;
use App\Support\ReportClock;
use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class VendorEstimateAttachmentsAndLabelsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Http::preventStrayRequests();
        $this->withoutMiddleware(ValidateCsrfToken::class);
        $this->travelTo(now()->setDate(2026, 11, 18)->setTime(20, 4));
        Mail::fake();
        Storage::fake('local');
    }

    private function estimate(): array
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
            'answers' => array_merge(array_fill_keys(array_keys(BuyerQualificationQuestionnaire::questions()), 'yes'), ['q3a' => 'operating_budget', 'q9a' => 'standard']),
        ])->assertRedirect()->assertSessionHasNoErrors();
        $lead = VendorBuyerLead::firstOrFail();
        $this->actingAs($vendor)->post(route('vendor-estimates.store', $lead), [
            'title' => 'Lobby estimate', 'scope' => 'Lobby security.', 'hourly_rate' => 64.29, 'weekly_hours' => 336,
            'weeks_per_year' => 52, 'one_time_cost' => 1123.27, 'valid_until' => now()->addDays(30)->toDateString(), 'status' => 'ready',
        ])->assertRedirect();

        return ['vendor' => $vendor, 'estimate' => VendorLeadEstimate::firstOrFail()];
    }

    public function test_money_fields_carry_the_currency_symbol_and_the_retainer_label(): void
    {
        ['vendor' => $vendor, 'estimate' => $estimate] = $this->estimate();

        $page = $this->actingAs($vendor)->get(route('vendor-estimates.edit', $estimate))->assertOk();
        $page->assertSee('One-time retainer fee');
        $page->assertDontSee('One-time cost');
        $page->assertSee('input-group-text', false);
        // The symbol sits beside the rate and the retainer, not beside hours.
        $this->assertSame(2, substr_count($page->getContent(), 'input-group-text'));
    }

    public function test_staff_required_is_optional_whole_officers_and_reaches_the_report(): void
    {
        ['vendor' => $vendor, 'estimate' => $estimate] = $this->estimate();

        // Optional: an estimate saved without it stays valid and prints nothing.
        $this->assertNull($estimate->staff_required);
        $this->actingAs($vendor)->get(route('vendor-estimates.edit', $estimate))->assertOk()->assertSee('Staff required');

        $this->actingAs($vendor)->patch(route('vendor-estimates.update', $estimate), [
            'title' => 'Lobby estimate', 'scope' => 'Lobby security.', 'hourly_rate' => 64.29, 'weekly_hours' => 336,
            'weeks_per_year' => 52, 'one_time_cost' => 1123.27, 'staff_required' => 7,
            'valid_until' => now()->addDays(30)->toDateString(), 'status' => 'ready',
        ])->assertRedirect()->assertSessionHasNoErrors();

        $this->assertSame(7, $estimate->fresh()->staff_required);

        $html = view('pdf.vendor-lead-estimate', [
            'estimate' => $estimate->fresh()->load('vendor'), 'user' => $vendor, 'masked' => true,
            'generatedAt' => ReportClock::stamp(), 'reportType' => 'vendor-lead-estimate-buyer',
        ])->render();
        $this->assertStringContainsString('Staff required', $html);
        $this->assertStringContainsString('One-time retainer fee', $html);
        $this->assertStringNotContainsString('One-time cost', $html);
    }

    public function test_staff_required_rejects_fractions_of_an_officer(): void
    {
        ['vendor' => $vendor, 'estimate' => $estimate] = $this->estimate();

        $this->actingAs($vendor)->patch(route('vendor-estimates.update', $estimate), [
            'title' => 'Lobby estimate', 'scope' => 'Lobby security.', 'hourly_rate' => 64.29, 'weekly_hours' => 336,
            'weeks_per_year' => 52, 'one_time_cost' => 1123.27, 'staff_required' => 2.5,
            'valid_until' => now()->addDays(30)->toDateString(), 'status' => 'ready',
        ])->assertSessionHasErrors('staff_required');

        $this->assertNull($estimate->fresh()->staff_required);
    }

    public function test_vendor_can_attach_a_photo_and_a_document_then_download_them(): void
    {
        ['vendor' => $vendor, 'estimate' => $estimate] = $this->estimate();

        $this->actingAs($vendor)->post(route('vendor-estimates.attachments.store', $estimate), [
            'attachments' => [
                UploadedFile::fake()->image('site-entrance.jpg'),
                UploadedFile::fake()->create('post-orders.pdf', 40, 'application/pdf'),
            ],
        ])->assertRedirect()->assertSessionHasNoErrors();

        $this->assertSame(2, $estimate->attachments()->count());
        $photo = $estimate->attachments()->where('original_name', 'site-entrance.jpg')->firstOrFail();
        $this->assertTrue($photo->isImage());
        // Private disk only. Nothing reachable under public/.
        $this->assertStringStartsWith('vendor-estimate-attachments/'.$estimate->id.'/', $photo->path);
        Storage::disk('local')->assertExists($photo->path);

        $this->actingAs($vendor)->get(route('vendor-estimates.attachments.download', [$estimate, $photo]))
            ->assertOk()
            ->assertHeader('Content-Disposition', 'attachment; filename=site-entrance.jpg');
    }

    public function test_attachments_are_listed_on_the_estimate(): void
    {
        ['vendor' => $vendor, 'estimate' => $estimate] = $this->estimate();
        $this->actingAs($vendor)->post(route('vendor-estimates.attachments.store', $estimate), [
            'attachments' => [UploadedFile::fake()->image('site-entrance.jpg')],
        ])->assertRedirect();

        $this->actingAs($vendor)->get(route('vendor-estimates.edit', $estimate))
            ->assertOk()
            ->assertSee('site-entrance.jpg')
            ->assertSee('Supporting files and photos');
    }

    public function test_vendor_can_remove_an_attachment_and_the_file_goes_with_it(): void
    {
        ['vendor' => $vendor, 'estimate' => $estimate] = $this->estimate();
        $this->actingAs($vendor)->post(route('vendor-estimates.attachments.store', $estimate), [
            'attachments' => [UploadedFile::fake()->image('site-entrance.jpg')],
        ])->assertRedirect();
        $attachment = $estimate->attachments()->firstOrFail();

        $this->actingAs($vendor)->delete(route('vendor-estimates.attachments.destroy', [$estimate, $attachment]))->assertRedirect();

        $this->assertSame(0, $estimate->attachments()->count());
        Storage::disk('local')->assertMissing($attachment->path);
    }

    public function test_executables_and_oversized_files_are_refused(): void
    {
        ['vendor' => $vendor, 'estimate' => $estimate] = $this->estimate();

        $this->actingAs($vendor)->post(route('vendor-estimates.attachments.store', $estimate), [
            'attachments' => [UploadedFile::fake()->create('payload.php', 10, 'application/x-php')],
        ])->assertSessionHasErrors('attachments.0');

        $this->actingAs($vendor)->post(route('vendor-estimates.attachments.store', $estimate), [
            'attachments' => [UploadedFile::fake()->create('huge.pdf', VendorEstimateAttachment::MAX_KILOBYTES + 1, 'application/pdf')],
        ])->assertSessionHasErrors('attachments.0');

        $this->assertSame(0, $estimate->attachments()->count());
    }

    public function test_the_per_estimate_file_limit_is_enforced(): void
    {
        ['vendor' => $vendor, 'estimate' => $estimate] = $this->estimate();
        $files = [];
        for ($i = 0; $i < VendorEstimateAttachment::MAX_PER_ESTIMATE; $i++) {
            $files[] = UploadedFile::fake()->image("photo-{$i}.jpg");
        }
        $this->actingAs($vendor)->post(route('vendor-estimates.attachments.store', $estimate), ['attachments' => $files])->assertRedirect()->assertSessionHasNoErrors();

        $this->actingAs($vendor)->post(route('vendor-estimates.attachments.store', $estimate), [
            'attachments' => [UploadedFile::fake()->image('one-too-many.jpg')],
        ])->assertSessionHasErrors('attachments');

        $this->assertSame(VendorEstimateAttachment::MAX_PER_ESTIMATE, $estimate->attachments()->count());
    }

    public function test_another_vendor_cannot_reach_these_files(): void
    {
        ['vendor' => $vendor, 'estimate' => $estimate] = $this->estimate();
        $this->actingAs($vendor)->post(route('vendor-estimates.attachments.store', $estimate), [
            'attachments' => [UploadedFile::fake()->image('site-entrance.jpg')],
        ])->assertRedirect();
        $attachment = $estimate->attachments()->firstOrFail();
        $other = User::factory()->create(['user_type' => 'vendor', 'nda_accepted_at' => now()]);

        $this->actingAs($other)->get(route('vendor-estimates.attachments.download', [$estimate, $attachment]))->assertNotFound();
        $this->actingAs($other)->delete(route('vendor-estimates.attachments.destroy', [$estimate, $attachment]))->assertNotFound();
        $this->actingAs($other)->post(route('vendor-estimates.attachments.store', $estimate), [
            'attachments' => [UploadedFile::fake()->image('intruder.jpg')],
        ])->assertNotFound();

        $this->assertSame(1, $estimate->attachments()->count());
    }

    public function test_reports_are_timestamped_in_the_eastern_zone(): void
    {
        // 8:04 PM UTC on 18 November is 3:04 PM in New York.
        $this->assertSame('America/New_York', ReportClock::timezone());
        $this->assertSame('Nov 18, 2026 3:04 PM EST', ReportClock::stamp());
        $this->assertSame('3:04 PM', ReportClock::now()->format('g:i A'));
    }

    public function test_the_eastern_zone_follows_daylight_saving(): void
    {
        $this->travelTo(now()->setDate(2026, 7, 15)->setTime(20, 4));

        // Not a fixed offset: July reads EDT, an hour later than EST would.
        $this->assertSame('Jul 15, 2026 4:04 PM EDT', ReportClock::stamp());
    }
}

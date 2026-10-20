<?php

namespace Tests\Feature;

use App\Mail\AppraisalAppointmentMail;
use App\Mail\ReportPdfMail;
use App\Mail\VendorEstimateSubmittedMail;
use App\Models\AppraisalAppointment;
use App\Models\AppraisalReview;
use App\Models\Setting;
use App\Models\User;
use App\Models\VendorEstimateSubmission;
use App\Services\CalculatorStateStore;
use App\Services\ProcessEmailCopies;
use App\Services\ReportService;
use App\Services\VendorWeightedRateEngine;
use App\Services\WalletService;
use Barryvdh\DomPDF\PDF;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Mockery;
use Tests\TestCase;

class ProcessEmailCopiesTest extends TestCase
{
    use RefreshDatabase;

    private function user(string $role = 'vendor'): User
    {
        $user = User::factory()->create(['user_type' => $role, 'phone_verified' => true, 'nda_accepted_at' => now()]);
        app(WalletService::class)->addTokens($user, 100, 'test', 'test credits');

        return $user;
    }

    private function stubPdf(): void
    {
        $pdf = Mockery::mock(PDF::class);
        $pdf->shouldReceive('output')->andReturn('%PDF-test');
        $this->partialMock(ReportService::class)->shouldReceive('calculatorPdf')->andReturn($pdf);
    }

    public function test_settings_default_to_requested_inbox_for_selected_processes_only(): void
    {
        $copies = app(ProcessEmailCopies::class);
        $this->assertSame(['info@getasecurityquote.com'], $copies->recipients('vendor_weighted'));
        $this->assertSame(['info@getasecurityquote.com'], $copies->recipients('estimates'));
        $this->assertSame([], $copies->recipients(null));
        $this->assertSame([], $copies->recipients('password_reset'));
        $this->assertNull(ProcessEmailCopies::processForReport('economic-justification'));
        $this->assertNull(ProcessEmailCopies::processForReport('main-menu'));
    }

    public function test_admin_can_view_and_save_independent_process_switches_and_custom_address(): void
    {
        $admin = $this->user('admin');
        $this->actingAs($admin)->get(route('admin.settings'))->assertOk()->assertSee('Process Email Copies (BCC)')->assertSee('info@getasecurityquote.com');
        app(ProcessEmailCopies::class)->settings(); // Prime the cache before the edit.
        $this->post(route('admin.settings.email-copies'), ['bcc_address' => 'records@example.test', 'vendor_weighted' => '1', 'estimates' => '0'])->assertRedirect(route('admin.settings'));
        $this->assertDatabaseHas('settings', ['key' => ProcessEmailCopies::ADDRESS_KEY, 'value' => 'records@example.test', 'group' => 'email_copies']);
        $this->assertSame(['records@example.test'], app(ProcessEmailCopies::class)->recipients('vendor_weighted'));
        $this->assertSame([], app(ProcessEmailCopies::class)->recipients('estimates'));
        $this->get(route('admin.settings'))->assertSee('records@example.test');
    }

    public function test_invalid_address_and_generic_settings_bypass_cannot_change_process_routing(): void
    {
        $this->actingAs($this->user('admin'));
        $this->post(route('admin.settings.email-copies'), ['bcc_address' => 'not an address', 'vendor_weighted' => '1', 'estimates' => '1'])->assertSessionHasErrors('bcc_address');
        $this->assertDatabaseMissing('settings', ['key' => ProcessEmailCopies::ADDRESS_KEY]);
        $this->post(route('admin.settings.update'), ['settings' => [ProcessEmailCopies::ADDRESS_KEY => ['value' => 'invalid', 'group' => 'general']]])->assertStatus(422);
    }

    public function test_non_admins_cannot_change_copy_settings(): void
    {
        $data = ['bcc_address' => 'other@example.test', 'vendor_weighted' => '1', 'estimates' => '1'];
        $this->postJson(route('admin.settings.email-copies'), $data)->assertUnauthorized();
        $this->actingAs($this->user())->postJson(route('admin.settings.email-copies'), $data)->assertForbidden();
        $this->assertDatabaseMissing('settings', ['key' => ProcessEmailCopies::ADDRESS_KEY]);
    }

    public function test_copy_recipient_is_deduplicated_against_primary_recipients_and_existing_bcc(): void
    {
        $copies = app(ProcessEmailCopies::class);
        $this->assertSame([], $copies->recipients('vendor_weighted', ['INFO@GetASecurityQuote.com']));
        $this->assertSame(['INFO@getasecurityquote.com'], $copies->recipients('estimates', [], ['INFO@getasecurityquote.com']));
        Setting::set(ProcessEmailCopies::ADDRESS_KEY, 'invalid', 'email_copies');
        $this->assertSame([], $copies->recipients('estimates'));
    }

    public function test_weighted_report_uses_admin_address_in_bcc_and_ignores_posted_copy_recipients(): void
    {
        Mail::fake();
        $vendor = $this->user();
        Setting::set(ProcessEmailCopies::ADDRESS_KEY, 'records@example.test', 'email_copies');
        app(CalculatorStateStore::class)->store($vendor, 'main-menu', ['meta' => ['vendorWeightedRates' => ['draft' => VendorWeightedRateEngine::presets()['sample']]]]);
        $this->stubPdf();
        $this->actingAs($vendor)->postJson(route('reports.email'), [
            'type' => 'vendor-weighted-rates', 'email' => $vendor->email, 'pdf_password' => 'TestOnlyPassword',
            'cc' => ['unexpected@example.test'], 'bcc' => ['unexpected@example.test'],
        ])->assertOk()->assertJsonPath('cc', [])->assertDontSee('records@example.test');
        Mail::assertSent(ReportPdfMail::class, fn ($mail) => $mail->hasBcc('records@example.test') && ! $mail->hasBcc('unexpected@example.test') && $mail->cc === []);
        Setting::set(ProcessEmailCopies::PROCESSES['vendor_weighted']['key'], '0', 'email_copies');
        $this->postJson(route('reports.email'), ['type' => 'vendor-weighted-rates', 'email' => $vendor->email, 'pdf_password' => 'TestOnlyPassword'])->assertOk();
        Mail::assertSent(ReportPdfMail::class, fn ($mail) => $mail->bcc === [] && $mail->cc === []);
    }

    public function test_estimate_pdf_delivery_uses_process_bcc_preserves_crm_and_can_be_disabled(): void
    {
        Mail::fake();
        config(['services.hubspot.bcc' => 'crm@example.test']);
        $vendor = $this->user();
        app(CalculatorStateStore::class)->store($vendor, 'budget-calculator', ['meta' => ['baselineWage' => 25]]);
        $this->stubPdf();
        $this->actingAs($vendor)->postJson(route('reports.email'), ['type' => 'budget-calculator', 'email' => 'buyer@example.test'])->assertOk();
        Mail::assertSent(ReportPdfMail::class, fn ($mail) => $mail->hasBcc('info@getasecurityquote.com') && $mail->hasBcc('crm@example.test') && ! $mail->hasBcc('info@getasecurityquotenow.com') && $mail->cc === []);
        Setting::set(ProcessEmailCopies::PROCESSES['estimates']['key'], '0', 'email_copies');
        $this->postJson(route('reports.email'), ['type' => 'budget-calculator', 'email' => 'buyer@example.test'])->assertOk();
        Mail::assertSent(ReportPdfMail::class, fn ($mail) => ! $mail->hasBcc('info@getasecurityquote.com') && $mail->hasBcc('crm@example.test'));
    }

    public function test_appraisal_booking_changes_and_reminders_have_no_process_copy_but_report_does(): void
    {
        $review = new AppraisalReview(['buyer_name' => 'Avery', 'buyer_email' => 'buyer@example.test']);
        $appointment = new AppraisalAppointment;
        foreach (['booking', 'rescheduled', 'canceled', 'reminder_24h', 'reminder_1h'] as $kind) {
            $mail = (new AppraisalAppointmentMail($review, $appointment, $kind))->to('buyer@example.test');
            $this->assertSame([], $mail->envelope()->bcc);
            $this->assertSame([], $mail->envelope()->cc);
        }
        $mail = (new AppraisalAppointmentMail($review, $appointment, 'report'))->to('buyer@example.test');
        $this->assertSame('info@getasecurityquote.com', $mail->envelope()->bcc[0]->address);
        $self = (new AppraisalAppointmentMail($review, $appointment, 'report'))->to('info@getasecurityquote.com');
        $this->assertSame([], $self->envelope()->bcc);
    }

    public function test_vendor_estimate_pdf_delivery_obeys_the_estimate_process_setting(): void
    {
        $submission = new VendorEstimateSubmission;
        $submission->setRelation('vendor', new User(['name' => 'Vendor']));
        $submission->setRelation('jobPosting', null);
        $mail = (new VendorEstimateSubmittedMail($submission, '%PDF-test', 'estimate.pdf', 'https://example.test/estimate'))->to('buyer@example.test');
        $this->assertSame('info@getasecurityquote.com', $mail->envelope()->bcc[0]->address);
        Setting::set(ProcessEmailCopies::PROCESSES['estimates']['key'], '0', 'email_copies');
        $this->assertSame([], $mail->envelope()->bcc);
    }
}

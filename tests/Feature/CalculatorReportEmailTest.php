<?php

namespace Tests\Feature;

use App\Mail\ReportPdfMail;
use App\Models\User;
use App\Services\CalculatorStateStore;
use App\Services\MasterInputsService;
use App\Services\ReportService;
use App\Services\V24\MainMenu\MainMenuComputeService;
use App\Services\VendorReportSigningService;
use App\Services\VendorWeightedRateEngine;
use App\Services\VendorWeightedRateReport;
use App\Services\WalletService;
use Barryvdh\DomPDF\PDF;
use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Mail\PendingMail;
use Illuminate\Support\Facades\Mail;
use Mockery;
use Symfony\Component\Mailer\Exception\TransportException;
use Tests\TestCase;

class CalculatorReportEmailTest extends TestCase
{
    use RefreshDatabase;

    private function user(string $type = 'vendor'): User
    {
        $user = User::factory()->create(['user_type' => $type, 'phone_verified' => true, 'nda_accepted_at' => now()]);
        app(WalletService::class)->addTokens($user, 500, 'test', 'test credits');

        return $user;
    }

    private function save(User $user): array
    {
        $scenario = ['meta' => ['basePayRate' => 24.75, 'vendorTrueHourlyCost' => 75, 'weeklyHoursPerformed' => 168, 'weeksInYear' => 52, 'vendorWeightedRates' => ['draft' => VendorWeightedRateEngine::presets()['sample']]]];
        app(CalculatorStateStore::class)->store($user, 'main-menu', $scenario, app(MainMenuComputeService::class)->compute($scenario));

        return $scenario;
    }

    public function test_main_menu_has_working_email_controls_instead_of_placeholder(): void
    {
        $vendor = $this->user();
        app(MasterInputsService::class)->getOrCreate($vendor)->update(['is_complete' => true]);
        $response = $this->actingAs($vendor)->get(route('main-menu-calculator.index'))->assertOk();
        $response->assertSee('main-report-email')->assertSee('Email Protected PDF to Me')->assertSee('Internal copies use BCC')->assertSee(route('reports.email'))->assertDontSee('Email functionality: connect');
    }

    public function test_main_report_can_be_emailed_to_multiple_valid_recipients(): void
    {
        Mail::fake();
        $vendor = $this->user();
        $this->save($vendor);
        $before = $vendor->calculatorStates()->first()->scenario;
        $this->actingAs($vendor)->postJson(route('reports.email'), ['type' => 'main-menu', 'email' => 'first@example.test; second@example.test', 'email2' => 'first@example.test'])->assertOk()->assertJsonPath('ok', true)->assertJsonCount(2, 'recipients');
        Mail::assertSent(ReportPdfMail::class, 2);
        Mail::assertSent(ReportPdfMail::class, fn ($mail) => $mail->hasTo('first@example.test') && $mail->hasBcc('info@getasecurityquotenow.com') && str_starts_with($mail->pdf, '%PDF-'));
        Mail::assertSent(ReportPdfMail::class, fn ($mail) => $mail->cc === [] && $mail->bodyView === 'emails.report-pdf');
        $this->assertSame($before, $vendor->calculatorStates()->first()->scenario);
    }

    public function test_private_signed_report_uses_own_saved_data_is_encrypted_and_bccs_gasq_without_password_in_body(): void
    {
        Mail::fake();
        $this->travelTo(now()->setDate(2026, 10, 12)->setTime(10, 0));
        config(['services.hubspot.bcc' => 'crm@example.test']);
        $vendor = $this->user();
        $scenario = $this->save($vendor);
        $service = app(VendorReportSigningService::class);
        $signature = $service->sign($scenario, $vendor, ['reportHash' => $service->status($scenario, $vendor)['reportHash'], 'signerName' => 'Vendor signer', 'method' => 'typed']);
        $this->travel(4)->days();
        $payload = null;
        $this->partialMock(ReportService::class, function ($mock) use (&$payload) {
            $mock->shouldReceive('calculatorPdf')->once()->andReturnUsing(function ($type, $data, $password) use (&$payload) {
                $payload = $data;

                return (new ReportService)->calculatorPdf($type, $data, $password);
            });
        });
        $this->actingAs($vendor)->withSession(['report_payload' => ['type' => 'main-menu', 'scenario' => ['meta' => ['companyName' => 'Foreign session']]]])->postJson(route('reports.email'), ['type' => 'vendor-weighted-rates', 'email' => $vendor->email, 'pdf_password' => 'LocalTestPassword', 'notes' => 'Do not include', 'electronicSignature' => ['signer_name' => 'forged']])->assertOk()->assertJsonPath('recipients.0', $vendor->email)->assertJsonPath('cc', []);
        $this->assertSame($signature->report_id, $payload['electronicSignature']->report_id);
        $html = view('pdf.vendor-weighted-rates', app(VendorWeightedRateReport::class)->prepare($payload))->render();
        $this->assertStringContainsString('Vendor signer', $html);
        $this->assertStringContainsString('October 12, 2026', $html);
        $this->assertStringNotContainsString('Foreign session', $html);
        Mail::assertSent(ReportPdfMail::class, function ($mail) use ($vendor) {
            $this->assertSame([], $mail->cc);
            $this->assertSame([], $mail->extraAttachments);
            $this->assertSame('Your GASQ Weighted Rate Report Is Ready — Schedule Your Review', $mail->subjectLine);
            $this->assertTrue($mail->hasBcc('info@getasecurityquote.com'));
            $this->assertFalse($mail->hasBcc('crm@example.test'));
            $this->assertTrue($mail->hasReplyTo('info@getasecurityquote.com'));
            $this->assertStringContainsString('/Encrypt', $mail->pdf);
            $this->assertStringContainsString('Dear '.e($vendor->name).',', $mail->render());
            $this->assertSame('October 12, 2026', $mail->bodyData['datePrepared']);
            $this->assertStringContainsString('provide the PDF password', $mail->render());
            $this->assertStringNotContainsString('LocalTestPassword', $mail->render());
            $this->assertStringNotContainsString('Do not include', $mail->render());

            return $mail->hasTo($vendor->email);
        });
        $this->assertStringNotContainsString('LocalTestPassword', json_encode($vendor->calculatorStates()->first()->scenario));
    }

    public function test_unsigned_weighted_report_cover_uses_current_report_date_and_requested_message(): void
    {
        Mail::fake();
        $this->travelTo(now()->setDate(2026, 10, 16)->setTime(10, 0));
        $vendor = $this->user();
        $this->save($vendor);
        $pdf = Mockery::mock(PDF::class);
        $pdf->shouldReceive('output')->andReturn('%PDF-test');
        $this->partialMock(ReportService::class)->shouldReceive('calculatorPdf')->once()
            ->withArgs(fn ($type, $data, $password) => $data['datePrepared'] === 'October 16, 2026' && $password === 'LocalTestPassword')
            ->andReturn($pdf);

        $this->actingAs($vendor)->postJson(route('reports.email'), [
            'type' => 'vendor-weighted-rates', 'email' => $vendor->email, 'pdf_password' => 'LocalTestPassword',
        ])->assertOk()->assertJsonPath('cc', []);

        Mail::assertSent(ReportPdfMail::class, function ($mail) {
            $body = $mail->render();
            foreach (['Executive Summary', 'Weighted average pay wage:', 'Weighted average bill rate:', 'Direct wage share:', 'Schedule Your Estimate &amp; Scope Review', 'please forward this email', 'Know Before You Buy. Qualifications First. Price Last.'] as $text) {
                $this->assertStringContainsString($text, $body);
            }
            $this->assertStringNotContainsString('LocalTestPassword', $body);

            return $mail->hasBcc('info@getasecurityquote.com') && $mail->cc === [];
        });
    }

    public function test_private_reports_reject_other_recipients_buyers_missing_passwords_and_invalid_addresses(): void
    {
        Mail::fake();
        $vendor = $this->user();
        $this->save($vendor);
        $valid = ['type' => 'vendor-weighted-rates', 'email' => $vendor->email, 'pdf_password' => 'LocalTestPassword'];
        foreach ([['email' => 'buyer@example.test'], ['email2' => 'second@example.test'], ['pdf_password' => 'short'], ['pdf_password' => ''], ['pdf_password' => '雪雪雪雪雪雪雪雪'], ['email' => 'invalid']] as $invalid) {
            $this->actingAs($vendor)->postJson(route('reports.email'), array_replace($valid, $invalid))->assertUnprocessable();
        }
        $this->actingAs($this->user('buyer'))->postJson(route('reports.email'), $valid)->assertForbidden();
        Mail::assertNothingSent();
    }

    public function test_report_changes_omit_old_signature_and_other_account_without_state_cannot_email_it(): void
    {
        Mail::fake();
        $vendor = $this->user();
        $scenario = $this->save($vendor);
        $service = app(VendorReportSigningService::class);
        $service->sign($scenario, $vendor, ['reportHash' => $service->status($scenario, $vendor)['reportHash'], 'signerName' => 'Old signer', 'method' => 'typed']);
        $scenario['meta']['vendorWeightedRates']['draft']['roles'][0]['billRate']++;
        app(CalculatorStateStore::class)->store($vendor, 'main-menu', $scenario);
        $pdf = Mockery::mock(PDF::class);
        $pdf->shouldReceive('output')->andReturn('%PDF-test');
        $mock = $this->partialMock(ReportService::class);
        $mock->shouldReceive('calculatorPdf')->once()->withArgs(fn ($type, $data, $password) => $data['electronicSignature'] === null)->andReturn($pdf);
        $this->actingAs($vendor)->postJson(route('reports.email'), ['type' => 'vendor-weighted-rates', 'email' => $vendor->email, 'pdf_password' => 'LocalTestPassword'])->assertOk();
        $other = $this->user();
        $this->actingAs($other)->postJson(route('reports.email'), ['type' => 'vendor-weighted-rates', 'email' => $other->email, 'pdf_password' => 'LocalTestPassword'])->assertUnprocessable();
        Mail::assertSent(ReportPdfMail::class, 1);
    }

    public function test_transport_failure_returns_actionable_error_without_exposing_credentials(): void
    {
        $vendor = $this->user();
        $this->save($vendor);
        $pending = Mockery::mock(PendingMail::class);
        $pending->shouldReceive('bcc')->andReturnSelf();
        $pending->shouldReceive('send')->andThrow(new TransportException('SMTP authentication failed: secret-credential'));
        Mail::shouldReceive('to')->once()->andReturn($pending);
        $response = $this->actingAs($vendor)->postJson(route('reports.email'), ['type' => 'main-menu', 'email' => $vendor->email]);
        $response->assertStatus(502)->assertJsonPath('ok', false)->assertSee('email service')->assertDontSee('secret-credential');
    }

    public function test_partial_delivery_identifies_successful_recipient_for_safe_retry(): void
    {
        $vendor = $this->user();
        $this->save($vendor);
        $first = Mockery::mock(PendingMail::class);
        $first->shouldReceive('bcc')->andReturnSelf();
        $first->shouldReceive('send')->once();
        $second = Mockery::mock(PendingMail::class);
        $second->shouldReceive('bcc')->andReturnSelf();
        $second->shouldReceive('send')->andThrow(new TransportException('temporary delivery failure'));
        Mail::shouldReceive('to')->with('first@example.test')->once()->andReturn($first);
        Mail::shouldReceive('to')->with('second@example.test')->once()->andReturn($second);
        $response = $this->actingAs($vendor)->postJson(route('reports.email'), ['type' => 'main-menu', 'email' => 'first@example.test,second@example.test']);
        $response->assertStatus(502)->assertJsonPath('ok', false)->assertSee('Report sent to first@example.test')->assertSee('retry only those addresses');
    }

    public function test_production_log_mailer_does_not_claim_delivery(): void
    {
        Mail::fake();
        $vendor = $this->user();
        $this->save($vendor);
        $this->withoutMiddleware(ValidateCsrfToken::class);
        $this->app->detectEnvironment(fn () => 'production');
        config(['mail.default' => 'log']);
        $this->actingAs($vendor)->postJson(route('reports.email'), ['type' => 'main-menu', 'email' => $vendor->email])->assertStatus(503)->assertJsonPath('ok', false);
        Mail::assertNothingSent();
    }

    public function test_generic_protected_customer_report_includes_a_working_configured_booking_link(): void
    {
        Mail::fake();
        config(['appraisal_reviews.provider' => 'calendly', 'appraisal_reviews.booking_url' => null, 'appraisal_reviews.bookafy.booking_url' => 'https://getasecurityquote.bookafy.com/schedule/?type=iframe&category_id=security']);
        $vendor = $this->user();
        $this->save($vendor);
        $pdf = Mockery::mock(PDF::class);
        $pdf->shouldReceive('output')->andReturn('%PDF-test-protected');
        $this->partialMock(ReportService::class)->shouldReceive('calculatorPdf')->once()->withArgs(fn ($type, $payload, $password) => $type === 'main-menu' && $password === 'SchedulingTestPassword')->andReturn($pdf);
        $this->actingAs($vendor)->postJson(route('reports.email'), ['type' => 'main-menu', 'email' => 'buyer@example.test', 'pdf_password' => 'SchedulingTestPassword'])->assertOk();
        Mail::assertSent(ReportPdfMail::class, function ($mail) {
            $this->assertSame('emails.report-pdf-protected', $mail->bodyView);
            $this->assertSame('Your GASQ Report Is Ready — Schedule Your Review', $mail->subjectLine);
            $body = $mail->render();
            $this->assertStringContainsString('Schedule Your Estimate &amp; Scope Review', $body);
            $this->assertStringContainsString('href="https://getasecurityquote.bookafy.com/schedule/?type=iframe&amp;category_id=security"', $body);
            $this->assertStringNotContainsString('SchedulingTestPassword', $body);

            return $mail->hasTo('buyer@example.test');
        });
    }

    public function test_workforce_allocation_email_password_is_optional_and_only_protected_copies_require_review(): void
    {
        Mail::fake();
        config(['appraisal_reviews.provider' => 'calendly', 'appraisal_reviews.booking_url' => null, 'appraisal_reviews.bookafy.booking_url' => 'https://getasecurityquote.bookafy.com/schedule/']);
        $vendor = $this->user();
        app(CalculatorStateStore::class)->store($vendor, 'budget-calculator', ['meta' => ['baselineWage' => 25, 'hoursPerDay' => 8, 'daysPerWeek' => 5, 'weeksPerYear' => 52]]);
        $pdf = Mockery::mock(PDF::class);
        $pdf->shouldReceive('output')->andReturn('%PDF-test');
        $passwords = [];
        $this->partialMock(ReportService::class)->shouldReceive('calculatorPdf')->twice()->withArgs(function ($type, $payload, $password) use (&$passwords) {
            $passwords[] = $password;

            return $type === 'budget-calculator-allocation';
        })->andReturn($pdf);
        $this->actingAs($vendor)->postJson(route('reports.email'), ['type' => 'budget-calculator-allocation', 'email' => 'buyer@example.test'])->assertOk();
        $this->postJson(route('reports.email'), ['type' => 'budget-calculator-allocation', 'email' => 'buyer@example.test', 'pdf_password' => 'OptionalReviewPassword'])->assertOk();
        $this->assertSame([null, 'OptionalReviewPassword'], $passwords);
        Mail::assertSent(ReportPdfMail::class, fn ($mail) => $mail->bodyView === 'emails.report-pdf');
        Mail::assertSent(ReportPdfMail::class, fn ($mail) => $mail->bodyView === 'emails.workforce-allocation-protected' && str_contains($mail->render(), 'Schedule Your Estimate &amp; Scope Review') && ! str_contains($mail->render(), 'OptionalReviewPassword'));
    }

    /**
     * Figures and masking taken from the approved cover message for report
     * GASQ-20261107-105838-V9, whose own totals are $782,068 in-house against
     * $547,448 outsourced with $234,620 recovered.
     */
    public function test_protected_cost_to_protect_cover_matches_the_approved_comparison_and_masking(): void
    {
        $estimate = [
            'baselineWage' => 24.37, 'internalTcoHourly' => 89.52, 'vendorTcoHourly' => 62.67,
            'internalOtHourly' => 134.28, 'vendorOtHourly' => 94.00,
            'annualPerInternalFte' => 130344.69, 'annualPerVendorFte' => 91241.28,
            'weeklyCoverageHours' => 168, 'monthlyCoverageHours' => 728, 'annualCoverageHours' => 8736,
            'weeksPerYear' => 52, 'monthsOfCoverage' => 12, 'ftesRequired' => 6,
            'totalWeeklyInternal' => 15039.77, 'totalWeeklyVendor' => 10527.84,
            'totalMonthlyInternal' => 65172.34, 'totalMonthlyVendor' => 45620.64,
            'totalAnnualInternal' => 782068.11, 'totalAnnualVendor' => 547447.68,
            'annualCapitalRecovery' => 234620.43, 'recoveryPct' => 30,
        ];
        $html = view('emails.cost-to-protect-protected', [
            'clientName' => 'Avery Chen', 'datePrepared' => 'November 7, 2026',
            'reportNumber' => 'GASQ-20261107-105838-V9', 'estimate' => $estimate,
            'bookingUrl' => 'https://getasecurityquote.bookafy.com/schedule/',
        ])->render();

        // Every money figure keeps its final group and cents, and nothing else.
        foreach (['$XX.37', '$XX.52', '$XX.67', '$XXX.28', '$XX.00', '$XXX,344.69', '$XX,241.28',
            '$XX,039.77', '$XX,527.84', '$XX,172.34', '$XX,620.64', '$XXX,068.11', '$XXX,447.68', '$XXX,620.43'] as $masked) {
            $this->assertStringContainsString($masked, $html, "missing masked figure {$masked}");
        }
        // The real amounts must never ship in the covering email.
        foreach (['782,068', '547,447', '234,620', '130,344', '89.52', '62.67', '24.37'] as $raw) {
            $this->assertStringNotContainsString($raw, $html, "leaked unmasked figure {$raw}");
        }
        // Coverage quantities are not concealed.
        foreach (['168', '728', '8,736', '52', '12', '6 FTEs'] as $plain) {
            $this->assertStringContainsString($plain, $html);
        }
        // The headline quotes the recovery without cents, the rows with them.
        $this->assertStringContainsString('reduce estimated annual costs by 30%, creating a potential $XXX,620 annual Capital Recovery Opportunity', $html);
        $this->assertStringContainsString('Detailed Cost Appraisal Comparison', $html);
        $this->assertStringContainsString('Buyer In-House Cost to Protect', $html);
        $this->assertStringContainsString('Vendor Outsourcing Cost', $html);
        $this->assertStringContainsString('What the Estimate Includes', $html);
        $this->assertStringContainsString('Next Step: Schedule Your Estimate &amp; Scope Review', $html);
        $this->assertStringContainsString('before releasing the report password and complete pricing', $html);
        $this->assertStringContainsString('href="https://getasecurityquote.bookafy.com/schedule/"', $html);
    }

    public function test_buyer_edition_cover_asks_for_a_review_only_when_its_pdf_is_protected(): void
    {
        Mail::fake();
        config(['appraisal_reviews.provider' => 'calendly', 'appraisal_reviews.booking_url' => null, 'appraisal_reviews.bookafy.booking_url' => 'https://getasecurityquote.bookafy.com/schedule/']);
        $vendor = $this->user();
        app(CalculatorStateStore::class)->store($vendor, 'budget-calculator', ['meta' => ['baselineWage' => 25, 'hoursPerDay' => 8, 'daysPerWeek' => 5, 'weeksPerYear' => 52]]);
        $pdf = Mockery::mock(PDF::class);
        $pdf->shouldReceive('output')->andReturn('%PDF-test');
        $this->partialMock(ReportService::class)->shouldReceive('calculatorPdf')->twice()->andReturn($pdf);
        $this->actingAs($vendor)->postJson(route('reports.email'), ['type' => 'budget-calculator-preview', 'email' => 'buyer@example.test'])->assertOk();
        $this->postJson(route('reports.email'), ['type' => 'budget-calculator-preview', 'email' => 'buyer@example.test', 'pdf_password' => 'BuyerEditionPassword'])->assertOk();
        // The free edition keeps its approved cover untouched.
        Mail::assertSent(ReportPdfMail::class, fn ($mail) => $mail->bodyView === 'emails.cost-to-protect-locked' && ! str_contains($mail->render(), 'Schedule Your Estimate'));
        // A protected copy must name the booked review as the way to the password.
        Mail::assertSent(ReportPdfMail::class, fn ($mail) => $mail->bodyView === 'emails.cost-to-protect-locked'
            && str_contains($mail->render(), 'Schedule Your Estimate &amp; Scope Review')
            && str_contains($mail->render(), 'password protected')
            && str_contains($mail->render(), 'href="https://getasecurityquote.bookafy.com/schedule/"')
            && ! str_contains($mail->render(), 'BuyerEditionPassword'));
    }
}

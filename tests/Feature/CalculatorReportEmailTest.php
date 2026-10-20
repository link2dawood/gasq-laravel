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
            $this->assertSame('GASQ Vendor Weighted Rate Report', $mail->subjectLine);
            $this->assertTrue($mail->hasBcc('info@getasecurityquote.com'));
            $this->assertFalse($mail->hasBcc('crm@example.test'));
            $this->assertTrue($mail->hasReplyTo('info@getasecurityquote.com'));
            $this->assertStringContainsString('/Encrypt', $mail->pdf);
            $this->assertStringContainsString('Hello '.e($vendor->name).',', $mail->render());
            $this->assertStringContainsString('dated October 12, 2026', $mail->render());
            $this->assertStringContainsString('Reply “ACCESS”', $mail->render());
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
            foreach (['dated October 16, 2026', 'For buyers:', 'For vendors:', 'staffing and payroll hours', '10-minute walkthrough', 'Please forward this email', 'Know Before You Buy. Know Before You Bid.', '(470) 633-2816'] as $text) {
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
}

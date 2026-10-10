<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\VendorReportSignature;
use App\Services\CalculatorStateStore;
use App\Services\ReportService;
use App\Services\VendorReportSigningService;
use App\Services\VendorWeightedRateEngine;
use App\Services\VendorWeightedRateReport;
use Barryvdh\DomPDF\PDF;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Date;
use Tests\TestCase;

class VendorReportSigningTest extends TestCase
{
    use RefreshDatabase;

    private function vendor(): User
    {
        return User::factory()->create(['user_type' => 'vendor', 'phone_verified' => true, 'nda_accepted_at' => now()]);
    }

    private function scenario(): array
    {
        return ['meta' => ['companyName' => 'Northgate Site', 'preparedBy' => 'Vendor analyst', 'vendorWeightedRates' => ['draft' => VendorWeightedRateEngine::presets()['sample']]]];
    }

    private function input(User $user, ?array $scenario = null): array
    {
        $scenario ??= $this->scenario();
        app(CalculatorStateStore::class)->store($user, 'main-menu', $scenario);

        return ['reportHash' => app(VendorReportSigningService::class)->status($scenario, $user)['reportHash'], 'signerName' => 'Taylor Vendor', 'signerTitle' => 'Director', 'method' => 'typed', 'consent' => true];
    }

    private function signUrl(): string
    {
        return route('backend.vendor-weighted-rates.sign');
    }

    private function png(bool $ink, int $width = 720): string
    {
        $image = imagecreatetruecolor($width, 180);
        imagefill($image, 0, 0, imagecolorallocate($image, 255, 255, 255));
        if ($ink) {
            imagesetthickness($image, 4);
            imageline($image, 50, 90, 500, 30, imagecolorallocate($image, 25, 51, 79));
        }
        ob_start();
        imagepng($image);
        $bytes = ob_get_clean();
        imagedestroy($image);

        return 'data:image/png;base64,'.base64_encode($bytes);
    }

    public function test_typed_signature_persists_server_identity_time_and_immutable_report_snapshot(): void
    {
        Date::setTestNow('2026-10-11 12:00:00');
        $vendor = $this->vendor();
        $input = $this->input($vendor);
        $this->actingAs($vendor)->postJson($this->signUrl(), $input + ['user_id' => 999, 'signed_at' => '2000-01-01', 'consentText' => 'forged'])->assertOk()->assertJsonPath('signatureStatus', 'signed')->assertJsonPath('signature.signerName', 'Taylor Vendor');
        $signature = VendorReportSignature::sole();
        $this->assertSame($vendor->id, $signature->user_id);
        $this->assertSame(VendorReportSigningService::CONSENT, $signature->consent);
        $this->assertTrue($signature->signed_at->equalTo(now()));
        $this->assertSame($vendor->email, $signature->snapshot['accountEmail']);
        $this->assertNull($signature->signature_image);
        $this->assertEquals(145, $signature->snapshot['document']['totals']['headcount']);
        $snapshot = $signature->snapshot;
        $state = $vendor->calculatorStates()->firstOrFail()->scenario;
        $this->assertSame('signed', app(VendorReportSigningService::class)->status($state, $vendor->fresh())['signatureStatus']);
        $state['meta']['vendorWeightedRates']['draft']['roles'][0]['billRate']++;
        app(CalculatorStateStore::class)->store($vendor, 'main-menu', $state);
        $this->assertSame('changed', app(VendorReportSigningService::class)->status($state, $vendor)['signatureStatus']);
        $this->assertNull(app(VendorReportSigningService::class)->active($state, $vendor));
        $this->assertSame($snapshot, $signature->fresh()->snapshot);
        Date::setTestNow();
    }

    public function test_consent_name_and_drawn_image_are_required(): void
    {
        $vendor = $this->vendor();
        $input = $this->input($vendor);
        foreach ([['consent' => false], ['signerName' => ''], ['method' => 'drawn'], ['method' => 'uploaded']] as $invalid) {
            $this->actingAs($vendor)->postJson($this->signUrl(), array_replace($input, $invalid))->assertUnprocessable();
        }
        $this->assertDatabaseCount('vendor_report_signatures', 0);
    }

    public function test_stale_hash_cannot_sign_new_saved_report(): void
    {
        $vendor = $this->vendor();
        $input = $this->input($vendor);
        $scenario = $this->scenario();
        $scenario['meta']['companyName'] = 'Changed site';
        app(CalculatorStateStore::class)->store($vendor, 'main-menu', $scenario);
        $this->actingAs($vendor)->postJson($this->signUrl(), $input)->assertStatus(409);
        $this->assertDatabaseCount('vendor_report_signatures', 0);
    }

    public function test_wages_hours_identity_and_currency_changes_require_signing_again(): void
    {
        $vendor = $this->vendor();
        $input = $this->input($vendor);
        $this->actingAs($vendor)->postJson($this->signUrl(), $input)->assertOk();
        $service = app(VendorReportSigningService::class);
        foreach (['actualWage', 'headcount', 'weeklyHoursPerEmployee', 'weeksPerYear'] as $field) {
            $scenario = $this->scenario();
            $scenario['meta']['vendorWeightedRates']['draft']['roles'][0][$field] = 10;
            $this->assertSame('changed', $service->status($scenario, $vendor)['signatureStatus'], $field);
        }
        $scenario = $this->scenario();
        $scenario['meta']['preparedBy'] = 'Changed analyst';
        $this->assertSame('changed', $service->status($scenario, $vendor)['signatureStatus']);
        $vendor->currency = $vendor->currency === 'EUR' ? 'USD' : 'EUR';
        $this->assertSame('changed', $service->status($this->scenario(), $vendor)['signatureStatus']);
    }

    public function test_shared_assumptions_are_bound_even_when_every_role_overrides_them(): void
    {
        $vendor = $this->vendor();
        $scenario = $this->scenario();
        foreach ($scenario['meta']['vendorWeightedRates']['draft']['roles'] as &$role) {
            $role['weeklyHoursPerEmployee'] = 40;
            $role['weeksPerYear'] = 52;
        }
        unset($role);
        $input = $this->input($vendor, $scenario);
        $this->actingAs($vendor)->postJson($this->signUrl(), $input)->assertOk();
        $service = app(VendorReportSigningService::class);
        $scenario['meta']['vendorWeightedRates']['draft']['weeklyHoursPerEmployee'] = '40.0';
        $this->assertSame('signed', $service->status($scenario, $vendor)['signatureStatus']);
        $scenario['meta']['vendorWeightedRates']['draft']['weeklyHoursPerEmployee'] = 30;
        $this->assertSame('changed', $service->status($scenario, $vendor)['signatureStatus']);
    }

    public function test_drawn_signature_is_normalized_and_rendered_in_protected_pdf(): void
    {
        $vendor = $this->vendor();
        $input = array_replace($this->input($vendor), ['method' => 'drawn', 'signatureImage' => $this->png(true)]);
        $this->actingAs($vendor)->postJson($this->signUrl(), $input)->assertOk()->assertJsonPath('signature.method', 'drawn');
        $signature = VendorReportSignature::sole();
        $this->assertStringStartsWith('data:image/png;base64,', $signature->signature_image);
        $payload = ['scenario' => $this->scenario(), 'user' => $vendor, 'electronicSignature' => $signature];
        $html = view('pdf.vendor-weighted-rates', app(VendorWeightedRateReport::class)->prepare($payload))->render();
        $this->assertStringContainsString($signature->signature_image, $html);
        $this->assertStringContainsString('Drawn signature', $html);
        $pdf = app(ReportService::class)->calculatorPdf('vendor-weighted-rates', $payload, 'LocalTestPassword');
        $this->assertStringContainsString('/Encrypt', $pdf->output());
    }

    public function test_blank_non_png_and_oversized_drawings_are_rejected(): void
    {
        $vendor = $this->vendor();
        $input = array_replace($this->input($vendor), ['method' => 'drawn']);
        foreach ([$this->png(false), $this->png(true, 1500), 'data:image/svg+xml;base64,'.base64_encode('<svg/>'), 'data:image/png;base64,broken'] as $image) {
            $this->actingAs($vendor)->postJson($this->signUrl(), $input + ['signatureImage' => $image])->assertUnprocessable()->assertJsonValidationErrors('signatureImage');
        }
        $this->assertDatabaseCount('vendor_report_signatures', 0);
    }

    public function test_download_attaches_only_current_account_current_signature(): void
    {
        $vendor = $this->vendor();
        $input = $this->input($vendor);
        $this->actingAs($vendor)->postJson($this->signUrl(), $input)->assertOk();
        $pdf = \Mockery::mock(PDF::class);
        $pdf->shouldReceive('download')->twice()->andReturn(response('protected PDF'));
        $mock = $this->mock(ReportService::class);
        $mock->shouldReceive('calculatorPdf')->once()->withArgs(fn ($type, $payload, $password) => $type === 'vendor-weighted-rates' && $payload['electronicSignature']->user_id === $vendor->id && str_starts_with($payload['reportNumber'], 'GASQ-SIGNED-') && $password === 'LocalTestPassword')->andReturn($pdf);
        $mock->shouldReceive('calculatorPdf')->once()->withArgs(fn ($type, $payload, $password) => $payload['electronicSignature'] === null && $password === 'LocalTestPassword')->andReturn($pdf);
        $mock->shouldReceive('filenameForCalculator')->andReturn('report.pdf');
        $this->postJson(route('reports.download'), ['type' => 'vendor-weighted-rates', 'pdf_password' => 'LocalTestPassword'])->assertOk();
        $scenario = $this->scenario();
        $scenario['meta']['companyName'] = 'Changed site';
        app(CalculatorStateStore::class)->store($vendor, 'main-menu', $scenario);
        $this->postJson(route('reports.download'), ['type' => 'vendor-weighted-rates', 'pdf_password' => 'LocalTestPassword', 'electronicSignature' => ['signer_name' => 'forged']])->assertOk();
        $this->assertStringNotContainsString('LocalTestPassword', json_encode(VendorReportSignature::sole()->snapshot));
    }

    public function test_other_accounts_buyers_and_guests_cannot_sign_vendor_report(): void
    {
        $vendor = $this->vendor();
        $input = $this->input($vendor);
        $this->postJson($this->signUrl(), $input)->assertUnauthorized();
        $other = $this->vendor();
        $this->input($other);
        $this->actingAs($other)->postJson($this->signUrl(), $input)->assertStatus(409);
        $buyer = User::factory()->create(['user_type' => 'buyer', 'phone_verified' => true, 'nda_accepted_at' => now()]);
        $this->actingAs($buyer)->postJson($this->signUrl(), $input)->assertForbidden();
        $this->assertDatabaseCount('vendor_report_signatures', 0);
    }

    public function test_signing_requires_own_saved_roster_and_admin_can_sign_own_report(): void
    {
        $vendor = $this->vendor();
        $input = ['reportHash' => str_repeat('a', 64), 'signerName' => 'Vendor', 'method' => 'typed', 'consent' => true];
        $this->actingAs($vendor)->postJson($this->signUrl(), $input)->assertUnprocessable();
        $admin = User::factory()->create(['user_type' => 'admin', 'phone_verified' => true, 'nda_accepted_at' => now()]);
        $this->actingAs($admin)->postJson($this->signUrl(), $this->input($admin))->assertOk();
        $this->assertSame($admin->id, VendorReportSignature::sole()->user_id);
    }
}

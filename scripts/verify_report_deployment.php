<?php

use App\Services\BudgetSummaryService;
use App\Services\ExchangeRateService;
use App\Services\ProcessEmailCopies;
use App\Services\ReportService;
use App\Services\VendorWeightedRateEngine;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;

// Read-only application checks: no customer records, bookings, or emails are created.
if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

require __DIR__.'/../vendor/autoload.php';
$app = require __DIR__.'/../bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();

foreach (['appraisal_reviews', 'appraisal_appointments', 'appraisal_deliveries'] as $table) {
    if (! Schema::hasTable($table)) {
        throw new RuntimeException('Missing appraisal migration: '.$table);
    }
}
echo "Appraisal schema: verified\n";

foreach (['admin.settings.email-copies', 'admin.appraisal-reviews.index', 'appraisal-appointments.show', 'appraisal-appointments.confirm', 'appraisals.calendly-webhook'] as $name) {
    if (! Route::has($name)) {
        throw new RuntimeException('Missing route: '.$name);
    }
}
echo "Process settings and appointment routes: verified\n";

$copies = app(ProcessEmailCopies::class);
$settings = $copies->settings();
if (! filter_var($settings['address'], FILTER_VALIDATE_EMAIL)) {
    throw new RuntimeException('The process BCC address is invalid.');
}
foreach (array_keys(ProcessEmailCopies::PROCESSES) as $process) {
    $expected = $settings['enabled'][$process] ? [$settings['address']] : [];
    if ($copies->recipients($process) !== $expected || $copies->recipients($process, [$settings['address']]) !== []) {
        throw new RuntimeException('Process BCC routing or deduplication failed.');
    }
    echo $process.' BCC: '.($settings['enabled'][$process] ? 'enabled' : 'disabled')."\n";
}
if ($copies->recipients(null) !== []) {
    throw new RuntimeException('An unrelated message acquired a process BCC.');
}
echo "Scoped BCC and recipient privacy: verified\n";

$rates = app(ExchangeRateService::class)->current();
if ((float) $rates['rates']['USD'] !== 1.0 || count($rates['rates']) !== count(config('currency.profiles'))) {
    throw new RuntimeException('The supported currency cross rates are incomplete.');
}
echo 'ECB reference sheet: '.$rates['asOf'].' | USD/CAD: '.number_format($rates['rates']['CAD'], 6)."\n";

$budget = app(BudgetSummaryService::class)->fromScenario(['meta' => ['baselineWage' => 25]]);
$budgetScenario = ['meta' => ['roiCostBasis' => 'budget', 'budgetScenario' => $budget['budgetScenario']]];
$budgetRoi = app(\App\Services\V24\MainMenu\EconomicJustificationEngine::class)->compute($budgetScenario);
if ($budgetRoi !== $budget || $budget['inHouseHourly'] !== 91.84 || $budget['vendorHourly'] !== 64.29 || $budget['totalStaffRequired'] !== 6) {
    throw new RuntimeException('Dashboard and budget ROI comparison failed.');
}
echo "Budget Summary and ROI cost basis: verified\n";

$payload = ['scenario' => ['meta' => ['vendorWeightedRates' => ['draft' => VendorWeightedRateEngine::presets()['sample']]]]];
$service = app(ReportService::class);
$pdf = $service->calculatorPdf('vendor-weighted-rates', $payload, bin2hex(random_bytes(12)));
if (! str_starts_with($pdf->output(), '%PDF-') || ! str_contains($pdf->output(), '/Encrypt') || $pdf->getDomPDF()->getCanvas()->get_page_count() < 2) {
    throw new RuntimeException('Protected vendor PDF rendering failed.');
}
echo "Protected vendor PDF rendering: verified\n";

$buyerPdf = $service->calculatorPdf('buyer-appraisal', ['scenario' => [], 'reportNumber' => 'DEPLOYMENT-CHECK'], bin2hex(random_bytes(12)));
if (! str_starts_with($buyerPdf->output(), '%PDF-') || ! str_contains($buyerPdf->output(), '/Encrypt')) {
    throw new RuntimeException('Protected buyer PDF rendering failed.');
}
echo "Protected buyer appraisal rendering: verified\n";

if (config('appraisal_reviews.enabled')) {
    foreach (['calendly_token', 'webhook_signing_key', 'event_type_uri', 'booking_url', 'reply_to'] as $key) {
        if (! config('appraisal_reviews.'.$key)) {
            throw new RuntimeException('Automatic delivery is enabled without complete scheduling configuration.');
        }
    }
    echo "Automatic appraisal delivery: enabled; live scheduling acceptance must be verified separately\n";
} else {
    echo "Automatic appraisal delivery: inactive pending live scheduling acceptance\n";
}
echo "Deployment report checks passed. No emails sent or customer data changed.\n";

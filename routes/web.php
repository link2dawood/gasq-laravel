<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\PageController;
use App\Http\Controllers\StripeCreditsController;

Route::get('/', [PageController::class, 'landing'])->name('landing');
Route::get('/marketplace-landing', [PageController::class, 'marketplaceLanding'])->name('marketplace-landing');
Route::get('/pricing', [PageController::class, 'pricing'])->name('pricing');
Route::get('/faq', [PageController::class, 'faq'])->name('faq');
Route::get('/payscale', [PageController::class, 'payScale'])->name('payscale');
Route::get('/payment-model', [PageController::class, 'paymentPolicy'])->name('payment-policy');
Route::get('/post-coverage-schedule', function () {
    return view('calculators.spa-shell', ['title' => 'Post Coverage Schedule']);
})->name('post-coverage-schedule');

// UI-only calculator/marketing pages (match gasq-calculator-project routes)
Route::get('/gasq-tco-calculator', function () {
    return view('calculators.spa-shell', ['title' => 'GASQ TCO Calculator']);
})->name('gasq-tco-calculator.index');

Route::get('/absorbed-rate-calculator', function () {
    return view('calculators.spa-shell', ['title' => 'Absorbed Rate Calculator']);
})->name('absorbed-rate-calculator.index');

Route::get('/government-contract-calculator', function () {
    return view('calculators.spa-shell', ['title' => 'Government Contract Calculator']);
})->name('government-contract-calculator.index');

Route::get('/keeps-doors-open-calculator', function () {
    return view('calculators.spa-shell', ['title' => 'Keeps Doors Open Calculator']);
})->name('keeps-doors-open-calculator.index');

Route::get('/open-bid-offer', function () {
    return view('calculators.spa-shell', ['title' => 'Open Bid Offer']);
})->name('open-bid-offer.index');

// Route aliases to match gasq-calculator-project SPA paths
Route::get('/post-job', function () {
    return view('calculators.spa-shell', ['title' => 'Post Job']);
})->name('post-job.index');

Route::get('/calculator', function () {
    return view('calculators.spa-shell', ['title' => 'Security Calculator']);
})->name('calculator.index');

Route::get('/gasq-instant-estimator', [App\Http\Controllers\InstantEstimatorController::class, 'index'])->name('gasq-instant-estimator.index');
Route::post('/gasq-instant-estimator', [App\Http\Controllers\InstantEstimatorController::class, 'index'])->name('gasq-instant-estimator.post');

// Keep navbar/calculator paths aligned
Route::get('/instant-estimator', function () {
    return redirect('/gasq-instant-estimator');
})->name('instant-estimator.react-ui');

// UI-only pages (match gasq-calculator-project routes)
Route::get('/vendor-form', function () {
    return view('calculators.spa-shell', ['title' => 'Vendor Form']);
})->name('vendor-form.index');

Route::get('/register/buyer', function () {
    return view('calculators.spa-shell', ['title' => 'Register as Buyer']);
})->name('register.buyer.index');

Route::get('/register/vendor', function () {
    return view('calculators.spa-shell', ['title' => 'Register as Vendor']);
})->name('register.vendor.index');

// Calculator Blade routes (pixel-perfect, all require auth)
Route::middleware('auth')->group(function () {
    Route::get('/main-menu-calculator', [App\Http\Controllers\MainMenuCalculatorController::class, 'index'])->name('main-menu-calculator.index');
    Route::post('/main-menu-calculator', [App\Http\Controllers\MainMenuCalculatorController::class, 'index'])->name('main-menu-calculator.post');

    Route::get('/contract-analysis', [App\Http\Controllers\ContractAnalysisController::class, 'index'])->name('contract-analysis.index');
    Route::post('/contract-analysis', [App\Http\Controllers\ContractAnalysisController::class, 'index'])->name('contract-analysis.post');

    Route::get('/security-billing', [App\Http\Controllers\SecurityBillingController::class, 'index'])->name('security-billing.index');
    Route::post('/security-billing', [App\Http\Controllers\SecurityBillingController::class, 'index'])->name('security-billing.post');

    Route::get('/mobile-patrol-calculator', [App\Http\Controllers\MobilePatrolController::class, 'calculator'])->name('mobile-patrol-calculator');
    Route::post('/mobile-patrol-calculator', [App\Http\Controllers\MobilePatrolController::class, 'calculator'])->name('mobile-patrol-calculator.post');

    Route::get('/mobile-patrol-comparison', [App\Http\Controllers\MobilePatrolController::class, 'comparison'])->name('mobile-patrol-comparison');
    Route::post('/mobile-patrol-comparison', [App\Http\Controllers\MobilePatrolController::class, 'comparison'])->name('mobile-patrol-comparison.post');

    Route::get('/cost-analysis', function () { return view('calculators.cost-analysis'); })->name('cost-analysis.index');
    Route::get('/bill-rate-analysis', function () { return view('calculators.bill-rate'); })->name('bill-rate-analysis.index');
    Route::get('/manpower-hours', function () { return view('calculators.manpower-hours'); })->name('manpower-hours.index');
    Route::get('/economic-justification', function () { return view('calculators.economic-justification'); })->name('economic-justification.index');
    Route::get('/hourly-pay-calculator', function () { return view('calculators.hourly-pay'); })->name('hourly-pay-calculator.index');
    Route::get('/budget-calculator', function () { return view('calculators.budget'); })->name('budget-calculator.index');
    Route::get('/unarmed-security-guard-services', function () { return view('calculators.unarmed-security-guard-services'); })->name('unarmed-security-guard-services.index');
    Route::get('/security-quote', function () { return view('calculators.security-quote'); })->name('security-quote.index');
    Route::get('/mobile-patrol-analysis', function () { return view('calculators.mobile-patrol-analysis'); })->name('mobile-patrol-analysis.index');
    Route::get('/global-security-pricing', function () { return view('calculators.global-security-pricing'); })->name('global-security-pricing.index');
});

// Public marketplace (view only)
Route::get('/job-board', [App\Http\Controllers\JobPostingController::class, 'index'])->name('job-board');
Route::get('/jobs', [App\Http\Controllers\JobPostingController::class, 'index'])->name('jobs.index');
Route::get('/jobs/{job}', [App\Http\Controllers\JobPostingController::class, 'show'])->name('jobs.show');
Route::get('/vendor-profile/{user}', [App\Http\Controllers\VendorProfileController::class, 'show'])->name('vendor-profile.show');

Auth::routes();

Route::get('/home', [App\Http\Controllers\HomeController::class, 'index'])->name('home');

// React SPA: session, CSRF, wallet balance, feature rules (same-origin Laravel session)
Route::get('/api/spa/session', [App\Http\Controllers\Api\SpaSessionController::class, 'show'])->name('api.spa.session');

// Profile & Account
Route::middleware('auth')->group(function () {
    Route::post('/api/spa/wallet/spend', [App\Http\Controllers\Api\SpaWalletController::class, 'spend'])->name('api.spa.wallet.spend');
    Route::post('/api/spa/mail/economic-justification', [App\Http\Controllers\Api\SpaCalculatorMailController::class, 'economicJustification'])->name('api.spa.mail.economic-justification');
    Route::post('/api/spa/mail/calculator-pdf', [App\Http\Controllers\Api\SpaCalculatorMailController::class, 'calculatorPdf'])->name('api.spa.mail.calculator-pdf');

    Route::get('/profile', [ProfileController::class, 'show'])->name('profile.show');
    Route::get('/profile/edit', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::put('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::put('/profile/password', [ProfileController::class, 'updatePassword'])->name('profile.password.update');
    Route::post('/profile/avatar', [ProfileController::class, 'updateAvatar'])->name('profile.avatar.update');
    Route::delete('/profile/avatar', [ProfileController::class, 'removeAvatar'])->name('profile.avatar.remove');
    Route::get('/account-balance', [App\Http\Controllers\AccountBalanceController::class, 'index'])->name('account-balance');
    Route::get('/credits', [App\Http\Controllers\CreditsController::class, 'index'])->name('credits');
    Route::get('/credits/success', [App\Http\Controllers\CreditsController::class, 'success'])->name('credits.success');
    Route::post('/credits/checkout/{plan}', [StripeCreditsController::class, 'checkout'])->name('credits.checkout');

    // Discovery call
    Route::get('/discovery-call', [App\Http\Controllers\DiscoveryCallController::class, 'index'])->name('discovery-call.index');
    Route::post('/discovery-call', [App\Http\Controllers\DiscoveryCallController::class, 'store'])->name('discovery-call.store');

    // Jobs (create, edit, delete) and bids
    Route::resource('jobs', App\Http\Controllers\JobPostingController::class)->except(['index', 'show'])->names('jobs');
    Route::post('/jobs/{job}/bids', [App\Http\Controllers\BidController::class, 'store'])->name('bids.store');
    Route::put('/bids/{bid}', [App\Http\Controllers\BidController::class, 'update'])->name('bids.update');
    Route::post('/bids/{bid}/respond', [App\Http\Controllers\BidController::class, 'respond'])->name('bids.respond');
    Route::post('/bids/{bid}/counter-offer', [App\Http\Controllers\BidController::class, 'counterOffer'])->name('bids.counter-offer');

    // Calculator backend endpoints (non-conflicting; used later for functionality)
    Route::post('/_backend/security-billing/compute', \App\Http\Controllers\Backend\SecurityBillingComputeController::class)
        ->name('backend.security-billing.compute');
    Route::post('/_backend/security-billing/v24/compute', \App\Http\Controllers\Backend\SecurityBillingV24ComputeController::class)
        ->name('backend.security-billing.v24.compute');

    Route::get('/_backend/instant-estimator', [App\Http\Controllers\InstantEstimatorController::class, 'index'])->name('backend.instant-estimator.index');
    Route::post('/_backend/instant-estimator', [App\Http\Controllers\InstantEstimatorController::class, 'index'])->name('backend.instant-estimator.post');
    Route::post('/_backend/instant-estimator/compute', \App\Http\Controllers\Backend\InstantEstimatorComputeController::class)
        ->name('backend.instant-estimator.compute');

    Route::get('/_backend/main-menu-calculator', [App\Http\Controllers\MainMenuCalculatorController::class, 'index'])->name('backend.main-menu-calculator.index');
    Route::post('/_backend/main-menu-calculator', [App\Http\Controllers\MainMenuCalculatorController::class, 'index'])->name('backend.main-menu-calculator.post');
    Route::post('/_backend/main-menu/compute', \App\Http\Controllers\Backend\MainMenuComputeController::class)
        ->name('backend.main-menu.compute');

    Route::get('/_backend/contract-analysis', [App\Http\Controllers\ContractAnalysisController::class, 'index'])->name('backend.contract-analysis.index');
    Route::post('/_backend/contract-analysis', [App\Http\Controllers\ContractAnalysisController::class, 'index'])->name('backend.contract-analysis.post');
    Route::post('/_backend/contract-analysis/v24/compute', \App\Http\Controllers\Backend\ContractAnalysisV24ComputeController::class)
        ->name('backend.contract-analysis.v24.compute');

    Route::get('/_backend/security-billing', [App\Http\Controllers\SecurityBillingController::class, 'index'])->name('backend.security-billing.index');
    Route::post('/_backend/security-billing', [App\Http\Controllers\SecurityBillingController::class, 'index'])->name('backend.security-billing.post');

    Route::get('/_backend/mobile-patrol-calculator', [App\Http\Controllers\MobilePatrolController::class, 'calculator'])->name('backend.mobile-patrol.calculator');
    Route::post('/_backend/mobile-patrol-calculator', [App\Http\Controllers\MobilePatrolController::class, 'calculator'])->name('backend.mobile-patrol.calculator.post');
    Route::post('/_backend/mobile-patrol/v24/compute', \App\Http\Controllers\Backend\MobilePatrolV24ComputeController::class)
        ->name('backend.mobile-patrol.v24.compute');

    Route::post('/_backend/standalone/{type}/v24/compute', \App\Http\Controllers\Backend\StandaloneV24ComputeController::class)
        ->whereIn('type', [
            'cost-analysis',
            'bill-rate-analysis',
            'manpower-hours',
            'economic-justification',
            'hourly-pay-calculator',
            'budget-calculator',
            'mobile-patrol-analysis',
            'global-security-pricing',
        ])
        ->name('backend.standalone.v24.compute');

    Route::get('/_backend/mobile-patrol-comparison', [App\Http\Controllers\MobilePatrolController::class, 'comparison'])->name('backend.mobile-patrol.comparison');
    Route::post('/_backend/mobile-patrol-comparison', [App\Http\Controllers\MobilePatrolController::class, 'comparison'])->name('backend.mobile-patrol.comparison.post');

    // TCO scenario / Inputs graph (V24-aligned payload for calculators & parity)
    Route::get('/_backend/scenarios', [App\Http\Controllers\Backend\ScenarioController::class, 'index'])->name('backend.scenarios.index');
    Route::post('/_backend/scenarios', [App\Http\Controllers\Backend\ScenarioController::class, 'store'])->name('backend.scenarios.store');
    Route::get('/_backend/scenarios/{scenario}', [App\Http\Controllers\Backend\ScenarioController::class, 'show'])->name('backend.scenarios.show');
    Route::put('/_backend/scenarios/{scenario}', [App\Http\Controllers\Backend\ScenarioController::class, 'update'])->name('backend.scenarios.update');
    Route::get('/_backend/scenarios/{scenario}/payload', [App\Http\Controllers\Backend\ScenarioController::class, 'payload'])->name('backend.scenarios.payload');

    // PDF reports: download receipt, download/email calculator report
    Route::get('/reports/receipt/{transaction}', [App\Http\Controllers\ReportController::class, 'downloadReceipt'])->name('reports.receipt');
    Route::get('/reports/download', [App\Http\Controllers\ReportController::class, 'downloadReport'])->name('reports.download');
    Route::post('/reports/email', [App\Http\Controllers\ReportController::class, 'emailReport'])->name('reports.email');
});

// Stripe webhook (no auth)
Route::post('/stripe/webhook', [StripeCreditsController::class, 'webhook'])->name('stripe.webhook');

// Admin
Route::middleware(['auth', 'admin'])->group(function () {
    Route::get('/admin/analytics', [App\Http\Controllers\AnalyticsController::class, 'index'])->name('admin.analytics');
    Route::get('/admin/settings', [App\Http\Controllers\AdminSettingsController::class, 'index'])->name('admin.settings');
    Route::post('/admin/settings', [App\Http\Controllers\AdminSettingsController::class, 'update'])->name('admin.settings.update');
    Route::post('/admin/settings/logo', [App\Http\Controllers\AdminSettingsController::class, 'uploadLogo'])->name('admin.settings.logo');
    Route::post('/admin/settings/logo/remove', [App\Http\Controllers\AdminSettingsController::class, 'removeLogo'])->name('admin.settings.logo.remove');
    Route::get('/admin/tokens', [App\Http\Controllers\AdminTokensController::class, 'index'])->name('admin.tokens');
    Route::post('/admin/tokens/adjust', [App\Http\Controllers\AdminTokensController::class, 'adjust'])->name('admin.tokens.adjust');
    Route::post('/admin/tokens/features/{rule}', [App\Http\Controllers\AdminTokensController::class, 'updateFeature'])->name('admin.tokens.features.update');
    Route::resource('admin/faqs', App\Http\Controllers\AdminFaqController::class)->except(['show'])->names([
        'index' => 'admin.faqs.index',
        'create' => 'admin.faqs.create',
        'store' => 'admin.faqs.store',
        'edit' => 'admin.faqs.edit',
        'update' => 'admin.faqs.update',
        'destroy' => 'admin.faqs.destroy',
    ]);
    Route::resource('admin/content-sections', App\Http\Controllers\AdminContentSectionController::class)->parameters(['content-section' => 'content_section'])->except(['show'])->names([
        'index' => 'admin.content-sections.index',
        'create' => 'admin.content-sections.create',
        'store' => 'admin.content-sections.store',
        'edit' => 'admin.content-sections.edit',
        'update' => 'admin.content-sections.update',
        'destroy' => 'admin.content-sections.destroy',
    ]);
});

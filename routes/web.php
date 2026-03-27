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
    return response()->file(public_path('react-ui/index.html'));
})->name('post-coverage-schedule');

// UI-only calculator/marketing pages (match gasq-calculator-project routes)
Route::get('/gasq-tco-calculator', function () {
    return response()->file(public_path('react-ui/index.html'));
})->name('gasq-tco-calculator.index');

Route::get('/absorbed-rate-calculator', function () {
    return response()->file(public_path('react-ui/index.html'));
})->name('absorbed-rate-calculator.index');

Route::get('/government-contract-calculator', function () {
    return response()->file(public_path('react-ui/index.html'));
})->name('government-contract-calculator.index');

Route::get('/keeps-doors-open-calculator', function () {
    return response()->file(public_path('react-ui/index.html'));
})->name('keeps-doors-open-calculator.index');

Route::get('/open-bid-offer', function () {
    return response()->file(public_path('react-ui/index.html'));
})->name('open-bid-offer.index');

// Route aliases to match gasq-calculator-project SPA paths
Route::get('/post-job', function () {
    return response()->file(public_path('react-ui/index.html'));
})->name('post-job.index');

Route::get('/calculator', function () {
    return response()->file(public_path('react-ui/index.html'));
})->name('calculator.index');

Route::get('/gasq-instant-estimator', function () {
    return response()->file(public_path('react-ui/index.html'));
})->name('gasq-instant-estimator.index');

// Keep navbar/calculator paths aligned to React UI
Route::get('/instant-estimator', function () {
    return redirect('/gasq-instant-estimator');
})->name('instant-estimator.react-ui');

// UI-only pages (match gasq-calculator-project routes)
Route::get('/vendor-form', function () {
    return response()->file(public_path('react-ui/index.html'));
})->name('vendor-form.index');

Route::get('/register/buyer', function () {
    return response()->file(public_path('react-ui/index.html'));
})->name('register.buyer.index');

Route::get('/register/vendor', function () {
    return response()->file(public_path('react-ui/index.html'));
})->name('register.vendor.index');

Route::get('/cost-analysis', function () {
    return response()->file(public_path('react-ui/index.html'));
})->name('cost-analysis.index');

// React SPA calculator routes (UI preview with matching tab highlight)
Route::get('/bill-rate-analysis', function () {
    return response()->file(public_path('react-ui/index.html'));
})->name('bill-rate-analysis.index');

Route::get('/manpower-hours', function () {
    return response()->file(public_path('react-ui/index.html'));
})->name('manpower-hours.index');

Route::get('/economic-justification', function () {
    return response()->file(public_path('react-ui/index.html'));
})->name('economic-justification.index');

// React calculator routes (pixel-perfect UI)
Route::get('/main-menu-calculator', function () {
    return response()->file(public_path('react-ui/index.html'));
})->name('main-menu-calculator.react-ui');

Route::get('/contract-analysis', function () {
    return response()->file(public_path('react-ui/index.html'));
})->name('contract-analysis.react-ui');

Route::get('/security-billing', function () {
    return response()->file(public_path('react-ui/index.html'));
})->name('security-billing.react-ui');

Route::get('/hourly-pay-calculator', function () {
    return response()->file(public_path('react-ui/index.html'));
})->name('hourly-pay-calculator.react-ui');

Route::get('/budget-calculator', function () {
    return response()->file(public_path('react-ui/index.html'));
})->name('budget-calculator.react-ui');

Route::get('/mobile-patrol-calculator', function () {
    return response()->file(public_path('react-ui/index.html'));
})->name('mobile-patrol-calculator.react-ui');

Route::get('/mobile-patrol-comparison', function () {
    return response()->file(public_path('react-ui/index.html'));
})->name('mobile-patrol-comparison.react-ui');

Route::get('/unarmed-security-guard-services', function () {
    return response()->file(public_path('react-ui/index.html'));
})->name('unarmed-security-guard-services.react-ui');

Route::get('/security-quote', function () {
    return response()->file(public_path('react-ui/index.html'));
})->name('security-quote.react-ui');

Route::get('/mobile-patrol-analysis', function () {
    return response()->file(public_path('react-ui/index.html'));
})->name('mobile-patrol-analysis.react-ui');

Route::get('/global-security-pricing', function () {
    return response()->file(public_path('react-ui/index.html'));
})->name('global-security-pricing.react-ui');

// Public marketplace (view only)
Route::get('/job-board', [App\Http\Controllers\JobPostingController::class, 'index'])->name('job-board');
Route::get('/jobs', [App\Http\Controllers\JobPostingController::class, 'index'])->name('jobs.index');
Route::get('/jobs/{job}', [App\Http\Controllers\JobPostingController::class, 'show'])->name('jobs.show');
Route::get('/vendor-profile/{user}', [App\Http\Controllers\VendorProfileController::class, 'show'])->name('vendor-profile.show');

Auth::routes();

Route::get('/home', [App\Http\Controllers\HomeController::class, 'index'])->name('home');

// Profile & Account
Route::middleware('auth')->group(function () {
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

    Route::get('/_backend/instant-estimator', [App\Http\Controllers\InstantEstimatorController::class, 'index'])->name('backend.instant-estimator.index');
    Route::post('/_backend/instant-estimator', [App\Http\Controllers\InstantEstimatorController::class, 'index'])->name('backend.instant-estimator.post');

    Route::get('/_backend/main-menu-calculator', [App\Http\Controllers\MainMenuCalculatorController::class, 'index'])->name('backend.main-menu-calculator.index');
    Route::post('/_backend/main-menu-calculator', [App\Http\Controllers\MainMenuCalculatorController::class, 'index'])->name('backend.main-menu-calculator.post');

    Route::get('/_backend/contract-analysis', [App\Http\Controllers\ContractAnalysisController::class, 'index'])->name('backend.contract-analysis.index');
    Route::post('/_backend/contract-analysis', [App\Http\Controllers\ContractAnalysisController::class, 'index'])->name('backend.contract-analysis.post');

    Route::get('/_backend/security-billing', [App\Http\Controllers\SecurityBillingController::class, 'index'])->name('backend.security-billing.index');
    Route::post('/_backend/security-billing', [App\Http\Controllers\SecurityBillingController::class, 'index'])->name('backend.security-billing.post');

    Route::get('/_backend/mobile-patrol-calculator', [App\Http\Controllers\MobilePatrolController::class, 'calculator'])->name('backend.mobile-patrol.calculator');
    Route::post('/_backend/mobile-patrol-calculator', [App\Http\Controllers\MobilePatrolController::class, 'calculator'])->name('backend.mobile-patrol.calculator.post');

    Route::get('/_backend/mobile-patrol-comparison', [App\Http\Controllers\MobilePatrolController::class, 'comparison'])->name('backend.mobile-patrol.comparison');
    Route::post('/_backend/mobile-patrol-comparison', [App\Http\Controllers\MobilePatrolController::class, 'comparison'])->name('backend.mobile-patrol.comparison.post');

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

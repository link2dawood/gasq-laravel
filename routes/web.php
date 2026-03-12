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
Route::get('/post-coverage-schedule', [PageController::class, 'postCoverageSchedule'])->name('post-coverage-schedule');

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

    // Calculators
    Route::get('/instant-estimator', [App\Http\Controllers\InstantEstimatorController::class, 'index'])->name('instant-estimator.index');
    Route::post('/instant-estimator', [App\Http\Controllers\InstantEstimatorController::class, 'index']);
    Route::get('/main-menu-calculator', [App\Http\Controllers\MainMenuCalculatorController::class, 'index'])->name('main-menu-calculator.index');
    Route::post('/main-menu-calculator', [App\Http\Controllers\MainMenuCalculatorController::class, 'index']);
    Route::get('/contract-analysis', [App\Http\Controllers\ContractAnalysisController::class, 'index'])->name('contract-analysis.index');
    Route::post('/contract-analysis', [App\Http\Controllers\ContractAnalysisController::class, 'index']);
    Route::get('/security-billing', [App\Http\Controllers\SecurityBillingController::class, 'index'])->name('security-billing.index');
    Route::post('/security-billing', [App\Http\Controllers\SecurityBillingController::class, 'index']);
    Route::get('/mobile-patrol-calculator', [App\Http\Controllers\MobilePatrolController::class, 'calculator'])->name('mobile-patrol.calculator');
    Route::post('/mobile-patrol-calculator', [App\Http\Controllers\MobilePatrolController::class, 'calculator']);
    Route::get('/mobile-patrol-comparison', [App\Http\Controllers\MobilePatrolController::class, 'comparison'])->name('mobile-patrol.comparison');
    Route::post('/mobile-patrol-comparison', [App\Http\Controllers\MobilePatrolController::class, 'comparison']);

    // Standalone aliases for React calculators
    Route::get('/budget-calculator', function () {
        return redirect()->route('main-menu-calculator.index', ['tab' => 'economic']);
    })->name('budget-calculator.index');

    Route::get('/hourly-pay-calculator', function () {
        return redirect()->route('main-menu-calculator.index', ['tab' => 'billrate']);
    })->name('hourly-pay-calculator.index');

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

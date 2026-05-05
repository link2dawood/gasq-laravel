<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\PageController;
use App\Http\Controllers\AdminVendorOpportunityController;
use App\Http\Controllers\OpenBidOfferController;
use App\Http\Controllers\StripeCreditsController;
use App\Http\Controllers\VendorEstimateSubmissionController;
use App\Http\Controllers\VendorLeadsController;
use App\Http\Controllers\VendorOpportunityController;

Route::get('/', [PageController::class, 'landing'])->name('landing');
Route::get('/marketplace-landing', [PageController::class, 'marketplaceLanding'])->name('marketplace-landing');
Route::get('/pricing', [PageController::class, 'pricing'])->name('pricing');
Route::get('/faq', [PageController::class, 'faq'])->name('faq');
Route::get('/payscale', [PageController::class, 'payScale'])->name('payscale');
Route::get('/payment-model', [PageController::class, 'paymentPolicy'])->name('payment-policy');
Route::get('/terms-and-conditions', [PageController::class, 'terms'])->name('terms');
Route::get('/privacy-policy', [PageController::class, 'privacy'])->name('privacy-policy');
// Vendor-only standalone calculators.
Route::middleware(['auth', 'phone.verified', 'vendor'])->group(function () {
    Route::get('/post-coverage-schedule', function () {
        return view('calculators.post-coverage-schedule');
    })->name('post-coverage-schedule');

    Route::get('/gasq-tco-calculator', function () {
        return view('calculators.gasq-tco-calculator');
    })->name('gasq-tco-calculator.index');

    Route::get('/government-contract-calculator', function () {
        return view('calculators.government-contract-calculator');
    })->name('government-contract-calculator.index');
});

Route::get('/open-bid-offer', OpenBidOfferController::class)
    ->middleware('auth')
    ->name('open-bid-offer.index');

Route::get('/vendor-opportunities/{invitation}', [VendorOpportunityController::class, 'show'])
    ->middleware('signed')
    ->name('vendor-opportunities.show');

Route::get('/post-job', function () {
    return view('calculators.post-job');
})->name('post-job.index');

Route::get('/calculator', function () {
    return view('calculators.index');
})->name('calculator.index');

Route::match(['get', 'post'], '/instant-estimator', [App\Http\Controllers\InstantEstimatorController::class, 'index'])
    ->middleware(['auth', 'calc.credits:instant_estimator_access'])
    ->name('instant-estimator.index');

Route::get('/vendor-form', function () {
    return view('pages.vendor-form');
})->name('vendor-form.index');

Route::get('/register/buyer', function () {
    return view('pages.register-buyer');
})->name('register.buyer.index');

Route::get('/register/vendor', function () {
    return view('pages.register-vendor');
})->name('register.vendor.index');

// Auth-only routes (phone verification)
Route::middleware('auth')->group(function () {
    Route::get('/vendor/leads', [VendorLeadsController::class, 'index'])->name('vendor-leads.index');
    Route::get('/vendor-opportunities/manage/{invitation}', [VendorOpportunityController::class, 'manage'])
        ->name('vendor-opportunities.manage');

    // Phone verification (signup OTP)
    Route::get('/phone/verify', [App\Http\Controllers\Auth\PhoneVerificationController::class, 'show'])->name('phone.verify.show');
    Route::post('/phone/verify/send', [App\Http\Controllers\Auth\PhoneVerificationController::class, 'send'])->name('phone.verify.send');
    Route::post('/phone/verify', [App\Http\Controllers\Auth\PhoneVerificationController::class, 'check'])->name('phone.verify.check');
});

// Master Inputs should remain editable anytime (no credits needed).
Route::middleware(['auth', 'phone.verified'])->group(function () {
    Route::get('/master-inputs', [App\Http\Controllers\MasterInputsController::class, 'index'])->name('master-inputs.index');
});

// Buyer estimator helpers and buyer job flow.
Route::middleware(['auth'])->group(function () {
    Route::post('/instant-estimator/prepare-job', [App\Http\Controllers\JobPostingController::class, 'prepareFromEstimator'])
        ->name('instant-estimator.prepare-job');
    Route::post('/instant-estimator/checkout-fee', [App\Http\Controllers\InstantEstimatorFeeCheckoutController::class, 'store'])
        ->name('instant-estimator.fee-checkout');

    // Vendor → submit estimate to a buyer who posted a job
    Route::get('/vendor-estimate-submissions/open-jobs', [VendorEstimateSubmissionController::class, 'openJobs'])
        ->name('vendor-estimate-submissions.open-jobs');
    Route::post('/vendor-estimate-submissions', [VendorEstimateSubmissionController::class, 'store'])
        ->name('vendor-estimate-submissions.store');

    // Jobs (create, edit, delete) and bids.
    // Buyers can reach the job flow before phone verification because the job form
    // itself now handles inline SMS verification for the contact phone.
    Route::post('/jobs/create/start', [App\Http\Controllers\JobPostingController::class, 'start'])->name('jobs.create.start');
    Route::post('/jobs/preview', [App\Http\Controllers\JobPostingController::class, 'preview'])->name('jobs.preview');
    Route::get('/jobs/review', [App\Http\Controllers\JobPostingController::class, 'review'])->name('jobs.review');
    Route::post('/jobs/review/edit', [App\Http\Controllers\JobPostingController::class, 'editReview'])->name('jobs.review.edit');
    Route::post('/jobs/publish', [App\Http\Controllers\JobPostingController::class, 'publish'])->name('jobs.publish');
    Route::post('/jobs/{job}/hire', [App\Http\Controllers\JobPostingController::class, 'hire'])->name('jobs.hire');
    Route::post('/jobs/{job}/close', [App\Http\Controllers\JobPostingController::class, 'close'])->name('jobs.close');
    Route::resource('jobs', App\Http\Controllers\JobPostingController::class)->except(['index', 'show'])->names('jobs');
    Route::post('/jobs/{job}/bids', [App\Http\Controllers\BidController::class, 'store'])->name('bids.store');
    Route::post('/jobs/{job}/offer-response', [App\Http\Controllers\BidController::class, 'offerResponse'])->name('bids.offer-response');
    Route::put('/bids/{bid}', [App\Http\Controllers\BidController::class, 'update'])->name('bids.update');
    Route::post('/bids/{bid}/respond', [App\Http\Controllers\BidController::class, 'respond'])->name('bids.respond');
    Route::post('/bids/{bid}/counter-offer', [App\Http\Controllers\BidController::class, 'counterOffer'])->name('bids.counter-offer');

    Route::post('/vendor-opportunities/{invitation}/accept', [VendorOpportunityController::class, 'accept'])
        ->name('vendor-opportunities.accept');
    Route::post('/vendor-opportunities/{invitation}/decline', [VendorOpportunityController::class, 'decline'])
        ->name('vendor-opportunities.decline');
    Route::post('/vendor-opportunities/{invitation}/bid', [VendorOpportunityController::class, 'submitBid'])
        ->name('vendor-opportunities.submit-bid');
});

// Vendor-only calculator suite.
Route::middleware(['auth', 'phone.verified', 'has.credits', 'buyer.has_job', 'master.inputs'])->group(function () {
    Route::get('/budget-calculator', function () { return view('calculators.budget'); })->name('budget-calculator.index');
});

Route::middleware(['auth', 'phone.verified', 'vendor', 'has.credits', 'buyer.has_job', 'master.inputs'])->group(function () {
    Route::get('/main-menu-calculator', [App\Http\Controllers\MainMenuCalculatorController::class, 'index'])->name('main-menu-calculator.index');
    Route::post('/main-menu-calculator', [App\Http\Controllers\MainMenuCalculatorController::class, 'index'])->name('main-menu-calculator.post');

    Route::get('/security-billing', [App\Http\Controllers\SecurityBillingController::class, 'index'])->name('security-billing.index');
    Route::post('/security-billing', [App\Http\Controllers\SecurityBillingController::class, 'index'])->name('security-billing.post');

    Route::get('/mobile-patrol-calculator', [App\Http\Controllers\MobilePatrolController::class, 'calculator'])->middleware('calc.credits:mobile_patrol_calculator_access')->name('mobile-patrol-calculator');
    Route::post('/mobile-patrol-calculator', [App\Http\Controllers\MobilePatrolController::class, 'calculator'])->middleware('calc.credits:mobile_patrol_calculator_access')->name('mobile-patrol-calculator.post');

    Route::get('/mobile-patrol-comparison', [App\Http\Controllers\MobilePatrolController::class, 'comparison'])->name('mobile-patrol-comparison');
    Route::post('/mobile-patrol-comparison', [App\Http\Controllers\MobilePatrolController::class, 'comparison'])->name('mobile-patrol-comparison.post');

    Route::get('/bill-rate-analysis', function () { return view('calculators.bill-rate'); })->name('bill-rate-analysis.index');
    Route::get('/economic-justification', function () { return view('calculators.economic-justification'); })->name('economic-justification.index');

    Route::get('/mobile-patrol-analysis', function () { return view('calculators.mobile-patrol-analysis'); })->name('mobile-patrol-analysis.index');
    Route::get('/mobile-patrol-hit-calculator', function () {
        return view('calculators.mobile-patrol-hit-calculator');
    })->middleware('calc.credits:mobile_patrol_hit_calculator_access')->name('mobile-patrol-hit-calculator.index');
    Route::get('/buyer-fit-index', function () {
        return view('calculators.buyer-fit-index');
    })->name('buyer-fit-index.index');
    Route::get('/gasq-direct-labor-build-up', function () {
        return view('calculators.gasq-direct-labor-build-up');
    })->name('gasq-direct-labor-build-up.index');
    Route::get('/gasq-additional-cost-stack', function () {
        return view('calculators.gasq-additional-cost-stack');
    })->name('gasq-additional-cost-stack.index');
    Route::get('/workforce-appraisal-report', function () {
        return view('calculators.workforce-appraisal-report', ['pageKey' => 'full-report']);
    })->name('workforce-appraisal-report.index');
    Route::get('/cfo-bill-rate-breakdown', function () {
        return view('calculators.workforce-appraisal-report', ['pageKey' => 'cfo']);
    })->name('cfo-bill-rate-breakdown.index');
    Route::get('/post-position-summary', function () {
        return view('calculators.workforce-appraisal-report', ['pageKey' => 'posts']);
    })->name('post-position-summary.index');
    Route::get('/appraisal-comparison-summary', function () {
        return view('calculators.workforce-appraisal-report', ['pageKey' => 'appraisal']);
    })->name('appraisal-comparison-summary.index');
});

// Public marketplace (view only)
Route::get('/job-board', function () {
    return redirect()->route('jobs.index');
})->name('job-board');
Route::get('/jobs', [App\Http\Controllers\JobPostingController::class, 'index'])->name('jobs.index');
Route::get('/jobs/{job}/bids-fragment', [App\Http\Controllers\JobPostingController::class, 'bidsFragment'])->name('jobs.bids-fragment');

// Buyer / token-bearing customer can view a submitted vendor estimate
Route::get('/vendor-estimate-submissions/{submission}', [VendorEstimateSubmissionController::class, 'show'])
    ->name('vendor-estimate-submissions.show');
Route::get('/vendor-profile/{user}', [App\Http\Controllers\VendorProfileController::class, 'show'])->name('vendor-profile.show');

Auth::routes(['verify' => true]);

Route::get('/home', [App\Http\Controllers\HomeController::class, 'index'])->name('home');

// Embedded SPA: session, CSRF, wallet balance, feature rules (same-origin Laravel session)
Route::get('/api/spa/session', [App\Http\Controllers\Api\SpaSessionController::class, 'show'])->name('api.spa.session');

// Profile & Account
Route::middleware(['auth', 'phone.verified'])->group(function () {
    Route::post('/api/spa/wallet/spend', [App\Http\Controllers\Api\SpaWalletController::class, 'spend'])->name('api.spa.wallet.spend');
    Route::post('/api/spa/mail/economic-justification', [App\Http\Controllers\Api\SpaCalculatorMailController::class, 'economicJustification'])->name('api.spa.mail.economic-justification');
    Route::post('/api/spa/mail/calculator-pdf', [App\Http\Controllers\Api\SpaCalculatorMailController::class, 'calculatorPdf'])->name('api.spa.mail.calculator-pdf');

    Route::get('/api/master-inputs', [App\Http\Controllers\Api\MasterInputsApiController::class, 'show'])->name('api.master-inputs.show');
    Route::put('/api/master-inputs', [App\Http\Controllers\Api\MasterInputsApiController::class, 'update'])->name('api.master-inputs.update');

    Route::get('/profile', [ProfileController::class, 'show'])->name('profile.show');
    Route::get('/profile/edit', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::post('/profile/phone/send', [ProfileController::class, 'sendPhoneVerification'])->name('profile.phone.send');
    Route::post('/profile/phone/verify', [ProfileController::class, 'verifyPhoneCode'])->name('profile.phone.verify');
    Route::put('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::put('/profile/password', [ProfileController::class, 'updatePassword'])->name('profile.password.update');
    Route::post('/profile/avatar', [ProfileController::class, 'updateAvatar'])->name('profile.avatar.update');
    Route::delete('/profile/avatar', [ProfileController::class, 'removeAvatar'])->name('profile.avatar.remove');
    Route::get('/account-balance', [App\Http\Controllers\AccountBalanceController::class, 'index'])->name('account-balance');
    Route::get('/credits', [App\Http\Controllers\CreditsController::class, 'index'])->name('credits');
    Route::get('/credits/success', [App\Http\Controllers\CreditsController::class, 'success'])->name('credits.success');
    Route::post('/credits/redeem', [App\Http\Controllers\CreditsController::class, 'redeem'])->name('credits.redeem');
    Route::post('/credits/checkout/{plan}', [StripeCreditsController::class, 'checkout'])->name('credits.checkout');

    // Discovery call
    Route::get('/discovery-call', [App\Http\Controllers\DiscoveryCallController::class, 'index'])->name('discovery-call.index');
    Route::post('/discovery-call', [App\Http\Controllers\DiscoveryCallController::class, 'store'])->name('discovery-call.store');

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

    Route::post('/_backend/report-payload', \App\Http\Controllers\Backend\ReportPayloadController::class)
        ->name('backend.report-payload.store');

    Route::post('/_backend/standalone/{type}/v24/compute', \App\Http\Controllers\Backend\StandaloneV24ComputeController::class)
        ->whereIn('type', [
            'bill-rate-analysis',
            'economic-justification',
            'budget-calculator',
            'mobile-patrol-analysis',
            'workforce-appraisal-report',
            'mobile-patrol-hit-calculator',
            'gasq-tco-calculator',
            'government-contract-calculator',
            'buyer-fit-index',
            'gasq-direct-labor-build-up',
            'gasq-additional-cost-stack',
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

// Keep the public show route after auth-only job routes so /jobs/create is not
// captured as a {job} parameter and turned into a 404.
Route::get('/jobs/{job}', [App\Http\Controllers\JobPostingController::class, 'show'])->name('jobs.show');

// Stripe webhook (no auth)
Route::post('/stripe/webhook', [StripeCreditsController::class, 'webhook'])->name('stripe.webhook');

// Admin
Route::middleware(['auth', 'admin'])->group(function () {
    Route::get('/admin/analytics', [App\Http\Controllers\AnalyticsController::class, 'index'])->name('admin.analytics');
    Route::get('/admin/vendor-opportunities', [AdminVendorOpportunityController::class, 'index'])->name('admin.vendor-opportunities.index');
    Route::get('/admin/vendor-opportunities/{opportunity}', [AdminVendorOpportunityController::class, 'show'])->name('admin.vendor-opportunities.show');
    Route::post('/admin/vendor-opportunities/{opportunity}/approve', [AdminVendorOpportunityController::class, 'approve'])->name('admin.vendor-opportunities.approve');
    Route::post('/admin/vendor-opportunities/{opportunity}/close', [AdminVendorOpportunityController::class, 'close'])->name('admin.vendor-opportunities.close');
    Route::post('/admin/vendor-opportunity-invitations/{invitation}/award', [AdminVendorOpportunityController::class, 'award'])->name('admin.vendor-opportunities.award');
    Route::get('/admin/settings', [App\Http\Controllers\AdminSettingsController::class, 'index'])->name('admin.settings');
    Route::post('/admin/settings', [App\Http\Controllers\AdminSettingsController::class, 'update'])->name('admin.settings.update');
    Route::post('/admin/settings/logo', [App\Http\Controllers\AdminSettingsController::class, 'uploadLogo'])->name('admin.settings.logo');
    Route::post('/admin/settings/logo/remove', [App\Http\Controllers\AdminSettingsController::class, 'removeLogo'])->name('admin.settings.logo.remove');
    Route::get('/admin/twilio', [App\Http\Controllers\AdminTwilioController::class, 'show'])->name('admin.twilio.show');
    Route::post('/admin/twilio/send-test', [App\Http\Controllers\AdminTwilioController::class, 'sendTest'])->name('admin.twilio.send-test');
    Route::get('/admin/tokens', [App\Http\Controllers\AdminTokensController::class, 'index'])->name('admin.tokens');
    Route::post('/admin/tokens/adjust', [App\Http\Controllers\AdminTokensController::class, 'adjust'])->name('admin.tokens.adjust');
    Route::post('/admin/tokens/features/{rule}', [App\Http\Controllers\AdminTokensController::class, 'updateFeature'])->name('admin.tokens.features.update');
    Route::resource('admin/coupons', App\Http\Controllers\AdminCouponController::class)->except(['show'])->names([
        'index' => 'admin.coupons.index',
        'create' => 'admin.coupons.create',
        'store' => 'admin.coupons.store',
        'edit' => 'admin.coupons.edit',
        'update' => 'admin.coupons.update',
        'destroy' => 'admin.coupons.destroy',
    ]);
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

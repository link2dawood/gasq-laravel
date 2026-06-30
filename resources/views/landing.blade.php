@extends('layouts.app')

@section('title', 'GetASecurityQuoteNow — Know the True Cost of Security Services Before You Buy')

@push('styles')
<style>
    /* Subtitle — larger, bold, brand navy */
    .gasq-hero-subtitle { font-size: 1.25rem; font-weight: 700; color: #062e7a; }
    /* Darker, more readable body copy across the landing page */
    .gasq-hero-lead,
    .gasq-hero-bg .text-gasq-muted,
    .gasq-section p,
    .gasq-section .text-gasq-muted,
    .gasq-list-check li { color: #333444 !important; }
    .gasq-hero-lead,
    .gasq-section p,
    .gasq-list-check li { font-size: 1.0625rem; }
</style>
@endpush

@section('content')
<div class="min-vh-100">

    {{-- HERO --}}
    <section class="gasq-hero-bg">
        <div class="container text-center px-4">
            <h1 class="gasq-hero-title mb-4">Know the True Cost of Security Services Before You Buy.</h1>
            <p class="gasq-hero-lead lead mb-4 mx-auto">
                GetASecurityQuoteNow (GASQ) helps property owners, procurement teams, and security buyers
                compare the <em>real total cost of ownership</em> of security services before signing a contract.
            </p>
            <p class="gasq-hero-subtitle mb-4 mx-auto" style="max-width: 48rem;">
                We don&rsquo;t just collect quotes. We help you understand:
            </p>
            <ul class="gasq-list-check mx-auto text-start mb-5" style="max-width: 36rem;">
                <li>What security services <em>should</em> cost</li>
                <li>What your in-house cost would be</li>
                <li>What hidden labor costs vendors absorb</li>
                <li>What your capital recovery opportunity looks like</li>
                <li>Whether a proposal is realistic, risky, or overpriced</li>
            </ul>
            <div class="d-flex flex-column flex-md-row gap-3 justify-content-center align-items-center mb-3">
                <a href="{{ route('instant-estimator.index') }}" class="btn btn-primary btn-lg px-4 py-3 shadow">
                    <i class="fa fa-calculator me-2"></i>Get An Instant Security Cost Estimate
                </a>
            </div>
            <p class="small text-gasq-muted mb-0">
                <i class="fa fa-shield-alt me-1"></i>CFO Tested. CFO Approved.
            </p>
        </div>
    </section>

    {{-- BUYER PAIN POINT --}}
    <section class="gasq-section">
        <div class="container px-4">
            <div class="text-center mb-5">
                <h2 class="gasq-section-title mb-3">Most Buyers Don&rsquo;t Know What Security Really Costs</h2>
                <p class="text-gasq-muted mx-auto" style="max-width: 48rem;">
                    Many buyers compare vendors against each other instead of comparing vendors against the
                    <em>actual cost of performing the service correctly.</em>
                </p>
            </div>
            <div class="row g-4 align-items-center">
                <div class="col-lg-6">
                    <div class="gasq-card card p-4 p-lg-5">
                        <h3 class="card-title gasq-card-title-lg mb-4 text-gasq-destructive">That leads to:</h3>
                        <ul class="gasq-list-dot mb-0 text-gasq-destructive">
                            <li>Understaffed contracts</li>
                            <li>High turnover</li>
                            <li>Hidden workforce costs</li>
                            <li>Poor service quality</li>
                            <li>Unrealistic pricing</li>
                            <li>Increased liability exposure</li>
                        </ul>
                    </div>
                </div>
                <div class="col-lg-6">
                    <div class="gasq-card card p-4 p-lg-5">
                        <h3 class="card-title gasq-card-title-lg mb-3">GASQ changes that.</h3>
                        <p class="text-gasq-muted mb-4">
                            We help buyers answer the one question every procurement decision really turns on:
                        </p>
                        <blockquote class="fs-4 fw-semibold text-primary mb-0">
                            &ldquo;Can I afford this service before I buy it?&rdquo;
                        </blockquote>
                    </div>
                </div>
            </div>
        </div>
    </section>

    {{-- POSITIONING / KBB --}}
    <section class="gasq-section-muted">
        <div class="container px-4">
            <div class="text-center mb-5">
                <h2 class="gasq-section-title mb-3">The Kelly Blue Book for Security Services Pricing&trade;</h2>
                <p class="text-gasq-muted mx-auto" style="max-width: 52rem;">
                    GASQ helps buyers make smarter security purchasing decisions using workforce-based
                    pricing analytics, cost realism modeling, and procurement intelligence.
                </p>
            </div>
            <div class="gasq-card card p-4 p-lg-5 mx-auto" style="max-width: 60rem;">
                <h3 class="card-title gasq-card-title-lg mb-4 text-center">Our platform compares:</h3>
                <div class="row g-3">
                    <div class="col-md-6">
                        <ul class="gasq-list-check mb-0">
                            <li>In-House Security Costs</li>
                            <li>Outsourced Vendor Costs</li>
                            <li>Workforce Sustainment Expenses</li>
                        </ul>
                    </div>
                    <div class="col-md-6">
                        <ul class="gasq-list-check mb-0">
                            <li>Hours Paid But Not Worked</li>
                            <li>Capital Recovery Opportunities</li>
                            <li>Price Realism &amp; Risk Exposure</li>
                        </ul>
                    </div>
                </div>
                <p class="text-center fw-semibold text-primary mt-4 mb-0">CFO Tested. CFO Approved.</p>
            </div>
        </div>
    </section>

    {{-- HOW IT WORKS --}}
    <section class="gasq-section" id="how-it-works">
        <div class="container px-4">
            <div class="text-center mb-5">
                <h2 class="gasq-section-title mb-3">How GASQ Works</h2>
                <p class="text-gasq-muted mx-auto" style="max-width: 48rem;">
                    From your scope of work to a vetted vendor decision &mdash; in four steps.
                </p>
            </div>
            <div class="row g-4">
                <div class="col-md-6 col-lg-3">
                    <div class="gasq-card card h-100 p-4">
                        <div class="d-flex align-items-center gap-3 mb-3">
                            <span class="gasq-step-num flex-shrink-0">1</span>
                            <h4 class="h6 fw-semibold mb-0">Enter Your Requirements</h4>
                        </div>
                        <p class="small text-gasq-muted mb-2">Tell us:</p>
                        <ul class="small text-gasq-muted mb-0 ps-3">
                            <li>Property type</li>
                            <li>Coverage hours</li>
                            <li>Number of officers</li>
                            <li>Armed or unarmed</li>
                            <li>Patrol or standing post</li>
                            <li>Service expectations</li>
                        </ul>
                    </div>
                </div>
                <div class="col-md-6 col-lg-3">
                    <div class="gasq-card card h-100 p-4">
                        <div class="d-flex align-items-center gap-3 mb-3">
                            <span class="gasq-step-num flex-shrink-0">2</span>
                            <h4 class="h6 fw-semibold mb-0">Receive a Workforce-to-Post&trade; Cost Appraisal</h4>
                        </div>
                        <p class="small text-gasq-muted mb-2">Our pricing engine evaluates:</p>
                        <ul class="small text-gasq-muted mb-0 ps-3">
                            <li>Direct labor</li>
                            <li>Employer burden</li>
                            <li>Workforce maintenance cost</li>
                            <li>True total cost of ownership (TCO)</li>
                            <li>Vendor absorbed costs</li>
                        </ul>
                    </div>
                </div>
                <div class="col-md-6 col-lg-3">
                    <div class="gasq-card card h-100 p-4">
                        <div class="d-flex align-items-center gap-3 mb-3">
                            <span class="gasq-step-num flex-shrink-0">3</span>
                            <h4 class="h6 fw-semibold mb-0">Compare Your Options</h4>
                        </div>
                        <p class="small text-gasq-muted mb-2">See:</p>
                        <ul class="small text-gasq-muted mb-0 ps-3">
                            <li>Buyer-to-vendor comparisons</li>
                            <li>Cost recovery opportunities</li>
                            <li>Price realism ranges</li>
                            <li>Floor, target, and ceiling pricing</li>
                        </ul>
                    </div>
                </div>
                <div class="col-md-6 col-lg-3">
                    <div class="gasq-card card h-100 p-4">
                        <div class="d-flex align-items-center gap-3 mb-3">
                            <span class="gasq-step-num flex-shrink-0">4</span>
                            <h4 class="h6 fw-semibold mb-0">Connect With Prequalified Vendors</h4>
                        </div>
                        <p class="small text-gasq-muted mb-0">
                            Post your approved budget offer to our vendor network and receive vendor
                            acceptance responses.
                        </p>
                    </div>
                </div>
            </div>
            <div class="text-center mt-5">
                <a href="{{ route('instant-estimator.index') }}" class="btn btn-primary btn-lg">
                    Start Step 1 &rarr;
                </a>
            </div>
        </div>
    </section>

    {{-- WHAT YOU GET --}}
    <section class="gasq-section-muted">
        <div class="container px-4">
            <div class="text-center mb-5">
                <h2 class="gasq-section-title mb-3">What You Get With GASQ</h2>
                <p class="text-gasq-muted mx-auto" style="max-width: 48rem;">
                    A complete pricing intelligence toolkit purpose-built for security procurement.
                </p>
            </div>
            <div class="row g-3">
                @php
                    $features = [
                        ['icon' => 'fa-calculator',      'label' => 'Instant Security Cost Estimator'],
                        ['icon' => 'fa-users',           'label' => 'Workforce-to-Post™ Analysis'],
                        ['icon' => 'fa-coins',           'label' => 'Capital Recovery Report'],
                        ['icon' => 'fa-balance-scale',   'label' => 'Price Realism Review'],
                        ['icon' => 'fa-network-wired',   'label' => 'Vendor Acceptance Network'],
                        ['icon' => 'fa-check-double',    'label' => 'Budget Validation Tools'],
                        ['icon' => 'fa-chart-line',      'label' => 'Security ROI Analysis'],
                        ['icon' => 'fa-door-open',       'label' => 'Cost Per Door Models'],
                        ['icon' => 'fa-route',           'label' => 'Mobile Patrol Cost Modeling'],
                        ['icon' => 'fa-file-invoice-dollar', 'label' => 'CFO-Style Pricing Reports'],
                    ];
                @endphp
                @foreach ($features as $feature)
                    <div class="col-6 col-md-4 col-lg-3 col-xl-2">
                        <div class="gasq-card card h-100 p-3 p-md-4 text-center">
                            <i class="fa {{ $feature['icon'] }} text-primary fa-2x mb-3"></i>
                            <p class="small fw-semibold mb-0">{!! $feature['label'] !!}</p>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </section>

    {{-- GUARANTEE --}}
    <section class="gasq-roi-section">
        <div class="container px-4">
            <div class="gasq-card card gasq-roi-card p-4 p-lg-5 mx-auto" style="max-width: 56rem;">
                <div class="text-center mb-4">
                    <h2 class="gasq-section-title text-primary mb-3">Built for Buyers. Trusted by Vendors.</h2>
                    <p class="text-gasq-muted mb-0" style="font-size: 1.125rem;">
                        Our pricing models are designed to create realistic expectations for both buyers
                        and security service providers.
                    </p>
                </div>
                <h3 class="card-title gasq-card-title-lg mb-3">GASQ Advantages</h3>
                <ul class="gasq-list-check mb-0">
                    <li>Buyer-to-vendor comparison model</li>
                    <li>Vendor replacement guarantee</li>
                    <li>Price lock options</li>
                    <li>Transparent workforce pricing</li>
                    <li>Budget-first procurement approach</li>
                    <li>Educational procurement tools</li>
                </ul>
            </div>
        </div>
    </section>

    {{-- FINAL CTA --}}
    <section class="gasq-cta-bg">
        <div class="container px-4 text-center">
            <h2 class="gasq-section-title mb-3">Stop Shopping Blind.</h2>
            <p class="lead text-gasq-muted mb-5">Know your security cost before you buy.</p>
            <div class="row g-4 justify-content-center">
                <div class="col-md-4">
                    <div class="gasq-card card p-4 p-lg-5 h-100">
                        <i class="fa fa-calculator text-primary fa-2x mb-3"></i>
                        <h3 class="gasq-card-title-lg mb-2">Start My Free Estimate</h3>
                        <p class="text-gasq-muted small mb-4">Generate a Workforce-to-Post&trade; cost appraisal in minutes.</p>
                        <a href="{{ route('instant-estimator.index') }}" class="btn btn-primary w-100 py-3 mt-auto">
                            Start Estimate &rarr;
                        </a>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="gasq-card card p-4 p-lg-5 h-100">
                        <i class="fa fa-file-upload text-primary fa-2x mb-3"></i>
                        <h3 class="gasq-card-title-lg mb-2">Upload My Scope of Work</h3>
                        <p class="text-gasq-muted small mb-4">Post your scope and let prequalified vendors respond on your terms.</p>
                        <a href="{{ route('jobs.create') }}" class="btn btn-outline-primary w-100 py-3 mt-auto">
                            Post Scope &rarr;
                        </a>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="gasq-card card p-4 p-lg-5 h-100">
                        <i class="fa fa-calendar-check text-primary fa-2x mb-3"></i>
                        <h3 class="gasq-card-title-lg mb-2">Schedule a Pricing Review</h3>
                        <p class="text-gasq-muted small mb-4">Walk through your numbers with the GASQ team before you commit.</p>
                        <a href="{{ route('discovery-call.index') }}" class="btn btn-outline-primary w-100 py-3 mt-auto">
                            Book Review &rarr;
                        </a>
                    </div>
                </div>
            </div>
            <p class="small text-gasq-muted mt-5 mb-0">
                The Workforce-to-Post&trade; Price Permit &amp; Capital Recovery Platform for Security Services.
            </p>
        </div>
    </section>

</div>

@section('footer')
    @include('partials.site-footer')
@endsection
@endsection

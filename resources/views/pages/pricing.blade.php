@extends('layouts.app')

@section('title', $pricingTitle ?? 'Pricing')

@section('content')
@php
    $audience = $audience ?? null;
    $pricingTitle = $pricingTitle ?? 'Pricing';
    $registerRoute = $audience === 'vendor' ? 'register.vendor.index' : ($audience === 'buyer' ? 'register.buyer.index' : 'register');
@endphp
<div class="container py-5">
    @if($audience === 'buyer')
        {{-- BUYER PRICING — buyers never spend credits. EnsureCreditsForCalculator lets
             every non-vendor through free, and the only buyer-side charge in the app is
             the per-engagement appraisal fee (InstantEstimatorFeeCheckoutController).
             So this page is deliberately NOT rendered from pricing_plans. --}}
        <div class="text-center mb-5">
            <h1 class="h2 mb-2">{{ $pricingTitle }}</h1>
            <p class="text-gasq-muted mb-0 mx-auto" style="max-width: 44rem;">
                Buyer tools are free. You pay only when you want a formal, GASQ Certified&trade; appraisal
                you can put in front of finance or attach to an RFP.
            </p>
        </div>

        <div class="row g-4 justify-content-center">
            <div class="col-md-4">
                <x-card title="Buyer Tools">
                    <p class="fs-3 fw-bold mb-1">Free</p>
                    <p class="text-muted small mb-3">No credits, no card</p>
                    <ul class="small mb-3">
                        <li>Instant Estimator</li>
                        <li>Your Cost to Protect&trade; benchmark</li>
                        <li>Workforce-to-Post&trade; staffing breakdown</li>
                        <li>Post a job and receive vendor bids</li>
                        <li>Compare bids against your benchmark</li>
                    </ul>
                    <a href="{{ route('instant-estimator.index') }}" class="btn btn-primary w-100">Start My Free Estimate</a>
                </x-card>
            </div>

            <div class="col-md-4">
                <x-card title="GASQ Certified Appraisal">
                    <p class="fs-3 fw-bold mb-1">Per engagement</p>
                    <p class="text-muted small mb-3">Quoted from your scope</p>
                    <ul class="small mb-3">
                        <li>Formal Cost to Protect appraisal document</li>
                        <li>Reviewed against the GASQ Certified methodology</li>
                        <li>Capital-recovery and budget-validation analysis</li>
                        <li>Procurement-ready for RFP and finance review</li>
                    </ul>
                    <a href="{{ route('instant-estimator.index') }}" class="btn btn-primary w-100">Price My Appraisal</a>
                    <p class="text-muted small mt-2 mb-0 text-center">
                        The fee depends on scope and contract value, so it is quoted from your job &mdash;
                        not a fixed list price.
                    </p>
                </x-card>
            </div>

            <div class="col-md-4">
                <x-card title="Procurement Support">
                    <p class="fs-3 fw-bold mb-1">Talk to us</p>
                    <p class="text-muted small mb-3">Complex or multi-site procurement</p>
                    <ul class="small mb-3">
                        <li>RFP and scope review</li>
                        <li>Multi-site and multi-post modelling</li>
                        <li>Discovery call with a GASQ analyst</li>
                        <li>Dedicated support through award</li>
                    </ul>
                    <a href="{{ route('contact') }}" class="btn btn-outline-primary w-100">Schedule a Pricing Review</a>
                </x-card>
            </div>
        </div>

        <p class="text-center text-gasq-muted small mt-4 mb-0">
            Credits are a vendor-side currency for bidding on GASQ opportunities. Buyers are never charged credits.
        </p>
    @else
    <div class="text-center mb-4">
        <h1 class="h2 mb-2">{{ $pricingTitle }}</h1>
        <p class="text-gasq-muted mb-3 mx-auto" style="max-width: 44rem;">
            Credits power vendor access to the calculators, estimate submissions, and buyer opportunity unlocks.
            Buy once, or subscribe monthly for a recurring allotment.
        </p>

        {{-- Billing toggle --}}
        <div class="btn-group" role="group" id="billingToggle" aria-label="Billing interval">
            <button type="button" class="btn btn-primary" data-billing="onetime">One-time</button>
            <button type="button" class="btn btn-outline-primary" data-billing="monthly">Monthly</button>
        </div>
    </div>

    @if($plans->isEmpty())
        <p class="text-muted text-center">Pricing plans will be listed here. <a href="{{ route('contact') }}">Contact us</a> for details.</p>
    @else
        <div class="row g-4 justify-content-center">
            @foreach($plans as $plan)
                <div class="col-md-4">
                    <x-card :title="$plan->name">
                        {{-- Price --}}
                        <div class="billing-onetime">
                            <p class="fs-3 fw-bold mb-1">${{ number_format($plan->price, 2) }}</p>
                            <p class="text-muted small mb-3">{{ $plan->tokens_included }} credits &middot; one-time</p>
                        </div>
                        <div class="billing-monthly d-none">
                            @if($plan->monthly_price)
                                <p class="fs-3 fw-bold mb-1">${{ number_format($plan->monthly_price, 2) }} <span class="fs-6 text-muted fw-normal">/ month</span></p>
                                <p class="text-muted small mb-3">{{ $plan->tokens_included }} credits every month</p>
                            @else
                                <p class="fs-3 fw-bold mb-1 text-muted">—</p>
                                <p class="text-muted small mb-3">Monthly not available for this plan</p>
                            @endif
                        </div>

                        @if($plan->features)
                            <ul class="small mb-3">
                                @foreach($plan->features as $f)
                                    <li>{{ $f }}</li>
                                @endforeach
                            </ul>
                        @endif

                        {{-- CTA: one-time --}}
                        <div class="billing-onetime">
                            @auth
                                <form method="POST" action="{{ route('credits.checkout', $plan) }}">
                                    @csrf
                                    <button type="submit" class="btn btn-primary w-100">Buy Now</button>
                                </form>
                            @else
                                <a href="{{ route($registerRoute, ['plan' => $plan->id]) }}" class="btn btn-primary w-100">Get Started</a>
                            @endauth
                        </div>

                        {{-- CTA: monthly --}}
                        <div class="billing-monthly d-none">
                            @if($plan->monthly_price)
                                @auth
                                    <form method="POST" action="{{ route('credits.subscribe', $plan) }}">
                                        @csrf
                                        <button type="submit" class="btn btn-primary w-100">Subscribe Monthly</button>
                                    </form>
                                @else
                                    <a href="{{ route($registerRoute, ['plan' => $plan->id, 'interval' => 'monthly']) }}" class="btn btn-primary w-100">Get Started</a>
                                @endauth
                            @endif
                        </div>
                    </x-card>
                </div>
            @endforeach
        </div>

        <p class="text-center text-gasq-muted small mt-4 mb-0">
            Credits never expire. One-time purchases are a single charge; monthly plans renew automatically and can be cancelled any time.
        </p>
    @endif
    @endif
</div>

<script>
    (function () {
        var toggle = document.getElementById('billingToggle');
        if (!toggle) return;
        toggle.querySelectorAll('button').forEach(function (btn) {
            btn.addEventListener('click', function () {
                var mode = btn.dataset.billing;
                toggle.querySelectorAll('button').forEach(function (b) {
                    b.classList.toggle('btn-primary', b === btn);
                    b.classList.toggle('btn-outline-primary', b !== btn);
                });
                document.querySelectorAll('.billing-onetime').forEach(function (el) { el.classList.toggle('d-none', mode !== 'onetime'); });
                document.querySelectorAll('.billing-monthly').forEach(function (el) { el.classList.toggle('d-none', mode !== 'monthly'); });
            });
        });
    })();
</script>
@endsection

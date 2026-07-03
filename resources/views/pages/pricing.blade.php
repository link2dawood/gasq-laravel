@extends('layouts.app')

@section('title', $pricingTitle ?? 'Pricing')

@section('content')
@php
    $audience = $audience ?? null;
    $pricingTitle = $pricingTitle ?? 'Pricing';
    $registerRoute = $audience === 'vendor' ? 'register.vendor.index' : ($audience === 'buyer' ? 'register.buyer.index' : 'register');
@endphp
<div class="container py-5">
    <div class="text-center mb-4">
        <div class="d-flex justify-content-center gap-2 mb-3">
            <a href="{{ route('pricing.buyers') }}" class="btn btn-sm {{ $audience === 'buyer' ? 'btn-primary' : 'btn-outline-primary' }}">For Buyers</a>
            <a href="{{ route('pricing.vendors') }}" class="btn btn-sm {{ $audience === 'vendor' ? 'btn-primary' : 'btn-outline-primary' }}">For Vendors</a>
        </div>
        <h1 class="h2 mb-2">{{ $pricingTitle }}</h1>
        <p class="text-gasq-muted mb-3">Buy credits once, or subscribe monthly for a recurring credit allotment. Credits power the calculators and reports.</p>

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

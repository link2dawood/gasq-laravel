@extends('layouts.app')
@section('title', 'Vendor Dashboard')
@section('header_variant', 'dashboard')

@push('styles')
<style>
    .vendor-dashboard-wrap { background: #f6f7fb; min-height: calc(100vh - 72px); }
    .vendor-dashboard-grid { display: grid; gap: 1.5rem; grid-template-columns: repeat(3, minmax(0, 1fr)); }
    .vendor-panel { background: #fff; border: 1px solid #d8dde8; border-radius: 1rem; overflow: hidden; }
    .vendor-panel-head { padding: 1.5rem; border-bottom: 1px solid #e5e9f2; }
    .vendor-panel-body { padding: 1.5rem; }
    .vendor-metric-circle {
        width: 170px; height: 170px; margin: 0 auto 1rem; border-radius: 999px;
        background: #c9c9cf; color: #fff; display: grid; place-items: center;
        font-size: 3rem; font-weight: 800; letter-spacing: -0.04em;
    }
    .vendor-progress { height: 1.2rem; background: #edf1f7; border-radius: 999px; overflow: hidden; }
    .vendor-progress > span { display: block; height: 100%; background: linear-gradient(90deg, #1f6fff, #4f8fff); }
    .vendor-cta {
        display: inline-flex; align-items: center; justify-content: center; min-width: 10rem;
        padding: .85rem 1.4rem; border-radius: 999px; background: #ff825f; color: #fff;
        text-decoration: none; font-weight: 700;
    }
    .vendor-cta:hover { color: #fff; background: #f36e47; }
    .vendor-link-btn {
        display: inline-flex; align-items: center; justify-content: center; min-width: 9rem;
        padding: .7rem 1.2rem; border-radius: 999px; border: 1px solid #2f71ff; color: #2f71ff;
        text-decoration: none; font-weight: 600; background: #fff;
    }
    .vendor-link-btn:hover { background: #f5f8ff; color: #2f71ff; }
    .vendor-panel-wide { grid-column: span 2; }
    @media (max-width: 991.98px) {
        .vendor-dashboard-grid { grid-template-columns: repeat(2, minmax(0, 1fr)); }
    }
    @media (max-width: 767.98px) {
        .vendor-dashboard-grid { grid-template-columns: 1fr; }
        .vendor-metric-circle { width: 140px; height: 140px; font-size: 2.4rem; }
        .vendor-panel-wide { grid-column: span 1; }
    }
</style>
@endpush

@section('content')
<div class="vendor-dashboard-wrap py-4 px-3 px-md-4">
    <div class="container-xl">
        <h1 class="display-5 fw-semibold mb-3">Welcome, {{ auth()->user()->name }}</h1>

        <div class="vendor-panel mb-4">
            <div class="vendor-panel-body">
                <p class="mb-0 fs-4">Welcome to your dashboard!</p>
            </div>
        </div>

        <div class="vendor-dashboard-grid">
            <section class="vendor-panel">
                <div class="vendor-panel-head"><h2 class="h2 mb-0">Complete your profile</h2></div>
                <div class="vendor-panel-body">
                    <p class="fs-4 mb-3">Your profile is completed {{ $vendorProfileCompletion ?? 0 }}%</p>
                    <div class="vendor-progress mb-4"><span style="width: {{ min(100, max(0, (int) ($vendorProfileCompletion ?? 0))) }}%"></span></div>
                    <p class="fs-4 mb-4">There are higher chances to get leads if you complete your profile.</p>
                    <a href="{{ route('profile.show') }}" class="vendor-cta">Complete profile</a>
                </div>
            </section>

            <section class="vendor-panel">
                <div class="vendor-panel-head"><h2 class="h2 mb-0">Leads</h2></div>
                <div class="vendor-panel-body text-center">
                    <div class="vendor-metric-circle">{{ $vendorLeadCount ?? 0 }}</div>
                    <p class="fs-3 mb-1">Leads</p>
                    <p class="fs-4 text-gasq-muted mb-4">{{ $vendorUnreadLeadCount ?? 0 }} unread leads</p>
                    <a href="{{ route('vendor-leads.index') }}" class="vendor-link-btn">View My Leads</a>
                </div>
            </section>

            <section class="vendor-panel">
                <div class="vendor-panel-head"><h2 class="h2 mb-0">Help</h2></div>
                <div class="vendor-panel-body">
                    <p class="fs-4 mb-0">Here you will find the help section</p>
                </div>
            </section>

            <section class="vendor-panel">
                <div class="vendor-panel-head"><h2 class="h2 mb-0">Lead settings</h2></div>
                <div class="vendor-panel-body">
                    <p class="fs-4 mb-4">There are no lead settings right now</p>
                    <a href="{{ route('profile.show') }}" class="vendor-link-btn">Lead Settings</a>
                </div>
            </section>

            <section class="vendor-panel">
                <div class="vendor-panel-head"><h2 class="h2 mb-0">Credits</h2></div>
                <div class="vendor-panel-body text-center">
                    <div class="vendor-metric-circle">{{ $vendorWalletBalance ?? ($walletBalance ?? 0) }}</div>
                    <p class="fs-3 mb-4">Credits Left</p>
                    <a href="{{ route('credits') }}" class="vendor-cta">Buy More Credits</a>
                </div>
            </section>

            <section class="vendor-panel">
                <div class="vendor-panel-head"><h2 class="h2 mb-0">Special Pages</h2></div>
                <div class="vendor-panel-body text-center">
                    <p class="mb-3"><a href="{{ route('discovery-call.index') }}" class="fs-3">GASQ Seminars</a></p>
                    <p class="mb-0"><a href="{{ route('faq') }}" class="fs-3">Documents</a></p>
                </div>
            </section>

            <section class="vendor-panel">
                <div class="vendor-panel-head"><h2 class="h2 mb-0">Responses</h2></div>
                <div class="vendor-panel-body">
                    <p class="fs-4 mb-4">
                        @if(($vendorResponseCount ?? 0) > 0)
                            You have {{ $vendorResponseCount }} recorded responses right now
                        @else
                            You do not have any responses right now
                        @endif
                    </p>
                    <a href="{{ route('vendor-leads.index', ['view' => 'responses']) }}" class="vendor-cta">View All</a>
                </div>
            </section>

            <section class="vendor-panel vendor-panel-wide">
                <div class="vendor-panel-head"><h2 class="h2 mb-0">Instant Estimator</h2></div>
                <div class="vendor-panel-body">
                    <p class="text-uppercase text-gasq-muted fw-semibold mb-3">Key Features</p>
                    <ul class="fs-4 mb-4">
                        <li>Access to the Instant Estimator for service providers</li>
                        <li>Simple data input fields that generate quick results</li>
                        <li>Send calculations to the preparer and additional contact</li>
                        <li>Accurate guard requirements for different security scenarios</li>
                        <li>Insights you need to make informed decisions</li>
                    </ul>
                    <a href="{{ route('instant-estimator.index') }}" class="vendor-cta">Open Instant Estimator</a>
                </div>
            </section>
        </div>
    </div>
</div>
@endsection

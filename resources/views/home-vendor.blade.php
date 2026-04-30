@extends('layouts.app')
@section('title', 'Vendor Dashboard')
@section('header_variant', 'dashboard')

@push('styles')
<style>
    .vendor-dashboard-wrap { background: #f6f7fb; min-height: calc(100vh - 72px); }
    .vendor-dashboard-grid { display: grid; gap: 1rem; grid-template-columns: repeat(3, minmax(0, 1fr)); }
    .vendor-panel { background: #fff; border: 1px solid #d8dde8; border-radius: 1rem; overflow: hidden; }
    .vendor-panel-head { padding: 1.1rem 1.25rem; border-bottom: 1px solid #e5e9f2; }
    .vendor-panel-body { padding: 1.1rem 1.25rem 1.25rem; }
    .vendor-metric-circle {
        width: 150px; height: 150px; margin: 0 auto .85rem; border-radius: 999px;
        background: #c9c9cf; color: #fff; display: grid; place-items: center;
        font-size: 2.5rem; font-weight: 800; letter-spacing: -0.04em;
    }
    .vendor-progress { height: 1rem; background: #edf1f7; border-radius: 999px; overflow: hidden; }
    .vendor-progress > span { display: block; height: 100%; background: linear-gradient(90deg, #1f6fff, #4f8fff); }
    .vendor-meta-line { color: #667089; font-size: .98rem; line-height: 1.45; }
    .vendor-kpi-row {
        display: flex;
        flex-wrap: wrap;
        gap: .6rem;
        margin: 0 0 1rem;
    }
    .vendor-kpi-pill {
        display: inline-flex;
        align-items: center;
        gap: .35rem;
        padding: .45rem .7rem;
        border-radius: 999px;
        background: #f4f7fd;
        border: 1px solid #e1e7f2;
        color: #24304a;
        font-size: .92rem;
        font-weight: 600;
    }
    .vendor-cta {
        display: inline-flex; align-items: center; justify-content: center; min-width: 10rem;
        padding: .75rem 1.25rem; border-radius: 999px; background: #ff825f; color: #fff;
        text-decoration: none; font-weight: 700;
    }
    .vendor-cta:hover { color: #fff; background: #f36e47; }
    .vendor-link-btn {
        display: inline-flex; align-items: center; justify-content: center; min-width: 9rem;
        padding: .6rem 1.05rem; border-radius: 999px; border: 1px solid #2f71ff; color: #2f71ff;
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
        <h1 class="display-6 fw-semibold mb-2">Welcome, {{ auth()->user()->name }}</h1>

        <div class="vendor-panel mb-3">
            <div class="vendor-panel-body">
                <p class="mb-0 fs-5">Welcome to your dashboard!</p>
            </div>
        </div>

        <div class="vendor-dashboard-grid">
            <section class="vendor-panel">
                <div class="vendor-panel-head"><h2 class="h2 mb-0">Complete your profile</h2></div>
                <div class="vendor-panel-body">
                    <p class="fs-5 mb-2">Your profile is completed {{ $vendorProfileCompletion ?? 0 }}%</p>
                    <div class="vendor-progress mb-3"><span style="width: {{ min(100, max(0, (int) ($vendorProfileCompletion ?? 0))) }}%"></span></div>
                    <p class="fs-5 mb-2">{{ $vendorProfileMessage ?? 'There are higher chances to get leads if you complete your profile.' }}</p>
                    <p class="vendor-meta-line mb-3">
                        @if(($vendorProfileMissingCount ?? 0) > 0)
                            {{ $vendorProfileMissingCount }} profile item{{ ($vendorProfileMissingCount ?? 0) === 1 ? '' : 's' }} still missing
                        @else
                            Profile quality looks strong for vendor matching.
                        @endif
                    </p>
                    <a href="{{ route('profile.show') }}" class="vendor-cta">{{ $vendorProfileCta ?? 'Complete profile' }}</a>
                </div>
            </section>

            <section class="vendor-panel">
                <div class="vendor-panel-head"><h2 class="h2 mb-0">Leads</h2></div>
                <div class="vendor-panel-body text-center">
                    <div class="vendor-metric-circle">{{ $vendorLeadCount ?? 0 }}</div>
                    <p class="fs-4 mb-1">Leads</p>
                    <p class="fs-5 text-gasq-muted mb-3">{{ $vendorUnreadLeadCount ?? 0 }} unread leads</p>
                    <div class="vendor-kpi-row justify-content-center">
                        <span class="vendor-kpi-pill"><i class="fa fa-bolt"></i>{{ $vendorActiveLeadCount ?? 0 }} active</span>
                        <span class="vendor-kpi-pill"><i class="fa fa-envelope-open"></i>{{ $vendorUnreadLeadCount ?? 0 }} unread</span>
                    </div>
                    <p class="vendor-meta-line mb-3">{{ $vendorLeadMessage ?? 'Your invited leads will appear here as soon as GASQ routes matching projects.' }}</p>
                    <a href="{{ route('vendor-leads.index') }}" class="vendor-link-btn">View My Leads</a>
                </div>
            </section>

            <section class="vendor-panel">
                <div class="vendor-panel-head"><h2 class="h2 mb-0">Help</h2></div>
                <div class="vendor-panel-body">
                    <p class="fs-5 mb-0">Here you will find the help section</p>
                </div>
            </section>

            <section class="vendor-panel">
                <div class="vendor-panel-head"><h2 class="h2 mb-0">Lead settings</h2></div>
                <div class="vendor-panel-body">
                    <p class="fs-5 mb-3">There are no lead settings right now</p>
                    <a href="{{ route('profile.show') }}" class="vendor-link-btn">Lead Settings</a>
                </div>
            </section>

            <section class="vendor-panel">
                <div class="vendor-panel-head"><h2 class="h2 mb-0">Credits</h2></div>
                <div class="vendor-panel-body text-center">
                    <div class="vendor-metric-circle">{{ $vendorWalletBalance ?? ($walletBalance ?? 0) }}</div>
                    <p class="fs-4 mb-3">Credits Left</p>
                    <a href="{{ route('credits') }}" class="vendor-cta">Buy More Credits</a>
                </div>
            </section>

            <section class="vendor-panel">
                <div class="vendor-panel-head"><h2 class="h2 mb-0">Special Pages</h2></div>
                <div class="vendor-panel-body text-center">
                    <p class="mb-2"><a href="{{ route('discovery-call.index') }}" class="fs-4">GASQ Seminars</a></p>
                    <p class="mb-0"><a href="{{ route('faq') }}" class="fs-4">Documents</a></p>
                </div>
            </section>

            <section class="vendor-panel">
                <div class="vendor-panel-head"><h2 class="h2 mb-0">Responses</h2></div>
                <div class="vendor-panel-body">
                    <p class="fs-5 mb-3">
                        @if(($vendorResponseCount ?? 0) > 0)
                            You have {{ $vendorResponseCount }} recorded responses right now
                        @else
                            You do not have any responses right now
                        @endif
                    </p>
                    <div class="vendor-kpi-row">
                        <span class="vendor-kpi-pill"><i class="fa fa-circle-check text-success"></i>{{ $vendorAcceptedResponseCount ?? 0 }} accepted</span>
                        <span class="vendor-kpi-pill"><i class="fa fa-file-signature text-primary"></i>{{ $vendorBidSubmittedCount ?? 0 }} bids</span>
                        <span class="vendor-kpi-pill"><i class="fa fa-circle-xmark text-muted"></i>{{ $vendorDeclinedResponseCount ?? 0 }} declined</span>
                    </div>
                    <p class="vendor-meta-line mb-3">{{ $vendorResponseMessage ?? 'No vendor responses recorded yet.' }}</p>
                    <a href="{{ route('vendor-leads.index', ['view' => 'responses']) }}" class="vendor-cta">View All</a>
                </div>
            </section>

            <section class="vendor-panel vendor-panel-wide">
                <div class="vendor-panel-head"><h2 class="h2 mb-0">Instant Estimator</h2></div>
                <div class="vendor-panel-body">
                    <p class="text-uppercase text-gasq-muted fw-semibold mb-2">Key Features</p>
                    <ul class="fs-5 mb-3">
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

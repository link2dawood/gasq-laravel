@extends('layouts.app')

@section('title', 'Open Bid Offer')

@section('content')
<div class="py-5" style="background: linear-gradient(135deg, rgba(13,110,253,0.10) 0%, rgba(255,255,255,1) 60%);">
    <div class="container">
        <div class="text-center mb-5">
            <div class="d-inline-block px-4 py-2 rounded-pill bg-secondary text-white mb-4">
                UI Preview
            </div>
            <h1 class="display-4 fw-bold mb-3">A Smarter Way to Secure Security Services</h1>
            <p class="lead text-gasq-muted mx-auto" style="max-width: 900px;">
                Open Bid Offer Process eliminates endless back-and-forth negotiations and helps buyers and vendors
                align on fairness, transparency, and true market value.
            </p>

            <div class="d-flex justify-content-center gap-3 flex-wrap mt-4">
                <a class="btn btn-primary btn-lg" href="{{ route('jobs.create') }}">Post Your Job Today</a>
                <a class="btn btn-outline-primary btn-lg" href="{{ url('/main-menu-calculator') }}">Try Our Calculator</a>
            </div>
        </div>
    </div>
</div>

<div class="container py-5">
    <div class="text-center mb-5">
        <h2 class="fw-bold mb-3">How It Works</h2>
        <p class="text-gasq-muted mb-0">Five steps to better security procurement</p>
    </div>

    <div class="row g-4 mb-5">
        <div class="col-md-6 col-lg-4">
            <x-card title="1) Buyer Posts Job" subtitle="Scope, budget, hours, and requirements">
                <p class="mb-0 text-gasq-muted">
                    Enter your scope and receive a True Cost Appraisal Report to make sure the numbers are accurate.
                </p>
            </x-card>
        </div>
        <div class="col-md-6 col-lg-4">
            <x-card title="2) Vendors Respond" subtitle="Accept, Decline, or Counter Offer">
                <div class="mb-2 text-gasq-muted">Responses:</div>
                <ul class="mb-0 ps-3 text-gasq-muted">
                    <li>Accept: agree to your terms</li>
                    <li>Decline: opt out (tracked for budget realism)</li>
                    <li>Counter: propose higher rate with justification</li>
                </ul>
            </x-card>
        </div>
        <div class="col-md-6 col-lg-4">
            <x-card title="3) Mandatory Interviews" subtitle="Value, professionalism, and fit">
                <p class="mb-0 text-gasq-muted">
                    Vendors who accept or counter must be interviewed so you choose based on value—not just price.
                </p>
            </x-card>
        </div>
        <div class="col-md-6 col-lg-4">
            <x-card title="4) Mandatory Risk Assessment" subtitle="Validate threats and proper staffing">
                <p class="mb-0 text-gasq-muted">
                    Before award, selected vendor completes a risk assessment to protect your site or event.
                </p>
            </x-card>
        </div>
        <div class="col-md-6 col-lg-4">
            <x-card title="5) Contract Award & Vendor Replacement" subtitle="Confidence + backup plan">
                <p class="mb-0 text-gasq-muted">
                    Award once risk assessment is complete. Non-selected vendors remain on the replacement list.
                </p>
            </x-card>
        </div>
    </div>

    <div class="row g-4 mb-5">
        <div class="col-lg-6">
            <x-card title="Why this is different" subtitle="Fair & transparent by design">
                <ul class="mb-0 ps-3 text-gasq-muted">
                    <li>Fair & Transparent: no hidden games</li>
                    <li>Value over low price</li>
                    <li>Guaranteed interviews for accepting/countering vendors</li>
                    <li>Risk-proof contracts with required assessment</li>
                    <li>Built-in backup: vendor replacement guarantee list</li>
                </ul>
            </x-card>
        </div>
        <div class="col-lg-6">
            <x-card title="Safeguards" subtitle="Decision support">
                <ul class="mb-0 ps-3 text-gasq-muted">
                    <li>Price Lock Guarantee</li>
                    <li>Vendor Replacement Guarantee</li>
                    <li>ROI Appraisal Report</li>
                </ul>
            </x-card>
        </div>
    </div>

    <div class="text-center">
        <h2 class="fw-bold mb-3">Ready to Experience Procurement 2.0?</h2>
        <p class="text-gasq-muted mx-auto mb-4" style="max-width: 850px;">
            Every security contract begins with clarity, fairness, and confidence.
        </p>
        <div class="d-flex justify-content-center gap-3 flex-wrap">
            <a class="btn btn-primary" href="{{ route('jobs.create') }}">Post Your Job Today</a>
            <a class="btn btn-outline-primary" href="{{ route('faq') }}">Learn How GASQ Protects Buyers & Vendors</a>
        </div>
    </div>
</div>
@endsection


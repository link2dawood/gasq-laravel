@extends('layouts.app')

@section('header_variant', 'dashboard')

@section('title', 'Security Calculator')

@section('content')
<div class="container py-5">
    <div class="text-center mb-5">
        <div class="d-inline-block px-4 py-2 rounded-pill bg-secondary text-white mb-4">
            Calculator hub
        </div>
        <h1 class="display-4 fw-bold mb-3">Security Calculators</h1>
        <p class="lead text-gasq-muted mx-auto" style="max-width: 900px;">
            Pick a tool below (sign in may be required). Some calculators are currently hidden while we finalize updates.
        </p>
    </div>

    <div class="row g-4">
        <div class="col-md-6 col-lg-4">
            <x-card title="Master inputs" subtitle="Shared settings">
                <p class="text-gasq-muted small">Edit the shared drivers used across all calculators.</p>
                <a class="btn btn-primary" href="{{ route('master-inputs.index') }}">Open</a>
            </x-card>
        </div>
        <div class="col-md-6 col-lg-4">
            <x-card title="Main menu" subtitle="Dashboard of calculators">
                <p class="text-gasq-muted small">Central entry with V24-backed flows.</p>
                <a class="btn btn-primary" href="{{ url('/main-menu-calculator') }}">Open</a>
            </x-card>
        </div>
        <div class="col-md-6 col-lg-4">
            <x-card title="Security billing" subtitle="Signed-in">
                <p class="text-gasq-muted small">Billing scenarios and reports.</p>
                <a class="btn btn-primary" href="{{ url('/security-billing') }}">Open</a>
            </x-card>
        </div>
        <div class="col-md-6 col-lg-4">
            <x-card title="Mobile patrol (per hit)" subtitle="Hit / stop cost model">
                <p class="text-gasq-muted small">Estimate cost per hit and billable per hit.</p>
                <a class="btn btn-primary" href="{{ url('/mobile-patrol-hit-calculator') }}">Open</a>
            </x-card>
        </div>
        <div class="col-md-6 col-lg-4">
            <x-card title="Mobile patrol" subtitle="Calculator + analysis">
                <p class="text-gasq-muted small">Mobile patrol billable rate calculator and analysis tools.</p>
                <a class="btn btn-primary" href="{{ url('/mobile-patrol-calculator') }}">Open</a>
            </x-card>
        </div>
        <div class="col-md-6 col-lg-4">
            <x-card title="Economic justification" subtitle="ROI analysis">
                <p class="text-gasq-muted small">Compare in-house vs vendor cost and savings.</p>
                <a class="btn btn-primary" href="{{ url('/economic-justification') }}">Open</a>
            </x-card>
        </div>
        <div class="col-md-6 col-lg-4">
            <x-card title="Government contract" subtitle="Rate build-up">
                <p class="text-gasq-muted small">Estimate government contract bill rates from burdens and fee.</p>
                <a class="btn btn-primary" href="{{ url('/government-contract-calculator') }}">Open</a>
            </x-card>
        </div>
        <div class="col-md-6 col-lg-4">
            <x-card title="GASQ TCO" subtitle="Vendor vs should-cost">
                <p class="text-gasq-muted small">Compare vendor TCO benchmark vs GASQ should-cost bill rate.</p>
                <a class="btn btn-primary" href="{{ url('/gasq-tco-calculator') }}">Open</a>
            </x-card>
        </div>
    </div>
</div>
@endsection

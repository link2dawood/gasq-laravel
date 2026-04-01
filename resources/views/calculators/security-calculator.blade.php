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
            The <code>/calculator</code> path now opens this native Laravel hub instead of the React shell. Pick a tool
            below (sign in may be required).
        </p>
    </div>

    <div class="row g-4">
        <div class="col-md-6 col-lg-4">
            <x-card title="Main menu" subtitle="Dashboard of calculators">
                <p class="text-gasq-muted small">Central entry with V24-backed flows.</p>
                <a class="btn btn-primary" href="{{ url('/main-menu-calculator') }}">Open</a>
            </x-card>
        </div>
        <div class="col-md-6 col-lg-4">
            <x-card title="Instant estimator" subtitle="Public quick estimate">
                <p class="text-gasq-muted small">Location-aware wage and rate snapshot.</p>
                <a class="btn btn-primary" href="{{ url('/gasq-instant-estimator') }}">Open</a>
            </x-card>
        </div>
        <div class="col-md-6 col-lg-4">
            <x-card title="Contract analysis" subtitle="Signed-in">
                <p class="text-gasq-muted small">Multi-tab contract breakdown.</p>
                <a class="btn btn-primary" href="{{ url('/contract-analysis') }}">Open</a>
            </x-card>
        </div>
        <div class="col-md-6 col-lg-4">
            <x-card title="Security billing" subtitle="Signed-in">
                <p class="text-gasq-muted small">Billing scenarios and reports.</p>
                <a class="btn btn-primary" href="{{ url('/security-billing') }}">Open</a>
            </x-card>
        </div>
        <div class="col-md-6 col-lg-4">
            <x-card title="TCO (preview)" subtitle="Static Blade page">
                <p class="text-gasq-muted small">Illustrative total cost of ownership layout.</p>
                <a class="btn btn-outline-primary" href="{{ url('/gasq-tco-calculator') }}">Open</a>
            </x-card>
        </div>
        <div class="col-md-6 col-lg-4">
            <x-card title="Pricing" subtitle="Global security pricing">
                <p class="text-gasq-muted small">Posts, bill rate, and scenario comparison.</p>
                <a class="btn btn-primary" href="{{ url('/global-security-pricing') }}">Open</a>
            </x-card>
        </div>
        <div class="col-md-6 col-lg-4">
            <x-card title="Mobile patrol (per hit)" subtitle="Hit / stop cost model">
                <p class="text-gasq-muted small">Estimate cost per hit and billable per hit.</p>
                <a class="btn btn-primary" href="{{ url('/mobile-patrol-hit-calculator') }}">Open</a>
            </x-card>
        </div>
    </div>
</div>
@endsection

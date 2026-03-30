@extends('layouts.app')

@section('header_variant', 'dashboard')

@section('title', 'Security Quote')

@section('content')
<div class="container py-5">
    <div class="text-center mb-5">
        <h1 class="display-5 fw-bold mb-3">Security Quote</h1>
        <p class="lead text-gasq-muted mx-auto" style="max-width: 900px;">
            UI preview of the React route <code>/security-quote</code>. No quote calculations are performed here.
        </p>
        <div class="d-flex justify-content-center gap-3 flex-wrap mt-4">
            <a class="btn btn-primary btn-lg" href="{{ url('/main-menu-calculator?tab=security') }}">Open Security Cost Calculator</a>
            <a class="btn btn-outline-primary btn-lg" href="{{ url('/post-job') }}">Post Job Offer</a>
        </div>
    </div>

    <div class="row g-4">
        <div class="col-lg-7">
            <x-card title="Example Quote Summary" subtitle="Static preview">
                <div class="row g-3">
                    <div class="col-md-6">
                        <div class="text-gasq-muted small mb-1">Quote Type</div>
                        <div class="fw-semibold">Unarmed + Access Control (Example)</div>
                    </div>
                    <div class="col-md-6">
                        <div class="text-gasq-muted small mb-1">Coverage Window</div>
                        <div class="fw-semibold">8 hrs/day, Mon - Fri</div>
                    </div>
                    <div class="col-md-6">
                        <div class="text-gasq-muted small mb-1">Guards</div>
                        <div class="fw-semibold">2</div>
                    </div>
                    <div class="col-md-6">
                        <div class="text-gasq-muted small mb-1">Estimated Weekly Total</div>
                        <div class="fw-semibold">$6,120.00</div>
                    </div>
                </div>
            </x-card>
        </div>
        <div class="col-lg-5">
            <x-card title="What happens next?" subtitle="Functional later">
                <ul class="mb-0 ps-3 text-gasq-muted">
                    <li>Run the functional calculator in `Main Menu` → `Security Cost`.</li>
                    <li>Use the generated numbers to support your bid/award process.</li>
                    <li>Finalize the report (PDF/email) when wired to backend logic.</li>
                </ul>
            </x-card>
        </div>
    </div>
</div>
@endsection


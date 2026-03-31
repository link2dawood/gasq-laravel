@extends('layouts.app')

@section('header_variant', 'dashboard')

@section('title', 'Vendor Registration (UI)')

@section('content')
<div class="container py-5">
    <div class="text-center mb-5">
        <div class="d-inline-block px-4 py-2 rounded-pill bg-secondary text-white mb-4">
            UI Preview Only
        </div>
        <h1 class="display-4 fw-bold mb-3">Vendor Registration</h1>
        <p class="lead text-gasq-muted mx-auto" style="max-width: 900px;">
            Static preview of vendor onboarding (no submission, no API calls).
        </p>
        <div class="d-flex justify-content-center gap-3 flex-wrap mt-4">
            <a class="btn btn-primary btn-lg" href="{{ url('/vendor-form') }}">Open Vendor Form UI</a>
            <a class="btn btn-outline-primary btn-lg" href="{{ route('register') }}">Go to Standard Registration</a>
        </div>
    </div>

    <div class="row g-4">
        <div class="col-lg-8">
            <x-card title="UI Preview Sections" subtitle="Disabled placeholders">
                <div class="row g-3">
                    <div class="col-md-6">
                        <div class="p-4 rounded border h-100">
                            <div class="fw-bold mb-1">Vendor Prequalification</div>
                            <div class="text-gasq-muted small">No inputs in this preview.</div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="p-4 rounded border h-100">
                            <div class="fw-bold mb-1">SB 68 Prequalification</div>
                            <div class="text-gasq-muted small">Static UI only.</div>
                        </div>
                    </div>
                    <div class="col-md-12">
                        <div class="p-4 rounded border h-100">
                            <div class="fw-bold mb-1">Capability Statement</div>
                            <div class="text-gasq-muted small">
                                Placeholder area for vendor capability statement details (disabled in preview).
                            </div>
                        </div>
                    </div>
                </div>
            </x-card>
        </div>

        <div class="col-lg-4">
            <x-card title="Next" subtitle="UI-only">
                <p class="text-gasq-muted mb-0">
                    Once you want it functional, we can wire these steps to Laravel controllers, validation, and
                    storage.
                </p>
            </x-card>
        </div>
    </div>
</div>
@endsection


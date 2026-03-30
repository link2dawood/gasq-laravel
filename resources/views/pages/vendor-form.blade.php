@extends('layouts.app')

@section('title', 'Vendor Form')

@section('content')
<div class="container py-5">
    <div class="text-center mb-5">
        <div class="d-inline-block px-4 py-2 rounded-pill bg-secondary text-white mb-4">
            UI Preview Only
        </div>
        <h1 class="display-4 fw-bold mb-3">Vendor Registration</h1>
        <p class="lead text-gasq-muted mx-auto" style="max-width: 900px;">
            This page is UI-only (no submission). For real vendor onboarding, use the standard registration flow.
        </p>

        <div class="d-flex justify-content-center gap-3 flex-wrap mt-4">
            <a class="btn btn-primary btn-lg" href="{{ url('/register/vendor') }}">Go to Vendor Registration</a>
            <a class="btn btn-outline-primary btn-lg" href="{{ url('/') }}">Back to Home</a>
        </div>
    </div>

    <div class="row g-4">
        <div class="col-lg-7">
            <x-card title="Company Information" subtitle="Static placeholders (disabled)">
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label">Legal Business Name</label>
                        <input disabled class="form-control" placeholder="e.g. SecureGuard Solutions" />
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Business Structure</label>
                        <select disabled class="form-select">
                            <option>Select business structure</option>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Phone Number</label>
                        <input disabled class="form-control" placeholder="+1 555-0100" />
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Email Address</label>
                        <input disabled class="form-control" placeholder="your@email.com" />
                    </div>
                    <div class="col-md-12">
                        <label class="form-label">Business Address</label>
                        <input disabled class="form-control" placeholder="123 Security Ave" />
                    </div>
                </div>
            </x-card>
        </div>

        <div class="col-lg-5">
            <x-card title="Core Competencies" subtitle="Disabled selection chips">
                <div class="d-flex flex-wrap gap-2">
                    <span class="badge text-bg-primary">Guards</span>
                    <span class="badge text-bg-primary">Mobile Patrol</span>
                    <span class="badge text-bg-primary">Event Security</span>
                    <span class="badge text-bg-primary">Consulting</span>
                </div>
                <hr class="my-4">
                <p class="text-gasq-muted mb-0">
                    In the live flow, this section is a multi-step questionnaire and capability statement.
                </p>
            </x-card>
        </div>
    </div>
</div>
@endsection


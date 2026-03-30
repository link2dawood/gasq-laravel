@extends('layouts.app')

@section('title', 'Buyer Registration (UI)')

@section('content')
<div class="container py-5">
    <div class="text-center mb-5">
        <div class="d-inline-block px-4 py-2 rounded-pill bg-secondary text-white mb-4">
            UI Preview Only
        </div>
        <h1 class="display-4 fw-bold mb-3">Buyer Registration & Assessment</h1>
        <p class="lead text-gasq-muted mx-auto" style="max-width: 900px;">
            Static preview of the buyer onboarding flow (no submission, no API calls).
        </p>
        <div class="d-flex justify-content-center gap-3 flex-wrap mt-4">
            <a class="btn btn-primary btn-lg" href="{{ route('register') }}">Go to Standard Registration</a>
            <a class="btn btn-outline-primary btn-lg" href="{{ url('/calculator') }}">View Calculator UI</a>
        </div>
    </div>

    <div class="row g-4">
        <div class="col-lg-8">
            <x-card title="Steps (UI only)" subtitle="Mirrors the step-by-step tab structure">
                <div class="list-group">
                    <div class="list-group-item">
                        <strong>1) Client Registration</strong>
                        <div class="text-gasq-muted small mt-1">Disabled fields in preview.</div>
                    </div>
                    <div class="list-group-item">
                        <strong>2) Buyer Qualification</strong>
                        <div class="text-gasq-muted small mt-1">No questionnaire inputs in this preview.</div>
                    </div>
                    <div class="list-group-item">
                        <strong>3) Property Risk Assessment</strong>
                        <div class="text-gasq-muted small mt-1">Static preview only.</div>
                    </div>
                    <div class="list-group-item">
                        <strong>4) Post Job Offer</strong>
                        <div class="text-gasq-muted small mt-1">Post flow is handled by Laravel job creation.</div>
                    </div>
                </div>
            </x-card>
        </div>

        <div class="col-lg-4">
            <x-card title="Why this page exists" subtitle="UI-only">
                <p class="text-gasq-muted mb-0">
                    Use this page to show the onboarding steps visually. Enable the full flow later by wiring to Laravel
                    controllers and database tables.
                </p>
            </x-card>
        </div>
    </div>
</div>
@endsection


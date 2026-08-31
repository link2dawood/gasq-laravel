@extends('layouts.app')

@section('title', 'Verified Shared Resource Rate')
@section('header_variant', 'dashboard')

@section('content')
<div class="container py-4 py-lg-5" style="max-width: 60rem;">

    <div class="mb-4">
        <h1 class="h2 fw-bold mb-2">Verified Shared Resource Rate&trade;</h1>
        <p class="text-gasq-muted mb-0" style="max-width: 46rem;">
            Shared Resource pricing is an operating model, not a discount. It is available only to
            vendors with enough existing volume to legitimately spread qualifying resources across
            multiple accounts &mdash; and only once that structure has been validated.
        </p>
    </div>

    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif
    @if(session('error'))
        <div class="alert alert-danger">{{ session('error') }}</div>
    @endif

    {{-- CURRENT STANDING --}}
    @php
        $badgeClass = match($status) {
            'approved' => 'bg-success',
            'eligible_for_review' => 'bg-primary',
            'financial_review_pending' => 'bg-info text-dark',
            'not_eligible', 'rejected' => 'bg-danger',
            'expired' => 'bg-warning text-dark',
            default => 'bg-secondary',
        };
    @endphp
    <div class="card gasq-card mb-4">
        <div class="card-body">
            <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
                <h2 class="h5 fw-bold mb-0">Your status</h2>
                <span class="badge {{ $badgeClass }} fs-6">{{ strtoupper($statusLabels[$status] ?? 'Unknown') }}</span>
            </div>

            <div class="row g-3">
                <div class="col-sm-4">
                    <div class="small text-uppercase text-gasq-muted fw-semibold">Minimum required</div>
                    <div class="h4 fw-bold mb-0">{{ number_format($minimumHours) }}</div>
                    <div class="small text-gasq-muted">weekly billable hours</div>
                </div>
                <div class="col-sm-4">
                    <div class="small text-uppercase text-gasq-muted fw-semibold">Your submitted hours</div>
                    <div class="h4 fw-bold mb-0">
                        {{ $volume ? number_format((float) $volume->weekly_billable_hours) : '—' }}
                    </div>
                    <div class="small text-gasq-muted">
                        {{ $volume?->verified_at ? 'verified ' . $volume->verified_at->format('j M Y') : 'not yet verified' }}
                    </div>
                </div>
                <div class="col-sm-4">
                    <div class="small text-uppercase text-gasq-muted fw-semibold">Verification expires</div>
                    <div class="h4 fw-bold mb-0">
                        {{ $volume?->expires_at ? $volume->expires_at->format('j M Y') : '—' }}
                    </div>
                </div>
            </div>

            @if($eligibleForReview)
                <div class="alert alert-info small mt-3 mb-0">
                    <strong>You have cleared the hours gate.</strong> That makes you eligible to
                    <em>apply</em> for Shared Resource pricing. It does not approve a rate &mdash; your
                    full line-item bill-rate breakdown must still reconcile and be approved by GASQ.
                </div>
            @endif
        </div>
    </div>

    {{-- WHAT IS BLOCKING --}}
    @if($blockingReasons !== [])
        <div class="card gasq-card border-warning mb-4">
            <div class="card-body">
                <h2 class="h5 fw-bold mb-3">Outstanding before you can submit Shared Resource pricing</h2>
                <ul class="mb-0 small">
                    @foreach($blockingReasons as $reason)
                        <li class="mb-1">{{ $reason }}</li>
                    @endforeach
                </ul>
            </div>
        </div>
    @endif

    {{-- STEP 1 — OPERATING VOLUME --}}
    <div class="card gasq-card mb-4">
        <div class="card-body">
            <h2 class="h5 fw-bold mb-1">Step 1 &mdash; Verify your operating scale</h2>
            <p class="text-gasq-muted small">
                Hours must be current, active, billable, verifiable security-service hours.
                GASQ verifies them &mdash; they cannot be self-certified.
            </p>

            <form action="{{ route('shared-resource.volume.store') }}" method="POST">
                @csrf
                <div class="row g-3">
                    <div class="col-sm-4">
                        <label class="form-label small fw-semibold">Active weekly billable hours</label>
                        <input type="number" step="0.01" min="0" name="weekly_billable_hours"
                               class="form-control @error('weekly_billable_hours') is-invalid @enderror"
                               value="{{ old('weekly_billable_hours', $volume?->weekly_billable_hours) }}" required>
                        @error('weekly_billable_hours')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-sm-4">
                        <label class="form-label small fw-semibold">Active accounts</label>
                        <input type="number" min="0" name="account_count" class="form-control"
                               value="{{ old('account_count', $volume?->account_count) }}">
                    </div>
                </div>
                <div class="mt-3">
                    <label class="form-label small fw-semibold">Supporting evidence</label>
                    <textarea name="evidence_notes" rows="3" class="form-control"
                              placeholder="Client roster, billing reports, scheduling reports, payroll summaries. Confidential details may be redacted so long as GASQ can still verify active billable volume.">{{ old('evidence_notes', $volume?->evidence_notes) }}</textarea>
                </div>
                <button type="submit" class="btn btn-primary mt-3">Submit for verification</button>
            </form>
        </div>
    </div>

    {{-- STEP 2 — BILL-RATE BREAKDOWN --}}
    <div class="card gasq-card">
        <div class="card-body">
            <h2 class="h5 fw-bold mb-1">Step 2 &mdash; Submit your line-item bill-rate breakdown</h2>
            <p class="text-gasq-muted small">
                A single lump-sum hourly rate is not sufficient. Every component must be declared,
                each shared line must show how it was allocated, and the total must reconcile to
                your proposed rate.
            </p>

            @if($breakdown)
                <div class="row g-3 mb-3">
                    <div class="col-sm-4">
                        <div class="small text-uppercase text-gasq-muted fw-semibold">Proposed rate</div>
                        <div class="h5 fw-bold mb-0">${{ number_format((float) $breakdown->proposed_bill_rate, 2) }}/hr</div>
                    </div>
                    <div class="col-sm-4">
                        <div class="small text-uppercase text-gasq-muted fw-semibold">Line items total</div>
                        <div class="h5 fw-bold mb-0">${{ number_format((float) $breakdown->line_items_total, 2) }}</div>
                    </div>
                    <div class="col-sm-4">
                        <div class="small text-uppercase text-gasq-muted fw-semibold">Reconciles</div>
                        <div class="h5 fw-bold mb-0">
                            @if($breakdown->reconciles)
                                <span class="text-success"><i class="fa fa-circle-check me-1"></i>Yes</span>
                            @else
                                <span class="text-danger"><i class="fa fa-circle-xmark me-1"></i>No</span>
                            @endif
                        </div>
                    </div>
                </div>
            @endif

            @if($eligibleForReview)
                <a href="{{ route('shared-resource.breakdown.edit') }}" class="btn btn-primary">
                    {{ $breakdown ? 'Revise breakdown' : 'Build breakdown' }}
                </a>
            @else
                <button class="btn btn-primary" disabled>Build breakdown</button>
                <div class="form-text">Available once your operating volume is verified at or above the minimum.</div>
            @endif
        </div>
    </div>
</div>
@endsection

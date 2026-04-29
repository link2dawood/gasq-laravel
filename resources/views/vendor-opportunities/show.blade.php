@extends('layouts.app')

@section('title', 'Vendor Opportunity')

@section('content')
@php
    $status = $invitation->status;
    $responseTarget = $opportunity->max_accepts;
    $progressPct = $responseTarget > 0 ? min(100, ($respondedCount / $responseTarget) * 100) : 0;
    $acceptedEverCount = $acceptedCount;
    $jobTitle = $job->title ?: 'Security Opportunity';
    $showUnlockedDetails = $buyerDetailsUnlocked;
@endphp

<div class="container py-4">
    <div class="d-flex flex-wrap justify-content-between align-items-start gap-3 mb-4">
        <div>
            <p class="text-uppercase text-gasq-muted small fw-semibold mb-1">GASQ Vendor Opportunity</p>
            <h1 class="gasq-page-title mb-2">{{ $jobTitle }}</h1>
            <p class="text-gasq-muted mb-0">{{ $job->location ?: 'Location not provided' }}</p>
        </div>
        <div class="text-md-end">
            <span class="badge bg-primary-subtle text-primary-emphasis border">{{ strtoupper($opportunity->lead_tier) }}-Tier Lead</span>
            <div class="small text-gasq-muted mt-2">Wallet balance: {{ number_format($walletBalance) }} credits</div>
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif
    @if(session('error'))
        <div class="alert alert-danger">{{ session('error') }}</div>
    @endif
    @if($errors->any())
        <div class="alert alert-danger">
            {{ $errors->first() }}
        </div>
    @endif

    <div class="card gasq-card mb-4">
        <div class="card-body">
            <div class="d-flex flex-column flex-lg-row justify-content-between gap-3">
                <div class="flex-grow-1">
                    <div class="d-flex flex-wrap gap-2 mb-2">
                        <span class="badge bg-light text-dark border">{{ $respondedCount }}/{{ $responseTarget }} responded</span>
                        <span class="badge bg-success-subtle text-success-emphasis">{{ $acceptedEverCount }} accepted</span>
                        <span class="badge bg-secondary-subtle text-secondary-emphasis">{{ ucfirst(str_replace('_', ' ', $status)) }}</span>
                    </div>
                    <div class="progress mb-3" style="height: 6px;">
                        <div class="progress-bar bg-success" role="progressbar" style="width: {{ $progressPct }}%"></div>
                    </div>
                    <p class="mb-0 text-gasq-muted">
                        Credits are charged only when you accept and unlock buyer details.
                        @if($invitation->accepted_at && $invitation->expires_at)
                            Your bid window closes {{ $invitation->expires_at->diffForHumans() }}.
                        @endif
                    </p>
                </div>
                <div class="d-flex flex-wrap gap-2 align-self-start">
                    <form action="{{ route('vendor-opportunities.accept', $invitation) }}" method="POST">
                        @csrf
                        <button type="submit" class="btn btn-success" @disabled($opportunity->isClosed() || $acceptedCount >= $responseTarget && ! $invitation->accepted_at)>
                            Accept Opportunity ({{ number_format($invitation->credits_to_unlock) }} credits)
                        </button>
                    </form>
                    <button class="btn btn-outline-secondary" type="button" data-bs-toggle="collapse" data-bs-target="#decline-panel" aria-expanded="{{ $errors->has('decline_reason') ? 'true' : 'false' }}">
                        Decline
                    </button>
                </div>
            </div>
            <div class="collapse mt-3 @if($errors->has('decline_reason') || $status === 'declined') show @endif" id="decline-panel">
                <div class="card card-body bg-light">
                    <form action="{{ route('vendor-opportunities.decline', $invitation) }}" method="POST">
                        @csrf
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label">Reason</label>
                                <select name="decline_reason" class="form-select" required>
                                    <option value="">Select a reason</option>
                                    <option value="outside_service_area" @selected(old('decline_reason') === 'outside_service_area')>Outside service area</option>
                                    <option value="staffing_unavailable" @selected(old('decline_reason') === 'staffing_unavailable')>Staffing unavailable</option>
                                    <option value="pricing_too_low" @selected(old('decline_reason') === 'pricing_too_low')>Pricing too low</option>
                                    <option value="not_interested" @selected(old('decline_reason') === 'not_interested')>Not interested</option>
                                    <option value="other" @selected(old('decline_reason') === 'other')>Other</option>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Other reason</label>
                                <input type="text" name="decline_reason_other" class="form-control" value="{{ old('decline_reason_other') }}">
                            </div>
                        </div>
                        <button type="submit" class="btn btn-outline-secondary mt-3">Confirm decline</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-4">
        <div class="col-lg-7">
            <div class="card gasq-card mb-4">
                <div class="card-body">
                    <h2 class="h4 fw-bold mb-3">Opportunity Snapshot</h2>
                    <div class="row g-3">
                        <div class="col-md-6">
                            <p class="mb-1"><strong>Service Type</strong></p>
                            <p class="text-gasq-muted mb-0">{{ $job->category ?: 'Not provided' }}</p>
                        </div>
                        <div class="col-md-6">
                            <p class="mb-1"><strong>Estimated Annual Contract Value</strong></p>
                            <p class="text-gasq-muted mb-0">${{ number_format((float) $opportunity->estimated_annual_contract_value, 2) }}</p>
                        </div>
                        <div class="col-md-6">
                            <p class="mb-1"><strong>Coverage</strong></p>
                            <p class="text-gasq-muted mb-0">
                                {{ (int) data_get($questionnaire, 'hours_per_day', 0) * (int) data_get($questionnaire, 'days_per_week', 0) ?: 'Not provided' }} hours per week
                            </p>
                        </div>
                        <div class="col-md-6">
                            <p class="mb-1"><strong>Staff Required</strong></p>
                            <p class="text-gasq-muted mb-0">{{ $job->guards_per_shift ?: 'Not provided' }}</p>
                        </div>
                        <div class="col-md-6">
                            <p class="mb-1"><strong>Start Timeline</strong></p>
                            <p class="text-gasq-muted mb-0">{{ data_get($questionnaire, 'service_start_timeline', 'Not provided') }}</p>
                        </div>
                        <div class="col-md-6">
                            <p class="mb-1"><strong>Primary Reason</strong></p>
                            <p class="text-gasq-muted mb-0">{{ data_get($questionnaire, 'primary_reason', $job->description ?: 'Not provided') }}</p>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card gasq-card mb-4">
                <div class="card-body">
                    <h2 class="h4 fw-bold mb-3">Buyer Contact</h2>
                    <div class="row g-3">
                        <div class="col-md-6">
                            <p class="mb-1"><strong>Buyer</strong></p>
                            <p class="text-gasq-muted mb-0">{{ $buyer->name ?: 'Not provided' }}</p>
                        </div>
                        <div class="col-md-6">
                            <p class="mb-1"><strong>Company</strong></p>
                            <p class="text-gasq-muted mb-0">{{ $buyer->company ?: 'Not provided' }}</p>
                        </div>
                        <div class="col-md-6">
                            <p class="mb-1"><strong>Email</strong></p>
                            <p class="text-gasq-muted mb-0">{{ $showUnlockedDetails ? ($fullBuyerEmail ?: 'Not provided') : $maskedBuyerEmail }}</p>
                        </div>
                        <div class="col-md-6">
                            <p class="mb-1"><strong>Phone</strong></p>
                            <p class="text-gasq-muted mb-0">{{ $showUnlockedDetails ? ($fullBuyerPhone ?: 'Not provided') : $maskedBuyerPhone }}</p>
                        </div>
                    </div>
                </div>
            </div>

            @if($showUnlockedDetails)
                <div class="card gasq-card">
                    <div class="card-body">
                        <h2 class="h4 fw-bold mb-3">Submit Bid</h2>
                        @if($acceptedPathOpen)
                            <form action="{{ route('vendor-opportunities.submit-bid', $invitation) }}" method="POST">
                                @csrf
                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <label class="form-label">Hourly bill rate</label>
                                        <input type="number" name="hourly_bill_rate" class="form-control" step="0.01" min="0" value="{{ old('hourly_bill_rate', $invitation->bid?->hourly_bill_rate) }}" required>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Weekly price</label>
                                        <input type="number" name="weekly_price" class="form-control" step="0.01" min="0" value="{{ old('weekly_price', $invitation->bid?->weekly_price) }}" required>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Monthly price</label>
                                        <input type="number" name="monthly_price" class="form-control" step="0.01" min="0" value="{{ old('monthly_price', $invitation->bid?->monthly_price) }}" required>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Annual price</label>
                                        <input type="number" name="annual_price" class="form-control" step="0.01" min="0" value="{{ old('annual_price', $invitation->bid?->annual_price) }}" required>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Start availability</label>
                                        <input type="text" name="start_availability" class="form-control" value="{{ old('start_availability', $invitation->bid?->start_availability) }}" required>
                                    </div>
                                    <div class="col-12">
                                        <label class="form-label">Staffing plan</label>
                                        <textarea name="staffing_plan" class="form-control" rows="4" required>{{ old('staffing_plan', $invitation->bid?->staffing_plan) }}</textarea>
                                    </div>
                                    <div class="col-12">
                                        <label class="form-label">Vendor notes</label>
                                        <textarea name="vendor_notes" class="form-control" rows="3">{{ old('vendor_notes', $invitation->bid?->vendor_notes) }}</textarea>
                                    </div>
                                </div>
                                <button type="submit" class="btn btn-primary mt-3">Submit Bid</button>
                            </form>
                        @else
                            <p class="text-gasq-muted mb-0">Your bid window is no longer open for this invitation.</p>
                        @endif
                    </div>
                </div>
            @endif
        </div>

        <div class="col-lg-5">
            <div class="card gasq-card mb-4">
                <div class="card-body">
                    <h2 class="h4 fw-bold mb-3">Lead Quality</h2>
                    <ul class="list-unstyled mb-0">
                        <li class="mb-2">Decision maker verified: {{ $opportunity->decision_maker_verified ? 'Yes' : 'No' }}</li>
                        <li class="mb-2">Budget confirmed: {{ $opportunity->budget_confirmed ? 'Yes' : 'No' }}</li>
                        <li class="mb-2">Scope completed: {{ $opportunity->scope_completed ? 'Yes' : 'No' }}</li>
                        <li class="mb-2">Timeline within 60 days: {{ $opportunity->timeline_ready ? 'Yes' : 'No' }}</li>
                        <li class="mb-0">Buyer willing to move forward if pricing fits: {{ $opportunity->move_forward_confirmed ? 'Yes' : 'No' }}</li>
                    </ul>
                </div>
            </div>

            @if($invitation->bid)
                <div class="card gasq-card mb-4">
                    <div class="card-body">
                        <h2 class="h4 fw-bold mb-3">Latest Bid Scoring</h2>
                        <p class="mb-1"><strong>Realism score:</strong> {{ $invitation->bid->realism_score ?? 'Pending' }}</p>
                        <p class="mb-1"><strong>Label:</strong> {{ ucfirst((string) ($invitation->bid->realism_label ?? 'pending')) }}</p>
                        <p class="mb-0"><strong>Flagged:</strong> {{ $invitation->bid->realism_flagged ? 'Yes' : 'No' }}</p>
                    </div>
                </div>
            @endif

            <div class="card gasq-card">
                <div class="card-body">
                    <h2 class="h4 fw-bold mb-3">Respond at Bottom Too</h2>
                    <div class="d-flex flex-wrap gap-2">
                        <form action="{{ route('vendor-opportunities.accept', $invitation) }}" method="POST">
                            @csrf
                            <button type="submit" class="btn btn-success" @disabled($opportunity->isClosed() || $acceptedCount >= $responseTarget && ! $invitation->accepted_at)>
                                Accept Opportunity
                            </button>
                        </form>
                        <button class="btn btn-outline-secondary" type="button" data-bs-toggle="collapse" data-bs-target="#decline-panel-bottom" aria-expanded="false">
                            Decline
                        </button>
                    </div>
                    <div class="collapse mt-3" id="decline-panel-bottom">
                        <div class="card card-body bg-light">
                            <form action="{{ route('vendor-opportunities.decline', $invitation) }}" method="POST">
                                @csrf
                                <label class="form-label">Reason</label>
                                <select name="decline_reason" class="form-select mb-3" required>
                                    <option value="">Select a reason</option>
                                    <option value="outside_service_area">Outside service area</option>
                                    <option value="staffing_unavailable">Staffing unavailable</option>
                                    <option value="pricing_too_low">Pricing too low</option>
                                    <option value="not_interested">Not interested</option>
                                    <option value="other">Other</option>
                                </select>
                                <input type="text" name="decline_reason_other" class="form-control mb-3" placeholder="Optional details">
                                <button type="submit" class="btn btn-outline-secondary">Confirm decline</button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

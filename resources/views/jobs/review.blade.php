@extends('layouts.app')

@section('title', 'Review Job Announcement')

@section('content')
@php
    $questionnaire = $questionnaire ?? [];
    $serviceTypes = $questionnaire['service_types'] ?? [];
    $shiftsNeeded = $questionnaire['shifts_needed'] ?? [];
    $documents = $questionnaire['supporting_documents'] ?? [];
@endphp

<div class="container py-4">
    <nav aria-label="breadcrumb" class="mb-3">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="{{ route('job-board') }}">Job Board</a></li>
            <li class="breadcrumb-item">Buyer Questionnaire</li>
            <li class="breadcrumb-item active">Review Announcement</li>
        </ol>
    </nav>

    @if(session('error'))
        <x-alert type="danger" dismissible>{{ session('error') }}</x-alert>
    @endif

    {{-- Renamed from "Review Your Generated Job Announcement" (review spec §1, P0-1):
         "job announcement" reads as an employment advert, not a procurement step. --}}
    <div class="d-flex flex-wrap justify-content-between align-items-start gap-3 mb-4">
        <div>
            <h1 class="h2 mb-2">Review &amp; Validate Security Service Opportunity</h1>
            <p class="text-gasq-muted mb-0" style="max-width: 46rem;">
                Review your scope, staffing requirements, wage assumptions, approved budget, pricing
                basis, and procurement requirements before releasing this opportunity to qualified
                GASQ vendors.
            </p>
        </div>
        <div class="d-flex gap-2">
            <form action="{{ route('jobs.review.edit') }}" method="POST">
                @csrf
                <button type="submit" class="btn btn-outline-secondary">Return to Edit</button>
            </form>
        </div>
    </div>

    {{-- VALIDATION GATE (review spec §2). This page is a procurement control point, so
         the release control below is disabled until every blocking check passes. --}}
    @php
        $validation = $validation ?? ['status' => 'action_required', 'checks' => [], 'blocking_count' => 0, 'warning_count' => 0];
        $isReady = ($validation['status'] ?? '') === 'ready';
    @endphp
    <div class="card gasq-card mb-4 border-{{ $isReady ? 'success' : 'warning' }}">
        <div class="card-body">
            <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
                <h2 class="h5 fw-bold mb-0">Opportunity Validation Status</h2>
                @if($isReady)
                    <span class="badge bg-success fs-6">READY FOR VENDOR RELEASE</span>
                @else
                    <span class="badge bg-warning text-dark fs-6">
                        ACTION REQUIRED &mdash; {{ $validation['blocking_count'] }}
                        {{ \Illuminate\Support\Str::plural('ITEM', $validation['blocking_count']) }} MUST BE COMPLETED
                    </span>
                @endif
            </div>

            <div class="row g-2">
                @foreach($validation['checks'] as $check)
                    <div class="col-md-6">
                        <div class="d-flex align-items-start gap-2 small">
                            @if($check['passed'])
                                <i class="fa fa-circle-check text-success mt-1"></i>
                            @elseif($check['blocking'])
                                <i class="fa fa-circle-xmark text-danger mt-1"></i>
                            @else
                                <i class="fa fa-triangle-exclamation text-warning mt-1"></i>
                            @endif
                            <div>
                                <span class="{{ $check['passed'] ? '' : 'fw-semibold' }}">{{ $check['label'] }}</span>
                                @if(! $check['passed'] && $check['message'])
                                    <div class="text-gasq-muted">{{ $check['message'] }}</div>
                                @endif
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </div>

    <x-card class="mb-4">
        <div class="small text-uppercase text-gasq-muted fw-semibold mb-2">Generated Announcement</div>
        <h2 class="h4 mb-3">{{ $preview['title'] ?? 'Security Services Request' }}</h2>
        <div class="row g-3 mb-3">
            <div class="col-md-4">
                <div class="small text-uppercase text-gasq-muted fw-semibold">Service</div>
                <div>{{ $preview['category'] ?? 'Not provided' }}</div>
            </div>
            <div class="col-md-4">
                <div class="small text-uppercase text-gasq-muted fw-semibold">Location</div>
                <div>{{ $preview['location'] ?? 'Not provided' }}</div>
            </div>
            <div class="col-md-4">
                <div class="small text-uppercase text-gasq-muted fw-semibold">Start Date</div>
                <div>{{ $preview['service_start_date'] ?? 'Not provided' }}</div>
            </div>
            <div class="col-md-4">
                <div class="small text-uppercase text-gasq-muted fw-semibold">Budget</div>
                <div>
                    @if(($preview['budget_min'] ?? null) !== null || ($preview['budget_max'] ?? null) !== null)
                        ${{ number_format((float) ($preview['budget_min'] ?? 0), 2) }} - ${{ number_format((float) ($preview['budget_max'] ?? 0), 2) }}
                    @else
                        Not provided
                    @endif
                </div>
            </div>
            <div class="col-md-4">
                <div class="small text-uppercase text-gasq-muted fw-semibold">Property Type</div>
                <div>{{ $preview['property_type'] ?? 'Not provided' }}</div>
            </div>
            <div class="col-md-4">
                <div class="small text-uppercase text-gasq-muted fw-semibold">Guards per Shift</div>
                <div>{{ $preview['guards_per_shift'] ?? 'Not provided' }}</div>
            </div>
        </div>

        @if(! empty($preview['description']))
            <div class="small text-uppercase text-gasq-muted fw-semibold mb-2">Announcement Summary</div>
            <div class="mb-0">{!! nl2br(e($preview['description'])) !!}</div>
        @endif
    </x-card>

    <div class="row g-4">
        <div class="col-lg-6">
            <x-card title="Scope Snapshot" class="h-100">
                <div class="row g-3">
                    <div class="col-sm-6">
                        <div class="small text-uppercase text-gasq-muted fw-semibold">Service Types</div>
                        <div>{{ $serviceTypes !== [] ? implode(', ', $serviceTypes) : 'Not provided' }}</div>
                    </div>
                    <div class="col-sm-6">
                        <div class="small text-uppercase text-gasq-muted fw-semibold">Request Type</div>
                        <div>{{ ucwords(str_replace('_', ' ', (string) ($questionnaire['request_type'] ?? 'Not provided'))) }}</div>
                    </div>
                    <div class="col-sm-6">
                        <div class="small text-uppercase text-gasq-muted fw-semibold">Hours per Day</div>
                        <div>{{ $questionnaire['hours_per_day'] ?? 'Not provided' }}</div>
                    </div>
                    <div class="col-sm-6">
                        <div class="small text-uppercase text-gasq-muted fw-semibold">Days per Week</div>
                        <div>{{ $questionnaire['days_per_week'] ?? 'Not provided' }}</div>
                    </div>
                    <div class="col-sm-6">
                        <div class="small text-uppercase text-gasq-muted fw-semibold">Weeks per Year</div>
                        <div>{{ $questionnaire['weeks_per_year'] ?? 'Not provided' }}</div>
                    </div>
                    <div class="col-sm-6">
                        <div class="small text-uppercase text-gasq-muted fw-semibold">Shifts Needed</div>
                        <div>{{ $shiftsNeeded !== [] ? implode(', ', $shiftsNeeded) : 'Not provided' }}</div>
                    </div>
                    <div class="col-sm-6">
                        <div class="small text-uppercase text-gasq-muted fw-semibold">Assignment Type</div>
                        <div>{{ ucwords(str_replace('_', ' ', (string) ($questionnaire['assignment_type'] ?? 'Not provided'))) }}</div>
                    </div>
                    <div class="col-sm-6">
                        <div class="small text-uppercase text-gasq-muted fw-semibold">Contract Term</div>
                        <div>{{ $questionnaire['desired_contract_term'] ?? 'Not provided' }}</div>
                    </div>
                    <div class="col-sm-6">
                        <div class="small text-uppercase text-gasq-muted fw-semibold">Armed / Unarmed</div>
                        <div>{{ ucwords((string) ($questionnaire['armed_status'] ?? 'Not provided')) }}</div>
                    </div>
                    <div class="col-sm-6">
                        <div class="small text-uppercase text-gasq-muted fw-semibold">Coverage / Deployment</div>
                        <div>{{ ! empty($questionnaire['deployment_types']) ? implode(', ', (array) $questionnaire['deployment_types']) : 'Not provided' }}</div>
                    </div>
                    <div class="col-sm-6">
                        <div class="small text-uppercase text-gasq-muted fw-semibold">Approved Budget</div>
                        <div>{{ isset($questionnaire['approved_budget_amount']) && $questionnaire['approved_budget_amount'] !== '' ? '$' . number_format((float) $questionnaire['approved_budget_amount'], 2) : 'Not provided' }}</div>
                    </div>
                    <div class="col-sm-6">
                        <div class="small text-uppercase text-gasq-muted fw-semibold">Selection Method</div>
                        @php $sm = $questionnaire['selection_method'] ?? ''; @endphp
                        <div>{{ $sm === 'accept_decline' ? 'Accept or Decline Offer' : ($sm === 'sealed_price' ? 'Submit Sealed Vendor Price' : 'Not provided') }}</div>
                    </div>
                    @if(($questionnaire['selection_method'] ?? '') === 'accept_decline')
                    <div class="col-sm-6">
                        <div class="small text-uppercase text-gasq-muted fw-semibold">GASQ Offer Price</div>
                        <div>{{ isset($questionnaire['offer_price']) && $questionnaire['offer_price'] !== '' ? '$' . number_format((float) $questionnaire['offer_price'], 2) : 'Not provided' }}</div>
                    </div>
                    @endif
                </div>
            </x-card>
        </div>
        <div class="col-lg-6">
            <x-card title="Buyer Qualification Snapshot" class="h-100">
                <div class="row g-3">
                    <div class="col-sm-6">
                        <div class="small text-uppercase text-gasq-muted fw-semibold">Decision Maker</div>
                        <div>{{ ucwords(str_replace('_', ' ', (string) ($questionnaire['final_decision_maker'] ?? 'Not provided'))) }}</div>
                    </div>
                    <div class="col-sm-6">
                        <div class="small text-uppercase text-gasq-muted fw-semibold">Budget Approved</div>
                        <div>{{ ucwords(str_replace('_', ' ', (string) ($questionnaire['funds_approval_status'] ?? 'Not provided'))) }}</div>
                    </div>
                    <div class="col-sm-6">
                        <div class="small text-uppercase text-gasq-muted fw-semibold">Ready to Move Forward</div>
                        <div>{{ ucwords(str_replace('_', ' ', (string) ($questionnaire['move_forward_if_accepted'] ?? 'Not provided'))) }}</div>
                    </div>
                    <div class="col-sm-6">
                        <div class="small text-uppercase text-gasq-muted fw-semibold">Current Security Setup</div>
                        <div>{{ ucwords(str_replace('_', ' ', (string) ($questionnaire['current_security_setup'] ?? 'Not provided'))) }}</div>
                    </div>
                    <div class="col-sm-6">
                        <div class="small text-uppercase text-gasq-muted fw-semibold">Multiple Locations</div>
                        <div>{{ ($questionnaire['multiple_locations'] ?? '') === 'yes' ? 'Yes' : 'No' }}</div>
                    </div>
                    <div class="col-sm-6">
                        <div class="small text-uppercase text-gasq-muted fw-semibold">Supporting Documents</div>
                        <div>{{ count($documents) }} uploaded</div>
                    </div>
                    <div class="col-12">
                        <div class="small text-uppercase text-gasq-muted fw-semibold">Primary Reason</div>
                        <div>{{ $questionnaire['primary_reason'] ?? 'Not provided' }}</div>
                    </div>
                    <div class="col-12">
                        <div class="small text-uppercase text-gasq-muted fw-semibold">Additional Vendor Notes</div>
                        <div>{{ $questionnaire['additional_notes_to_vendors'] ?? 'None provided' }}</div>
                    </div>
                </div>
            </x-card>
        </div>
    </div>

    @if(! empty($preview['special_requirements']))
        <x-card title="Special Requirements" class="mt-4">
            <ul class="mb-0">
                @foreach($preview['special_requirements'] as $requirement)
                    <li>{{ $requirement }}</li>
                @endforeach
            </ul>
        </x-card>
    @endif

    {{-- VENDOR PREVIEW (review spec §23) — the buyer verifies exactly what leaves the
         building before it leaves. Buyer-confidential figures (True Cost to Protect,
         capital recovery, internal budget analysis) are never in this set (§9). --}}
    <x-card title="What Vendors Will See" class="mt-4">
        <p class="text-gasq-muted small">
            Vendors receive only the information they need to make a responsible commitment.
            Your True Cost to Protect&trade;, capital-recovery analysis, internal budget
            assumptions and buyer notes are <strong>not</strong> released.
        </p>
        <div class="row g-3">
            <div class="col-md-6">
                <div class="small text-uppercase text-gasq-muted fw-semibold mb-2">Released to vendors</div>
                <ul class="small mb-0">
                    <li>Service location and facility type</li>
                    <li>Scope of work and duties</li>
                    <li>Coverage hours, posts and staffing</li>
                    <li>Baseline wage assumption</li>
                    <li>Start date and contract term</li>
                    <li>Licensing, insurance and equipment requirements</li>
                    <li>Buyer offer, where applicable</li>
                </ul>
            </div>
            <div class="col-md-6">
                <div class="small text-uppercase text-gasq-muted fw-semibold mb-2">Kept confidential</div>
                <ul class="small mb-0 text-gasq-muted">
                    <li>True Cost to Protect&trade;</li>
                    <li>Capital Recovery Opportunity&trade;</li>
                    <li>Internal budget analysis and maximum authority</li>
                    <li>Competing vendor pricing</li>
                    <li>Buyer notes and internal GASQ analysis</li>
                </ul>
            </div>
        </div>
    </x-card>

    {{-- SEALED PRICING DISCLOSURE (review spec §20, P0-12) --}}
    <x-card title="Sealed Pricing Protection" class="mt-4">
        <p class="mb-2"><i class="fa fa-lock me-2 text-primary"></i><strong>Vendor pricing remains sealed.</strong></p>
        <p class="text-gasq-muted small mb-0">
            Vendors do not compete by watching each other's pricing. Pricing stays sealed through
            qualification, site review, interview and evaluation. You assess qualifications,
            capability, proposed solution and site assessment first &mdash; the selected vendor's
            price is revealed afterwards.
        </p>
    </x-card>

    {{-- FINAL BUYER CERTIFICATIONS (review spec §24, P0-13).
         Enforced server-side in JobPostingController::publish() — the disabled button is a
         convenience, never the control. --}}
    <form action="{{ route('jobs.publish') }}" method="POST" class="mt-4" id="releaseForm">
        @csrf
        <x-card title="Final Buyer Certification">
            @php
                $certifications = [
                    'cert_scope_reviewed' => 'I have reviewed the complete Scope of Work.',
                    'cert_coverage_correct' => 'The coverage hours and staffing requirements are correct.',
                    'cert_wage_correct' => 'The stated baseline wage is correct.',
                    'cert_dates_correct' => 'The start date and contract term are correct.',
                    'cert_budget_authority' => 'I confirm an approved budget or purchasing authority exists.',
                    'cert_vendor_responses' => 'I understand vendors may accept, decline, or request clarification.',
                    'cert_scope_changes' => 'I understand material scope changes may require the opportunity to be reissued.',
                    'cert_sealed_pricing' => 'I understand vendor prices remain sealed under the GASQ process.',
                    'cert_price_variance' => 'I understand vendor-to-vendor price differences are Price Variance, not necessarily Capital Recovery.',
                    'cert_capital_recovery' => 'I understand Capital Recovery Opportunity can only be established from the appropriate buyer-side cost comparison.',
                    'cert_shared_resource_hours' => 'I understand Shared Resource pricing requires GASQ verification of a vendor\'s minimum 1,000 weekly billable hours.',
                    'cert_shared_resource_breakdown' => 'I understand any Shared Resource rate must be supported by a complete line-item bill-rate breakdown.',
                ];
            @endphp

            @foreach($certifications as $name => $label)
                <div class="form-check mb-2">
                    <input class="form-check-input js-cert" type="checkbox" name="{{ $name }}" id="{{ $name }}" value="1" {{ $isReady ? '' : 'disabled' }}>
                    <label class="form-check-label small" for="{{ $name }}">{{ $label }}</label>
                </div>
            @endforeach

            <div class="form-check mt-3 p-3 rounded" style="background:#f5f7fa;">
                <input class="form-check-input js-cert" type="checkbox" name="cert_authorize_release" id="cert_authorize_release" value="1" {{ $isReady ? '' : 'disabled' }}>
                <label class="form-check-label fw-semibold" for="cert_authorize_release">
                    I authorize GASQ to release this opportunity to qualified vendors.
                </label>
            </div>

            @unless($isReady)
                <div class="alert alert-warning small mt-3 mb-0">
                    Complete the outstanding items in the validation panel above before this
                    opportunity can be released.
                </div>
            @endunless

            <div class="d-flex flex-wrap gap-2 mt-4">
                <button type="submit" class="btn btn-primary btn-lg" id="releaseBtn" disabled>
                    <i class="fa fa-paper-plane me-2"></i>Release Opportunity to Vendor Network
                </button>
            </div>
        </x-card>
    </form>
</div>

@push('scripts')
<script>
// Enable release only once every certification is ticked. This mirrors the server-side
// check in JobPostingController::publish(); it does not replace it.
(function () {
    var form = document.getElementById('releaseForm');
    if (!form) return;
    var boxes = Array.prototype.slice.call(form.querySelectorAll('.js-cert'));
    var btn = document.getElementById('releaseBtn');
    if (!btn || boxes.length === 0) return;

    function sync() {
        btn.disabled = boxes.some(function (b) { return b.disabled || !b.checked; });
    }

    boxes.forEach(function (b) { b.addEventListener('change', sync); });
    sync();
})();
</script>
@endpush
@endsection

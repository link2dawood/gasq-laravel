@extends('layouts.app')

@section('title', 'Review & Validate Security Service Opportunity')

@section('content')
@php
    $questionnaire = $questionnaire ?? [];
    $reviewPayload = $reviewPayload ?? $questionnaire;
    $validation = $validation ?? ['status' => 'action_required', 'checks' => [], 'blocking_count' => 0, 'warning_count' => 0];
    $isReady = ($validation['status'] ?? '') === 'ready';
    $status = $opportunityStatus ?? ($isReady ? \App\Support\OpportunityStatus::READY_FOR_RELEASE : \App\Support\OpportunityStatus::VALIDATION_REQUIRED);
    $statusLabel = \App\Support\OpportunityStatus::label($status);
    $validationChecks = collect($validation['checks'] ?? [])->keyBy('key');
    $blockingChecks = collect($validation['checks'] ?? [])->filter(fn ($check) => $check['blocking'] && ! $check['passed']);
    $coverageMathCheck = $validationChecks->get('coverage_math');

    $serviceTypes = array_values(array_filter((array) ($reviewPayload['service_types'] ?? $questionnaire['service_types'] ?? [])));
    $dutiesRequired = array_values(array_filter((array) ($questionnaire['duties_required'] ?? [])));
    $shiftsNeeded = array_values(array_filter((array) ($questionnaire['shifts_needed'] ?? [])));
    $documents = array_values(array_filter((array) ($questionnaire['supporting_documents'] ?? [])));
    $deploymentTypes = array_values(array_filter((array) ($questionnaire['deployment_types'] ?? [])));
    $insuranceMinimums = array_values(array_filter((array) ($reviewPayload['insurance_minimums_required'] ?? $questionnaire['insurance_minimums_required'] ?? [])));

    $selectionMethod = (string) ($reviewPayload['selection_method'] ?? '');
    $selectionMethodLabel = match ($selectionMethod) {
        'accept_decline' => 'Accept or Decline Offer',
        'sealed_price' => 'Submit Sealed Vendor Price',
        default => 'Not provided',
    };

    $budgetApprovalStatus = (string) ($reviewPayload['budget_approved_status'] ?? $reviewPayload['funds_approval_status'] ?? '');
    $budgetApprovalLabel = match ($budgetApprovalStatus) {
        'yes', 'approved' => 'Approved',
        'pending' => 'Pending approval',
        'no' => 'Not approved',
        default => $budgetApprovalStatus !== '' ? ucwords(str_replace('_', ' ', $budgetApprovalStatus)) : 'Not provided',
    };

    $baselineWage = is_numeric($preview['baseline_wage'] ?? null)
        ? (float) $preview['baseline_wage']
        : (is_numeric($reviewPayload['baseline_wage'] ?? null) ? (float) $reviewPayload['baseline_wage'] : null);
    $baselineSource = (string) ($preview['baseline_wage_source'] ?? $reviewPayload['baseline_wage_source'] ?? '');
    $baselineSourceLabel = match ($baselineSource) {
        'current_employee' => 'Current employee wage',
        'existing_vendor' => 'Existing vendor wage',
        'current_contract' => 'Current contract wage',
        'buyer_assumption' => 'Buyer assumption',
        'local_market' => 'Local market assumption',
        'living_wage' => 'Living wage benchmark',
        'collective_agreement' => 'Collective agreement',
        'other' => 'Other basis',
        default => 'Not specified',
    };

    $approvedBudget = is_numeric($reviewPayload['approved_budget_amount'] ?? null) ? (float) $reviewPayload['approved_budget_amount'] : null;
    $budgetMin = is_numeric($preview['budget_min'] ?? null) ? (float) $preview['budget_min'] : null;
    $budgetMax = is_numeric($preview['budget_max'] ?? null) ? (float) $preview['budget_max'] : null;
    $estimatedContractValue = $budgetMin !== null || $budgetMax !== null
        ? ($budgetMin !== null && $budgetMax !== null && abs($budgetMin - $budgetMax) < 0.01
            ? '$' . number_format($budgetMin, 2)
            : '$' . number_format((float) ($budgetMin ?? 0), 2) . ' - $' . number_format((float) ($budgetMax ?? 0), 2))
        : 'Not provided';

    $hoursPerDay = is_numeric($reviewPayload['hours_per_day'] ?? null) ? (float) $reviewPayload['hours_per_day'] : null;
    $daysPerWeek = is_numeric($reviewPayload['days_per_week'] ?? null) ? (float) $reviewPayload['days_per_week'] : null;
    $weeksPerYear = is_numeric($reviewPayload['weeks_per_year'] ?? null) ? (float) $reviewPayload['weeks_per_year'] : null;
    $staffPerShift = is_numeric($reviewPayload['staff_per_shift'] ?? ($preview['guards_per_shift'] ?? null))
        ? (float) ($reviewPayload['staff_per_shift'] ?? $preview['guards_per_shift'])
        : null;
    $weeklyCoverageHours = $hoursPerDay !== null && $daysPerWeek !== null && $staffPerShift !== null
        ? $hoursPerDay * $daysPerWeek * $staffPerShift
        : null;
    $annualCoverageHours = $weeklyCoverageHours !== null && $weeksPerYear !== null
        ? $weeklyCoverageHours * $weeksPerYear
        : null;
    $weeklyHoursPerOfficer = $hoursPerDay !== null && $daysPerWeek !== null ? $hoursPerDay * $daysPerWeek : null;

    $scopeVersion = (string) ($preview['scope_version'] ?? '1.0');
@endphp

<div class="container py-4">
    <nav aria-label="breadcrumb" class="mb-3">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="{{ route('job-board') }}">Job Board</a></li>
            <li class="breadcrumb-item">Buyer Questionnaire</li>
            <li class="breadcrumb-item active">Review &amp; Validate</li>
        </ol>
    </nav>

    @if(session('error'))
        <x-alert type="danger" dismissible>{{ session('error') }}</x-alert>
    @endif

    <section class="rounded-4 overflow-hidden mb-4" style="background:linear-gradient(135deg, #10213a 0%, #1e3a5f 100%);">
        <div class="p-4 p-lg-5 text-white">
            <div class="d-flex flex-wrap justify-content-between align-items-start gap-3 mb-4">
                <div>
                    <div class="small text-uppercase fw-semibold mb-2" style="letter-spacing:.14em; color:rgba(255,255,255,.72);">
                        Final Procurement Validation Gate
                    </div>
                    <h1 class="h2 mb-3 text-white">Review &amp; Validate Security Service Opportunity</h1>
                    <p class="mb-0" style="max-width:48rem; color:rgba(255,255,255,.82);">
                        Complete every blocking item, confirm the procurement controls below, and
                        release only the vendor-facing information that qualified security providers
                        need in order to evaluate this opportunity responsibly.
                    </p>
                </div>
                <div class="d-flex flex-column flex-sm-row gap-2">
                    <span class="badge {{ $isReady ? 'bg-success' : 'bg-warning text-dark' }} fs-6 align-self-start">
                        {{ strtoupper($statusLabel) }}
                    </span>
                    <form action="{{ route('jobs.review.edit') }}" method="POST">
                        @csrf
                        <button type="submit" class="btn btn-light">Return to Edit</button>
                    </form>
                </div>
            </div>

            <div class="row g-3">
                <div class="col-md-3">
                    <div class="h-100 rounded-4 p-3" style="background:rgba(255,255,255,.10); border:1px solid rgba(255,255,255,.14);">
                        <div class="small text-uppercase fw-semibold mb-2" style="letter-spacing:.08em; color:rgba(255,255,255,.65);">Release Status</div>
                        <div class="h4 mb-1 text-white">{{ $isReady ? 'Ready to Release' : 'Action Required' }}</div>
                        <div class="small" style="color:rgba(255,255,255,.76);">
                            {{ $validation['blocking_count'] }} blocking item{{ $validation['blocking_count'] === 1 ? '' : 's' }}
                            · {{ $validation['warning_count'] }} warning{{ $validation['warning_count'] === 1 ? '' : 's' }}
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="h-100 rounded-4 p-3" style="background:rgba(255,255,255,.10); border:1px solid rgba(255,255,255,.14);">
                        <div class="small text-uppercase fw-semibold mb-2" style="letter-spacing:.08em; color:rgba(255,255,255,.65);">Scope Version</div>
                        <div class="h4 mb-1 text-white">v{{ $scopeVersion }}</div>
                        <div class="small" style="color:rgba(255,255,255,.76);">Release creates the vendor baseline for future change control.</div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="h-100 rounded-4 p-3" style="background:rgba(255,255,255,.10); border:1px solid rgba(255,255,255,.14);">
                        <div class="small text-uppercase fw-semibold mb-2" style="letter-spacing:.08em; color:rgba(255,255,255,.65);">Baseline Wage</div>
                        <div class="h4 mb-1 text-white">
                            {{ $baselineWage !== null ? '$' . number_format($baselineWage, 2) . '/hr' : 'Not set' }}
                        </div>
                        <div class="small" style="color:rgba(255,255,255,.76);">{{ $baselineSourceLabel }}</div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="h-100 rounded-4 p-3" style="background:rgba(255,255,255,.10); border:1px solid rgba(255,255,255,.14);">
                        <div class="small text-uppercase fw-semibold mb-2" style="letter-spacing:.08em; color:rgba(255,255,255,.65);">Pricing Path</div>
                        <div class="h4 mb-1 text-white">{{ $selectionMethodLabel }}</div>
                        <div class="small" style="color:rgba(255,255,255,.76);">
                            {{ $selectionMethod === 'sealed_price' ? 'Vendor pricing remains sealed through qualification.' : 'Buyer publishes a fixed GASQ offer price.' }}
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <x-card class="mb-4">
        <div class="d-flex flex-wrap justify-content-between align-items-start gap-3 mb-3">
            <div>
                <div class="small text-uppercase text-gasq-muted fw-semibold mb-2">Validation Panel</div>
                <h2 class="h5 fw-bold mb-1">Opportunity Validation Status</h2>
                <p class="small text-gasq-muted mb-0">
                    This page is the final release control point. No opportunity should be released
                    to the vendor network until every blocking requirement passes.
                </p>
            </div>
            <div class="text-sm-end">
                <div class="small text-uppercase text-gasq-muted fw-semibold">Current State</div>
                <div class="h5 mb-0 {{ $isReady ? 'text-success' : 'text-warning' }}">{{ strtoupper($statusLabel) }}</div>
            </div>
        </div>

        @if(! $isReady && $blockingChecks->isNotEmpty())
            <div class="alert alert-warning small mb-3">
                <strong>Release is blocked.</strong> Complete the following before continuing:
                {{ $blockingChecks->pluck('label')->implode(' · ') }}.
            </div>
        @endif

        <div class="row g-3">
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
                            <div class="{{ $check['passed'] ? 'fw-semibold' : 'fw-bold' }}">{{ $check['label'] }}</div>
                            @if(! $check['passed'] && $check['message'])
                                <div class="text-gasq-muted">{{ $check['message'] }}</div>
                            @endif
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    </x-card>

    <div class="row g-4 mb-4">
        <div class="col-xl-8">
            <x-card title="Opportunity Overview" class="h-100">
                <h2 class="h4 mb-3">{{ $preview['title'] ?? 'Security Services Opportunity' }}</h2>
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
                        <div class="small text-uppercase text-gasq-muted fw-semibold">Property Type</div>
                        <div>{{ $preview['property_type'] ?? 'Not provided' }}</div>
                    </div>
                    <div class="col-md-4">
                        <div class="small text-uppercase text-gasq-muted fw-semibold">Request Type</div>
                        <div>{{ filled($questionnaire['request_type'] ?? null) ? ucwords(str_replace('_', ' ', (string) $questionnaire['request_type'])) : 'Not provided' }}</div>
                    </div>
                    <div class="col-md-4">
                        <div class="small text-uppercase text-gasq-muted fw-semibold">Contract Term</div>
                        <div>{{ $questionnaire['desired_contract_term'] ?? 'Not provided' }}</div>
                    </div>
                    <div class="col-md-4">
                        <div class="small text-uppercase text-gasq-muted fw-semibold">Decision Maker</div>
                        <div>{{ filled($reviewPayload['final_decision_maker'] ?? null) ? ucwords(str_replace('_', ' ', (string) $reviewPayload['final_decision_maker'])) : 'Not provided' }}</div>
                    </div>
                    <div class="col-md-4">
                        <div class="small text-uppercase text-gasq-muted fw-semibold">Approval Authority</div>
                        <div>{{ filled($reviewPayload['approval_authority'] ?? null) ? ucwords(str_replace('_', ' ', (string) $reviewPayload['approval_authority'])) : 'Not provided' }}</div>
                    </div>
                    <div class="col-md-4">
                        <div class="small text-uppercase text-gasq-muted fw-semibold">Budget Status</div>
                        <div>{{ $budgetApprovalLabel }}</div>
                    </div>
                </div>

                @if(! empty($preview['description']))
                    <div class="small text-uppercase text-gasq-muted fw-semibold mb-2">Opportunity Summary</div>
                    <div class="mb-0">{!! nl2br(e($preview['description'])) !!}</div>
                @endif
            </x-card>
        </div>
        <div class="col-xl-4">
            <x-card title="Scope Version Control" class="h-100">
                <div class="rounded-4 p-3 mb-3" style="background:#f5f8fc; border:1px solid #d7e3f3;">
                    <div class="small text-uppercase fw-semibold mb-1 text-gasq-muted">Current Draft Version</div>
                    <div class="h3 mb-1">v{{ $scopeVersion }}</div>
                    <div class="small text-gasq-muted">
                        When this opportunity is released, version {{ $scopeVersion }} becomes the
                        baseline that vendors evaluate and respond to.
                    </div>
                </div>
                <div class="small text-gasq-muted mb-2">
                    Material changes after release do not silently replace the original scope.
                </div>
                <ul class="small mb-0">
                    <li>Coverage, staffing, wage, duties, location, insurance, dates, and service type changes are tracked.</li>
                    <li>Material scope changes create a new major version and can require vendor re-acknowledgement.</li>
                    <li>Minor pre-release edits remain draft refinements until the opportunity is released.</li>
                </ul>
            </x-card>
        </div>
    </div>

    <div class="row g-4 mb-4">
        <div class="col-lg-7">
            <x-card title="Scope of Work" class="h-100">
                <div class="row g-3">
                    <div class="col-md-6">
                        <div class="small text-uppercase text-gasq-muted fw-semibold">Service Types</div>
                        <div>{{ $serviceTypes !== [] ? implode(', ', $serviceTypes) : 'Not provided' }}</div>
                    </div>
                    <div class="col-md-6">
                        <div class="small text-uppercase text-gasq-muted fw-semibold">Armed / Unarmed</div>
                        <div>{{ filled($questionnaire['armed_status'] ?? null) ? ucwords((string) $questionnaire['armed_status']) : 'Not provided' }}</div>
                    </div>
                    <div class="col-md-6">
                        <div class="small text-uppercase text-gasq-muted fw-semibold">Deployment</div>
                        <div>{{ $deploymentTypes !== [] ? implode(', ', $deploymentTypes) : 'Not provided' }}</div>
                    </div>
                    <div class="col-md-6">
                        <div class="small text-uppercase text-gasq-muted fw-semibold">Service Package</div>
                        <div>{{ filled($questionnaire['service_package_expectation'] ?? null) ? ucwords(str_replace('_', ' ', (string) $questionnaire['service_package_expectation'])) : 'Not provided' }}</div>
                    </div>
                    <div class="col-12">
                        <div class="small text-uppercase text-gasq-muted fw-semibold">Duties Required</div>
                        <div>{{ $dutiesRequired !== [] ? implode(', ', $dutiesRequired) : 'Not provided' }}</div>
                    </div>
                    <div class="col-12">
                        <div class="small text-uppercase text-gasq-muted fw-semibold">Known Site Risks</div>
                        <div>{{ $questionnaire['known_site_risks'] ?? 'None provided' }}</div>
                    </div>
                    <div class="col-md-4">
                        <div class="small text-uppercase text-gasq-muted fw-semibold">Equipment Requirements</div>
                        <div>{{ $questionnaire['equipment_requirements'] ?? 'None provided' }}</div>
                    </div>
                    <div class="col-md-4">
                        <div class="small text-uppercase text-gasq-muted fw-semibold">Uniform Requirements</div>
                        <div>{{ $questionnaire['uniform_requirements'] ?? 'None provided' }}</div>
                    </div>
                    <div class="col-md-4">
                        <div class="small text-uppercase text-gasq-muted fw-semibold">Reporting Requirements</div>
                        <div>{{ $questionnaire['reporting_requirements'] ?? 'None provided' }}</div>
                    </div>
                    <div class="col-12">
                        <div class="small text-uppercase text-gasq-muted fw-semibold">Supporting Documents</div>
                        <div>{{ count($documents) > 0 ? count($documents) . ' uploaded' : 'No documents uploaded' }}</div>
                    </div>
                </div>
            </x-card>
        </div>
        <div class="col-lg-5">
            <x-card title="Coverage / Staffing Validation" class="h-100">
                <div class="row g-3">
                    <div class="col-sm-6">
                        <div class="small text-uppercase text-gasq-muted fw-semibold">Hours per Day</div>
                        <div>{{ $hoursPerDay !== null ? number_format($hoursPerDay, 0) : 'Not provided' }}</div>
                    </div>
                    <div class="col-sm-6">
                        <div class="small text-uppercase text-gasq-muted fw-semibold">Days per Week</div>
                        <div>{{ $daysPerWeek !== null ? number_format($daysPerWeek, 0) : 'Not provided' }}</div>
                    </div>
                    <div class="col-sm-6">
                        <div class="small text-uppercase text-gasq-muted fw-semibold">Weeks per Year</div>
                        <div>{{ $weeksPerYear !== null ? number_format($weeksPerYear, 0) : 'Not provided' }}</div>
                    </div>
                    <div class="col-sm-6">
                        <div class="small text-uppercase text-gasq-muted fw-semibold">Staff per Shift</div>
                        <div>{{ $staffPerShift !== null ? number_format($staffPerShift, 0) : 'Not provided' }}</div>
                    </div>
                    <div class="col-sm-6">
                        <div class="small text-uppercase text-gasq-muted fw-semibold">Shifts Needed</div>
                        <div>{{ $shiftsNeeded !== [] ? implode(', ', $shiftsNeeded) : 'Not provided' }}</div>
                    </div>
                    <div class="col-sm-6">
                        <div class="small text-uppercase text-gasq-muted fw-semibold">Weekly Coverage Hours</div>
                        <div>{{ $weeklyCoverageHours !== null ? number_format($weeklyCoverageHours, 0) : 'Not provided' }}</div>
                    </div>
                    <div class="col-sm-6">
                        <div class="small text-uppercase text-gasq-muted fw-semibold">Annual Coverage Hours</div>
                        <div>{{ $annualCoverageHours !== null ? number_format($annualCoverageHours, 0) : 'Not provided' }}</div>
                    </div>
                    <div class="col-sm-6">
                        <div class="small text-uppercase text-gasq-muted fw-semibold">Hours per Officer / Week</div>
                        <div>{{ $weeklyHoursPerOfficer !== null ? number_format($weeklyHoursPerOfficer, 0) : 'Not provided' }}</div>
                    </div>
                </div>

                @if($coverageMathCheck && ! $coverageMathCheck['passed'])
                    <div class="alert alert-warning small mt-3 mb-0">
                        {{ $coverageMathCheck['message'] }}
                    </div>
                @else
                    <div class="alert alert-success small mt-3 mb-0">
                        Coverage inputs and staffing assumptions reconcile for release review.
                    </div>
                @endif
            </x-card>
        </div>
    </div>

    <div class="row g-4 mb-4">
        <div class="col-lg-5">
            <x-card title="Baseline Wage &amp; Financial Basis" class="h-100">
                <div class="rounded-4 p-4 mb-3" style="background:#7f1d1d; color:#fff;">
                    <div class="small text-uppercase fw-semibold mb-2" style="letter-spacing:.08em; color:rgba(255,255,255,.72);">Baseline Wage</div>
                    <div class="display-6 fw-semibold mb-1">
                        {{ $baselineWage !== null ? '$' . number_format($baselineWage, 2) . '/hr' : 'Not provided' }}
                    </div>
                    <div class="small" style="color:rgba(255,255,255,.8);">
                        Workforce wage assumption released to vendors. This is not the vendor bill rate.
                    </div>
                </div>

                <div class="row g-3">
                    <div class="col-sm-6">
                        <div class="small text-uppercase text-gasq-muted fw-semibold">Wage Source</div>
                        <div>{{ $baselineSourceLabel }}</div>
                    </div>
                    <div class="col-sm-6">
                        <div class="small text-uppercase text-gasq-muted fw-semibold">Approved Budget</div>
                        <div>{{ $approvedBudget !== null ? '$' . number_format($approvedBudget, 2) : 'Not provided' }}</div>
                    </div>
                    <div class="col-sm-6">
                        <div class="small text-uppercase text-gasq-muted fw-semibold">Estimated Contract Value</div>
                        <div>{{ $estimatedContractValue }}</div>
                    </div>
                    <div class="col-sm-6">
                        <div class="small text-uppercase text-gasq-muted fw-semibold">Pricing Method</div>
                        <div>{{ $selectionMethodLabel }}</div>
                    </div>
                    @if($selectionMethod === 'accept_decline')
                        <div class="col-12">
                            <div class="small text-uppercase text-gasq-muted fw-semibold">GASQ Offer Price</div>
                            <div>{{ is_numeric($reviewPayload['offer_price'] ?? null) ? '$' . number_format((float) $reviewPayload['offer_price'], 2) : 'Not provided' }}</div>
                        </div>
                    @endif
                </div>
            </x-card>
        </div>
        <div class="col-lg-7">
            <x-card title="Vendor Pricing &amp; Confidentiality" class="h-100">
                <div class="row g-4">
                    <div class="col-md-6">
                        <div class="small text-uppercase text-gasq-muted fw-semibold mb-2">Released to vendors</div>
                        <ul class="small mb-0">
                            <li>Service location, property type, and start timing</li>
                            <li>Scope of work, duties, site risks, and operating requirements</li>
                            <li>Coverage schedule, staffing model, and shifts needed</li>
                            <li>Baseline wage assumption vendors must support</li>
                            <li>Insurance minimums and compliance requirements</li>
                            <li>Buyer offer terms or sealed pricing path, depending on selection method</li>
                        </ul>
                    </div>
                    <div class="col-md-6">
                        <div class="small text-uppercase text-gasq-muted fw-semibold mb-2">Kept confidential</div>
                        <ul class="small mb-0 text-gasq-muted">
                            <li>True Cost to Protect and buyer-side cost benchmarks</li>
                            <li>Capital Recovery analysis and internal financial commentary</li>
                            <li>Competing vendor pricing and internal evaluation notes</li>
                            <li>Buyer maximum authority, internal budget strategy, and non-public analysis</li>
                        </ul>
                    </div>
                </div>

                <div class="rounded-4 p-3 mt-4" style="background:#f7fafc; border:1px solid #d9e3ef;">
                    <div class="d-flex align-items-start gap-2">
                        <i class="fa fa-lock text-primary mt-1"></i>
                        <div class="small">
                            <strong>Sealed pricing rules.</strong>
                            @if($selectionMethod === 'sealed_price')
                                Vendor prices remain sealed through qualification, site review,
                                interview, and preferred-vendor selection.
                            @else
                                This opportunity uses a published buyer offer price rather than a
                                sealed vendor-price release.
                            @endif
                        </div>
                    </div>
                </div>

                <div class="rounded-4 p-3 mt-3" style="background:#fff8eb; border:1px solid #f0d8a8;">
                    <div class="small text-uppercase fw-semibold mb-2" style="letter-spacing:.08em; color:#8a5a12;">Shared Resource Rate Rules</div>
                    <ul class="small mb-0">
                        <li>Only vendors with at least 1,000 verified weekly billable hours are eligible for Shared Resource rate review.</li>
                        <li>Eligibility does not approve the rate.</li>
                        <li>Every Shared Resource rate must be supported by a complete line-item bill-rate breakdown before submission.</li>
                    </ul>
                </div>
            </x-card>
        </div>
    </div>

    <x-card title="Compliance Requirements" class="mb-4">
        <div class="row g-3">
            <div class="col-md-6">
                <div class="small text-uppercase text-gasq-muted fw-semibold">Insurance Minimums</div>
                <div>{{ $insuranceMinimums !== [] ? implode(', ', $insuranceMinimums) : 'Not provided' }}</div>
            </div>
            <div class="col-md-6">
                <div class="small text-uppercase text-gasq-muted fw-semibold">Ready to Move Forward</div>
                <div>{{ filled($reviewPayload['move_forward_if_accepted'] ?? null) ? ucwords(str_replace('_', ' ', (string) $reviewPayload['move_forward_if_accepted'])) : 'Not provided' }}</div>
            </div>
            <div class="col-12">
                <div class="small text-uppercase text-gasq-muted fw-semibold">Compliance Terms</div>
                <div>{{ $questionnaire['compliance_terms'] ?? 'None provided' }}</div>
            </div>
            <div class="col-12">
                <div class="small text-uppercase text-gasq-muted fw-semibold">Additional Vendor Notes</div>
                <div>{{ $questionnaire['additional_notes_to_vendors'] ?? 'None provided' }}</div>
            </div>
        </div>
    </x-card>

    <form action="{{ route('jobs.publish') }}" method="POST" id="releaseForm">
        @csrf
        <x-card title="Final Buyer Certification">
            <p class="small text-gasq-muted mb-3">
                Confirm each control below before releasing this opportunity to the GASQ vendor network.
            </p>

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

            <div class="form-check mt-3 p-3 rounded-3" style="background:#f5f7fa;">
                <input class="form-check-input js-cert" type="checkbox" name="cert_authorize_release" id="cert_authorize_release" value="1" {{ $isReady ? '' : 'disabled' }}>
                <label class="form-check-label fw-semibold" for="cert_authorize_release">
                    I authorize GASQ to release this opportunity to qualified vendors.
                </label>
            </div>

            @unless($isReady)
                <div class="alert alert-warning small mt-3 mb-0">
                    Complete the outstanding validation items above before this opportunity can be released.
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

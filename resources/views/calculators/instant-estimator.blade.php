@extends('layouts.app')
@section('title', 'GASQ Instant Estimator')
@section('header_variant', 'dashboard')

@section('content')
@php
    $estimatorUser = auth()->user();
    $canPrepareEstimatorJob = $estimatorUser?->isBuyer() ?? false;
@endphp
<div class="gasq-estimator-shell">
    <div class="container-xl py-4 py-lg-5">

        {{-- Page header --}}
        <div class="d-flex align-items-center gap-3 mb-4 d-print-none">
            <a href="{{ route('calculator.index') }}" class="btn btn-outline-secondary btn-sm">
                <i class="fa fa-arrow-left"></i>
            </a>
            <div>
                <div class="text-uppercase small fw-semibold text-gasq-muted tracking-wide">Instant Calculator</div>
                <h1 class="h2 fw-bold mb-0">GASQ Instant Estimator</h1>
            </div>
        </div>

        <div id="instantEstimatorStatus" class="alert d-none mb-4" role="alert"></div>

        {{-- Hero header row --}}
        <div class="row g-4 mb-4">
            <div class="col-lg-8">
                <div class="est-panel h-100 d-flex align-items-center gap-3">
                    <div class="est-shield-icon d-none d-md-flex">
                        <i class="fa fa-shield fa-xl"></i>
                    </div>
                    <div>
                        <h2 class="fw-bold mb-1 h4">GASQ Instant Estimator</h2>
                        <p class="text-gasq-muted mb-0 small">Baseline pay, coverage planning, internal TCO, outsourced bill rate, and report sharing.</p>
                        <span class="est-chip est-chip-dark mt-2 d-inline-flex">CFO Tested. CFO Approved.</span>
                    </div>
                </div>
            </div>
            <div class="col-lg-4">
                <div class="est-hero-panel h-100">
                    <div class="small text-uppercase opacity-75">Recommended range</div>
                    <div class="display-6 fw-bold mb-1 mt-1" id="heroRecommendedRange">$28.00 – $39.00</div>
                    <div class="small opacity-75 mb-2" id="heroServiceLabel">Unarmed Security Services</div>
                    <span class="est-chip est-chip-accent" id="heroRateBand">Within recommended band</span>
                </div>
            </div>
        </div>

        {{-- Tab nav --}}
        <ul class="nav est-tab-nav mb-4 d-print-none" role="tablist">
            <li class="nav-item">
                <a class="nav-link active" data-bs-toggle="tab" href="#tab-estimator" role="tab">
                    <i class="fa fa-calculator me-1"></i> Estimator
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" data-bs-toggle="tab" href="#tab-rates" role="tab">
                    <i class="fa fa-sliders me-1"></i> Rate Library
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" data-bs-toggle="tab" href="#tab-report" role="tab">
                    <i class="fa fa-file-alt me-1"></i> Report View
                </a>
            </li>
        </ul>

        <div class="tab-content">

            {{-- ====== TAB 1: ESTIMATOR ====== --}}
            <div class="tab-pane fade show active" id="tab-estimator" role="tabpanel">

                {{-- Step indicator --}}
                <div class="est-stepper mb-4 d-print-none">
                    <div class="est-step active" id="stepInd1" role="button" tabindex="0">
                        <div class="est-step-dot">1</div>
                        <div class="est-step-label">Questionnaire</div>
                    </div>
                    <div class="est-step-line"></div>
                    <div class="est-step" id="stepInd2" role="button" tabindex="0">
                        <div class="est-step-dot">2</div>
                        <div class="est-step-label">Estimate Setup</div>
                    </div>
                    <div class="est-step-line"></div>
                    <div class="est-step locked" id="stepInd3">
                        <div class="est-step-dot"><i class="fa fa-lock fa-xs"></i></div>
                        <div class="est-step-label">Results</div>
                    </div>
                </div>

                {{-- ---- STEP 1: Contact & Qualification ---- --}}
                <div id="estPanel1">
                    <div class="est-panel mb-4">
                        <div class="est-section-label mb-1">Step 1</div>
                        <h3 class="mb-1">Contact &amp; Qualification</h3>
                        <p class="text-gasq-muted small mb-4">Complete the buyer questionnaire here first. These answers autofill your job offer if you choose the post-job path later.</p>
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label fw-semibold" for="role">Role of requester</label>
                                <select id="role" class="form-select">
                                    <option value="buyer">Buyer</option>
                                    <option value="vendor">Vendor</option>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-semibold" for="name">Name</label>
                                <input id="name" class="form-control" placeholder="Full name">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-semibold" for="company">Company / business</label>
                                <input id="company" class="form-control" placeholder="Company name">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-semibold" for="contactJobTitle">Job title</label>
                                <input id="contactJobTitle" class="form-control" placeholder="Property manager, operations director, etc.">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-semibold" for="propertySiteName">Property / site name</label>
                                <input id="propertySiteName" class="form-control" placeholder="Site or property name">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-semibold" for="email">Primary email recipient</label>
                                <input id="email" type="email" class="form-control" placeholder="name@company.com">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-semibold" for="additionalEmails">Additional "To" recipients</label>
                                <input id="additionalEmails" class="form-control" placeholder="ops@company.com, buyer@company.com">
                                <div class="form-text">Separate multiple with commas</div>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-semibold" for="ccEmails">CC recipients</label>
                                <input id="ccEmails" class="form-control" placeholder="finance@company.com">
                            </div>
                            <div class="col-12">
                                <label class="form-label fw-semibold" for="bccEmails">BCC recipients</label>
                                <input id="bccEmails" class="form-control" placeholder="archive@company.com">
                            </div>
                            <div class="col-12">
                                <div class="form-check form-switch">
                                    <input id="sendToVendorNetwork" class="form-check-input" type="checkbox">
                                    <label class="form-check-label fw-semibold" for="sendToVendorNetwork">Send to vendor network</label>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-semibold" for="phone">Phone</label>
                                <input id="phone" class="form-control" placeholder="0000000000">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-semibold" for="location">Project location</label>
                                <input id="location" class="form-control" list="estimatorLocationOptions" placeholder="Property, city, or state">
                                <datalist id="estimatorLocationOptions">
                                    @foreach(($locations ?? []) as $location)
                                        <option value="{{ ucwords(str_replace('-', ' ', $location)) }}"></option>
                                    @endforeach
                                </datalist>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-semibold" for="companyWebsite">Company website</label>
                                <input id="companyWebsite" class="form-control" placeholder="https://example.com">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-semibold" for="propertyType">Property type</label>
                                <select id="propertyType" class="form-select">
                                    <option value="">Choose...</option>
                                    <option value="Apartment / Multifamily">Apartment / Multifamily</option>
                                    <option value="HOA / Community Association">HOA / Community Association</option>
                                    <option value="Commercial Office">Commercial Office</option>
                                    <option value="Retail Center">Retail Center</option>
                                    <option value="Warehouse / Industrial">Warehouse / Industrial</option>
                                    <option value="Healthcare">Healthcare</option>
                                    <option value="School / Education">School / Education</option>
                                    <option value="Hotel / Hospitality">Hotel / Hospitality</option>
                                    <option value="Government Facility">Government Facility</option>
                                    <option value="Event Venue">Event Venue</option>
                                    <option value="Other">Other</option>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-semibold" for="currentSecuritySetup">Current security setup</label>
                                <select id="currentSecuritySetup" class="form-select">
                                    <option value="">Choose...</option>
                                    <option value="in_house">In-house</option>
                                    <option value="outsourced">Outsourced</option>
                                    <option value="none">None</option>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-semibold" for="serviceStartTimeline">Service start timeline</label>
                                <select id="serviceStartTimeline" class="form-select">
                                    <option value="">Choose...</option>
                                    <option value="immediate">Immediate</option>
                                    <option value="15_days_or_less">15 days or less</option>
                                    <option value="30_days_or_less">30 days or less</option>
                                    <option value="30_60_days">30-60 days</option>
                                    <option value="future_planning">Future planning</option>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-semibold" for="decisionMaker">Are you the decision maker?</label>
                                <select id="decisionMaker" class="form-select">
                                    <option value="yes">Yes</option>
                                    <option value="no">No</option>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-semibold" for="approvedBudget">Budget approved?</label>
                                <select id="approvedBudget" class="form-select">
                                    <option value="yes">Yes</option>
                                    <option value="no">No</option>
                                    <option value="considering">Considering it</option>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-semibold" for="budgetAmount">Budget amount</label>
                                <input id="budgetAmount" class="form-control" placeholder="$0.00">
                            </div>
                            <div class="col-12">
                                <label class="form-label fw-semibold" for="notes">Notes and scope comments</label>
                                <textarea id="notes" class="form-control" rows="2" placeholder="Post duties, special instructions, or scope context"></textarea>
                            </div>
                            <div class="col-12">
                                <div class="form-check form-switch">
                                    <input id="wantsComparison" class="form-check-input" type="checkbox" checked>
                                    <label class="form-check-label fw-semibold" for="wantsComparison">Include in-house vs outsourcing comparison</label>
                                </div>
                            </div>
                            <div class="col-12">
                                <label class="form-label fw-semibold" for="attachments">Reference files</label>
                                <input id="attachments" type="file" class="form-control" multiple>
                                <div class="form-text">Filenames will appear in the report summary.</div>
                                <div id="attachmentList" class="est-file-list mt-2"></div>
                            </div>
                        </div>
                        <div class="d-flex justify-content-end mt-4 pt-3" style="border-top:1px solid rgba(6,45,121,.08)">
                            <button type="button" class="btn btn-primary btn-lg px-5" id="goToStep2Btn">
                                Continue to Calculation <i class="fa fa-arrow-right ms-2"></i>
                            </button>
                        </div>
                    </div>
                </div>

                {{-- ---- STEP 2: Calculation inputs ---- --}}
                <div id="estPanel2" class="d-none">
                    <div class="est-panel mb-4">
                        <div class="d-flex align-items-center justify-content-between mb-1">
                            <div class="est-section-label">Step 2</div>
                            <button type="button" class="btn btn-link text-gasq-muted p-0 small" id="backToStep1Btn">
                                <i class="fa fa-arrow-left me-1"></i> Back to Step 1
                            </button>
                        </div>
                        <h3 class="mb-2">Start Your Calculation</h3>
                        <p class="text-gasq-muted small mb-4">Step 3 stays locked until you choose either <strong>Pay 1% Fee</strong> or <strong>Post a Job</strong>.</p>
                        <div class="row g-3">
                            <div class="col-12">
                                <label class="form-label fw-semibold" for="serviceType">Security service type</label>
                                <select id="serviceType" class="form-select">
                                    <option value="unarmed">Unarmed Security Services</option>
                                    <option value="armed">Armed Security Services</option>
                                    <option value="supervisor">Security Site Supervisor</option>
                                    <option value="mobile">Mobile Patrol Services</option>
                                    <option value="loss">Loss / Crime Prevention Services</option>
                                    <option value="executive">Executive Protection Agent</option>
                                    <option value="offduty">Off Duty Police Officer</option>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-semibold" for="selectedRate">Baseline hourly pay rate</label>
                                <div class="input-group">
                                    <span class="input-group-text">$</span>
                                    <input id="selectedRate" type="number" min="0" step="0.01" class="form-control">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-semibold" for="coverageModel">Coverage model</label>
                                <select id="coverageModel" class="form-select">
                                    <option value="hours">Budget by coverage hours</option>
                                    <option value="checks">Budget by weekly checks</option>
                                </select>
                            </div>

                            {{-- Hours model --}}
                            <div id="hoursCoverageGroup" class="col-12">
                                <div class="row g-3">
                                    <div class="col-4">
                                        <label class="form-label fw-semibold" for="hoursPerDay">Hours per day</label>
                                        <input id="hoursPerDay" type="number" min="8" max="24" class="form-control" value="8">
                                        <div class="form-text">Min 8 hrs</div>
                                    </div>
                                    <div class="col-4">
                                        <label class="form-label fw-semibold" for="daysPerWeek">Days per week</label>
                                        <input id="daysPerWeek" type="number" min="1" max="7" class="form-control" value="5">
                                    </div>
                                    <div class="col-4">
                                        <label class="form-label fw-semibold" for="staffPerShift">Staff per shift</label>
                                        <input id="staffPerShift" type="number" min="1" max="1000" class="form-control" value="1">
                                    </div>
                                </div>
                            </div>

                            {{-- Checks model --}}
                            <div id="checksCoverageGroup" class="col-12 d-none">
                                <div class="row g-3">
                                    <div class="col-12">
                                        <label class="form-label fw-semibold" for="weeklyChecks">Weekly checks</label>
                                        <select id="weeklyChecks" class="form-select">
                                            <option value="21">21 weekly checks</option>
                                            <option value="28">28 weekly checks</option>
                                            <option value="42">42 weekly checks</option>
                                            <option value="56">56 weekly checks</option>
                                            <option value="84">84 weekly checks</option>
                                        </select>
                                        <div class="form-text" id="weeklyChecksDefinition"></div>
                                    </div>
                                    <div class="col-6">
                                        <label class="form-label fw-semibold" for="minutesPerCheck">Minutes per check</label>
                                        <input id="minutesPerCheck" type="number" min="8" max="60" class="form-control" value="15">
                                    </div>
                                    <div class="col-6">
                                        <label class="form-label fw-semibold" for="staffPerCheck">Staff per check</label>
                                        <input id="staffPerCheck" type="number" min="1" max="1000" class="form-control" value="1">
                                    </div>
                                </div>
                            </div>

                            <div class="col-12">
                                <label class="form-label fw-semibold" for="weeks">Weeks covered by this budget</label>
                                <input id="weeks" type="number" min="1" max="52" class="form-control" value="52">
                            </div>
                        </div>

                        <div class="est-method-note mt-3">
                            Direct labor → employer cost (÷0.70) → annualized (×3,744 hrs) → internal true hourly (÷1,456 productive hrs) → outsourced rate (×0.70 absorption).
                        </div>

                        <div class="d-flex justify-content-end mt-4 pt-3" style="border-top:1px solid rgba(6,45,121,.08)">
                            <button type="button" class="btn btn-primary btn-lg px-5" id="viewResultsBtn">
                                <i class="fa fa-lock me-2"></i> View My Results
                            </button>
                        </div>
                    </div>
                </div>

                {{-- ---- GATE: shown after step 2, before step 3 unlocks ---- --}}
                <div id="estGate" class="d-none">
                    <div class="est-panel est-gate-panel text-center mb-4 py-5">
                        <div class="est-gate-lock-icon mb-3">
                            <i class="fa fa-lock fa-2x"></i>
                        </div>
                        <h3 class="fw-bold mb-2">Your Estimate Is Ready</h3>
                        <p class="text-gasq-muted mb-1">To reveal your full cost analysis, select one option below.</p>
                        <p class="text-gasq-muted small mb-4">
                            Price Permit Fee: <strong id="gateAppraisalFeeDisplay">$0.00</strong>
                            <span class="opacity-75">&nbsp;(1% of annual recovered capital)</span>
                        </p>
                        <div class="d-flex flex-column flex-sm-row justify-content-center gap-3 mb-3">
                            <button type="button" class="btn btn-primary btn-lg px-5" id="gateFeeBtn">
                                <i class="fa fa-credit-card me-2"></i> Pay 1% Fee
                                <span class="fw-normal opacity-75 ms-1 small" id="gateFeeAmount"></span>
                            </button>
                            <button type="button" class="btn btn-outline-primary btn-lg px-5" id="gatePostJobBtn">
                                <i class="fa fa-briefcase me-2"></i> {{ $canPrepareEstimatorJob ? 'Post a Job Instead' : 'Sign In to Post a Job' }}
                            </button>
                        </div>
                        <p class="small text-gasq-muted">
                            {{ $canPrepareEstimatorJob
                                ? 'Posting a job is free. Your questionnaire data autofills the job offer form.'
                                : 'The post-job path is for buyer accounts. Sign in as a buyer to carry this questionnaire into the job offer flow.' }}
                        </p>
                        <button type="button" class="btn btn-link text-gasq-muted small mt-1" id="backToStep2Btn">
                            <i class="fa fa-arrow-left me-1"></i> Back to Calculation
                        </button>
                    </div>
                </div>

                {{-- ---- STEP 3: Results (hidden until gate unlocked) ---- --}}
                <div id="estPanel3" class="d-none">

                    <div class="d-flex align-items-center justify-content-between mb-3 d-print-none">
                        <div>
                            <div class="est-section-label">Step 3 — Results Unlocked</div>
                            <h3 class="mb-0">Your Cost Analysis</h3>
                        </div>
                        <div class="d-flex align-items-center gap-3">
                            <a href="{{ route('jobs.create', ['step' => 'details']) }}" class="btn btn-outline-primary btn-sm d-none" id="continueToJobButton">
                                <i class="fa fa-briefcase me-1"></i> Continue to Job Offer
                            </a>
                            <button type="button" class="btn btn-link text-gasq-muted small p-0" id="backToStep2FromResults">
                                <i class="fa fa-arrow-left me-1"></i> Back
                            </button>
                        </div>
                    </div>

                    {{-- Stat cards --}}
                    <div class="row g-3 mb-4">
                        <div class="col-6 col-xl-3">
                            <div class="est-stat">
                                <div class="est-stat-label"><i class="fa fa-clock me-1"></i>Weekly Coverage Hours</div>
                                <div class="est-stat-value" id="statWeeklyCoverage">0</div>
                                <div class="est-stat-sub">hours per week</div>
                            </div>
                        </div>
                        <div class="col-6 col-xl-3">
                            <div class="est-stat">
                                <div class="est-stat-label"><i class="fa fa-calendar me-1"></i>Budget Covers</div>
                                <div class="est-stat-value est-stat-value-sm" id="coverageHeadline">0 weeks · 0 months</div>
                            </div>
                        </div>
                        <div class="col-6 col-xl-3">
                            <div class="est-stat">
                                <div class="est-stat-label"><i class="fa fa-dollar-sign me-1"></i>Outsourced Hourly</div>
                                <div class="est-stat-value" id="statOutsourcedHourly">$0.00</div>
                            </div>
                        </div>
                        <div class="col-6 col-xl-3">
                            <div class="est-stat">
                                <div class="est-stat-label"><i class="fa fa-dollar-sign me-1"></i>Internal True Hourly</div>
                                <div class="est-stat-value" id="statInternalHourly">$0.00</div>
                            </div>
                        </div>
                    </div>

                    {{-- Cost breakdown cards --}}
                    <div class="row g-4 mb-4">
                        <div class="col-xl-6">
                            <div class="est-panel">
                                <div class="d-flex align-items-center gap-2 mb-3">
                                    <i class="fa fa-dollar-sign text-primary"></i>
                                    <h4 class="mb-0 fw-bold">Contractor Total Outsourced Costs</h4>
                                </div>
                                <div class="row g-3">
                                    <div class="col-6">
                                        <div class="est-metric-tile">
                                            <div class="est-metric-label">Total Hourly</div>
                                            <div class="est-metric-value" id="outHourly">$0.00</div>
                                        </div>
                                    </div>
                                    <div class="col-6">
                                        <div class="est-metric-tile">
                                            <div class="est-metric-label">Total Weekly</div>
                                            <div class="est-metric-value" id="outWeekly">$0.00</div>
                                        </div>
                                    </div>
                                    <div class="col-6">
                                        <div class="est-metric-tile">
                                            <div class="est-metric-label">Total Monthly</div>
                                            <div class="est-metric-value" id="outMonthly">$0.00</div>
                                        </div>
                                    </div>
                                    <div class="col-6">
                                        <div class="est-metric-tile est-metric-tile-accent">
                                            <div class="est-metric-label">Annual / Term</div>
                                            <div class="est-metric-value" id="outTerm">$0.00</div>
                                        </div>
                                    </div>
                                    <span id="outAnnual" class="d-none"></span>
                                </div>
                            </div>
                        </div>
                        <div class="col-xl-6">
                            <div class="est-panel">
                                <div class="d-flex align-items-center gap-2 mb-3">
                                    <i class="fa fa-chart-line text-primary"></i>
                                    <h4 class="mb-0 fw-bold">Internal Total Cost of Ownership</h4>
                                </div>
                                <div class="row g-3">
                                    <div class="col-6">
                                        <div class="est-metric-tile">
                                            <div class="est-metric-label">Internal True Hourly</div>
                                            <div class="est-metric-value" id="inHourly">$0.00</div>
                                        </div>
                                    </div>
                                    <div class="col-6">
                                        <div class="est-metric-tile">
                                            <div class="est-metric-label">Weekly TCO</div>
                                            <div class="est-metric-value" id="inWeekly">$0.00</div>
                                        </div>
                                    </div>
                                    <div class="col-6">
                                        <div class="est-metric-tile">
                                            <div class="est-metric-label">Monthly TCO</div>
                                            <div class="est-metric-value" id="inMonthly">$0.00</div>
                                        </div>
                                    </div>
                                    <div class="col-6">
                                        <div class="est-metric-tile est-metric-tile-accent">
                                            <div class="est-metric-label">Annual / Term</div>
                                            <div class="est-metric-value" id="inTerm">$0.00</div>
                                        </div>
                                    </div>
                                    <span id="inAnnual" class="d-none"></span>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- ROI & Recovery card --}}
                    <div id="roiSection" class="mb-4">
                        <div class="est-panel">
                            <div class="d-flex align-items-center gap-2 mb-3">
                                <i class="fa fa-chart-line text-primary"></i>
                                <h4 class="mb-0 fw-bold">ROI, Payback &amp; Recovery</h4>
                            </div>
                            <div class="row g-3">
                                <div class="col-6 col-xl-3">
                                    <div class="est-metric-tile">
                                        <div class="est-metric-label">Capital Recovered</div>
                                        <div class="est-metric-value" id="recoveredCapital">$0.00</div>
                                        <div class="est-metric-sub">term savings vs in-house</div>
                                    </div>
                                </div>
                                <div class="col-6 col-xl-3">
                                    <div class="est-metric-tile">
                                        <div class="est-metric-label">Price Permit Fee</div>
                                        <div class="est-metric-value" id="appraisalFee">$0.00</div>
                                        <div class="est-metric-sub">1% of annual recovered capital</div>
                                    </div>
                                </div>
                                <div class="col-6 col-xl-3">
                                    <div class="est-metric-tile">
                                        <div class="est-metric-label">Efficiency Gain</div>
                                        <div class="est-metric-value" id="efficiencyGain">0 : 1</div>
                                    </div>
                                </div>
                                <div class="col-6 col-xl-3">
                                    <div class="est-metric-tile">
                                        <div class="est-metric-label">Payback Period</div>
                                        <div class="est-metric-value" id="paybackMonths">0 months</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                </div>{{-- /estPanel3 --}}

                {{-- Hidden elements for JS compatibility (always in DOM) --}}
                <span id="statWorkforce" class="d-none"></span>
                <span id="outsourcedTermHeadline" class="d-none"></span>
                <span id="summarySubtitle" class="d-none"></span>

            </div>{{-- /tab-estimator --}}

            {{-- ====== TAB 2: RATE LIBRARY ====== --}}
            <div class="tab-pane fade" id="tab-rates" role="tabpanel">
                <div class="est-panel">
                    <h3 class="fw-bold mb-1">Rate Library</h3>
                    <p class="text-gasq-muted mb-4">Editable baseline pay ranges for each service category. Changes apply immediately to the Estimator tab.</p>
                    <div class="row g-3">
                        <div class="col-md-6">
                            <div class="est-library-item">
                                <div>
                                    <div class="fw-semibold">Unarmed Security Services</div>
                                    <div class="small text-gasq-muted">$28.00 – $39.00</div>
                                </div>
                                <div class="input-group input-group-sm">
                                    <span class="input-group-text">$</span>
                                    <input class="form-control rate-library-input" data-rate-key="unarmed" type="number" min="0" step="0.01">
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="est-library-item">
                                <div>
                                    <div class="fw-semibold">Armed Security Services</div>
                                    <div class="small text-gasq-muted">$40.00 – $52.00</div>
                                </div>
                                <div class="input-group input-group-sm">
                                    <span class="input-group-text">$</span>
                                    <input class="form-control rate-library-input" data-rate-key="armed" type="number" min="0" step="0.01">
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="est-library-item">
                                <div>
                                    <div class="fw-semibold">Security Site Supervisor</div>
                                    <div class="small text-gasq-muted">$40.00 – $52.00</div>
                                </div>
                                <div class="input-group input-group-sm">
                                    <span class="input-group-text">$</span>
                                    <input class="form-control rate-library-input" data-rate-key="supervisor" type="number" min="0" step="0.01">
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="est-library-item">
                                <div>
                                    <div class="fw-semibold">Mobile Patrol Services</div>
                                    <div class="small text-gasq-muted">$40.00 – $52.00</div>
                                </div>
                                <div class="input-group input-group-sm">
                                    <span class="input-group-text">$</span>
                                    <input class="form-control rate-library-input" data-rate-key="mobile" type="number" min="0" step="0.01">
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="est-library-item">
                                <div>
                                    <div class="fw-semibold">Loss / Crime Prevention Services</div>
                                    <div class="small text-gasq-muted">$40.00 – $52.00</div>
                                </div>
                                <div class="input-group input-group-sm">
                                    <span class="input-group-text">$</span>
                                    <input class="form-control rate-library-input" data-rate-key="loss" type="number" min="0" step="0.01">
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="est-library-item">
                                <div>
                                    <div class="fw-semibold">Executive Protection Agent</div>
                                    <div class="small text-gasq-muted">$53.00 – $68.00</div>
                                </div>
                                <div class="input-group input-group-sm">
                                    <span class="input-group-text">$</span>
                                    <input class="form-control rate-library-input" data-rate-key="executive" type="number" min="0" step="0.01">
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="est-library-item">
                                <div>
                                    <div class="fw-semibold">Off Duty Police Officer</div>
                                    <div class="small text-gasq-muted">$53.00 – $68.00</div>
                                </div>
                                <div class="input-group input-group-sm">
                                    <span class="input-group-text">$</span>
                                    <input class="form-control rate-library-input" data-rate-key="offduty" type="number" min="0" step="0.01">
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>{{-- /tab-rates --}}

            {{-- ====== TAB 3: REPORT VIEW ====== --}}
            <div class="tab-pane fade" id="tab-report" role="tabpanel">
                <div class="est-panel">
                    <div class="d-flex flex-column flex-md-row align-items-start align-items-md-center justify-content-between gap-3 mb-4 d-print-none">
                        <div>
                            <h3 class="fw-bold mb-1">Estimate Report</h3>
                            <p class="text-gasq-muted mb-0 small" id="summarySubtitleReport">Summary for email delivery, PDF export, and internal review.</p>
                        </div>
                        <div class="d-flex flex-wrap gap-2">
                            <button type="button" class="btn btn-outline-secondary btn-sm" id="downloadReportButton">
                                <i class="fa fa-download me-1"></i> Export PDF
                            </button>
                            <button type="button" class="btn btn-outline-secondary btn-sm" id="emailReportButton">
                                <i class="fa fa-envelope me-1"></i> Email Report
                            </button>
                            <button type="button" class="btn btn-outline-secondary btn-sm" id="printReportButton">
                                <i class="fa fa-print me-1"></i> Print
                            </button>
                            <button type="button" class="btn btn-primary btn-sm" id="copySummaryButton">
                                <i class="fa fa-copy me-1"></i> Copy Summary
                            </button>
                            <button type="button" class="btn btn-outline-secondary btn-sm" id="resetEstimatorButton">
                                <i class="fa fa-rotate me-1"></i> Reset
                            </button>
                        </div>
                    </div>

                    <div class="row g-4 mb-4">
                        <div class="col-md-6">
                            <div class="est-report-block-outer">
                                <div class="text-uppercase small fw-semibold text-gasq-muted mb-3">Requester</div>
                                <div class="est-report-row"><span>Name</span><strong id="reportRequester">—</strong></div>
                                <div class="est-report-row"><span>Company</span><strong id="reportCompany">—</strong></div>
                                <div class="est-report-row"><span>Location</span><strong id="reportLocation">—</strong></div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="est-report-block-outer">
                                <div class="text-uppercase small fw-semibold text-gasq-muted mb-3">Estimate Summary</div>
                                <div class="est-report-row"><span>Service</span><strong id="reportService">—</strong></div>
                                <div class="est-report-row"><span>Baseline pay</span><strong id="reportRate">$0.00/hr</strong></div>
                                <div class="est-report-row"><span>Budget</span><strong id="reportBudget">—</strong></div>
                                <div class="est-report-row"><span>Attachments</span><strong id="reportAttachments">—</strong></div>
                            </div>
                        </div>
                    </div>

                    {{-- Readiness signals --}}
                    <div class="mb-4">
                        <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-2">
                            <h5 class="fw-semibold mb-0">Buyer Readiness Signals
                                <span class="est-chip est-chip-light ms-2" id="summaryReadinessBadge" style="font-size:.75rem">Buyer ready</span>
                            </h5>
                            <span class="small text-gasq-muted" id="budgetAlignmentText">Budget not entered yet</span>
                        </div>
                        <div class="d-flex flex-wrap gap-2" id="readinessSignals"></div>
                    </div>

                    <div class="est-summary-footnote">
                        Directional output only. Actual proposal pricing will vary by location conditions, contract structure, overtime exposure, and vendor requirements.
                    </div>
                </div>

                <form id="instantEstimatorEmailForm" action="{{ route('reports.email') }}" method="POST" class="d-none">
                    @csrf
                    <input type="hidden" name="type" value="instant-estimator">
                    <input type="hidden" name="email" id="instantEstimatorEmailTarget" value="{{ auth()->user()?->email }}">
                </form>
            </div>{{-- /tab-report --}}

        </div>{{-- /tab-content --}}
    </div>
</div>
@endsection

@push('scripts')
<style>
    .gasq-estimator-shell {
        background:
            radial-gradient(circle at top right, rgba(6, 45, 121, 0.08), transparent 28rem),
            linear-gradient(180deg, #f6f8fc 0%, #ffffff 32%, #f5f7fb 100%);
        min-height: calc(100vh - 5rem);
    }

    .tracking-wide { letter-spacing: 0.08em; }

    /* Shield icon in header */
    .est-shield-icon {
        width: 3.5rem;
        height: 3.5rem;
        border-radius: 1rem;
        background: rgba(90,30,36,.1);
        display: flex;
        align-items: center;
        justify-content: center;
        color: #5a1e24;
        flex-shrink: 0;
    }

    /* Tab nav */
    .est-tab-nav {
        background: rgba(255,255,255,.75);
        border: 1px solid rgba(6,45,121,.1);
        border-radius: 1rem;
        padding: .35rem;
        gap: .25rem;
    }
    .est-tab-nav .nav-link {
        border-radius: .75rem;
        color: var(--gasq-muted, #6b7280);
        font-weight: 600;
        font-size: .92rem;
        padding: .5rem 1.1rem;
        transition: background .15s, color .15s;
    }
    .est-tab-nav .nav-link.active {
        background: #fff;
        color: var(--gasq-primary, #062d79);
        box-shadow: 0 2px 8px -4px rgba(6,45,121,.2);
    }

    /* Dark/accent chips */
    .est-chip-dark {
        background: rgba(90,30,36,.12);
        color: #5a1e24;
        border: 1px solid rgba(90,30,36,.15);
        display: inline-flex;
        align-items: center;
        border-radius: 999px;
        padding: .3rem .75rem;
        font-size: .76rem;
        font-weight: 700;
        letter-spacing: .02em;
    }

    .est-hero-panel {
        padding: 1.35rem;
        border-radius: 1.25rem;
        background: linear-gradient(150deg, #0f2c63 0%, #123a86 52%, #0e2450 100%);
        color: #fff;
        min-height: 100%;
        display: flex;
        flex-direction: column;
        justify-content: center;
    }

    .est-panel {
        padding: 1.35rem;
        border: 1px solid rgba(6, 45, 121, 0.1);
        border-radius: 1.4rem;
        background: rgba(255, 255, 255, 0.86);
        box-shadow: 0 22px 45px -34px rgba(6, 45, 121, 0.45);
        backdrop-filter: blur(12px);
    }

    /* Stepper */
    .est-stepper {
        display: flex;
        align-items: center;
        gap: .9rem;
        padding: 1rem 1.2rem;
        border: 1px solid rgba(6, 45, 121, 0.1);
        border-radius: 1.2rem;
        background: rgba(255, 255, 255, 0.82);
        box-shadow: 0 18px 36px -30px rgba(6, 45, 121, 0.35);
        overflow-x: auto;
    }

    .est-step {
        display: inline-flex;
        align-items: center;
        gap: .75rem;
        min-width: fit-content;
        color: var(--gasq-muted, #6b7280);
        transition: color .15s ease, opacity .15s ease;
        cursor: pointer;
        user-select: none;
    }

    .est-step.locked {
        cursor: default;
        opacity: .7;
    }

    .est-step-dot {
        width: 2.25rem;
        height: 2.25rem;
        border-radius: 999px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        border: 1px solid rgba(6, 45, 121, 0.16);
        background: rgba(6, 45, 121, 0.04);
        color: var(--gasq-primary, #062d79);
        font-weight: 800;
        flex-shrink: 0;
    }

    .est-step-label {
        font-size: .92rem;
        font-weight: 700;
        white-space: nowrap;
    }

    .est-step-line {
        flex: 1 1 3rem;
        min-width: 2.5rem;
        height: 1px;
        background: linear-gradient(90deg, rgba(6, 45, 121, 0.08), rgba(6, 45, 121, 0.18));
    }

    .est-step.active {
        color: var(--gasq-primary, #062d79);
    }

    .est-step.active .est-step-dot {
        background: linear-gradient(135deg, #0f2c63 0%, #123a86 100%);
        border-color: transparent;
        color: #fff;
        box-shadow: 0 8px 18px -10px rgba(6, 45, 121, 0.55);
    }

    .est-step.locked .est-step-dot {
        background: rgba(107, 114, 128, 0.08);
        border-color: rgba(107, 114, 128, 0.18);
        color: #6b7280;
    }

    .est-panel-header {
        display: flex;
        justify-content: space-between;
        align-items: flex-start;
        gap: 1rem;
        margin-bottom: 1.25rem;
    }

    .est-section-label {
        text-transform: uppercase;
        letter-spacing: 0.08em;
        font-size: 0.72rem;
        font-weight: 700;
        color: var(--gasq-primary);
    }

    .est-panel h3, .est-panel h4 {
        margin: 0;
        letter-spacing: -0.02em;
    }

    .est-method-note {
        padding: 0.9rem 1rem;
        border-radius: 1rem;
        background: rgba(6, 45, 121, 0.05);
        color: var(--gasq-muted);
        font-size: 0.88rem;
        line-height: 1.55;
    }

    /* Stat cards */
    .est-stat {
        height: 100%;
        padding: 1rem;
        border-radius: 1rem;
        background: rgba(255, 255, 255, 0.9);
        border: 1px solid rgba(6, 45, 121, 0.1);
        box-shadow: 0 4px 12px -6px rgba(6,45,121,.15);
    }
    .est-stat-label {
        font-size: 0.74rem;
        text-transform: uppercase;
        letter-spacing: 0.05em;
        color: var(--gasq-muted);
        margin-bottom: 0.4rem;
    }
    .est-stat-value {
        font-weight: 800;
        letter-spacing: -0.03em;
        font-size: 1.4rem;
        color: var(--gasq-primary, #062d79);
    }
    .est-stat-value-sm {
        font-size: 1rem;
        letter-spacing: -0.01em;
        line-height: 1.3;
    }
    .est-stat-sub {
        font-size: .75rem;
        color: var(--gasq-muted);
        margin-top: .2rem;
    }

    /* Metric tiles */
    .est-metric-tile {
        height: 100%;
        padding: 0.95rem 1rem;
        border-radius: 1rem;
        background: rgba(6, 45, 121, 0.05);
        border: 1px solid rgba(6, 45, 121, 0.08);
    }
    .est-metric-tile-accent {
        background: rgba(6, 45, 121, 0.1);
        border-color: rgba(6, 45, 121, 0.18);
    }
    .est-metric-label {
        font-size: 0.76rem;
        text-transform: uppercase;
        letter-spacing: 0.05em;
        color: var(--gasq-muted);
        margin-bottom: 0.35rem;
    }
    .est-metric-value {
        font-weight: 800;
        letter-spacing: -0.03em;
        font-size: 1.15rem;
        color: var(--gasq-primary, #062d79);
    }
    .est-metric-sub {
        font-size: .72rem;
        color: var(--gasq-muted);
        margin-top: .2rem;
    }

    /* Chip styles */
    .est-chip {
        display: inline-flex;
        align-items: center;
        gap: 0.45rem;
        border-radius: 999px;
        padding: 0.45rem 0.8rem;
        font-size: 0.76rem;
        font-weight: 700;
        letter-spacing: 0.02em;
    }
    .est-chip-light {
        background: rgba(255,255,255,.14);
        color: #fff;
        border: 1px solid rgba(255,255,255,.16);
    }
    .est-chip-accent {
        background: rgba(255, 214, 102, 0.18);
        color: #ffd666;
        border: 1px solid rgba(255, 214, 102, 0.2);
    }
    .est-chip-good { background: rgba(16,185,129,.12); color: #047857; }
    .est-chip-warn { background: rgba(245,158,11,.14); color: #b45309; }
    .est-chip-neutral { background: rgba(6,45,121,.06); color: var(--gasq-primary); }

    /* Report tab blocks */
    .est-report-block-outer {
        padding: 1.2rem;
        border-radius: 1rem;
        background: rgba(6,45,121,.04);
        border: 1px solid rgba(6,45,121,.1);
    }
    .est-report-row {
        display: flex;
        justify-content: space-between;
        align-items: baseline;
        gap: 0.8rem;
        padding: 0.4rem 0;
        border-bottom: 1px solid rgba(6, 45, 121, 0.06);
        font-size: 0.93rem;
    }
    .est-report-row:last-child { border-bottom: 0; padding-bottom: 0; }

    /* Library items */
    .est-library-item {
        display: flex;
        justify-content: space-between;
        gap: 1rem;
        align-items: center;
        padding: 0.9rem 1rem;
        border-radius: 1rem;
        background: rgba(6, 45, 121, 0.04);
        border: 1px solid rgba(6, 45, 121, 0.08);
    }

    /* File list */
    .est-file-list { display: flex; flex-wrap: wrap; gap: 0.5rem; }
    .est-file-tag {
        display: inline-flex;
        align-items: center;
        gap: 0.4rem;
        padding: 0.35rem 0.65rem;
        border-radius: 999px;
        background: rgba(6, 45, 121, 0.06);
        color: var(--gasq-primary);
        font-size: 0.82rem;
        font-weight: 600;
    }

    /* Summary footnote */
    .est-summary-footnote {
        padding: 0.9rem 1rem;
        border-radius: 1rem;
        background: rgba(6, 45, 121, 0.05);
        color: var(--gasq-muted);
        font-size: 0.9rem;
        line-height: 1.55;
    }

    /* Form controls */
    .form-control, .form-select, .input-group-text {
        border-radius: 0.9rem;
        border-color: rgba(6, 45, 121, 0.14);
        min-height: 3rem;
    }
    .input-group .form-control, .input-group .input-group-text { border-radius: 0.9rem; }
    .form-control:focus, .form-select:focus {
        border-color: rgba(6, 45, 121, 0.35);
        box-shadow: 0 0 0 0.2rem rgba(6, 45, 121, 0.12);
    }

    @media (max-width: 991.98px) {
        .est-hero-panel { min-height: auto; }
        .est-stepper {
            gap: .65rem;
            padding: .9rem 1rem;
        }
        .est-step-label {
            font-size: .85rem;
        }
        .est-step-dot {
            width: 2rem;
            height: 2rem;
        }
    }

    @media print {
        .gasq-navbar, .d-print-none, .est-tab-nav, .est-panel h3 { display: none !important; }
        .gasq-estimator-shell { background: #fff !important; }
        .est-panel { box-shadow: none !important; border: 1px solid #ddd !important; }
    }
</style>

<script>
const STORAGE_KEY = 'gasq.instantEstimatorDraft.v2';
const REPORT_TYPE = 'instant-estimator';
const REPORT_DOWNLOAD_URL = @json(route('reports.download', ['type' => 'instant-estimator']));
const REPORT_PAYLOAD_URL = @json(route('backend.report-payload.store'));
const PREPARE_JOB_URL = @json(route('instant-estimator.prepare-job'));
const LOGIN_URL = @json(route('login'));
const CAN_PREPARE_JOB = @json($canPrepareEstimatorJob);
const IS_AUTHENTICATED = @json(auth()->check());
const VENDOR_NETWORK_RECIPIENTS = [
    'vendors@getasecurityquote.com',
    'network@getasecurityquote.com',
];

const SERVICE_TYPES = {
    unarmed: { label: 'Unarmed Security Services', min: 28, max: 39 },
    armed: { label: 'Armed Security Services', min: 40, max: 52 },
    supervisor: { label: 'Security Site Supervisor', min: 40, max: 52 },
    mobile: { label: 'Mobile Patrol Services', min: 40, max: 52 },
    loss: { label: 'Loss / Crime Prevention Services', min: 40, max: 52 },
    executive: { label: 'Executive Protection Agent', min: 53, max: 68 },
    offduty: { label: 'Off Duty Police Officer', min: 53, max: 68 },
};

const CHECK_OPTIONS = {
    21: {
        checks: 21,
        label: '21 weekly checks',
        definition: '1 check every 8 hours; 3 checks per day or 1 check per 8 hour shift; 7 days per week',
        visitsPerWeek: 21,
    },
    28: {
        checks: 28,
        label: '28 weekly checks',
        definition: '1 check every 6 hours; 4 checks per day; 7 days per week',
        visitsPerWeek: 28,
    },
    42: {
        checks: 42,
        label: '42 weekly checks',
        definition: '1 check every 4 hours; 6 checks per day or 2 checks per 8 hour shift; 7 days per week',
        visitsPerWeek: 42,
    },
    56: {
        checks: 56,
        label: '56 weekly checks',
        definition: '1 check every 3 hours; 8 checks per day; 7 days per week',
        visitsPerWeek: 56,
    },
    84: {
        checks: 84,
        label: '84 weekly checks',
        definition: '1 check every 2 hours; 12 checks per day or 4 checks per 8 hour shift; 7 days per week',
        visitsPerWeek: 84,
    },
};

const DEFAULT_PAY = {
    unarmed: 33,
    armed: 46,
    supervisor: 46,
    mobile: 46,
    loss: 46,
    executive: 60,
    offduty: 60,
};

let estimatorDraft = {
    payRates: { ...DEFAULT_PAY },
};

function byId(id) {
    return document.getElementById(id);
}

function fmtCurrency(value) {
    const amount = Number.isFinite(value) ? value : 0;
    return new Intl.NumberFormat('en-US', {
        style: 'currency',
        currency: 'USD',
        maximumFractionDigits: 2,
    }).format(amount);
}

function fmtNumber(value, digits = 2) {
    const amount = Number.isFinite(value) ? value : 0;
    return new Intl.NumberFormat('en-US', {
        minimumFractionDigits: digits,
        maximumFractionDigits: digits,
    }).format(amount);
}

function ceilWhole(value) {
    return Math.ceil(Number.isFinite(value) ? value : 0);
}

function clamp(value, min, max) {
    return Math.min(Math.max(value, min), max);
}

function toNumber(value, fallback = 0) {
    const numeric = Number(value);
    return Number.isFinite(numeric) ? numeric : fallback;
}

function parseCurrencyInput(value) {
    if (!value) {
        return 0;
    }

    const numeric = Number(String(value).replace(/[^0-9.-]/g, ''));
    return Number.isFinite(numeric) ? numeric : 0;
}

function humanizeLocation(value) {
    if (!value) {
        return '';
    }

    return String(value)
        .replace(/-/g, ' ')
        .replace(/\b\w/g, char => char.toUpperCase());
}

function parseEmailList(value) {
    return String(value || '')
        .split(',')
        .map(item => item.trim())
        .filter(Boolean);
}

function selectedServiceMeta(serviceType) {
    return SERVICE_TYPES[serviceType] || SERVICE_TYPES.unarmed;
}

function rateBand(rate, serviceMeta) {
    if (rate < serviceMeta.min) {
        return {
            label: 'Below recommended band',
            className: 'est-chip-warn',
        };
    }

    if (rate > serviceMeta.max) {
        return {
            label: 'Above recommended band',
            className: 'est-chip-neutral',
        };
    }

    return {
        label: 'Within recommended band',
        className: 'est-chip-good',
    };
}

function readinessMeta(state) {
    const isReady = state.decisionMaker === 'yes' && state.approvedBudget === 'yes';

    if (isReady) {
        return {
            label: 'Buyer ready',
            className: 'est-chip-light',
        };
    }

    if (state.decisionMaker !== 'yes' && state.approvedBudget === 'no') {
        return {
            label: 'Needs authority + budget',
            className: 'est-chip-light',
        };
    }

    return {
        label: 'Needs follow-up',
        className: 'est-chip-light',
    };
}

function attachmentNames() {
    return Array.from(byId('attachments').files || []).map(file => file.name);
}

function normalizeLocationState(location) {
    if (!location) {
        return '';
    }

    const slug = String(location)
        .trim()
        .toLowerCase()
        .replace(/[^a-z0-9]+/g, '-')
        .replace(/^-+|-+$/g, '');

    return slug;
}

function collectState() {
    return {
        role: byId('role').value || 'buyer',
        name: byId('name').value.trim(),
        company: byId('company').value.trim(),
        contactJobTitle: byId('contactJobTitle').value.trim(),
        propertySiteName: byId('propertySiteName').value.trim(),
        email: byId('email').value.trim(),
        phone: byId('phone').value.trim(),
        location: byId('location').value.trim(),
        companyWebsite: byId('companyWebsite').value.trim(),
        propertyType: byId('propertyType').value || '',
        currentSecuritySetup: byId('currentSecuritySetup').value || '',
        serviceStartTimeline: byId('serviceStartTimeline').value || '',
        serviceType: byId('serviceType').value || 'unarmed',
        selectedRate: clamp(toNumber(byId('selectedRate').value, 0), 0, 100000),
        coverageModel: byId('coverageModel').value || 'hours',
        hoursPerDay: clamp(toNumber(byId('hoursPerDay').value, 8), 8, 24),
        daysPerWeek: clamp(toNumber(byId('daysPerWeek').value, 5), 1, 7),
        weeks: clamp(toNumber(byId('weeks').value, 52), 1, 52),
        staffPerShift: clamp(toNumber(byId('staffPerShift').value, 1), 1, 1000),
        weeklyChecks: byId('weeklyChecks').value || '21',
        minutesPerCheck: clamp(toNumber(byId('minutesPerCheck').value, 15), 8, 60),
        staffPerCheck: clamp(toNumber(byId('staffPerCheck').value, 1), 1, 1000),
        wantsComparison: byId('wantsComparison').checked,
        decisionMaker: byId('decisionMaker').value || 'yes',
        approvedBudget: byId('approvedBudget').value || 'yes',
        budgetAmount: byId('budgetAmount').value.trim(),
        notes: byId('notes').value.trim(),
        additionalEmails: byId('additionalEmails').value.trim(),
        ccEmails: byId('ccEmails').value.trim(),
        bccEmails: byId('bccEmails').value.trim(),
        sendToVendorNetwork: byId('sendToVendorNetwork').checked,
        attachments: attachmentNames(),
        payRates: { ...estimatorDraft.payRates },
    };
}

function computeResults(state) {
    const serviceMeta = selectedServiceMeta(state.serviceType);
    const selectedCheckOption = CHECK_OPTIONS[state.weeklyChecks] || CHECK_OPTIONS[21];
    const weeklyCoverageHours = state.coverageModel === 'checks'
        ? (selectedCheckOption.visitsPerWeek * state.minutesPerCheck * state.staffPerCheck) / 60
        : state.hoursPerDay * state.daysPerWeek * state.staffPerShift;

    const monthlyCoverageHours = weeklyCoverageHours * 52 / 12;
    const annualCoverageHours = weeklyCoverageHours * 52;
    const termCoverageHours = weeklyCoverageHours * state.weeks;
    const monthsOfCoverageRaw = state.weeks / 4.3333333333;
    const monthsOfCoverageRounded = ceilWhole(monthsOfCoverageRaw);
    const weeksCoveredRounded = ceilWhole(state.weeks);

    const productiveHoursPerOfficer = 1456;
    const annualizedFTE = productiveHoursPerOfficer > 0 ? annualCoverageHours / productiveHoursPerOfficer : 0;
    const totalWorkforceRequired = Math.ceil(annualizedFTE);

    const directLabor = state.selectedRate;
    const employerCost = directLabor > 0 ? directLabor / 0.70 : 0;
    const annualEmployerCost = employerCost * 3744;
    const internalTrueHourly = annualEmployerCost > 0 ? annualEmployerCost / 1456 : 0;
    const outsourcedHourly = internalTrueHourly * 0.70;

    const outsourcedWeekly = outsourcedHourly * weeklyCoverageHours;
    const outsourcedMonthly = outsourcedHourly * monthlyCoverageHours;
    const outsourcedAnnual = outsourcedHourly * annualCoverageHours;
    const outsourcedTerm = outsourcedHourly * termCoverageHours;

    const internalWeekly = internalTrueHourly * weeklyCoverageHours;
    const internalMonthly = internalTrueHourly * monthlyCoverageHours;
    const internalAnnual = internalTrueHourly * annualCoverageHours;
    const internalTerm = internalTrueHourly * termCoverageHours;

    const annualCostPerProfessionalOut = totalWorkforceRequired > 0 ? outsourcedAnnual / totalWorkforceRequired : 0;
    const annualCostPerProfessionalIn = totalWorkforceRequired > 0 ? internalAnnual / totalWorkforceRequired : 0;
    const hourlyPerProfessionalOut = totalWorkforceRequired > 0 ? outsourcedHourly / totalWorkforceRequired : 0;
    const hourlyPerProfessionalIn = totalWorkforceRequired > 0 ? internalTrueHourly / totalWorkforceRequired : 0;
    const costPerMinuteOut = outsourcedHourly / 60;
    const costPerMinuteIn = internalTrueHourly / 60;

    const recoveredCapitalAnnual = Math.max(0, internalAnnual - outsourcedAnnual);
    const recoveredCapitalTerm = Math.max(0, internalTerm - outsourcedTerm);
    const appraisalFee = recoveredCapitalAnnual * 0.01;
    const efficiencyGain = appraisalFee > 0 ? recoveredCapitalAnnual / appraisalFee : 0;
    const breakevenMonths = internalMonthly > 0 ? outsourcedTerm / internalMonthly : 0;
    const budgetNumeric = parseCurrencyInput(state.budgetAmount);
    const budgetGap = budgetNumeric > 0 ? budgetNumeric - outsourcedTerm : null;

    return {
        serviceMeta,
        selectedCheckOption,
        weeklyCoverageHours,
        monthlyCoverageHours,
        annualCoverageHours,
        termCoverageHours,
        monthsOfCoverageRaw,
        monthsOfCoverageRounded,
        weeksCoveredRounded,
        totalWorkforceRequired,
        annualizedFTE,
        directLabor,
        employerCost,
        annualEmployerCost,
        internalTrueHourly,
        outsourcedHourly,
        outsourcedWeekly,
        outsourcedMonthly,
        outsourcedAnnual,
        outsourcedTerm,
        annualCostPerProfessionalOut,
        annualCostPerProfessionalIn,
        hourlyPerProfessionalOut,
        hourlyPerProfessionalIn,
        costPerMinuteOut,
        costPerMinuteIn,
        internalWeekly,
        internalMonthly,
        internalAnnual,
        internalTerm,
        recoveredCapitalAnnual,
        recoveredCapitalTerm,
        appraisalFee,
        efficiencyGain,
        breakevenMonths,
        overtimeInHouse: internalTrueHourly * 1.5,
        overtimeOutsourced: outsourcedHourly * 1.5,
        differenceHourly: internalTrueHourly - outsourcedHourly,
        differenceWeekly: internalWeekly - outsourcedWeekly,
        differenceMonthly: internalMonthly - outsourcedMonthly,
        differenceAnnual: internalAnnual - outsourcedAnnual,
        differencePerProfessionalAnnual: annualCostPerProfessionalIn - annualCostPerProfessionalOut,
        differencePerProfessionalHourly: hourlyPerProfessionalIn - hourlyPerProfessionalOut,
        budgetNumeric,
        budgetGap,
    };
}

function buildSummaryText(state, results) {
    return [
        'GASQ Instant Estimator Summary',
        '',
        `Requester: ${state.name || '-'}`,
        `Company: ${state.company || '-'}`,
        `Job Title: ${state.contactJobTitle || '-'}`,
        `Property / Site Name: ${state.propertySiteName || '-'}`,
        `Role: ${state.role || '-'}`,
        `Primary Email: ${state.email || '-'}`,
        `Phone: ${state.phone || '-'}`,
        `Location: ${state.location || '-'}`,
        `Website: ${state.companyWebsite || '-'}`,
        `Property Type: ${state.propertyType || '-'}`,
        `Current Security Setup: ${state.currentSecuritySetup || '-'}`,
        `Service Start Timeline: ${state.serviceStartTimeline || '-'}`,
        `Service: ${results.serviceMeta.label}`,
        `Baseline Pay: ${fmtCurrency(state.selectedRate)}/hr`,
        `Coverage Model: ${state.coverageModel === 'checks' ? 'Weekly checks' : 'Coverage hours'}`,
        `Coverage Term: ${results.weeksCoveredRounded} weeks / ${results.monthsOfCoverageRounded} months`,
        `Weekly Coverage Hours: ${fmtNumber(results.weeklyCoverageHours, 0)}`,
        `Outsourced Hourly: ${fmtCurrency(results.outsourcedHourly)}`,
        `Outsourced Term Cost: ${fmtCurrency(results.outsourcedTerm)}`,
        `Internal True Hourly: ${fmtCurrency(results.internalTrueHourly)}`,
        `Internal Term Cost: ${fmtCurrency(results.internalTerm)}`,
        `Recovered Capital: ${fmtCurrency(results.recoveredCapitalTerm)}`,
        `Appraisal Fee: ${fmtCurrency(results.appraisalFee)}`,
        `Efficiency Gain: ${fmtNumber(results.efficiencyGain, 0)} : 1`,
        `Payback: ${fmtNumber(results.breakevenMonths)} months`,
        `Decision Maker: ${state.decisionMaker}`,
        `Budget Approved: ${state.approvedBudget}`,
        `Budget Amount: ${state.budgetAmount || '-'}`,
        `Notes: ${state.notes || '-'}`,
        `Attachments: ${state.attachments.join(', ') || '-'}`,
        `Send to Vendor Network: ${state.sendToVendorNetwork ? 'Yes' : 'No'}`,
    ].join('\n');
}

function buildScenarioPayload(state) {
    const weeklyHours = state.coverageModel === 'checks'
        ? (state.staffPerCheck * state.minutesPerCheck * (CHECK_OPTIONS[state.weeklyChecks]?.visitsPerWeek || 21)) / 60
        : state.hoursPerDay * state.daysPerWeek * state.staffPerShift;

    return {
        meta: {
            role: state.role,
            location: state.location,
            locationState: normalizeLocationState(state.location),
            company: state.company,
            requesterName: state.name,
            contactJobTitle: state.contactJobTitle,
            propertySiteName: state.propertySiteName,
            requesterEmail: state.email,
            requesterPhone: state.phone,
            companyWebsite: state.companyWebsite,
            propertyType: state.propertyType,
            currentSecuritySetup: state.currentSecuritySetup,
            serviceStartTimeline: state.serviceStartTimeline,
            serviceType: state.serviceType,
            selectedRate: state.selectedRate,
            coverageModel: state.coverageModel,
            hoursPerDay: state.hoursPerDay,
            daysPerWeek: state.daysPerWeek,
            weeks: state.weeks,
            staffPerShift: state.staffPerShift,
            weeklyChecks: state.weeklyChecks,
            minutesPerCheck: state.minutesPerCheck,
            staffPerCheck: state.staffPerCheck,
            decisionMaker: state.decisionMaker,
            approvedBudget: state.approvedBudget,
            budgetAmount: state.budgetAmount,
            notes: state.notes,
            wantsComparison: state.wantsComparison,
            sendToVendorNetwork: state.sendToVendorNetwork,
            attachments: state.attachments,
            payRates: state.payRates,
            hoursPerWeek: weeklyHours,
            guards: state.coverageModel === 'checks' ? state.staffPerCheck : state.staffPerShift,
        },
        posts: [
            {
                postName: 'Instant Estimator Post',
                positionTitle: state.serviceType,
                weeklyHours: weeklyHours,
                qtyRequired: state.coverageModel === 'checks' ? state.staffPerCheck : state.staffPerShift,
            },
        ],
    };
}

function buildReportResult(state, results) {
    return {
        request: {
            name: state.name,
            company: state.company,
            contactJobTitle: state.contactJobTitle,
            propertySiteName: state.propertySiteName,
            email: state.email,
            phone: state.phone,
            location: state.location,
            website: state.companyWebsite,
            propertyType: state.propertyType,
            currentSecuritySetup: state.currentSecuritySetup,
            serviceStartTimeline: state.serviceStartTimeline,
            budgetAmount: state.budgetAmount,
            notes: state.notes,
            attachments: state.attachments,
            decisionMaker: state.decisionMaker,
            approvedBudget: state.approvedBudget,
        },
        kpis: {
            serviceType: state.serviceType,
            serviceLabel: results.serviceMeta.label,
            recommendedMin: results.serviceMeta.min,
            recommendedMax: results.serviceMeta.max,
            coverageModel: state.coverageModel,
            selectedCheckDefinition: results.selectedCheckOption.definition,
            weeksCoveredRounded: results.weeksCoveredRounded,
            monthsOfCoverage: Number(results.monthsOfCoverageRaw.toFixed(2)),
            monthsOfCoverageRounded: results.monthsOfCoverageRounded,
            weeklyCoverageHours: Number(results.weeklyCoverageHours.toFixed(2)),
            monthlyCoverageHours: Number(results.monthlyCoverageHours.toFixed(2)),
            annualCoverageHours: Number(results.annualCoverageHours.toFixed(2)),
            termCoverageHours: Number(results.termCoverageHours.toFixed(2)),
            totalWorkforceRequired: results.totalWorkforceRequired,
            annualizedFte: Number(results.annualizedFTE.toFixed(2)),
            directLabor: Number(results.directLabor.toFixed(2)),
            employerCost: Number(results.employerCost.toFixed(2)),
            annualEmployerCost: Number(results.annualEmployerCost.toFixed(2)),
            internalTrueHourly: Number(results.internalTrueHourly.toFixed(2)),
            outsourcedHourly: Number(results.outsourcedHourly.toFixed(2)),
            outsourcedWeekly: Number(results.outsourcedWeekly.toFixed(2)),
            outsourcedMonthly: Number(results.outsourcedMonthly.toFixed(2)),
            outsourcedAnnual: Number(results.outsourcedAnnual.toFixed(2)),
            outsourcedTerm: Number(results.outsourcedTerm.toFixed(2)),
            internalWeekly: Number(results.internalWeekly.toFixed(2)),
            internalMonthly: Number(results.internalMonthly.toFixed(2)),
            internalAnnual: Number(results.internalAnnual.toFixed(2)),
            internalTerm: Number(results.internalTerm.toFixed(2)),
            annualCostPerProfessionalOut: Number(results.annualCostPerProfessionalOut.toFixed(2)),
            annualCostPerProfessionalIn: Number(results.annualCostPerProfessionalIn.toFixed(2)),
            hourlyPerProfessionalOut: Number(results.hourlyPerProfessionalOut.toFixed(2)),
            hourlyPerProfessionalIn: Number(results.hourlyPerProfessionalIn.toFixed(2)),
            costPerMinuteOut: Number(results.costPerMinuteOut.toFixed(2)),
            costPerMinuteIn: Number(results.costPerMinuteIn.toFixed(2)),
            recoveredCapitalAnnual: Number(results.recoveredCapitalAnnual.toFixed(2)),
            recoveredCapitalTerm: Number(results.recoveredCapitalTerm.toFixed(2)),
            appraisalFee: Number(results.appraisalFee.toFixed(2)),
            efficiencyGain: Number(results.efficiencyGain.toFixed(2)),
            breakevenMonths: Number(results.breakevenMonths.toFixed(2)),
            differenceHourly: Number(results.differenceHourly.toFixed(2)),
            differenceWeekly: Number(results.differenceWeekly.toFixed(2)),
            differenceMonthly: Number(results.differenceMonthly.toFixed(2)),
            differenceAnnual: Number(results.differenceAnnual.toFixed(2)),
            differencePerProfessionalAnnual: Number(results.differencePerProfessionalAnnual.toFixed(2)),
            differencePerProfessionalHourly: Number(results.differencePerProfessionalHourly.toFixed(2)),
            overtimeInHouse: Number(results.overtimeInHouse.toFixed(2)),
            overtimeOutsourced: Number(results.overtimeOutsourced.toFixed(2)),
            estimatedHourlyRate: Number(results.outsourcedHourly.toFixed(2)),
            estimatedWeeklyTotal: Number(results.outsourcedWeekly.toFixed(2)),
            estimatedMonthlyTotal: Number(results.outsourcedMonthly.toFixed(2)),
            estimatedAnnualTotal: Number(results.outsourcedAnnual.toFixed(2)),
        },
    };
}

function showStatus(type, message) {
    const alert = byId('instantEstimatorStatus');
    alert.className = `alert alert-${type}`;
    alert.textContent = message;
    alert.classList.remove('d-none');
}

function clearStatus() {
    byId('instantEstimatorStatus').classList.add('d-none');
}

function updateRateLibraryInputs() {
    document.querySelectorAll('.rate-library-input').forEach(input => {
        const key = input.dataset.rateKey;
        input.value = estimatorDraft.payRates[key] ?? DEFAULT_PAY[key] ?? 0;
    });
}

function persistDraft(state) {
    const payload = {
        ...state,
        attachments: [],
    };

    try {
        window.localStorage.setItem(STORAGE_KEY, JSON.stringify(payload));
    } catch (error) {
        console.warn('Unable to persist instant estimator draft.', error);
    }
}

function renderAttachmentList(state) {
    const container = byId('attachmentList');
    container.innerHTML = '';

    if (state.attachments.length === 0) {
        return;
    }

    state.attachments.forEach(name => {
        const item = document.createElement('span');
        item.className = 'est-file-tag';
        item.innerHTML = `<i class="fa fa-paperclip"></i><span>${name}</span>`;
        container.appendChild(item);
    });
}

function renderReadinessSignals(state, results) {
    const container = byId('readinessSignals');
    container.innerHTML = '';

    const serviceRateBand = rateBand(state.selectedRate, results.serviceMeta);
    const entries = [
        {
            label: state.decisionMaker === 'yes' ? 'Decision maker confirmed' : 'Needs decision-maker alignment',
            className: state.decisionMaker === 'yes' ? 'est-chip-good' : 'est-chip-warn',
        },
        {
            label: state.approvedBudget === 'yes'
                ? 'Budget approved'
                : (state.approvedBudget === 'considering' ? 'Budget under review' : 'Budget not approved'),
            className: state.approvedBudget === 'yes' ? 'est-chip-good' : 'est-chip-warn',
        },
        {
            label: serviceRateBand.label,
            className: serviceRateBand.className,
        },
        {
            label: state.coverageModel === 'checks'
                ? `${results.selectedCheckOption.label} selected`
                : `${fmtNumber(results.weeklyCoverageHours, 0)} weekly coverage hours`,
            className: 'est-chip-neutral',
        },
    ];

    entries.forEach(entry => {
        const chip = document.createElement('span');
        chip.className = `est-chip ${entry.className}`;
        chip.textContent = entry.label;
        container.appendChild(chip);
    });
}

function render() {
    const state = collectState();
    estimatorDraft.payRates[state.serviceType] = state.selectedRate;
    const results = computeResults(state);
    const serviceBand = rateBand(state.selectedRate, results.serviceMeta);
    const readiness = readinessMeta(state);

    byId('hoursCoverageGroup').classList.toggle('d-none', state.coverageModel !== 'hours');
    byId('checksCoverageGroup').classList.toggle('d-none', state.coverageModel !== 'checks');
    byId('roiSection').classList.toggle('d-none', !state.wantsComparison);
    byId('weeklyChecksDefinition').textContent = results.selectedCheckOption.definition;

    byId('heroRecommendedRange').textContent = `${fmtCurrency(results.serviceMeta.min)} – ${fmtCurrency(results.serviceMeta.max)}`;
    byId('heroServiceLabel').textContent = results.serviceMeta.label;
    byId('heroRateBand').textContent = serviceBand.label;
    byId('heroRateBand').className = `est-chip est-chip-accent`;

    byId('summaryReadinessBadge').textContent = readiness.label;
    byId('summarySubtitle').textContent = state.wantsComparison
        ? 'Outsourced rate, internal true cost, and recovered capital'
        : 'Directional outsourced pricing based on your selected pay baseline';
    byId('outsourcedTermHeadline').textContent = fmtCurrency(results.outsourcedTerm);
    byId('coverageHeadline').textContent = `${results.weeksCoveredRounded} wks · ${results.monthsOfCoverageRounded} mo · ${fmtNumber(results.termCoverageHours, 0)} hrs`;
    byId('statOutsourcedHourly').textContent = fmtCurrency(results.outsourcedHourly);
    byId('statInternalHourly').textContent = fmtCurrency(results.internalTrueHourly);
    byId('statWeeklyCoverage').textContent = fmtNumber(results.weeklyCoverageHours, 0);
    byId('statWorkforce').textContent = String(results.totalWorkforceRequired);

    byId('budgetAlignmentText').textContent = results.budgetNumeric > 0
        ? (results.budgetGap >= 0
            ? `${fmtCurrency(results.budgetGap)} remaining vs outsourced term`
            : `${fmtCurrency(Math.abs(results.budgetGap))} short vs outsourced term`)
        : 'Budget not entered yet';

    renderReadinessSignals(state, results);

    byId('outHourly').textContent = fmtCurrency(results.outsourcedHourly);
    byId('outWeekly').textContent = fmtCurrency(results.outsourcedWeekly);
    byId('outMonthly').textContent = fmtCurrency(results.outsourcedMonthly);
    byId('outAnnual').textContent = fmtCurrency(results.outsourcedAnnual);
    byId('outTerm').textContent = fmtCurrency(results.outsourcedTerm);

    byId('inHourly').textContent = fmtCurrency(results.internalTrueHourly);
    byId('inWeekly').textContent = fmtCurrency(results.internalWeekly);
    byId('inMonthly').textContent = fmtCurrency(results.internalMonthly);
    byId('inAnnual').textContent = fmtCurrency(results.internalAnnual);
    byId('inTerm').textContent = fmtCurrency(results.internalTerm);

    byId('recoveredCapital').textContent = fmtCurrency(results.recoveredCapitalTerm);
    byId('appraisalFee').textContent = fmtCurrency(results.appraisalFee);
    byId('efficiencyGain').textContent = `${fmtNumber(results.efficiencyGain, 0)} : 1`;
    byId('paybackMonths').textContent = `${fmtNumber(results.breakevenMonths)} months`;

    byId('reportRequester').textContent = state.name || '-';
    byId('reportCompany').textContent = state.company || '-';
    byId('reportLocation').textContent = state.location || '-';
    byId('reportService').textContent = results.serviceMeta.label;
    byId('reportRate').textContent = `${fmtCurrency(state.selectedRate)}/hr`;
    byId('reportBudget').textContent = state.budgetAmount || '-';
    byId('reportAttachments').textContent = state.attachments.join(', ') || '-';

    renderAttachmentList(state);
    updateRateLibraryInputs();
    persistDraft(state);
    clearStatus();

    window.__gasqInstantEstimator = {
        state,
        results,
        summary: buildSummaryText(state, results),
    };
}

function applyDraft(draft) {
    if (!draft || typeof draft !== 'object') {
        return;
    }

    estimatorDraft.payRates = {
        ...DEFAULT_PAY,
        ...(draft.payRates || {}),
    };

    const safeServiceType = draft.serviceType && SERVICE_TYPES[draft.serviceType] ? draft.serviceType : 'unarmed';

    byId('role').value = draft.role || 'buyer';
    byId('name').value = draft.name || '';
    byId('company').value = draft.company || '';
    byId('contactJobTitle').value = draft.contactJobTitle || '';
    byId('propertySiteName').value = draft.propertySiteName || '';
    byId('email').value = draft.email || '';
    byId('phone').value = draft.phone || '';
    byId('location').value = draft.location || draft.locationState || '';
    byId('companyWebsite').value = draft.companyWebsite || '';
    byId('propertyType').value = draft.propertyType || '';
    byId('currentSecuritySetup').value = draft.currentSecuritySetup || '';
    byId('serviceStartTimeline').value = draft.serviceStartTimeline || '';
    byId('serviceType').value = safeServiceType;
    byId('coverageModel').value = draft.coverageModel || 'hours';
    byId('hoursPerDay').value = draft.hoursPerDay || 8;
    byId('daysPerWeek').value = draft.daysPerWeek || 5;
    byId('weeks').value = draft.weeks || 52;
    byId('staffPerShift').value = draft.staffPerShift || draft.guards || 1;
    byId('weeklyChecks').value = String(draft.weeklyChecks || '21');
    byId('minutesPerCheck').value = draft.minutesPerCheck || 15;
    byId('staffPerCheck').value = draft.staffPerCheck || 1;
    byId('wantsComparison').checked = draft.wantsComparison !== false;
    byId('decisionMaker').value = draft.decisionMaker || 'yes';
    byId('approvedBudget').value = draft.approvedBudget || 'yes';
    byId('budgetAmount').value = draft.budgetAmount || '';
    byId('notes').value = draft.notes || '';
    byId('additionalEmails').value = draft.additionalEmails || '';
    byId('ccEmails').value = draft.ccEmails || '';
    byId('bccEmails').value = draft.bccEmails || '';
    byId('sendToVendorNetwork').checked = Boolean(draft.sendToVendorNetwork);
    byId('selectedRate').value = draft.selectedRate || estimatorDraft.payRates[safeServiceType] || DEFAULT_PAY[safeServiceType];

    updateRateLibraryInputs();
}

function deriveDraftFromSavedScenario() {
    const savedScenario = window.__gasqCalculatorState?.scenario || {};
    const meta = savedScenario.meta || {};

    if (Object.keys(meta).length === 0) {
        return null;
    }

    const hoursPerWeek = toNumber(meta.hoursPerWeek ?? meta.hours, 40);
    const guessedDays = hoursPerWeek >= 56 ? 7 : 5;
    const guessedHours = clamp(hoursPerWeek / guessedDays, 8, 24);

    return {
        role: meta.role || 'buyer',
        location: humanizeLocation(meta.locationState || meta.location || ''),
        contactJobTitle: meta.contactJobTitle || '',
        propertySiteName: meta.propertySiteName || '',
        serviceType: meta.serviceType || 'unarmed',
        coverageModel: meta.coverageModel || 'hours',
        propertyType: meta.propertyType || '',
        currentSecuritySetup: meta.currentSecuritySetup || '',
        serviceStartTimeline: meta.serviceStartTimeline || '',
        hoursPerDay: meta.hoursPerDay || guessedHours,
        daysPerWeek: meta.daysPerWeek || guessedDays,
        weeks: meta.weeks || 52,
        staffPerShift: meta.staffPerShift || meta.guards || 1,
        weeklyChecks: meta.weeklyChecks || '21',
        minutesPerCheck: meta.minutesPerCheck || 15,
        staffPerCheck: meta.staffPerCheck || 1,
        selectedRate: meta.selectedRate || DEFAULT_PAY[meta.serviceType || 'unarmed'],
        decisionMaker: meta.decisionMaker || 'yes',
        approvedBudget: meta.approvedBudget || 'yes',
        wantsComparison: meta.wantsComparison !== false,
        payRates: {
            ...DEFAULT_PAY,
            ...(meta.payRates || {}),
        },
    };
}

function loadInitialDraft() {
    try {
        const stored = window.localStorage.getItem(STORAGE_KEY);
        if (stored) {
            return JSON.parse(stored);
        }
    } catch (error) {
        console.warn('Unable to read instant estimator draft.', error);
    }

    return deriveDraftFromSavedScenario();
}

async function copySummary() {
    const payload = window.__gasqInstantEstimator;
    if (!payload) {
        return;
    }

    try {
        await navigator.clipboard.writeText(payload.summary);
        showStatus('success', 'Estimate summary copied to the clipboard.');
    } catch (error) {
        showStatus('warning', 'Clipboard copy was blocked in this browser. You can still use Email report or Print.');
    }
}

async function persistReportPayload() {
    const payload = window.__gasqInstantEstimator;
    if (!payload) {
        throw new Error('No estimator payload available.');
    }

    const response = await fetch(REPORT_PAYLOAD_URL, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'Accept': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
        },
        body: JSON.stringify({
            type: REPORT_TYPE,
            scenario: buildScenarioPayload(payload.state),
            result: buildReportResult(payload.state, payload.results),
        }),
    });

    if (!response.ok) {
        throw new Error('Unable to prepare the report payload.');
    }
}

async function downloadReport() {
    try {
        await persistReportPayload();
        window.location.href = REPORT_DOWNLOAD_URL;
    } catch (error) {
        showStatus('danger', 'We could not prepare the PDF right now. Please try again.');
    }
}

async function emailPlatformReport() {
    const payload = window.__gasqInstantEstimator;
    if (!payload) {
        return;
    }

    const primaryEmail = payload.state.email || @json(auth()->user()?->email);
    if (!primaryEmail) {
        showStatus('warning', 'Enter a primary email before sending the platform report.');
        return;
    }

    try {
        await persistReportPayload();
        byId('instantEstimatorEmailTarget').value = primaryEmail;
        byId('instantEstimatorEmailForm').submit();
    } catch (error) {
        showStatus('danger', 'We could not prepare the report email right now. Please try again.');
    }
}

function resetEstimator() {
    estimatorDraft = {
        payRates: { ...DEFAULT_PAY },
    };

    try {
        window.localStorage.removeItem(STORAGE_KEY);
    } catch (error) {
        console.warn('Unable to clear instant estimator draft.', error);
    }

    applyDraft({
        role: 'buyer',
        serviceType: 'unarmed',
        selectedRate: DEFAULT_PAY.unarmed,
        coverageModel: 'hours',
        hoursPerDay: 8,
        daysPerWeek: 5,
        weeks: 52,
        staffPerShift: 1,
        weeklyChecks: '21',
        minutesPerCheck: 15,
        staffPerCheck: 1,
        wantsComparison: true,
        decisionMaker: 'yes',
        approvedBudget: 'yes',
        payRates: { ...DEFAULT_PAY },
    });

    byId('attachments').value = '';
    byId('continueToJobButton').classList.add('d-none');
    byId('gateFeeAmount').textContent = '';
    byId('gateAppraisalFeeDisplay').textContent = fmtCurrency(0);
    render();
    setStepState(1);
    showStatus('success', 'Estimator reset to default values.');
}

function setStepState(step) {
    const panel1 = byId('estPanel1');
    const panel2 = byId('estPanel2');
    const gate = byId('estGate');
    const panel3 = byId('estPanel3');
    const step1 = byId('stepInd1');
    const step2 = byId('stepInd2');
    const step3 = byId('stepInd3');
    const reportTab = document.querySelector('[href="#tab-report"]');

    panel1.classList.toggle('d-none', step !== 1);
    panel2.classList.toggle('d-none', step !== 2);
    gate.classList.toggle('d-none', step !== 'gate');
    panel3.classList.toggle('d-none', step !== 3);

    step1.classList.toggle('active', step === 1);
    step2.classList.toggle('active', step === 2 || step === 'gate' || step === 3);
    step3.classList.toggle('active', step === 3);
    step3.classList.toggle('locked', step !== 3);

    if (reportTab) {
        reportTab.classList.toggle('disabled', step !== 3);
        reportTab.setAttribute('aria-disabled', step !== 3 ? 'true' : 'false');
    }
}

function validateFields(fieldIds) {
    let firstInvalid = null;

    fieldIds.forEach(id => {
        const element = byId(id);
        if (!element) {
            return;
        }

        const rawValue = element.type === 'checkbox' ? (element.checked ? '1' : '') : String(element.value || '').trim();
        const isValid = rawValue !== '';

        element.classList.toggle('is-invalid', !isValid);

        if (!isValid && !firstInvalid) {
            firstInvalid = element;
        }
    });

    if (firstInvalid) {
        firstInvalid.focus();
    }

    return !firstInvalid;
}

function validateStepOne() {
    const valid = validateFields([
        'name',
        'company',
        'contactJobTitle',
        'propertySiteName',
        'email',
        'phone',
        'location',
        'propertyType',
        'currentSecuritySetup',
        'serviceStartTimeline',
        'decisionMaker',
        'approvedBudget',
    ]);

    if (!valid) {
        showStatus('warning', 'Complete the buyer questionnaire in Step 1 before continuing to the calculation.');
    }

    return valid;
}

function validateStepTwo() {
    const valid = validateFields([
        'serviceType',
        'selectedRate',
        'coverageModel',
        'weeks',
    ]);

    if (!valid) {
        showStatus('warning', 'Complete the pricing inputs in Step 2 before trying to unlock the results.');
        return false;
    }

    const state = collectState();
    if (state.coverageModel === 'hours') {
        const hoursValid = validateFields(['hoursPerDay', 'daysPerWeek', 'staffPerShift']);
        if (!hoursValid) {
            showStatus('warning', 'Enter the coverage-hour inputs before continuing.');
            return false;
        }
    } else {
        const checksValid = validateFields(['weeklyChecks', 'minutesPerCheck', 'staffPerCheck']);
        if (!checksValid) {
            showStatus('warning', 'Enter the weekly-check inputs before continuing.');
            return false;
        }
    }

    return true;
}

function unlockResults(choice) {
    const continueToJobButton = byId('continueToJobButton');
    setStepState(3);

    if (choice === 'post-job') {
        continueToJobButton.classList.remove('d-none');
        showStatus('success', 'Job draft prepared. Your estimate is now unlocked and your buyer questionnaire is ready to continue in the job-posting flow.');
    } else if (choice === 'fee') {
        continueToJobButton.classList.add('d-none');
        showStatus('success', 'Fee path selected. Your Step 3 estimate is now unlocked.');
    }
}

async function prepareJobDraftFromEstimator() {
    const state = collectState();
    const response = await fetch(PREPARE_JOB_URL, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'Accept': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
        },
        body: JSON.stringify({
            service_type: state.serviceType,
            location: state.location,
            title: `${selectedServiceMeta(state.serviceType).label} request for ${state.location}`.trim(),
            contact_name: state.name,
            contact_job_title: state.contactJobTitle,
            organization_name: state.company,
            property_site_name: state.propertySiteName,
            contact_email: state.email,
            contact_phone: state.phone,
            business_address: state.location,
            final_decision_maker: state.decisionMaker === 'yes' ? 'yes' : 'no',
            funds_approval_status: state.approvedBudget === 'yes'
                ? 'flexible_budget'
                : (state.approvedBudget === 'considering' ? 'pending' : 'no_approved_budget'),
            move_forward_if_accepted: 'yes',
            property_type: state.propertyType,
            current_security_setup: state.currentSecuritySetup,
            service_start_timeline: state.serviceStartTimeline,
            primary_reason: state.notes,
            notes: state.notes,
            budget_amount_range: state.budgetAmount,
            hours_per_day: state.hoursPerDay,
            days_per_week: state.daysPerWeek,
            weeks_per_year: state.weeks,
            guards_per_shift: state.coverageModel === 'checks' ? state.staffPerCheck : state.staffPerShift,
            cost_comparison_requested: state.wantsComparison ? 'yes' : 'no',
        }),
    });

    if (response.redirected && response.url) {
        window.location.href = response.url;
        return null;
    }

    if (response.status === 401 || response.status === 419) {
        window.location.href = LOGIN_URL;
        return null;
    }

    const responseType = response.headers.get('content-type') || '';
    const data = responseType.includes('application/json')
        ? await response.json().catch(() => ({}))
        : {};

    if (!response.ok) {
        throw new Error(data.message || 'We could not prepare the job draft right now.');
    }

    const continueToJobButton = byId('continueToJobButton');
    const jobCreateUrl = data.job_create_url || continueToJobButton.getAttribute('href');
    continueToJobButton.setAttribute('href', jobCreateUrl);

    return {
        ...data,
        job_create_url: jobCreateUrl,
    };
}

function bindEvents() {
    document.querySelectorAll('input, select, textarea').forEach(element => {
        if (element.classList.contains('rate-library-input')) {
            return;
        }

        const eventName = element.type === 'checkbox' ? 'change' : 'input';
        element.addEventListener(eventName, () => {
            if (element.id === 'serviceType') {
                const serviceType = element.value || 'unarmed';
                byId('selectedRate').value = estimatorDraft.payRates[serviceType] ?? DEFAULT_PAY[serviceType] ?? 0;
            }

            render();
        });

        if (eventName !== 'change') {
            element.addEventListener('change', render);
        }
    });

    document.querySelectorAll('.rate-library-input').forEach(input => {
        input.addEventListener('input', event => {
            const key = event.currentTarget.dataset.rateKey;
            estimatorDraft.payRates[key] = clamp(toNumber(event.currentTarget.value, DEFAULT_PAY[key] ?? 0), 0, 100000);

            if (byId('serviceType').value === key) {
                byId('selectedRate').value = estimatorDraft.payRates[key];
            }

            render();
        });
    });

    byId('attachments').addEventListener('change', render);
    byId('copySummaryButton').addEventListener('click', copySummary);
    byId('downloadReportButton').addEventListener('click', downloadReport);
    byId('emailReportButton').addEventListener('click', emailPlatformReport);
    byId('printReportButton').addEventListener('click', () => window.print());
    byId('resetEstimatorButton').addEventListener('click', resetEstimator);

    byId('goToStep2Btn').addEventListener('click', () => {
        if (!validateStepOne()) {
            return;
        }
        setStepState(2);
        clearStatus();
    });

    byId('backToStep1Btn').addEventListener('click', () => setStepState(1));
    byId('viewResultsBtn').addEventListener('click', () => {
        if (!validateStepTwo()) {
            return;
        }

        const appraisalFee = window.__gasqInstantEstimator?.results?.appraisalFee ?? 0;
        byId('gateAppraisalFeeDisplay').textContent = fmtCurrency(appraisalFee);
        byId('gateFeeAmount').textContent = fmtCurrency(appraisalFee);
        setStepState('gate');
    });

    byId('backToStep2Btn').addEventListener('click', () => setStepState(2));
    byId('backToStep2FromResults').addEventListener('click', () => setStepState(2));

    byId('gateFeeBtn').addEventListener('click', () => unlockResults('fee'));
    byId('gatePostJobBtn').addEventListener('click', async event => {
        const trigger = event.currentTarget;

        if (!IS_AUTHENTICATED) {
            window.location.href = LOGIN_URL;
            return;
        }

        if (!CAN_PREPARE_JOB) {
            showStatus('warning', 'Post Job is available for buyer accounts. Sign in with a buyer account to continue into the job offer flow.');
            return;
        }

        try {
            trigger.disabled = true;
            showStatus('info', 'Preparing your buyer questionnaire and sending you to the job-posting form...');

            const data = await prepareJobDraftFromEstimator();

            if (data?.job_create_url) {
                window.location.href = data.job_create_url;
                return;
            }

            unlockResults('post-job');
        } catch (error) {
            showStatus('danger', error.message || 'We could not prepare the job draft right now.');
        } finally {
            trigger.disabled = false;
        }
    });

    document.querySelectorAll('#stepInd1, #stepInd2').forEach(element => {
        element.addEventListener('click', () => {
            if (element.id === 'stepInd1') {
                setStepState(1);
            }
            if (element.id === 'stepInd2') {
                setStepState(2);
            }
        });
    });

    const reportTab = document.querySelector('[href="#tab-report"]');
    if (reportTab) {
        reportTab.addEventListener('click', event => {
            if (byId('estPanel3').classList.contains('d-none')) {
                event.preventDefault();
                showStatus('warning', 'Step 3 is locked. Choose Pay 1% Fee or Post a Job to reveal the final numbers and report view.');
            }
        });
    }
}

document.addEventListener('DOMContentLoaded', () => {
    applyDraft(loadInitialDraft() || {
        role: 'buyer',
        serviceType: 'unarmed',
        selectedRate: DEFAULT_PAY.unarmed,
        coverageModel: 'hours',
        hoursPerDay: 8,
        daysPerWeek: 5,
        weeks: 52,
        staffPerShift: 1,
        weeklyChecks: '21',
        minutesPerCheck: 15,
        staffPerCheck: 1,
        wantsComparison: true,
        decisionMaker: 'yes',
        approvedBudget: 'yes',
        payRates: { ...DEFAULT_PAY },
    });

    bindEvents();
    render();
    setStepState(1);
});
</script>
@endpush

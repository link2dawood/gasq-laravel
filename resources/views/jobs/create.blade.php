@extends('layouts.app')

@section('title', 'Post Your Security Service Request')

@section('content')
@php
    $starter = $starter ?? [];
    $showDetailsStep = $showDetailsStep ?? false;
    $progressSections = [
        'Contact Information',
        'Decision Authority',
        'Service Location',
        'Service Request Details',
        'Schedule and Staffing',
        'Duties and Site Conditions',
        'Budget and Offer Terms',
        'Compliance Requirements',
        'Posting Terms and Submission',
    ];
    $selectedReadinessReasons = old('project_readiness_reasons', []);
    $selectedServices = old('service_types', $starter['service_types'] ?? []);
    $selectedShifts = old('shifts_needed', []);
    $selectedPatrolTypes = old('patrol_types', []);
    $selectedDuties = old('duties_required', []);
    $selectedPricingActions = old('if_pricing_exceeds', []);
    $selectedInsuranceMinimums = old('insurance_minimums_required', []);
@endphp

<div class="container py-4">
    <h1 class="h2 mb-2">Post Your Security Service Request</h1>
    <p class="text-gasq-muted mb-4">
        Start with your service and site details, then complete the buyer questionnaire so GASQ can build your security job offer announcement and invite qualified vendors to respond.
    </p>

    @if(! $showDetailsStep)
        <x-card title="Step 1: Service and Job Site">
            <p class="text-gasq-muted mb-4">
                Tell us what type of security service you need and where the work will happen. After this quick step, we will ask the full buyer questionnaire.
            </p>

            <form action="{{ route('jobs.create.start') }}" method="POST">
                @csrf

                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label">What type of security service are you requesting? <span class="text-danger">*</span></label>
                        <select name="starter_service_type" id="starter_service_type" class="form-select @error('starter_service_type') is-invalid @enderror" required>
                            <option value="">Choose...</option>
                            @foreach($starterServiceOptions as $serviceOption)
                                <option value="{{ $serviceOption }}" @selected(old('starter_service_type', $starter['starter_service_type'] ?? '') === $serviceOption)>{{ $serviceOption }}</option>
                            @endforeach
                        </select>
                        @error('starter_service_type')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-6 mb-3" id="starter_service_type_other_wrap">
                        <label class="form-label">If Other, please specify</label>
                        <input type="text" name="starter_service_type_other" class="form-control @error('starter_service_type_other') is-invalid @enderror" value="{{ old('starter_service_type_other', $starter['starter_service_type_other'] ?? '') }}">
                        @error('starter_service_type_other')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-4 mb-3">
                        <label class="form-label">Postal Code</label>
                        <input type="text" name="zip_code" class="form-control @error('zip_code') is-invalid @enderror" value="{{ old('zip_code', $starter['zip_code'] ?? '') }}">
                        @error('zip_code')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-8 mb-3">
                        @include('jobs.partials.location-fields', [
                            'suffix' => 'starter',
                            'location' => old('location', $starter['location'] ?? ''),
                            'latitude' => old('latitude', $starter['latitude'] ?? null),
                            'longitude' => old('longitude', $starter['longitude'] ?? null),
                            'googlePlaceId' => old('google_place_id', $starter['google_place_id'] ?? ''),
                        ])
                    </div>
                </div>

                <div class="d-flex gap-2">
                    <button type="submit" class="btn btn-primary">Continue to Buyer Questionnaire</button>
                    <a href="{{ route('job-board') }}" class="btn btn-outline-secondary">Cancel</a>
                </div>
            </form>
        </x-card>
    @else
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-body">
                <div class="small text-uppercase text-gasq-muted fw-semibold mb-3">Step 2: Buyer Questionnaire</div>
                <div class="d-flex flex-wrap gap-2 mb-3">
                    @foreach($progressSections as $index => $section)
                        <span class="badge rounded-pill text-bg-light border px-3 py-2">{{ $index + 1 }}. {{ $section }}</span>
                    @endforeach
                </div>
                <div class="row g-3 align-items-end">
                    <div class="col-md-4">
                        <div class="small text-uppercase text-gasq-muted fw-semibold">Requested Service</div>
                        <div>{{ $starter['service_label'] ?? 'Not set' }}</div>
                    </div>
                    <div class="col-md-4">
                        <div class="small text-uppercase text-gasq-muted fw-semibold">Postal Code</div>
                        <div>{{ ($starter['zip_code'] ?? '') !== '' ? $starter['zip_code'] : 'Not provided' }}</div>
                    </div>
                    <div class="col-md-4 text-md-end">
                        <a href="{{ route('jobs.create') }}" class="btn btn-outline-secondary btn-sm">Edit Service and Job Site</a>
                    </div>
                    <div class="col-12">
                        <div class="small text-uppercase text-gasq-muted fw-semibold">Service Address</div>
                        <div>{{ $starter['location'] ?? 'Not set' }}</div>
                    </div>
                </div>
            </div>
        </div>

        <x-card title="Buyer Online Posting Form">
            <form action="{{ route('jobs.preview') }}" method="POST" enctype="multipart/form-data">
                @csrf

                <input type="hidden" name="category" value="{{ old('category', $starter['category'] ?? '') }}">
                @foreach($selectedServices as $selectedService)
                    <input type="hidden" name="service_types[]" value="{{ $selectedService }}">
                @endforeach
                @if(in_array('Other', $selectedServices, true))
                    <input type="hidden" name="service_type_other" value="{{ old('service_type_other', $starter['service_type_other'] ?? '') }}">
                @endif
                <input type="hidden" name="location" value="{{ old('location', $starter['location'] ?? '') }}">
                <input type="hidden" name="zip_code" value="{{ old('zip_code', $starter['zip_code'] ?? '') }}">
                <input type="hidden" name="latitude" value="{{ old('latitude', $starter['latitude'] ?? '') }}">
                <input type="hidden" name="longitude" value="{{ old('longitude', $starter['longitude'] ?? '') }}">
                <input type="hidden" name="google_place_id" value="{{ old('google_place_id', $starter['google_place_id'] ?? '') }}">

                <h5 class="mb-3">Section 1: Contact Information</h5>
                <div class="row">
                <div class="col-md-6 mb-3">
                    <label class="form-label">Full Name <span class="text-danger">*</span></label>
                    <input type="text" name="contact_name" class="form-control @error('contact_name') is-invalid @enderror" value="{{ old('contact_name', auth()->user()->name) }}" required>
                    @error('contact_name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-6 mb-3">
                    <label class="form-label">Job Title <span class="text-danger">*</span></label>
                    <input type="text" name="contact_job_title" class="form-control @error('contact_job_title') is-invalid @enderror" value="{{ old('contact_job_title') }}" required>
                    @error('contact_job_title')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-6 mb-3">
                    <label class="form-label">Company / Property / Organization Name <span class="text-danger">*</span></label>
                    <input type="text" name="organization_name" class="form-control @error('organization_name') is-invalid @enderror" value="{{ old('organization_name', auth()->user()->company) }}" required>
                    @error('organization_name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-6 mb-3">
                    <label class="form-label">Property / Site Name <span class="text-danger">*</span></label>
                    <input type="text" name="property_site_name" class="form-control @error('property_site_name') is-invalid @enderror" value="{{ old('property_site_name') }}" required>
                    @error('property_site_name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-6 mb-3">
                    <label class="form-label">Email Address <span class="text-danger">*</span></label>
                    <input type="email" name="contact_email" class="form-control @error('contact_email') is-invalid @enderror" value="{{ old('contact_email', auth()->user()->email) }}" required>
                    @error('contact_email')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-6 mb-3">
                    <label class="form-label">Mobile Phone Number <span class="text-danger">*</span></label>
                    <input type="text" name="contact_phone" class="form-control @error('contact_phone') is-invalid @enderror" value="{{ old('contact_phone', auth()->user()->phone) }}" required>
                    @error('contact_phone')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-6 mb-3">
                    <label class="form-label">Verify Mobile Number by SMS <span class="text-danger">*</span></label>
                    <div class="d-flex flex-wrap gap-2 align-items-center">
                        @if(auth()->user()->phone_verified)
                            <span class="badge text-bg-success">SMS Verified</span>
                            <button type="button" class="btn btn-outline-success btn-sm" disabled>Verified on your account</button>
                        @else
                            <span class="badge text-bg-warning">Verification Required</span>
                            <a href="{{ route('phone.verify.show') }}" class="btn btn-outline-primary btn-sm">Verify Mobile Number by SMS</a>
                        @endif
                    </div>
                    <div class="form-text">This posting uses your existing account phone verification status.</div>
                </div>
                <div class="col-md-3 mb-3">
                    <label class="form-label">Preferred Contact Method <span class="text-danger">*</span></label>
                    <select name="preferred_contact_method" class="form-select @error('preferred_contact_method') is-invalid @enderror" required>
                        <option value="">Choose...</option>
                        <option value="email" @selected(old('preferred_contact_method') === 'email')>Email</option>
                        <option value="mobile_phone" @selected(old('preferred_contact_method') === 'mobile_phone')>Mobile Phone</option>
                        <option value="text_message" @selected(old('preferred_contact_method') === 'text_message')>Text Message</option>
                    </select>
                    @error('preferred_contact_method')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-3 mb-3">
                    <label class="form-label">Best Time to Contact You</label>
                    <select name="best_time_to_contact" class="form-select @error('best_time_to_contact') is-invalid @enderror">
                        <option value="">Choose...</option>
                        <option value="morning" @selected(old('best_time_to_contact') === 'morning')>Morning</option>
                        <option value="midday" @selected(old('best_time_to_contact') === 'midday')>Midday</option>
                        <option value="afternoon" @selected(old('best_time_to_contact') === 'afternoon')>Afternoon</option>
                        <option value="evening" @selected(old('best_time_to_contact') === 'evening')>Evening</option>
                    </select>
                    @error('best_time_to_contact')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-12 mb-3">
                    <label class="form-label">Business Address <span class="text-danger">*</span></label>
                    <textarea name="business_address" class="form-control @error('business_address') is-invalid @enderror" rows="2" required>{{ old('business_address') }}</textarea>
                    @error('business_address')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
            </div>

            <hr class="my-4">

            <h5 class="mb-3">Section 2: Decision Authority</h5>
            <div class="row">
                <div class="col-md-6 mb-3">
                    <label class="form-label">Are you the final decision maker for this security service request? <span class="text-danger">*</span></label>
                    <select name="final_decision_maker" id="final_decision_maker" class="form-select @error('final_decision_maker') is-invalid @enderror" required>
                        <option value="">Choose...</option>
                        <option value="yes" @selected(old('final_decision_maker') === 'yes')>Yes</option>
                        <option value="no" @selected(old('final_decision_maker') === 'no')>No</option>
                        <option value="authorized_representative" @selected(old('final_decision_maker') === 'authorized_representative')>I am an authorized representative</option>
                    </select>
                    @error('final_decision_maker')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-6 mb-3">
                    <label class="form-label">What is your authority to approve without additional approval? <span class="text-danger">*</span></label>
                    <select name="approval_authority" class="form-select @error('approval_authority') is-invalid @enderror" required>
                        <option value="">Choose...</option>
                        <option value="under_1000" @selected(old('approval_authority') === 'under_1000')>Under $1,000</option>
                        <option value="1000_4999" @selected(old('approval_authority') === '1000_4999')>$1,000 to $4,999</option>
                        <option value="5000_9999" @selected(old('approval_authority') === '5000_9999')>$5,000 to $9,999</option>
                        <option value="10000_24999" @selected(old('approval_authority') === '10000_24999')>$10,000 to $24,999</option>
                        <option value="25000_49999" @selected(old('approval_authority') === '25000_49999')>$25,000 to $49,999</option>
                        <option value="50000_plus" @selected(old('approval_authority') === '50000_plus')>$50,000+</option>
                        <option value="no_authority" @selected(old('approval_authority') === 'no_authority')>I do not have approval authority</option>
                    </select>
                    @error('approval_authority')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-6 mb-3" id="final_approver_wrap">
                    <label class="form-label">If you are not the final decision maker, who approves the final award?</label>
                    <input type="text" name="final_approver_name" class="form-control @error('final_approver_name') is-invalid @enderror" value="{{ old('final_approver_name') }}">
                    @error('final_approver_name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-6 mb-3">
                    <label class="form-label">Is your budget already approved? <span class="text-danger">*</span></label>
                    <select name="funds_approval_status" class="form-select @error('funds_approval_status') is-invalid @enderror" required>
                        <option value="">Choose...</option>
                        <option value="flexible_budget" @selected(old('funds_approval_status') === 'flexible_budget')>Yes</option>
                        <option value="no_approved_budget" @selected(old('funds_approval_status') === 'no_approved_budget')>No</option>
                        <option value="pending" @selected(old('funds_approval_status') === 'pending')>Pending approval</option>
                    </select>
                    @error('funds_approval_status')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-6 mb-3">
                    <label class="form-label">Are you prepared to move forward if qualified vendors accept your offer? <span class="text-danger">*</span></label>
                    <select name="move_forward_if_accepted" class="form-select @error('move_forward_if_accepted') is-invalid @enderror" required>
                        <option value="">Choose...</option>
                        <option value="yes" @selected(old('move_forward_if_accepted') === 'yes')>Yes</option>
                        <option value="no" @selected(old('move_forward_if_accepted') === 'no')>No</option>
                        <option value="need_internal_review" @selected(old('move_forward_if_accepted') === 'need_internal_review')>Need internal review first</option>
                    </select>
                    @error('move_forward_if_accepted')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-6 mb-3">
                    <label class="form-label">Do you know your in-house true cost? <span class="text-danger">*</span></label>
                    <select name="knows_true_inhouse_cost" class="form-select @error('knows_true_inhouse_cost') is-invalid @enderror" required>
                        <option value="">Choose...</option>
                        <option value="yes" @selected(old('knows_true_inhouse_cost') === 'yes')>Yes</option>
                        <option value="no" @selected(old('knows_true_inhouse_cost') === 'no')>No</option>
                    </select>
                    @error('knows_true_inhouse_cost')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-6 mb-3">
                    <label class="form-label">Have you calculated your true internal security cost? <span class="text-danger">*</span></label>
                    <select name="true_internal_cost_calculated" class="form-select @error('true_internal_cost_calculated') is-invalid @enderror" required>
                        <option value="">Choose...</option>
                        <option value="yes" @selected(old('true_internal_cost_calculated') === 'yes')>Yes</option>
                        <option value="no" @selected(old('true_internal_cost_calculated') === 'no')>No</option>
                    </select>
                    @error('true_internal_cost_calculated')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-12 mb-3">
                    <label class="form-label">Why are you requesting security services? <span class="text-danger">*</span></label>
                    <div class="d-flex flex-wrap gap-3">
                        @foreach(['new_requirement' => 'New requirement', 'replacing_provider' => 'Replacing provider', 'contract_expiring' => 'Contract expiring', 'incident_driven' => 'Incident-driven', 'budget_planning' => 'Budget planning'] as $value => $label)
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="project_readiness_reasons[]" value="{{ $value }}" id="readiness_{{ $value }}" @checked(in_array($value, $selectedReadinessReasons, true))>
                                <label class="form-check-label" for="readiness_{{ $value }}">{{ $label }}</label>
                            </div>
                        @endforeach
                    </div>
                    @error('project_readiness_reasons')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-6 mb-3">
                    <label class="form-label">When do you want services to begin? <span class="text-danger">*</span></label>
                    <select name="service_start_timeline" class="form-select @error('service_start_timeline') is-invalid @enderror" required>
                        <option value="immediate" @selected(old('service_start_timeline') === 'immediate')>Immediate</option>
                        <option value="15_days_or_less" @selected(old('service_start_timeline') === '15_days_or_less')>15 days or less</option>
                        <option value="30_days_or_less" @selected(old('service_start_timeline') === '30_days_or_less')>30 days or less</option>
                        <option value="30_60_days" @selected(old('service_start_timeline') === '30_60_days')>30-60 days</option>
                        <option value="future_planning" @selected(old('service_start_timeline') === 'future_planning')>Future planning</option>
                    </select>
                    @error('service_start_timeline')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
            </div>

            <hr class="my-4">

            <h5 class="mb-3">Section 3: Service Location</h5>
            <div class="row">
                <div class="col-12 mb-3">
                    <div class="rounded border bg-light px-3 py-3">
                        <div class="small text-uppercase text-gasq-muted fw-semibold mb-2">Quick-Start Job Site Details</div>
                        <div><strong>Service address:</strong> {{ old('location', $starter['location'] ?? '') }}</div>
                        <div><strong>Postal code:</strong> {{ old('zip_code', $starter['zip_code'] ?? '') !== '' ? old('zip_code', $starter['zip_code'] ?? '') : 'Not provided' }}</div>
                        @error('location')<div class="text-danger small mt-2">{{ $message }}</div>@enderror
                        @error('zip_code')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
                        @error('latitude')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
                        @error('longitude')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
                        <div class="mt-2">
                            <a href="{{ route('jobs.create') }}" class="btn btn-outline-secondary btn-sm">Edit Service and Job Site</a>
                        </div>
                    </div>
                </div>
                <div class="col-md-4 mb-3">
                    <label class="form-label">Is service needed at more than one location? <span class="text-danger">*</span></label>
                    <select name="multiple_locations" id="multiple_locations" class="form-select @error('multiple_locations') is-invalid @enderror" required>
                        <option value="">Choose...</option>
                        <option value="yes" @selected(old('multiple_locations') === 'yes')>Yes</option>
                        <option value="no" @selected(old('multiple_locations') === 'no')>No</option>
                    </select>
                    @error('multiple_locations')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-4 mb-3" id="locations_count_wrap">
                    <label class="form-label">If yes, how many locations require coverage?</label>
                    <input type="number" name="locations_count" class="form-control @error('locations_count') is-invalid @enderror" value="{{ old('locations_count') }}" min="1">
                    @error('locations_count')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-4 mb-3">
                    <label class="form-label">Property Type / Industry <span class="text-danger">*</span></label>
                    <select name="property_type" id="property_type" class="form-select @error('property_type') is-invalid @enderror" required>
                        <option value="">Choose...</option>
                        @foreach(['Apartment / Multifamily','HOA / Community Association','Commercial Office','Retail Center','Shopping Center / Mall','Warehouse / Industrial','Construction Site','School / Education','Healthcare','Hotel / Hospitality','Government Facility','Event Venue','Religious Facility','Other'] as $propertyType)
                            <option value="{{ $propertyType }}" @selected(old('property_type') === $propertyType)>{{ $propertyType }}</option>
                        @endforeach
                    </select>
                    @error('property_type')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-6 mb-3" id="property_type_other_wrap">
                    <label class="form-label">If Other, please specify</label>
                    <input type="text" name="property_type_other" class="form-control @error('property_type_other') is-invalid @enderror" value="{{ old('property_type_other') }}">
                    @error('property_type_other')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-6 mb-3">
                    <label class="form-label">Current security situation <span class="text-danger">*</span></label>
                    <select name="current_security_setup" class="form-select @error('current_security_setup') is-invalid @enderror" required>
                        <option value="">Choose...</option>
                        <option value="in_house" @selected(old('current_security_setup') === 'in_house')>In-house</option>
                        <option value="outsourced" @selected(old('current_security_setup') === 'outsourced')>Outsourced</option>
                        <option value="none" @selected(old('current_security_setup') === 'none')>None</option>
                    </select>
                    @error('current_security_setup')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-6 mb-3">
                    <label class="form-label">Are you replacing a provider? <span class="text-danger">*</span></label>
                    <select name="is_replacing_provider" class="form-select @error('is_replacing_provider') is-invalid @enderror" required>
                        <option value="">Choose...</option>
                        <option value="yes" @selected(old('is_replacing_provider') === 'yes')>Yes</option>
                        <option value="no" @selected(old('is_replacing_provider') === 'no')>No</option>
                    </select>
                    @error('is_replacing_provider')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
            </div>

            <hr class="my-4">

            <h5 class="mb-3">Section 4: Service Request Details</h5>
            <div class="row">
                <div class="col-md-6 mb-3">
                    <label class="form-label">Posting Title <span class="text-danger">*</span></label>
                    <input type="text" name="title" class="form-control @error('title') is-invalid @enderror" value="{{ old('title', $starter['title'] ?? '') }}" required>
                    @error('title')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-6 mb-3">
                    <label class="form-label">Requested Service</label>
                    <div class="form-control bg-light">{{ $starter['service_label'] ?? old('category', '') }}</div>
                    @error('category')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
                    @error('service_types')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
                    @if(in_array('Other', $selectedServices, true))
                        <div class="small text-gasq-muted mt-2">Other service detail: {{ old('service_type_other', $starter['service_type_other'] ?? '') }}</div>
                        @error('service_type_other')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
                    @endif
                </div>
                <div class="col-12 mb-3">
                    <div class="alert alert-light border mb-0">
                        Your requested service was collected in Step 1. Use the "Edit Service and Job Site" button above if you need to change it before submitting the full questionnaire.
                    </div>
                </div>
                <div class="col-md-4 mb-3">
                    <label class="form-label">Is this request for a new contract, replacement service, or additional coverage? <span class="text-danger">*</span></label>
                    <select name="request_type" class="form-select @error('request_type') is-invalid @enderror" required>
                        <option value="new_service" @selected(old('request_type') === 'new_service')>New Service</option>
                        <option value="replace_current_provider" @selected(old('request_type') === 'replace_current_provider')>Replace Current Provider</option>
                        <option value="expand_existing_coverage" @selected(old('request_type') === 'expand_existing_coverage')>Expand Existing Coverage</option>
                        <option value="temporary_emergency_coverage" @selected(old('request_type') === 'temporary_emergency_coverage')>Temporary / Emergency Coverage</option>
                    </select>
                    @error('request_type')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-4 mb-3">
                    <label class="form-label">Desired Service Start Date <span class="text-danger">*</span></label>
                    <input type="date" name="service_start_date" class="form-control @error('service_start_date') is-invalid @enderror" value="{{ old('service_start_date') }}" required>
                    @error('service_start_date')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-4 mb-3">
                    <label class="form-label">Desired Contract Term <span class="text-danger">*</span></label>
                    <select name="desired_contract_term" class="form-select @error('desired_contract_term') is-invalid @enderror" required>
                        @foreach(['One-time / temporary', 'Month-to-month', '3 months', '6 months', '12 months', 'Multi-year'] as $term)
                            <option value="{{ $term }}" @selected(old('desired_contract_term') === $term)>{{ $term }}</option>
                        @endforeach
                    </select>
                    @error('desired_contract_term')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-12 mb-3">
                    <label class="form-label">Primary reason for service request <span class="text-danger">*</span></label>
                    <textarea name="primary_reason" class="form-control @error('primary_reason') is-invalid @enderror" rows="3" required>{{ old('primary_reason') }}</textarea>
                    @error('primary_reason')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
            </div>

            <hr class="my-4">

            <h5 class="mb-3">Section 5: Schedule and Staffing</h5>
            <div class="row">
                <div class="col-md-3 mb-3">
                    <label class="form-label">Hours per day requiring coverage <span class="text-danger">*</span></label>
                    <input type="number" step="0.1" min="1" max="24" name="hours_per_day" class="form-control @error('hours_per_day') is-invalid @enderror" value="{{ old('hours_per_day') }}" required>
                    @error('hours_per_day')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-3 mb-3">
                    <label class="form-label">Days per week requiring coverage <span class="text-danger">*</span></label>
                    <input type="number" min="1" max="7" name="days_per_week" class="form-control @error('days_per_week') is-invalid @enderror" value="{{ old('days_per_week') }}" required>
                    @error('days_per_week')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-3 mb-3">
                    <label class="form-label">Weeks per year requiring coverage <span class="text-danger">*</span></label>
                    <input type="number" min="1" max="53" name="weeks_per_year" class="form-control @error('weeks_per_year') is-invalid @enderror" value="{{ old('weeks_per_year') }}" required>
                    @error('weeks_per_year')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-3 mb-3">
                    <label class="form-label">Number of officers required per 8-hour shift <span class="text-danger">*</span></label>
                    <input type="number" name="guards_per_shift" class="form-control @error('guards_per_shift') is-invalid @enderror" value="{{ old('guards_per_shift', 1) }}" min="1" required>
                    @error('guards_per_shift')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-12 mb-3">
                    <label class="form-label">Select all shifts needed <span class="text-danger">*</span></label>
                    <div class="d-flex flex-wrap gap-3">
                        @foreach(['Day Shift', 'Evening Shift', 'Overnight Shift', 'Weekend Coverage', 'Holiday Coverage'] as $shift)
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="shifts_needed[]" value="{{ $shift }}" id="shift_{{ \Illuminate\Support\Str::slug($shift) }}" @checked(in_array($shift, $selectedShifts, true))>
                                <label class="form-check-label" for="shift_{{ \Illuminate\Support\Str::slug($shift) }}">{{ $shift }}</label>
                            </div>
                        @endforeach
                    </div>
                    @error('shifts_needed')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-4 mb-3">
                    <label class="form-label">Is this a dedicated post, patrol route, or hybrid assignment? <span class="text-danger">*</span></label>
                    <select name="assignment_type" id="assignment_type" class="form-select @error('assignment_type') is-invalid @enderror" required>
                        <option value="dedicated_post" @selected(old('assignment_type') === 'dedicated_post')>Dedicated Post</option>
                        <option value="patrol_route" @selected(old('assignment_type') === 'patrol_route')>Patrol Route</option>
                        <option value="hybrid" @selected(old('assignment_type') === 'hybrid')>Hybrid</option>
                    </select>
                    @error('assignment_type')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-8 mb-3" id="patrol_types_wrap">
                    <label class="form-label">If patrol is required, what patrol type is needed?</label>
                    <div class="d-flex flex-wrap gap-3">
                        @foreach(['Foot Patrol', 'Vehicle Patrol', 'Golf Cart Patrol', 'Bike Patrol'] as $patrolType)
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="patrol_types[]" value="{{ $patrolType }}" id="patrol_{{ \Illuminate\Support\Str::slug($patrolType) }}" @checked(in_array($patrolType, $selectedPatrolTypes, true))>
                                <label class="form-check-label" for="patrol_{{ \Illuminate\Support\Str::slug($patrolType) }}">{{ $patrolType }}</label>
                            </div>
                        @endforeach
                    </div>
                    @error('patrol_types')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
                </div>
            </div>

            <hr class="my-4">

            <h5 class="mb-3">Section 6: Duties and Site Conditions</h5>
            <div class="row">
                <div class="col-12 mb-3">
                    <label class="form-label">Select all duties required <span class="text-danger">*</span></label>
                    <div class="d-flex flex-wrap gap-3">
                        @foreach(['Observe and Report', 'Access Control', 'Visitor Screening', 'Lobby / Front Desk Monitoring', 'Foot Patrol', 'Vehicle Patrol', 'Parking Lot Monitoring', 'Amenity Patrol', 'Incident Reporting', 'After-Hours Lock / Unlock', 'Alarm Response', 'Camera Monitoring', 'Package Room Oversight', 'Traffic / Gate Control', 'School Dismissal Support', 'Tenant / Resident Assistance', 'Other'] as $duty)
                            <div class="form-check">
                                <input class="form-check-input duty-checkbox" type="checkbox" name="duties_required[]" value="{{ $duty }}" id="duty_{{ \Illuminate\Support\Str::slug($duty) }}" @checked(in_array($duty, $selectedDuties, true))>
                                <label class="form-check-label" for="duty_{{ \Illuminate\Support\Str::slug($duty) }}">{{ $duty }}</label>
                            </div>
                        @endforeach
                    </div>
                    @error('duties_required')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-6 mb-3" id="duties_other_wrap">
                    <label class="form-label">If Other, please specify</label>
                    <input type="text" name="duties_other" class="form-control @error('duties_other') is-invalid @enderror" value="{{ old('duties_other') }}">
                    @error('duties_other')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-6 mb-3">
                    <label class="form-label">Service package expectation <span class="text-danger">*</span></label>
                    <select name="service_package_expectation" class="form-select @error('service_package_expectation') is-invalid @enderror" required>
                        <option value="observe_and_report_only" @selected(old('service_package_expectation') === 'observe_and_report_only')>Observe and Report Only</option>
                        <option value="detect_delay_assess_respond" @selected(old('service_package_expectation') === 'detect_delay_assess_respond')>Detect, Delay, Assess, and Respond within site policy</option>
                    </select>
                    @error('service_package_expectation')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-6 mb-3">
                    <label class="form-label">Are officers expected to remain hands-off unless required by law or emergency policy? <span class="text-danger">*</span></label>
                    <select name="hands_off_expected" class="form-select @error('hands_off_expected') is-invalid @enderror" required>
                        <option value="">Choose...</option>
                        <option value="yes" @selected(old('hands_off_expected') === 'yes')>Yes</option>
                        <option value="no" @selected(old('hands_off_expected') === 'no')>No</option>
                        <option value="not_sure" @selected(old('hands_off_expected') === 'not_sure')>Not Sure</option>
                    </select>
                    @error('hands_off_expected')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-6 mb-3">
                    <label class="form-label">Do you currently have written post orders or site instructions? <span class="text-danger">*</span></label>
                    <select name="has_written_post_orders" class="form-select @error('has_written_post_orders') is-invalid @enderror" required>
                        <option value="">Choose...</option>
                        <option value="yes" @selected(old('has_written_post_orders') === 'yes')>Yes</option>
                        <option value="no" @selected(old('has_written_post_orders') === 'no')>No</option>
                        <option value="in_progress" @selected(old('has_written_post_orders') === 'in_progress')>In progress</option>
                    </select>
                    @error('has_written_post_orders')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-6 mb-3">
                    <label class="form-label">Upload post orders, site maps, or special instructions</label>
                    <input type="file" name="supporting_documents[]" class="form-control @error('supporting_documents') is-invalid @enderror @error('supporting_documents.*') is-invalid @enderror" multiple accept=".pdf,.doc,.docx,.png,.jpg,.jpeg,.webp">
                    <div class="form-text">Optional, but strongly encouraged when documents already exist.</div>
                    @error('supporting_documents')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    @error('supporting_documents.*')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-6 mb-3">
                    <label class="form-label">Are there known site risks or recurring incidents that vendors should know about?</label>
                    <textarea name="known_site_risks" class="form-control @error('known_site_risks') is-invalid @enderror" rows="3">{{ old('known_site_risks') }}</textarea>
                    @error('known_site_risks')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
            </div>

            <hr class="my-4">

            <h5 class="mb-3">Section 7: Budget and Offer Terms</h5>
            <div class="row">
                <div class="col-md-3 mb-3">
                    <label class="form-label">Which budget format would you like to enter? <span class="text-danger">*</span></label>
                    <select name="budget_format" id="budget_format" class="form-select @error('budget_format') is-invalid @enderror" required>
                        <option value="hourly_budget" @selected(old('budget_format') === 'hourly_budget')>Hourly Budget</option>
                        <option value="monthly_budget" @selected(old('budget_format') === 'monthly_budget')>Monthly Budget</option>
                        <option value="annual_budget" @selected(old('budget_format') === 'annual_budget')>Annual Budget</option>
                        <option value="need_gasq_estimate" @selected(old('budget_format') === 'need_gasq_estimate')>Need GASQ to help estimate</option>
                    </select>
                    @error('budget_format')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-3 mb-3" id="hourly_budget_wrap">
                    <label class="form-label">Enter your target baseline hourly bill rate</label>
                    <input type="number" step="0.01" min="0" name="hourly_budget" class="form-control @error('hourly_budget') is-invalid @enderror" value="{{ old('hourly_budget') }}">
                    @error('hourly_budget')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-3 mb-3" id="monthly_budget_wrap">
                    <label class="form-label">Enter your approved monthly security budget</label>
                    <input type="number" step="0.01" min="0" name="monthly_budget" class="form-control @error('monthly_budget') is-invalid @enderror" value="{{ old('monthly_budget') }}">
                    @error('monthly_budget')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-3 mb-3" id="annual_budget_wrap">
                    <label class="form-label">Enter your approved annual security budget</label>
                    <input type="number" step="0.01" min="0" name="annual_budget" class="form-control @error('annual_budget') is-invalid @enderror" value="{{ old('annual_budget') }}">
                    @error('annual_budget')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-6 mb-3">
                    <label class="form-label">Are you willing to post a buyer-established offer for vendors to accept or decline? <span class="text-danger">*</span></label>
                    <select name="willing_post_offer" class="form-select @error('willing_post_offer') is-invalid @enderror" required>
                        <option value="">Choose...</option>
                        <option value="yes" @selected(old('willing_post_offer') === 'yes')>Yes</option>
                        <option value="no" @selected(old('willing_post_offer') === 'no')>No</option>
                    </select>
                    @error('willing_post_offer')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-6 mb-3">
                    <label class="form-label">If no vendor accepts your initial offer, will you allow a scope adjustment or pricing revision? <span class="text-danger">*</span></label>
                    <select name="allow_scope_adjustment" class="form-select @error('allow_scope_adjustment') is-invalid @enderror" required>
                        <option value="">Choose...</option>
                        <option value="yes" @selected(old('allow_scope_adjustment') === 'yes')>Yes</option>
                        <option value="no" @selected(old('allow_scope_adjustment') === 'no')>No</option>
                        <option value="maybe_after_review" @selected(old('allow_scope_adjustment') === 'maybe_after_review')>Maybe after review</option>
                    </select>
                    @error('allow_scope_adjustment')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-6 mb-3">
                    <label class="form-label">Would you like GASQ to show a side-by-side comparison of in-house versus outsourced cost? <span class="text-danger">*</span></label>
                    <select name="cost_comparison_requested" class="form-select @error('cost_comparison_requested') is-invalid @enderror" required>
                        <option value="">Choose...</option>
                        <option value="yes" @selected(old('cost_comparison_requested') === 'yes')>Yes</option>
                        <option value="no" @selected(old('cost_comparison_requested') === 'no')>No</option>
                    </select>
                    @error('cost_comparison_requested')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-6 mb-3">
                    <label class="form-label">Budget type</label>
                    <select name="budget_type" class="form-select @error('budget_type') is-invalid @enderror">
                        <option value="monthly" @selected(old('budget_type') === 'monthly')>Monthly</option>
                        <option value="annual" @selected(old('budget_type') === 'annual')>Annual</option>
                        <option value="contract_total" @selected(old('budget_type') === 'contract_total')>Contract total</option>
                    </select>
                    @error('budget_type')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-6 mb-3">
                    <label class="form-label">Budget amount or range</label>
                    <input type="text" name="budget_amount_range" class="form-control @error('budget_amount_range') is-invalid @enderror" value="{{ old('budget_amount_range') }}" placeholder="e.g. $12,000-$16,000 monthly">
                    @error('budget_amount_range')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-6 mb-3">
                    <label class="form-label">If pricing exceeds expectations, are you willing to:</label>
                    <div class="d-flex flex-wrap gap-3">
                        @foreach(['increase_budget' => 'Increase budget', 'adjust_scope' => 'Adjust scope', 'change_service_level' => 'Change service level'] as $value => $label)
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="if_pricing_exceeds[]" value="{{ $value }}" id="pricing_{{ $value }}" @checked(in_array($value, $selectedPricingActions, true))>
                                <label class="form-check-label" for="pricing_{{ $value }}">{{ $label }}</label>
                            </div>
                        @endforeach
                    </div>
                </div>
                <div class="col-md-6 mb-3">
                    <label class="form-label">Willing to adjust scope to match budget?</label>
                    <select name="willing_adjust_scope_to_budget" class="form-select @error('willing_adjust_scope_to_budget') is-invalid @enderror">
                        <option value="yes" @selected(old('willing_adjust_scope_to_budget') === 'yes')>Yes</option>
                        <option value="no" @selected(old('willing_adjust_scope_to_budget') === 'no')>No</option>
                    </select>
                    @error('willing_adjust_scope_to_budget')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
            </div>

            <hr class="my-4">

            <h5 class="mb-3">Section 8: Compliance Requirements</h5>
            <div class="row">
                <div class="col-md-6 mb-3">
                    <label class="form-label">Officer licensing required? <span class="text-danger">*</span></label>
                    <select name="officer_licensing_required" class="form-select @error('officer_licensing_required') is-invalid @enderror" required>
                        <option value="">Choose...</option>
                        <option value="yes" @selected(old('officer_licensing_required') === 'yes')>Yes</option>
                        <option value="no" @selected(old('officer_licensing_required') === 'no')>No</option>
                        <option value="depends_on_assignment" @selected(old('officer_licensing_required') === 'depends_on_assignment')>Depends on assignment</option>
                    </select>
                    @error('officer_licensing_required')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-6 mb-3">
                    <label class="form-label">Background checks required? <span class="text-danger">*</span></label>
                    <select name="background_checks_required" class="form-select @error('background_checks_required') is-invalid @enderror" required>
                        <option value="">Choose...</option>
                        <option value="yes" @selected(old('background_checks_required') === 'yes')>Yes</option>
                        <option value="no" @selected(old('background_checks_required') === 'no')>No</option>
                    </select>
                    @error('background_checks_required')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-6 mb-3">
                    <label class="form-label">Drug testing required? <span class="text-danger">*</span></label>
                    <select name="drug_testing_required" class="form-select @error('drug_testing_required') is-invalid @enderror" required>
                        <option value="">Choose...</option>
                        <option value="yes" @selected(old('drug_testing_required') === 'yes')>Yes</option>
                        <option value="no" @selected(old('drug_testing_required') === 'no')>No</option>
                    </select>
                    @error('drug_testing_required')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-6 mb-3">
                    <label class="form-label">Uniformed officers required? <span class="text-danger">*</span></label>
                    <select name="uniformed_officers_required" class="form-select @error('uniformed_officers_required') is-invalid @enderror" required>
                        <option value="">Choose...</option>
                        <option value="yes" @selected(old('uniformed_officers_required') === 'yes')>Yes</option>
                        <option value="no" @selected(old('uniformed_officers_required') === 'no')>No</option>
                    </select>
                    @error('uniformed_officers_required')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-12 mb-3">
                    <label class="form-label">Insurance minimums required?</label>
                    <div class="d-flex flex-wrap gap-3">
                        @foreach(['General Liability', 'Workers Compensation', 'Auto Liability', 'Umbrella / Excess Liability', 'Not sure'] as $insuranceMinimum)
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="insurance_minimums_required[]" value="{{ $insuranceMinimum }}" id="insurance_{{ \Illuminate\Support\Str::slug($insuranceMinimum) }}" @checked(in_array($insuranceMinimum, $selectedInsuranceMinimums, true))>
                                <label class="form-check-label" for="insurance_{{ \Illuminate\Support\Str::slug($insuranceMinimum) }}">{{ $insuranceMinimum }}</label>
                            </div>
                        @endforeach
                    </div>
                    @error('insurance_minimums_required')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
                </div>
                <div class="col-12 mb-3">
                    <label class="form-label">Enter any required insurance limits, special certifications, or compliance terms</label>
                    <textarea name="compliance_terms" class="form-control @error('compliance_terms') is-invalid @enderror" rows="3">{{ old('compliance_terms') }}</textarea>
                    @error('compliance_terms')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
            </div>

            <hr class="my-4">

            <h5 class="mb-3">Section 9: Posting Terms and Submission</h5>
            <div class="row">
                <div class="col-md-6 mb-3">
                    <label class="form-label">Do you want this posting sent to multiple qualified vendors for response? <span class="text-danger">*</span></label>
                    <select name="multiple_bids_required" class="form-select @error('multiple_bids_required') is-invalid @enderror" required>
                        <option value="">Choose...</option>
                        <option value="yes" @selected(old('multiple_bids_required') === 'yes')>Yes</option>
                        <option value="no" @selected(old('multiple_bids_required') === 'no')>No</option>
                    </select>
                    @error('multiple_bids_required')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-6 mb-3">
                    <label class="form-label">Preferred vendor response deadline <span class="text-danger">*</span></label>
                    <input type="date" name="vendor_response_deadline" class="form-control @error('vendor_response_deadline') is-invalid @enderror" value="{{ old('vendor_response_deadline') }}" required>
                    @error('vendor_response_deadline')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-12 mb-3">
                    <label class="form-label">Additional notes to vendors</label>
                    <textarea name="additional_notes_to_vendors" class="form-control @error('additional_notes_to_vendors') is-invalid @enderror" rows="3">{{ old('additional_notes_to_vendors') }}</textarea>
                    @error('additional_notes_to_vendors')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-6 mb-3">
                    <label class="form-label">Risk assessment in last 12 months?</label>
                    <select name="risk_assessment_last_12_months" class="form-select @error('risk_assessment_last_12_months') is-invalid @enderror">
                        <option value="yes_recent" @selected(old('risk_assessment_last_12_months') === 'yes_recent')>Yes, completed recently</option>
                        <option value="no_want_one" @selected(old('risk_assessment_last_12_months') === 'no_want_one')>No, but want one conducted</option>
                        <option value="no_waiver_required" @selected(old('risk_assessment_last_12_months') === 'no_waiver_required')>No, waiver required</option>
                    </select>
                    @error('risk_assessment_last_12_months')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-6 mb-3">
                    <label class="form-label">Expires at</label>
                    <input type="datetime-local" name="expires_at" class="form-control @error('expires_at') is-invalid @enderror" value="{{ old('expires_at') }}">
                    @error('expires_at')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-12 mb-2">
                    <div class="form-check">
                        <input class="form-check-input @error('buyer_certification') is-invalid @enderror" type="checkbox" name="buyer_certification" id="buyer_certification" value="1" @checked(old('buyer_certification')) required>
                        <label class="form-check-label" for="buyer_certification">
                            I certify that the information provided is accurate to the best of my knowledge, that I am authorized to submit this request or represent the requesting party, and that I understand GASQ may use this information to prepare and release a buyer-controlled security job offer announcement to qualified vendors.
                        </label>
                        @error('buyer_certification')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                    </div>
                </div>
                <div class="col-12 mb-4">
                    <div class="form-check">
                        <input class="form-check-input @error('consent_to_contact') is-invalid @enderror" type="checkbox" name="consent_to_contact" id="consent_to_contact" value="1" @checked(old('consent_to_contact')) required>
                        <label class="form-check-label" for="consent_to_contact">
                            I agree to be contacted by GASQ regarding this request, offer adjustments, vendor responses, and award follow-up.
                        </label>
                        @error('consent_to_contact')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                    </div>
                </div>
            </div>

            <div class="d-flex gap-2">
                <button type="submit" class="btn btn-primary">Generate Job Announcement Preview</button>
                <a href="{{ route('job-board') }}" class="btn btn-outline-secondary">Cancel</a>
            </div>
        </form>
    </x-card>
    @endif
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    function showWhen(condition, elementId) {
        const element = document.getElementById(elementId);
        if (!element) {
            return;
        }

        element.classList.toggle('d-none', !condition);
    }

    function selectedCheckboxValues(selector) {
        return Array.from(document.querySelectorAll(selector + ':checked')).map(function (input) {
            return input.value;
        });
    }

    function syncVisibility() {
        const starterServiceType = document.getElementById('starter_service_type');
        const finalDecisionMaker = document.getElementById('final_decision_maker');
        const multipleLocations = document.getElementById('multiple_locations');
        const propertyType = document.getElementById('property_type');
        const assignmentType = document.getElementById('assignment_type');
        const budgetFormat = document.getElementById('budget_format');

        showWhen(starterServiceType && starterServiceType.value === 'Other', 'starter_service_type_other_wrap');
        showWhen(finalDecisionMaker && ['no', 'authorized_representative'].includes(finalDecisionMaker.value), 'final_approver_wrap');
        showWhen(multipleLocations && multipleLocations.value === 'yes', 'locations_count_wrap');
        showWhen(propertyType && propertyType.value === 'Other', 'property_type_other_wrap');
        showWhen(assignmentType && ['patrol_route', 'hybrid'].includes(assignmentType.value), 'patrol_types_wrap');
        showWhen(selectedCheckboxValues('.service-type-checkbox').includes('Other'), 'service_type_other_wrap');
        showWhen(selectedCheckboxValues('.duty-checkbox').includes('Other'), 'duties_other_wrap');

        showWhen(budgetFormat && budgetFormat.value === 'hourly_budget', 'hourly_budget_wrap');
        showWhen(budgetFormat && budgetFormat.value === 'monthly_budget', 'monthly_budget_wrap');
        showWhen(budgetFormat && budgetFormat.value === 'annual_budget', 'annual_budget_wrap');
    }

    ['starter_service_type', 'final_decision_maker', 'multiple_locations', 'property_type', 'assignment_type', 'budget_format'].forEach(function (id) {
        const element = document.getElementById(id);
        if (element) {
            element.addEventListener('change', syncVisibility);
        }
    });

    document.querySelectorAll('.service-type-checkbox, .duty-checkbox').forEach(function (element) {
        element.addEventListener('change', syncVisibility);
    });

    syncVisibility();
});
</script>
@endsection

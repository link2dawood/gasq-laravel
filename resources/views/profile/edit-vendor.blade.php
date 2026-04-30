@extends('layouts.app')

@section('title', 'Vendor Settings')
@section('header_variant', 'dashboard')

@php
    $phoneVerification = $phoneVerification ?? ['phone' => '', 'verified' => false];
    $additionalInfo = is_array($additionalInfo ?? null) ? $additionalInfo : [];
    $formPhone = old('phone', $user->phone);
    $originalPhone = $user->phone ?? '';
    $verifiedCandidatePhone = (string) ($phoneVerification['phone'] ?? '');
    $isVerifiedCandidate = (bool) ($phoneVerification['verified'] ?? false);
    $isCurrentPhoneVerified = (bool) ($user->phone_verified ?? false);
    $normalizePhone = static fn ($value) => preg_replace('/[\s\-\(\)]+/', '', trim((string) $value)) ?: '';
    $normalizedFormPhone = $normalizePhone($formPhone);
    $normalizedOriginalPhone = $normalizePhone($originalPhone);
    $normalizedVerifiedCandidatePhone = $normalizePhone($verifiedCandidatePhone);
    $phoneCanSave = $normalizedFormPhone === ''
        || ($normalizedFormPhone === $normalizedOriginalPhone && $isCurrentPhoneVerified)
        || ($isVerifiedCandidate && $normalizedFormPhone === $normalizedVerifiedCandidatePhone);

    $generalInsurance = is_array(data_get($additionalInfo, 'insurance.general')) ? data_get($additionalInfo, 'insurance.general') : [];
    $workersCompInsurance = is_array(data_get($additionalInfo, 'insurance.workers_comp')) ? data_get($additionalInfo, 'insurance.workers_comp') : [];
    $selectedCapabilities = old('service_capabilities', $profile?->capabilities ?? $capability?->core_competencies ?? []);
    $selectedCertifications = old('certifications_flags', data_get($additionalInfo, 'certifications_flags', $capability?->certifications ?? []));
    $options = $vendorSettingsOptions ?? [];
@endphp

@push('styles')
<style>
    .vendor-settings-wrap { background: #f6f7fb; min-height: calc(100vh - 72px); }
    .vendor-settings-shell { max-width: 1180px; margin: 0 auto; }
    .vendor-settings-card { border: 1px solid #dce3ef; border-radius: 1rem; background: #fff; box-shadow: 0 10px 30px rgba(20, 30, 55, 0.04); }
    .vendor-settings-section + .vendor-settings-section { margin-top: 1rem; }
    .vendor-settings-header { padding: 1rem 1.2rem; border-bottom: 1px solid #e6ebf3; }
    .vendor-settings-body { padding: 1.15rem 1.2rem 1.25rem; }
    .vendor-settings-intro { color: #697389; max-width: 48rem; }
    .vendor-check-grid { display: grid; gap: .75rem; grid-template-columns: repeat(2, minmax(0, 1fr)); }
    .vendor-check-card {
        display: flex;
        align-items: center;
        gap: .65rem;
        padding: .8rem .9rem;
        border: 1px solid #dde4ef;
        border-radius: .9rem;
        background: #fbfcfe;
    }
    .vendor-radio-row { display: flex; flex-wrap: wrap; gap: .65rem; }
    .vendor-radio-pill {
        display: inline-flex;
        align-items: center;
        gap: .45rem;
        padding: .65rem .85rem;
        border: 1px solid #dde4ef;
        border-radius: 999px;
        background: #fbfcfe;
    }
    .vendor-form-note { color: #6c7690; font-size: .94rem; }
    @media (max-width: 767.98px) {
        .vendor-check-grid { grid-template-columns: 1fr; }
    }
</style>
@endpush

@section('content')
<div class="vendor-settings-wrap py-4 px-3 px-md-4">
    <div class="vendor-settings-shell">
        <div class="d-flex flex-wrap align-items-start justify-content-between gap-3 mb-4">
            <div>
                <h1 class="h2 fw-bold mb-1">Edit Profile</h1>
                <p class="vendor-settings-intro mb-0">Update your vendor settings, licensing, insurance, operating capabilities, and certification details.</p>
            </div>
            <a href="{{ route('home') }}" class="btn btn-outline-secondary"><i class="fa fa-arrow-left me-2"></i>Back to Dashboard</a>
        </div>

        @if (session('success'))
            <div class="alert alert-success alert-dismissible fade show mb-4" role="alert">
                {{ session('success') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        @endif

        @if ($errors->any())
            <div class="alert alert-danger alert-dismissible fade show mb-4" role="alert">
                <strong>Please fix the following:</strong>
                <ul class="mb-0 mt-2">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        @endif

        @if (session('phone_status'))
            <div class="alert alert-info alert-dismissible fade show mb-4" role="alert">
                {{ session('phone_status') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        @endif

        <form action="{{ route('profile.update') }}" method="POST" id="profileForm">
            @csrf
            @method('PUT')

            <section class="vendor-settings-card vendor-settings-section">
                <div class="vendor-settings-header">
                    <h2 class="h4 mb-1">Company Details</h2>
                    <p class="vendor-form-note mb-0">Basic information used for vendor matching and buyer-facing contact details.</p>
                </div>
                <div class="vendor-settings-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Your full name</label>
                            <input type="text" class="form-control @error('name') is-invalid @enderror" name="name" value="{{ old('name', $user->name) }}" placeholder="Get A Security Quote Team">
                            @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Email address</label>
                            <input type="email" class="form-control @error('email') is-invalid @enderror" name="email" value="{{ old('email', $user->email) }}" placeholder="team@getasecurityquote.com">
                            @error('email')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Phone number</label>
                            <div class="d-flex flex-column gap-2">
                                <div class="d-flex gap-2 align-items-start">
                                    <input type="text" class="form-control @error('phone') is-invalid @enderror" id="profile_phone" name="phone" value="{{ $formPhone }}" placeholder="+10000000000" autocomplete="tel">
                                    <button type="submit" class="btn btn-outline-primary flex-shrink-0" id="phone_verify_trigger" form="profilePhoneSendForm">Verify</button>
                                    <span class="badge text-bg-success align-self-center d-none" id="phone_verified_badge">Verified</span>
                                </div>
                                <div class="vendor-form-note">We automatically format your phone number. Save stays disabled until the current phone number is verified.</div>
                            </div>
                            @error('phone')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                            <div class="mt-2 d-none" id="phone_otp_wrap">
                                <label class="form-label small mb-1">Verification code</label>
                                <div class="d-flex gap-2">
                                    <input type="text" class="form-control @error('phone_otp') is-invalid @enderror" id="profile_phone_otp" name="profile_phone_otp_display" value="{{ old('code') }}" placeholder="123456" form="profilePhoneVerifyForm" autocomplete="one-time-code" inputmode="numeric">
                                    <button type="submit" class="btn btn-outline-secondary flex-shrink-0" form="profilePhoneVerifyForm">Confirm Code</button>
                                </div>
                                @error('phone_otp')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
                            </div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Company Name</label>
                            <input type="text" class="form-control @error('company_name') is-invalid @enderror" name="company_name" value="{{ old('company_name', $profile?->company_name ?? $user->company) }}" placeholder="Get A Security Quote">
                            @error('company_name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            <input type="hidden" name="company" value="{{ old('company', $user->company) }}">
                        </div>
                        <div class="col-12">
                            <label class="form-label">Street Address</label>
                            <input type="text" class="form-control @error('street_address') is-invalid @enderror" name="street_address" value="{{ old('street_address', $profile?->address) }}" placeholder="Street address">
                            @error('street_address')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">City</label>
                            <input type="text" class="form-control @error('city') is-invalid @enderror" name="city" value="{{ old('city', $user->city) }}" placeholder="City">
                            @error('city')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">State</label>
                            <input type="text" class="form-control @error('state') is-invalid @enderror" name="state" value="{{ old('state', $user->state) }}" placeholder="State">
                            @error('state')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">ZIP Code</label>
                            <input type="text" class="form-control @error('zip_code') is-invalid @enderror" name="zip_code" value="{{ old('zip_code', $user->zip_code) }}" placeholder="ZIP Code">
                            @error('zip_code')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Private Patrol Operators License number</label>
                            <input type="text" class="form-control @error('business_license_number') is-invalid @enderror" name="business_license_number" value="{{ old('business_license_number', $capability?->business_license_number) }}">
                            @error('business_license_number')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Expiration Date</label>
                            <input type="date" class="form-control @error('license_expiration_date') is-invalid @enderror" name="license_expiration_date" value="{{ old('license_expiration_date', data_get($additionalInfo, 'license_expiration_date')) }}">
                            @error('license_expiration_date')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Vendor EIN</label>
                            <input type="text" class="form-control @error('vendor_ein') is-invalid @enderror" name="vendor_ein" value="{{ old('vendor_ein', data_get($additionalInfo, 'vendor_ein')) }}">
                            @error('vendor_ein')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">How long have you been in business?</label>
                            <input type="number" min="0" class="form-control @error('years_of_experience') is-invalid @enderror" name="years_of_experience" value="{{ old('years_of_experience', $capability?->years_of_experience) }}">
                            @error('years_of_experience')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-12">
                            <label class="form-label">Profile Description</label>
                            <textarea class="form-control @error('profile_description') is-invalid @enderror" name="profile_description" rows="4" placeholder="Describe your company, service strengths, and operating approach">{{ old('profile_description', $profile?->description) }}</textarea>
                            @error('profile_description')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                    </div>
                </div>
            </section>

            <section class="vendor-settings-card vendor-settings-section">
                <div class="vendor-settings-header">
                    <h2 class="h4 mb-1">General Liability & Errors & Omissions Insurance Carrier</h2>
                </div>
                <div class="vendor-settings-body">
                    <div class="row g-3">
                        <div class="col-12">
                            <label class="form-label d-block">Limits covered</label>
                            <div class="vendor-radio-row">
                                @foreach(($options['limits_covered'] ?? []) as $value)
                                    <label class="vendor-radio-pill">
                                        <input type="radio" name="insurance[general][limits_covered]" value="{{ $value }}" @checked(old('insurance.general.limits_covered', data_get($generalInsurance, 'limits_covered')) === $value)>
                                        <span>{{ $value }}</span>
                                    </label>
                                @endforeach
                            </div>
                        </div>
                        <div class="col-12">
                            <label class="form-label d-block">Deductible per Occurrence</label>
                            <div class="vendor-radio-row">
                                @foreach(($options['deductibles'] ?? []) as $value)
                                    <label class="vendor-radio-pill">
                                        <input type="radio" name="insurance[general][deductible_per_occurrence]" value="{{ $value }}" @checked(old('insurance.general.deductible_per_occurrence', data_get($generalInsurance, 'deductible_per_occurrence')) === $value)>
                                        <span>{{ $value }}</span>
                                    </label>
                                @endforeach
                            </div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Insurance Company Name</label>
                            <input type="text" class="form-control" name="insurance[general][company_name]" value="{{ old('insurance.general.company_name', data_get($generalInsurance, 'company_name')) }}">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Insurance Company Address</label>
                            <input type="text" class="form-control" name="insurance[general][company_address]" value="{{ old('insurance.general.company_address', data_get($generalInsurance, 'company_address')) }}">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Insurance Policy Number</label>
                            <input type="text" class="form-control" name="insurance[general][policy_number]" value="{{ old('insurance.general.policy_number', data_get($generalInsurance, 'policy_number')) }}">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Insurance Carrier Phone Number</label>
                            <input type="text" class="form-control" name="insurance[general][carrier_phone]" value="{{ old('insurance.general.carrier_phone', data_get($generalInsurance, 'carrier_phone')) }}">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Insurance Carrier Agent to Contact</label>
                            <input type="text" class="form-control" name="insurance[general][agent_name]" value="{{ old('insurance.general.agent_name', data_get($generalInsurance, 'agent_name')) }}">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Insurance Carrier Agent Email Address</label>
                            <input type="email" class="form-control" name="insurance[general][agent_email]" value="{{ old('insurance.general.agent_email', data_get($generalInsurance, 'agent_email')) }}">
                        </div>
                    </div>
                </div>
            </section>

            <section class="vendor-settings-card vendor-settings-section">
                <div class="vendor-settings-header">
                    <h2 class="h4 mb-1">Workmans Comp Insurance Carrier</h2>
                </div>
                <div class="vendor-settings-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Insurance Company Name</label>
                            <input type="text" class="form-control" name="insurance[workers_comp][company_name]" value="{{ old('insurance.workers_comp.company_name', data_get($workersCompInsurance, 'company_name')) }}">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Insurance Company Address</label>
                            <input type="text" class="form-control" name="insurance[workers_comp][company_address]" value="{{ old('insurance.workers_comp.company_address', data_get($workersCompInsurance, 'company_address')) }}">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Insurance Policy Number</label>
                            <input type="text" class="form-control" name="insurance[workers_comp][policy_number]" value="{{ old('insurance.workers_comp.policy_number', data_get($workersCompInsurance, 'policy_number')) }}">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Insurance Carrier Phone Number</label>
                            <input type="text" class="form-control" name="insurance[workers_comp][carrier_phone]" value="{{ old('insurance.workers_comp.carrier_phone', data_get($workersCompInsurance, 'carrier_phone')) }}">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Insurance Carrier Agent to Contact</label>
                            <input type="text" class="form-control" name="insurance[workers_comp][agent_name]" value="{{ old('insurance.workers_comp.agent_name', data_get($workersCompInsurance, 'agent_name')) }}">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Insurance Carrier Agent Email Address</label>
                            <input type="email" class="form-control" name="insurance[workers_comp][agent_email]" value="{{ old('insurance.workers_comp.agent_email', data_get($workersCompInsurance, 'agent_email')) }}">
                        </div>
                    </div>
                </div>
            </section>

            <section class="vendor-settings-card vendor-settings-section">
                <div class="vendor-settings-header">
                    <h2 class="h4 mb-1">Operations & Services</h2>
                </div>
                <div class="vendor-settings-body">
                    <div class="row g-4">
                        <div class="col-12">
                            <label class="form-label d-block">Do you work in other states?</label>
                            <div class="vendor-radio-row">
                                @foreach(['yes' => 'Yes', 'no' => 'No'] as $value => $label)
                                    <label class="vendor-radio-pill">
                                        <input type="radio" name="works_other_states" value="{{ $value }}" @checked(old('works_other_states', data_get($additionalInfo, 'works_other_states')) === $value)>
                                        <span>{{ $label }}</span>
                                    </label>
                                @endforeach
                            </div>
                        </div>
                        <div class="col-12">
                            <label class="form-label d-block">What primary & additional services do you provide? (Please check all that apply)</label>
                            <div class="vendor-check-grid">
                                @foreach(($options['service_capabilities'] ?? []) as $value)
                                    <label class="vendor-check-card">
                                        <input type="checkbox" name="service_capabilities[]" value="{{ $value }}" @checked(in_array($value, $selectedCapabilities, true))>
                                        <span>{{ $value }}</span>
                                    </label>
                                @endforeach
                            </div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">How many Full Time Employees?</label>
                            <input type="number" min="0" class="form-control" name="full_time_employees" value="{{ old('full_time_employees', data_get($additionalInfo, 'full_time_employees')) }}">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">How many Part Time Employees?</label>
                            <input type="number" min="0" class="form-control" name="part_time_employees" value="{{ old('part_time_employees', data_get($additionalInfo, 'part_time_employees')) }}">
                        </div>
                    </div>

                    @php
                        $yesNoQuestions = [
                            'uses_gps_monitoring' => 'Do your company use GPS guard monitoring?',
                            'uses_guard_management_software' => 'Do your company use guard force management software for tracking time in/time out of security officers?',
                            'uses_tasers' => 'Do your employees carry Tasers or similar non-lethal weapons?',
                            'uses_body_cameras' => 'Do your company use body cameras?',
                            'uses_incident_reporting_software' => 'Do your company use real time incident reporting software?',
                            'uses_drones' => 'Do your company use drones in your operation?',
                            'uses_1099_employees' => 'Do your company use 1099 employees?',
                            'has_dispatch_center' => 'Do you have a 24-hour dispatch communication command & control center where management can be reached and dispatched within 30 minutes?',
                        ];
                    @endphp

                    <div class="row g-3 mt-1">
                        @foreach($yesNoQuestions as $field => $label)
                            <div class="col-12">
                                <label class="form-label d-block">{{ $label }}</label>
                                <div class="vendor-radio-row">
                                    @foreach(['yes' => 'Yes', 'no' => 'No'] as $value => $text)
                                        <label class="vendor-radio-pill">
                                            <input type="radio" name="{{ $field }}" value="{{ $value }}" @checked(old($field, data_get($additionalInfo, $field)) === $value)>
                                            <span>{{ $text }}</span>
                                        </label>
                                    @endforeach
                                </div>
                            </div>
                        @endforeach
                    </div>

                    <div class="mt-4">
                        <label class="form-label d-block">Branch Office Locations (please check all that apply)</label>
                        <div class="vendor-radio-row">
                            @foreach(($options['branch_office_scope'] ?? []) as $value => $label)
                                <label class="vendor-radio-pill">
                                    <input type="radio" name="branch_office_scope" value="{{ $value }}" @checked(old('branch_office_scope', data_get($additionalInfo, 'branch_office_scope')) === $value)>
                                    <span>{{ $label }}</span>
                                </label>
                            @endforeach
                        </div>
                    </div>
                </div>
            </section>

            <section class="vendor-settings-card vendor-settings-section">
                <div class="vendor-settings-header">
                    <h2 class="h4 mb-1">Certifications & Business Status</h2>
                </div>
                <div class="vendor-settings-body">
                    <div class="vendor-check-grid">
                        @foreach(($options['certifications_flags'] ?? []) as $value => $label)
                            <label class="vendor-check-card">
                                <input type="checkbox" name="certifications_flags[]" value="{{ $value }}" @checked(in_array($value, $selectedCertifications, true))>
                                <span>{{ $label }}</span>
                            </label>
                        @endforeach
                    </div>
                </div>
            </section>

            <div class="d-flex flex-wrap gap-2 mt-4">
                <button type="submit" class="btn btn-primary btn-lg" id="profile_save_button" @disabled(! $phoneCanSave)><i class="fa fa-check me-2"></i>Save Vendor Settings</button>
                <a href="{{ route('home') }}" class="btn btn-outline-secondary btn-lg">Cancel</a>
            </div>
        </form>

        <form action="{{ route('profile.phone.send') }}" method="POST" id="profilePhoneSendForm" class="d-none">
            @csrf
            <input type="hidden" name="phone" id="profile_phone_send_value" value="{{ $formPhone }}">
        </form>

        <form action="{{ route('profile.phone.verify') }}" method="POST" id="profilePhoneVerifyForm" class="d-none">
            @csrf
            <input type="hidden" name="phone" id="profile_phone_verify_value" value="{{ $formPhone }}">
            <input type="hidden" name="code" id="profile_phone_verify_code" value="{{ old('code') }}">
        </form>

        @if(!$user->google_id)
            <section class="vendor-settings-card vendor-settings-section mt-4">
                <div class="vendor-settings-header">
                    <h2 class="h4 mb-1">Password</h2>
                </div>
                <div class="vendor-settings-body">
                    <form action="{{ route('profile.password.update') }}" method="POST">
                        @csrf
                        @method('PUT')
                        <div class="row g-3">
                            <div class="col-md-4">
                                <label class="form-label">Current Password</label>
                                <input type="password" class="form-control @error('current_password') is-invalid @enderror" name="current_password">
                                @error('current_password')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">New Password</label>
                                <input type="password" class="form-control @error('password') is-invalid @enderror" name="password">
                                @error('password')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Confirm New Password</label>
                                <input type="password" class="form-control" name="password_confirmation">
                            </div>
                        </div>
                        <button type="submit" class="btn btn-warning mt-3"><i class="fa fa-lock me-2"></i>Update Password</button>
                    </form>
                </div>
            </section>
        @endif
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const phoneInput = document.getElementById('profile_phone');
    const verifyButton = document.getElementById('phone_verify_trigger');
    const verifiedBadge = document.getElementById('phone_verified_badge');
    const otpWrap = document.getElementById('phone_otp_wrap');
    const saveButton = document.getElementById('profile_save_button');
    const sendPhoneHidden = document.getElementById('profile_phone_send_value');
    const verifyPhoneHidden = document.getElementById('profile_phone_verify_value');
    const verifyCodeVisible = document.getElementById('profile_phone_otp');
    const verifyCodeHidden = document.getElementById('profile_phone_verify_code');

    const originalPhone = @json($originalPhone);
    const originalPhoneVerified = @json($isCurrentPhoneVerified);
    const verifiedCandidatePhone = @json($verifiedCandidatePhone);
    const verifiedCandidate = @json($isVerifiedCandidate);
    const phoneOtpHasError = @json($errors->has('phone_otp'));
    const phoneHasError = @json($errors->has('phone'));
    const phoneStatusMessage = @json((string) session('phone_status', ''));

    function normalizePhone(value) {
        return (value || '').trim().replace(/[\s\-()]+/g, '');
    }

    function syncPhoneTargets() {
        const value = phoneInput ? phoneInput.value : '';
        if (sendPhoneHidden) {
            sendPhoneHidden.value = value;
        }
        if (verifyPhoneHidden) {
            verifyPhoneHidden.value = value;
        }
        if (verifyCodeVisible && verifyCodeHidden) {
            verifyCodeHidden.value = verifyCodeVisible.value;
        }
    }

    function syncPhoneUi() {
        if (!phoneInput || !verifyButton || !verifiedBadge || !otpWrap || !saveButton) {
            return;
        }

        const currentPhone = normalizePhone(phoneInput.value);
        const original = normalizePhone(originalPhone);
        const verifiedPhone = normalizePhone(verifiedCandidatePhone);
        const phoneIsBlank = currentPhone === '';
        const phoneMatchesOriginal = currentPhone !== '' && currentPhone === original;
        const currentPhoneVerified = phoneMatchesOriginal
            ? originalPhoneVerified
            : (verifiedCandidate && currentPhone === verifiedPhone);

        verifyButton.classList.toggle('d-none', phoneIsBlank || currentPhoneVerified);
        verifiedBadge.classList.toggle('d-none', !currentPhoneVerified || phoneIsBlank);
        otpWrap.classList.toggle('d-none', phoneIsBlank || currentPhoneVerified || (!phoneOtpHasError && !phoneHasError && phoneStatusMessage === '' && currentPhone !== verifiedPhone));
        saveButton.disabled = !phoneIsBlank && !currentPhoneVerified;

        syncPhoneTargets();
    }

    if (phoneInput) {
        phoneInput.addEventListener('input', syncPhoneUi);
    }

    if (verifyCodeVisible) {
        verifyCodeVisible.addEventListener('input', function () {
            if (verifyCodeHidden) {
                verifyCodeHidden.value = verifyCodeVisible.value;
            }
        });
    }

    syncPhoneUi();
});
</script>
@endsection

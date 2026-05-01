@extends('layouts.app')

@section('title', 'Edit Profile')
@section('header_variant', 'dashboard')

@php
    $phoneVerification = $phoneVerification ?? ['phone' => '', 'verified' => false];
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
    $pageTitle = $user->isAdmin() ? 'Admin Settings' : 'Buyer Settings';
    $pageSubtitle = $user->isAdmin()
        ? 'Update your platform account, identity details, and security settings.'
        : 'Update your buyer account details and the contact information vendors will see.';
@endphp

@push('styles')
<style>
    .account-edit-shell {
        min-height: calc(100vh - 72px);
        background: #f5f7fb;
    }
    .account-edit-wrap {
        width: min(100%, 1160px);
        margin: 0 auto;
    }
    .account-edit-section {
        background: #fff;
        border: 1px solid #e2e8f2;
        border-radius: 1.1rem;
    }
    .account-edit-hero {
        padding: 1.5rem 1.65rem;
    }
    .account-edit-panel {
        padding: 1.35rem 1.45rem;
        height: 100%;
    }
    .account-edit-chip {
        display: inline-flex;
        align-items: center;
        gap: .45rem;
        padding: .44rem .75rem;
        border-radius: 999px;
        background: #f7f9fc;
        border: 1px solid #e1e7f0;
        color: #2d3954;
        font-size: .9rem;
        font-weight: 600;
    }
    .account-edit-note {
        color: #6d7690;
        font-size: .94rem;
        line-height: 1.5;
    }
    .account-edit-heading {
        color: #1f2a44;
        font-size: 1.05rem;
        font-weight: 700;
        margin-bottom: .2rem;
    }
    .account-edit-form-grid {
        display: grid;
        gap: 1.25rem;
        grid-template-columns: minmax(0, 1.7fr) minmax(280px, .95fr);
    }
    .account-edit-aside-list {
        display: grid;
        gap: .7rem;
        margin-bottom: 1rem;
    }
    .account-edit-aside-item {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 1rem;
        padding: .8rem .9rem;
        border-radius: .9rem;
        background: #f8fafc;
    }
    .account-edit-aside-item span:first-child {
        color: #6d7890;
        font-weight: 600;
    }
    .account-edit-aside-item strong {
        color: #1f2940;
    }
    @media (max-width: 991.98px) {
        .account-edit-form-grid {
            grid-template-columns: 1fr;
        }
    }
</style>
@endpush

@section('content')
<div class="account-edit-shell py-4 px-3 px-md-4">
    <div class="account-edit-wrap">
        <div class="account-edit-section account-edit-hero mb-4">
            <div class="d-flex flex-wrap align-items-start justify-content-between gap-3">
                <div class="d-flex align-items-start gap-3">
                    <img src="{{ $user->avatar_url }}" alt="" class="rounded-circle border border-3 border-white shadow-sm flex-shrink-0" width="74" height="74">
                    <div>
                        <div class="text-uppercase small fw-semibold text-gasq-muted mb-2">{{ $pageTitle }}</div>
                        <h1 class="display-6 fw-bold mb-2">{{ $user->name }}</h1>
                        <p class="text-gasq-muted mb-3">{{ $pageSubtitle }}</p>
                        <div class="d-flex flex-wrap gap-2">
                            <span class="account-edit-chip"><i class="fa fa-envelope"></i>{{ $user->email }}</span>
                            <span class="account-edit-chip"><i class="fa fa-user-shield"></i>{{ ucfirst($user->user_type ?? 'buyer') }}</span>
                            <span class="account-edit-chip"><i class="fa fa-badge-check"></i>{{ $user->email_verified_at ? 'Email Verified' : 'Email Not Verified' }}</span>
                        </div>
                    </div>
                </div>
                <a href="{{ route('profile.show') }}" class="btn btn-outline-secondary"><i class="fa fa-arrow-left me-2"></i>Back to Profile</a>
            </div>
        </div>

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

        <div class="account-edit-form-grid">
            <div>
                <section class="account-edit-section account-edit-panel">
                    <div class="account-edit-heading">Profile Information</div>
                    <p class="account-edit-note mb-4">These details control your account identity and the primary contact information used across the platform.</p>
                    <div>
                        <form action="{{ route('profile.update') }}" method="POST" id="profileForm">
                            @csrf
                            @method('PUT')
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="form-label">Full Name</label>
                                    <input type="text" class="form-control @error('name') is-invalid @enderror" name="name" value="{{ old('name', $user->name) }}" placeholder="Your full name">
                                    @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Email</label>
                                    <input type="email" class="form-control @error('email') is-invalid @enderror" name="email" value="{{ old('email', $user->email) }}" placeholder="your@email.com">
                                    @error('email')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Company</label>
                                    <input type="text" class="form-control @error('company') is-invalid @enderror" name="company" value="{{ old('company', $user->company) }}" placeholder="Your company">
                                    @error('company')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Phone</label>
                                    <div class="d-flex flex-column gap-2">
                                        <div class="d-flex gap-2 align-items-start">
                                            <input
                                                type="text"
                                                class="form-control @error('phone') is-invalid @enderror"
                                                id="profile_phone"
                                                name="phone"
                                                value="{{ $formPhone }}"
                                                placeholder="+10000000000"
                                                autocomplete="tel"
                                            >
                                            <button type="submit" class="btn btn-outline-primary flex-shrink-0" id="phone_verify_trigger" form="profilePhoneSendForm">
                                                Verify
                                            </button>
                                            <span class="badge text-bg-success align-self-center d-none" id="phone_verified_badge">Verified</span>
                                        </div>
                                        <div class="account-edit-note" id="phone_help_text">We format the number automatically. Save stays disabled until the active phone number is verified.</div>
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
                                <div class="col-md-5">
                                    <label class="form-label">City</label>
                                    <input type="text" class="form-control @error('city') is-invalid @enderror" name="city" value="{{ old('city', $user->city) }}" placeholder="City">
                                    @error('city')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label">State</label>
                                    <input type="text" class="form-control @error('state') is-invalid @enderror" name="state" value="{{ old('state', $user->state) }}" placeholder="State">
                                    @error('state')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">ZIP</label>
                                    <input type="text" class="form-control @error('zip_code') is-invalid @enderror" name="zip_code" value="{{ old('zip_code', $user->zip_code) }}" placeholder="ZIP">
                                    @error('zip_code')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                </div>
                            </div>
                            <div class="d-flex flex-wrap gap-2 mt-4">
                                <button type="submit" class="btn btn-primary" id="profile_save_button" @disabled(! $phoneCanSave)><i class="fa fa-check me-2"></i>Save Changes</button>
                                <a href="{{ route('profile.show') }}" class="btn btn-outline-secondary">Cancel</a>
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
                    </div>
                </section>
            </div>

            <div class="d-grid gap-4">
                <section class="account-edit-section account-edit-panel">
                    <div class="account-edit-heading">Account Snapshot</div>
                    <p class="account-edit-note">A quick reference while you edit your profile and access settings.</p>
                    <div class="account-edit-aside-list">
                        <div class="account-edit-aside-item">
                            <span>Account Type</span>
                            <strong class="text-capitalize">{{ $user->user_type ?? 'buyer' }}</strong>
                        </div>
                        <div class="account-edit-aside-item">
                            <span>Email Status</span>
                            <strong>{{ $user->email_verified_at ? 'Verified' : 'Not verified' }}</strong>
                        </div>
                        <div class="account-edit-aside-item">
                            <span>Phone Status</span>
                            <strong>{{ $phoneCanSave && $normalizedFormPhone !== '' ? 'Verified' : ($normalizedFormPhone === '' ? 'Optional' : 'Needs verification') }}</strong>
                        </div>
                    </div>
                    <p class="account-edit-note mb-0">If you change the active phone number, verify it before saving so platform notifications continue working correctly.</p>
                </section>

                <section class="account-edit-section account-edit-panel">
                    <div class="account-edit-heading">Password & Access</div>
                    <p class="account-edit-note mb-4">Keep your sign-in credentials current and secure.</p>
                    <div>
                        @if(!$user->google_id)
                            <form action="{{ route('profile.password.update') }}" method="POST">
                                @csrf
                                @method('PUT')
                                <div class="mb-3">
                                    <label class="form-label">Current Password</label>
                                    <input type="password" class="form-control @error('current_password') is-invalid @enderror" name="current_password" placeholder="Current password">
                                    @error('current_password')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">New Password</label>
                                    <input type="password" class="form-control @error('password') is-invalid @enderror" name="password" placeholder="New password">
                                    @error('password')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                </div>
                                <div class="mb-4">
                                    <label class="form-label">Confirm New Password</label>
                                    <input type="password" class="form-control" name="password_confirmation" placeholder="Confirm new password">
                                </div>
                                <button type="submit" class="btn btn-warning"><i class="fa fa-lock me-2"></i>Update Password</button>
                            </form>
                        @else
                            <p class="text-gasq-muted mb-0">This account uses a social sign-in method, so password changes may not apply here.</p>
                        @endif
                    </div>
                </section>
            </div>
        </div>
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

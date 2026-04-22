@extends('layouts.auth')

@section('title', 'Verify Phone')

@section('content')
@php
    $phoneVerification = $phoneVerification ?? ['phone' => '', 'verified' => false];
    $verifiedCandidatePhone = (string) ($phoneVerification['phone'] ?? '');
    $isVerifiedCandidate = (bool) ($phoneVerification['verified'] ?? false);
@endphp

<div class="text-center mb-4">
    <h1 class="h4 fw-bold text-gasq-foreground mb-2">Verify your phone</h1>
    <p class="text-gasq-muted small mb-0">
        Enter or update your phone number, request a verification code, and confirm it here before continuing.
    </p>
</div>

<div class="card gasq-card shadow-sm mx-auto" style="max-width: 32rem;">
    <div class="card-body p-4 p-lg-5">
        @if (session('status'))
            <div class="alert alert-success small">{{ session('status') }}</div>
        @endif

        <div class="mb-3">
            <label class="form-label">Phone number</label>
            <div class="d-flex gap-2 align-items-start">
                <input
                    type="text"
                    id="auth_phone"
                    class="form-control form-control-lg @error('phone') is-invalid @enderror"
                    value="{{ $phone }}"
                    placeholder="+12345678900"
                    autocomplete="tel"
                    autofocus
                >
                <button type="submit" class="btn btn-outline-primary btn-lg flex-shrink-0" id="auth_phone_verify_trigger" form="authPhoneSendForm">
                    Verify
                </button>
                <span class="badge text-bg-success align-self-center d-none" id="auth_phone_verified_badge">Verified</span>
            </div>
            @error('phone')
                <div class="invalid-feedback d-block">{{ $message }}</div>
            @enderror
            <div class="form-text">Use E.164 format, for example `+12345678900`.</div>
        </div>

        <div class="mb-4 d-none" id="auth_phone_otp_wrap">
            <label class="form-label">Verification code</label>
            <div class="d-flex gap-2">
                <input
                    type="text"
                    id="auth_phone_otp"
                    class="form-control form-control-lg @error('code') is-invalid @enderror @error('otp') is-invalid @enderror"
                    value="{{ old('code') }}"
                    placeholder="123456"
                    inputmode="numeric"
                    autocomplete="one-time-code"
                    form="authPhoneVerifyForm"
                >
                <button type="submit" class="btn btn-primary btn-lg flex-shrink-0" form="authPhoneVerifyForm">Confirm</button>
            </div>
            @error('code')
                <div class="invalid-feedback d-block">{{ $message }}</div>
            @enderror
            @error('otp')
                <div class="invalid-feedback d-block">{{ $message }}</div>
            @enderror
        </div>

        <form action="{{ route('phone.verify.send') }}" method="POST" id="authPhoneSendForm" class="d-none">
            @csrf
            <input type="hidden" name="phone" id="auth_phone_send_value" value="{{ $phone }}">
        </form>

        <form action="{{ route('phone.verify.check') }}" method="POST" id="authPhoneVerifyForm" class="d-none">
            @csrf
            <input type="hidden" name="phone" id="auth_phone_verify_value" value="{{ $phone }}">
            <input type="hidden" name="code" id="auth_phone_verify_code" value="{{ old('code') }}">
        </form>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const phoneInput = document.getElementById('auth_phone');
    const verifyButton = document.getElementById('auth_phone_verify_trigger');
    const verifiedBadge = document.getElementById('auth_phone_verified_badge');
    const otpWrap = document.getElementById('auth_phone_otp_wrap');
    const sendPhoneHidden = document.getElementById('auth_phone_send_value');
    const verifyPhoneHidden = document.getElementById('auth_phone_verify_value');
    const verifyCodeVisible = document.getElementById('auth_phone_otp');
    const verifyCodeHidden = document.getElementById('auth_phone_verify_code');

    const verifiedCandidatePhone = @json($verifiedCandidatePhone);
    const verifiedCandidate = @json($isVerifiedCandidate);
    const hasOtpError = @json($errors->has('otp') || $errors->has('code'));
    const hasPhoneError = @json($errors->has('phone'));
    const hasStatusMessage = @json((string) session('status', ''));

    function normalizePhone(value) {
        return (value || '').trim().replace(/[\s\-()]+/g, '');
    }

    function syncHiddenTargets() {
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
        if (!phoneInput || !verifyButton || !verifiedBadge || !otpWrap) {
            return;
        }

        const currentPhone = normalizePhone(phoneInput.value);
        const verifiedPhone = normalizePhone(verifiedCandidatePhone);
        const currentPhoneVerified = verifiedCandidate && currentPhone !== '' && currentPhone === verifiedPhone;

        verifyButton.classList.toggle('d-none', currentPhone === '' || currentPhoneVerified);
        verifiedBadge.classList.toggle('d-none', !currentPhoneVerified);

        const showOtp = !currentPhoneVerified && currentPhone !== '' && (hasOtpError || hasPhoneError || hasStatusMessage || currentPhone === verifiedPhone);
        otpWrap.classList.toggle('d-none', !showOtp);

        syncHiddenTargets();
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

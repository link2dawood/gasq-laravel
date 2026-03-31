@extends('layouts.auth')

@section('title', 'Verify Phone')

@section('content')
<div class="text-center mb-4">
    <h1 class="h4 fw-bold text-gasq-foreground mb-2">Verify your phone</h1>
    <p class="text-gasq-muted small mb-0">
        Enter the code we sent to <span class="fw-semibold">{{ $phone }}</span>.
    </p>
</div>

<div class="card gasq-card shadow-sm mx-auto" style="max-width: 28rem;">
    <div class="card-body p-4 p-lg-5">
        @if (session('status'))
            <div class="alert alert-success small">{{ session('status') }}</div>
        @endif

        <form action="{{ route('phone.verify.check') }}" method="POST" autocomplete="off" novalidate>
            @csrf
            <div class="mb-3">
                <label class="form-label">Verification code</label>
                <input
                    type="text"
                    name="code"
                    class="form-control form-control-lg @error('code') is-invalid @enderror @error('otp') is-invalid @enderror"
                    value="{{ old('code') }}"
                    placeholder="123456"
                    inputmode="numeric"
                    autocomplete="one-time-code"
                    autofocus
                >
                @error('code')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
                @error('otp')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>

            <button type="submit" class="btn btn-primary btn-lg w-100 mb-3">Verify</button>
        </form>

        <form action="{{ route('phone.verify.send') }}" method="POST" class="text-center">
            @csrf
            <button type="submit" class="btn btn-link p-0 text-primary text-decoration-none">
                Resend code
            </button>
        </form>
    </div>
</div>

@endsection


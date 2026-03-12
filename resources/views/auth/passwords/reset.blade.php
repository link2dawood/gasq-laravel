@extends('layouts.auth')

@section('title', 'Reset Password')

@section('content')
<div class="text-center mb-4">
    <h1 class="h4 fw-bold text-gasq-foreground mb-2">Reset your password</h1>
    <p class="text-gasq-muted small mb-0">Enter your email and choose a new password.</p>
</div>

<div class="card gasq-card shadow-sm mx-auto" style="max-width: 28rem;">
    <div class="card-body p-4 p-lg-5">
        <form action="{{ route('password.update') }}" method="POST" autocomplete="off" novalidate>
            @csrf

            <input type="hidden" name="token" value="{{ $token }}">

            <div class="mb-3">
                <label for="email" class="form-label">Email</label>
                <input
                    type="email"
                    name="email"
                    id="email"
                    value="{{ $email ?? old('email') }}"
                    class="form-control form-control-lg @error('email') is-invalid @enderror"
                    placeholder="your@email.com"
                    autocomplete="email"
                    autofocus
                    required
                >
                @error('email')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>

            <div class="mb-3">
                <label for="password" class="form-label">Password</label>
                <input
                    type="password"
                    name="password"
                    id="password"
                    class="form-control form-control-lg @error('password') is-invalid @enderror"
                    placeholder="Your new password"
                    autocomplete="new-password"
                    required
                >
                @error('password')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>

            <div class="mb-4">
                <label for="password_confirmation" class="form-label">Confirm Password</label>
                <input
                    type="password"
                    name="password_confirmation"
                    id="password_confirmation"
                    class="form-control form-control-lg"
                    placeholder="Confirm your new password"
                    autocomplete="new-password"
                    required
                >
            </div>

            <button type="submit" class="btn btn-primary btn-lg w-100">
                Reset password
            </button>
        </form>
    </div>
</div>

<p class="text-center text-gasq-muted small mt-4 mb-0">
    Remember your password?
    <a href="{{ route('login') }}" class="text-primary fw-medium text-decoration-none">Sign in</a>
</p>
@endsection

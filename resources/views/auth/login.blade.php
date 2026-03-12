@extends('layouts.auth')

@section('title', 'Sign In')

@section('content')
<div class="text-center mb-4">
    <h1 class="h4 fw-bold text-gasq-foreground mb-2">Login to your account</h1>
    <p class="text-gasq-muted small mb-0">Enter your email and password to access your account.</p>
</div>

<div class="card gasq-card shadow-sm mx-auto" style="max-width: 28rem;">
    <div class="card-body p-4 p-lg-5">
        <form action="{{ route('login') }}" method="POST" autocomplete="off" novalidate>
            @csrf

            <div class="mb-3">
                <label for="email" class="form-label">Email</label>
                <input
                    type="email"
                    name="email"
                    id="email"
                    value="{{ old('email') }}"
                    class="form-control form-control-lg @error('email') is-invalid @enderror"
                    placeholder="your@email.com"
                    autocomplete="email"
                    autofocus
                >
                @error('email')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>

            <div class="mb-3">
                <label for="password" class="form-label d-flex justify-content-between align-items-center">
                    <span>Password</span>
                    @if (Route::has('password.request'))
                        <a href="{{ route('password.request') }}" class="small text-primary text-decoration-none">Forgot password?</a>
                    @endif
                </label>
                <input
                    type="password"
                    name="password"
                    id="password"
                    class="form-control form-control-lg @error('password') is-invalid @enderror"
                    placeholder="Your password"
                    autocomplete="current-password"
                >
                @error('password')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>

            <div class="mb-4">
                <div class="form-check">
                    <input
                        type="checkbox"
                        class="form-check-input"
                        name="remember"
                        id="remember"
                        {{ old('remember') ? 'checked' : '' }}
                    >
                    <label class="form-check-label small" for="remember">Remember me</label>
                </div>
            </div>

            <button type="submit" class="btn btn-primary btn-lg w-100">
                Sign in
            </button>
        </form>
    </div>
</div>

<p class="text-center text-gasq-muted small mt-4 mb-0">
    Don't have an account?
    <a href="{{ route('register') }}" class="text-primary fw-medium text-decoration-none">Sign up</a>
</p>
@endsection


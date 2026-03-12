@extends('layouts.auth')

@section('title', 'Sign Up')

@section('content')
<div class="text-center mb-4">
    <h1 class="h4 fw-bold text-gasq-foreground mb-2">Create new account</h1>
    <p class="text-gasq-muted small mb-0">Join as a buyer or vendor. Enter your details to get started.</p>
</div>

<div class="card gasq-card shadow-sm mx-auto" style="max-width: 28rem;">
    <div class="card-body p-4 p-lg-5">
        <form action="{{ route('register') }}" method="POST" autocomplete="off" novalidate>
            @csrf

            <div class="mb-3">
                <label class="form-label">Name</label>
                <input
                    type="text"
                    name="name"
                    class="form-control form-control-lg @error('name') is-invalid @enderror"
                    value="{{ old('name') }}"
                    placeholder="Enter your name"
                    autocomplete="name"
                    autofocus
                >
                @error('name')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>

            <div class="mb-3">
                <label class="form-label">Email address</label>
                <input
                    type="email"
                    name="email"
                    class="form-control form-control-lg @error('email') is-invalid @enderror"
                    value="{{ old('email') }}"
                    placeholder="your@email.com"
                    autocomplete="email"
                >
                @error('email')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>

            <div class="mb-3">
                <label class="form-label">I am a</label>
                <select
                    name="user_type"
                    class="form-select form-select-lg @error('user_type') is-invalid @enderror"
                    required
                >
                    <option value="buyer" {{ old('user_type', 'buyer') === 'buyer' ? 'selected' : '' }}>
                        Buyer (need security services)
                    </option>
                    <option value="vendor" {{ old('user_type') === 'vendor' ? 'selected' : '' }}>
                        Vendor (provide security services)
                    </option>
                </select>
                @error('user_type')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>

            <div class="mb-3">
                <label class="form-label">Company <span class="text-gasq-muted">(optional)</span></label>
                <input
                    type="text"
                    name="company"
                    class="form-control form-control-lg @error('company') is-invalid @enderror"
                    value="{{ old('company') }}"
                    placeholder="Your company name"
                >
                @error('company')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>

            <div class="mb-3">
                <label class="form-label">Phone <span class="text-gasq-muted">(optional)</span></label>
                <input
                    type="text"
                    name="phone"
                    class="form-control form-control-lg @error('phone') is-invalid @enderror"
                    value="{{ old('phone') }}"
                    placeholder="+1 234 567 8900"
                >
                @error('phone')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>

            <div class="mb-3">
                <label class="form-label">Password</label>
                <input
                    type="password"
                    name="password"
                    class="form-control form-control-lg @error('password') is-invalid @enderror"
                    placeholder="Your password"
                    autocomplete="new-password"
                >
                @error('password')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>

            <div class="mb-3">
                <label class="form-label">Confirm Password</label>
                <input
                    type="password"
                    name="password_confirmation"
                    class="form-control form-control-lg"
                    placeholder="Confirm your password"
                    autocomplete="new-password"
                >
            </div>

            <div class="mb-4">
                <div class="form-check">
                    <input
                        type="checkbox"
                        class="form-check-input"
                        name="terms"
                        id="terms"
                        required
                    >
                    <label class="form-check-label small" for="terms">
                        I agree to the
                        <a href="{{ url('/#how-it-works') }}" class="text-primary text-decoration-none">terms and policy</a>.
                    </label>
                </div>
            </div>

            <button type="submit" class="btn btn-primary btn-lg w-100">
                Create account
            </button>
        </form>
    </div>
</div>

<p class="text-center text-gasq-muted small mt-4 mb-0">
    Already have an account?
    <a href="{{ route('login') }}" class="text-primary fw-medium text-decoration-none">Sign in</a>
</p>
@endsection


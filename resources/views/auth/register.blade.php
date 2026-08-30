@extends('layouts.app')

@section('title', 'Sign Up')

@section('content')
<div class="container py-5">
    <div class="text-center mb-4">
        <h1 class="h4 fw-bold text-gasq-foreground mb-2">Create new account</h1>
        <p class="text-gasq-muted small mb-0">Join as a buyer or vendor. Enter your details to get started.</p>
    </div>

    @php($betaClosedReason = \App\Support\Beta::closedReason())
    @php($betaSpotsLeft = \App\Support\Beta::spotsRemaining())

    @if($betaClosedReason)
        {{-- Beta full or past its closing date: explain and offer a way in, rather
             than showing a form that can only fail on submit. --}}
        <div class="card gasq-card shadow-sm mx-auto" style="max-width: 28rem;">
            <div class="card-body p-4 p-lg-5 text-center">
                <i class="fa fa-lock fa-2x text-gasq-muted mb-3"></i>
                <h2 class="h5 fw-bold mb-2">Beta registration is closed</h2>
                <p class="text-gasq-muted small mb-4">{{ $betaClosedReason }}</p>
                <a href="{{ route('contact') }}" class="btn btn-primary w-100 mb-2">Join the waiting list</a>
                <a href="{{ route('instant-estimator.index') }}" class="btn btn-outline-primary w-100">
                    Run a free estimate instead
                </a>
                <p class="small text-gasq-muted mt-3 mb-0">
                    Already have an account? <a href="{{ route('login') }}">Sign in</a>
                </p>
            </div>
        </div>
    @else
    <div class="card gasq-card shadow-sm mx-auto" style="max-width: 28rem;">
        <div class="card-body p-4 p-lg-5">
            @if($betaSpotsLeft !== null || \App\Support\Beta::hasClosingDate())
                {{-- Remaining places / closing date. Real limits, and they give
                     outreach a deadline to point at. --}}
                <div class="alert alert-info py-2 px-3 small mb-3">
                    @if($betaSpotsLeft !== null)
                        <strong>{{ $betaSpotsLeft }}</strong> beta {{ \Illuminate\Support\Str::plural('place', $betaSpotsLeft) }} left.
                    @endif
                    @if(\App\Support\Beta::hasClosingDate())
                        Beta closes {{ \App\Support\Beta::closingDate()->format('j F Y') }}.
                    @endif
                </div>
            @endif

            <form action="{{ route('register') }}" method="POST" autocomplete="off" novalidate>
                @csrf

                @if(config('beta.invite_only'))
                    {{-- Invite gate. Prefilled from ?invite=CODE so an invite link
                         just works and the tester never has to copy a code across. --}}
                    <div class="mb-3">
                        <label class="form-label">Invite code</label>
                        <input type="text" name="invite_code"
                               class="form-control form-control-lg @error('invite_code') is-invalid @enderror"
                               value="{{ old('invite_code', request()->query('invite')) }}"
                               placeholder="Your GASQ beta invite code" autocomplete="off">
                        @error('invite_code')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        <div class="form-text">GASQ is in closed beta — accounts are created by invitation.</div>
                    </div>
                @endif

                <div class="mb-3">
                    <label class="form-label">Name</label>
                    <input type="text" name="name" class="form-control form-control-lg @error('name') is-invalid @enderror" value="{{ old('name') }}" placeholder="Enter your name" autocomplete="name" autofocus>
                    @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>

                <div class="mb-3">
                    <label class="form-label">Email address</label>
                    <input type="email" name="email" class="form-control form-control-lg @error('email') is-invalid @enderror" value="{{ old('email') }}" placeholder="your@email.com" autocomplete="email">
                    @error('email')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>

                <div class="mb-3">
                    <label class="form-label">I am a</label>
                    <select name="user_type" class="form-select form-select-lg @error('user_type') is-invalid @enderror" required>
                        <option value="buyer" {{ old('user_type', 'buyer') === 'buyer' ? 'selected' : '' }}>Buyer (need security services)</option>
                        <option value="vendor" {{ old('user_type') === 'vendor' ? 'selected' : '' }}>Vendor (provide security services)</option>
                    </select>
                    @error('user_type')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>

                <div class="mb-3">
                    <label class="form-label">Company <span class="text-gasq-muted">(optional)</span></label>
                    <input type="text" name="company" class="form-control form-control-lg @error('company') is-invalid @enderror" value="{{ old('company') }}" placeholder="Your company name">
                    @error('company')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>

                <div class="mb-3">
                    <label class="form-label">Phone</label>
                    <input type="text" name="phone" class="form-control form-control-lg @error('phone') is-invalid @enderror" value="{{ old('phone') }}" placeholder="+1 234 567 8900">
                    @error('phone')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>

                <div class="mb-3">
                    <label class="form-label">Password</label>
                    <div class="input-group input-group-lg has-validation">
                        <input type="password" name="password" id="password" class="form-control @error('password') is-invalid @enderror" placeholder="Your password" autocomplete="new-password">
                        <button class="btn btn-outline-secondary" type="button" tabindex="-1" aria-label="Show password" onclick="gasqTogglePassword('password', this)">
                            <i class="fa fa-eye"></i>
                        </button>
                        @error('password')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                </div>

                <div class="mb-3">
                    <label class="form-label">Confirm Password</label>
                    <div class="input-group input-group-lg">
                        <input type="password" name="password_confirmation" id="password_confirmation" class="form-control" placeholder="Confirm your password" autocomplete="new-password">
                        <button class="btn btn-outline-secondary" type="button" tabindex="-1" aria-label="Show password" onclick="gasqTogglePassword('password_confirmation', this)">
                            <i class="fa fa-eye"></i>
                        </button>
                    </div>
                </div>

                <div class="mb-4">
                    <div class="form-check">
                        <input type="checkbox" class="form-check-input" name="terms" id="terms" required>
                        <label class="form-check-label small" for="terms">
                            I agree to the
                            <a href="{{ route('terms') }}" class="text-primary text-decoration-none">Terms &amp; Conditions</a>
                            and
                            <a href="{{ route('privacy-policy') }}" class="text-primary text-decoration-none">Privacy Policy</a>.
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
    @endif
</div>

@push('scripts')
<script>
    function gasqTogglePassword(id, btn) {
        var input = document.getElementById(id);
        if (!input) return;
        var icon = btn.querySelector('i');
        var showing = input.type === 'password';
        input.type = showing ? 'text' : 'password';
        if (icon) {
            icon.classList.toggle('fa-eye', !showing);
            icon.classList.toggle('fa-eye-slash', showing);
        }
        btn.setAttribute('aria-label', showing ? 'Hide password' : 'Show password');
    }
</script>
@endpush
@endsection

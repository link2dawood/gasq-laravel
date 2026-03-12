@extends('layouts.auth')

@section('title', 'Forgot Password')

@section('content')
<div class="text-center mb-4">
    <h1 class="h4 fw-bold text-gasq-foreground mb-2">Forgot your password?</h1>
    <p class="text-gasq-muted small mb-0">Enter your email and we'll send you a link to reset your password.</p>
</div>

<div class="card gasq-card shadow-sm mx-auto" style="max-width: 28rem;">
    <div class="card-body p-4 p-lg-5">
        @if (session('status'))
            <div class="alert alert-success mb-4" role="alert">
                {{ session('status') }}
            </div>
        @endif

        @if ($errors->has('email'))
            <div class="alert alert-danger mb-4" role="alert">
                {{ $errors->first('email') }}
            </div>
        @endif

        <form action="{{ route('password.email') }}" method="POST" autocomplete="off" novalidate>
            @csrf

            <div class="mb-4">
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
                    required
                >
                @error('email')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>

            <button type="submit" class="btn btn-primary btn-lg w-100">
                Send password reset link
            </button>
        </form>
    </div>
</div>

<p class="text-center text-gasq-muted small mt-4 mb-0">
    Remember your password?
    <a href="{{ route('login') }}" class="text-primary fw-medium text-decoration-none">Sign in</a>
</p>
@endsection

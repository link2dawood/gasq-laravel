@extends('layouts.auth')

@section('title', 'Verify Email')

@section('content')
<div class="text-center mb-4">
    <h1 class="h4 fw-bold text-gasq-foreground mb-2">Verify your email address</h1>
    <p class="text-gasq-muted small mb-0">
        Before continuing, please check your inbox for a verification link. If you didn't receive the email, we can send another.
    </p>
</div>

<div class="card gasq-card shadow-sm mx-auto" style="max-width: 28rem;">
    <div class="card-body p-4 p-lg-5 text-center">
        @if (session('status') == 'verification-link-sent')
            <div class="alert alert-success mb-4" role="alert">
                A new verification link has been sent to your email address.
            </div>
        @endif

        <form action="{{ route('verification.send') }}" method="POST" class="mb-0">
            @csrf
            <button type="submit" class="btn btn-primary btn-lg w-100">
                Resend verification email
            </button>
        </form>
    </div>
</div>

<p class="text-center text-gasq-muted small mt-4 mb-0">
    <a href="{{ route('login') }}" class="text-primary fw-medium text-decoration-none">Back to sign in</a>
</p>
@endsection

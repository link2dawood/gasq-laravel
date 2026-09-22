{{-- Invitation landing + email verification (spec 4-5). --}}
@extends('private-estimates.buyer._shell', ['title' => 'Private Estimate Invitation', 'steps' => 0])

@section('content')
    <div class="pe-card">
        <div class="pe-kicker">Private Estimate {{ $estimate->public_id }}</div>
        <h1>You've Been Invited to Receive a Private Security Service Estimate</h1>

        <p><strong>{{ $vendorName }}</strong> has invited you to complete a private security service estimate
            through Get A Security Quote.</p>

        <p class="pe-meta" style="margin-bottom:22px;">
            You'll confirm what you need, {{ $vendorName }} reviews it, and only then is the estimate priced.
            @if($estimate->site_name) This invitation covers <strong>{{ $estimate->site_name }}</strong>.@endif
        </p>

        @if($estimate->buyer_email_verified_at || $errors->any() || session('status'))
            <form method="POST" action="{{ route('private-estimates.buyer.verify-code', $token) }}">
                @csrf
                <label for="code" style="display:block;font-weight:600;margin-bottom:8px;">Enter your six-digit code</label>
                <input id="code" name="code" class="pe-field pe-code" inputmode="numeric" autocomplete="one-time-code"
                       maxlength="6" pattern="[0-9]*" required autofocus>
                <p class="pe-meta" style="margin:10px 0 18px;">Sent to {{ $estimate->buyer_email }} · expires in 10 minutes.</p>
                <button type="submit" class="pe-btn">Open My Estimate</button>
            </form>

            <form method="POST" action="{{ route('private-estimates.buyer.send-code', $token) }}" style="margin-top:14px;">
                @csrf
                <button type="submit" class="pe-btn secondary">Send a new code</button>
            </form>
        @else
            <form method="POST" action="{{ route('private-estimates.buyer.send-code', $token) }}">
                @csrf
                <button type="submit" class="pe-btn">Review Private Invitation</button>
                <p class="pe-meta" style="margin-top:12px;">
                    We'll email a six-digit code to {{ $estimate->buyer_email }} to confirm it's you.
                </p>
            </form>
        @endif
    </div>

    <p class="pe-meta" style="margin-top:16px;">
        This invitation expires {{ $invitation->expires_at->format('F j, Y') }}.
    </p>
@endsection

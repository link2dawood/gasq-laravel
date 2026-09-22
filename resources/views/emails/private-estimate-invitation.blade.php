@extends('emails.layouts.gasq-base', ['preheader' => 'A private security service estimate is waiting for you.'])

@section('content')
    <h2 style="margin:0 0 12px;font-size:20px;color:#0b2447;">You've Been Invited to Receive a Private Security Service Estimate</h2>

    <p style="margin:0 0 16px;">
        <strong>{{ $vendorName }}</strong> has invited you to complete a private security service estimate
        through Get A Security Quote.
    </p>

    <p style="margin:0 0 16px;">
        You'll confirm what you need, {{ $vendorName }} will review it, and only then is the estimate priced.
        It takes about five minutes.
    </p>

    <p style="margin:0 0 24px;">
        <a href="{{ $url }}"
           style="display:inline-block;background:#0d6efd;color:#ffffff;text-decoration:none;padding:12px 22px;border-radius:6px;font-weight:bold;">
            Review Private Invitation
        </a>
    </p>

    <p style="margin:0 0 8px;font-size:13px;color:#6b7280;">
        Estimate {{ $estimate->public_id }}@if($estimate->site_name) · {{ $estimate->site_name }}@endif
    </p>
    @if($expiresAt)
        <p style="margin:0 0 8px;font-size:13px;color:#6b7280;">
            This invitation expires on {{ $expiresAt->format('F j, Y') }}.
        </p>
    @endif
    <p style="margin:0;font-size:13px;color:#6b7280;">
        This invitation was prepared for {{ $estimate->buyer_email }}. We'll email a six-digit code to confirm
        it's you before anything is shown.
    </p>
@endsection

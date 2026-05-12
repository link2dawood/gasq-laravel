@php
    $job = $invitation->opportunity->jobPosting;
@endphp
@extends('emails.layouts.gasq-base')
@section('content')

<h2 style="margin:0 0 4px;color:#dc3545;">⏰ Pricing Submission Needed</h2>
<p style="color:#64748b;margin:0 0 18px;">{{ $job?->title ?? 'Accepted opportunity' }}</p>

<p>Hi {{ $notifiable->name ?? 'Security Professional' }},</p>

<p>You accepted this opportunity but your pricing submission is still outstanding. Your 24-hour bid window is still open — submit your pricing now to remain eligible.</p>

<p style="background:#fff3cd;color:#664d03;padding:12px 16px;border-radius:6px;">
    ⚠️ <strong>Action required.</strong> If pricing isn't submitted before the window closes, your slot will be released to another vendor.
</p>

<p style="margin:24px 0;">
    <a href="{{ $url }}"
       style="background:#dc3545;color:#fff;padding:12px 22px;border-radius:6px;text-decoration:none;display:inline-block;font-weight:600;">
        Submit Bid Now
    </a>
</p>

@endsection

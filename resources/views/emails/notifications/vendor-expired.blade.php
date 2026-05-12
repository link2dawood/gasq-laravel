@php
    $job = $invitation->opportunity->jobPosting;
@endphp
@extends('emails.layouts.gasq-base')
@section('content')

<h2 style="margin:0 0 4px;color:#64748b;">Opportunity Window Closed</h2>
<p style="color:#64748b;margin:0 0 18px;">{{ $job?->title ?? 'GASQ opportunity' }}</p>

<p>Hi {{ $notifiable->name ?? 'Security Professional' }},</p>

<p>The response window for this opportunity has closed. Slots have been awarded to other vendors who responded inside the response window.</p>

<p style="background:#e7f1ff;color:#084298;padding:12px 16px;border-radius:6px;">
    💡 Pre-qualified GASQ opportunities move fast. Vendors who respond within the first hours of receiving an alert have the highest acceptance rates. Open notifications promptly to claim future opportunities.
</p>

<p style="margin:24px 0;">
    <a href="{{ route('vendor-leads.index') }}"
       style="background:#0d6efd;color:#fff;padding:12px 22px;border-radius:6px;text-decoration:none;display:inline-block;font-weight:600;">
        View My Opportunities
    </a>
</p>

@endsection

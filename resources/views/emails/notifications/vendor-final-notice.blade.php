@php
    $job = $invitation->opportunity->jobPosting;
    $value = $invitation->opportunity->estimated_annual_contract_value ?? 0;
@endphp
@extends('emails.layouts.gasq-base')
@section('content')

<h2 style="margin:0 0 4px;color:#dc3545;">🚨 FINAL NOTICE — Closing Soon</h2>
<p style="color:#64748b;margin:0 0 18px;">{{ \App\Support\LeadFormatting::moneyShort($value) }} contract — {{ $job?->location ?? 'Location TBD' }}</p>

<p>Hi {{ $notifiable->name ?? 'Security Professional' }},</p>

<p style="background:#f8d7da;color:#842029;padding:14px 16px;border-radius:6px;font-weight:600;">
    Vendor selection for this pre-qualified opportunity is closing soon. After this window closes, the slot will be released to other vendors.
</p>

<p>If you intend to participate, submit your response now to retain your invitation.</p>

<p style="margin:24px 0;">
    <a href="{{ $url }}"
       style="background:#dc3545;color:#fff;padding:14px 28px;border-radius:6px;text-decoration:none;display:inline-block;font-weight:700;font-size:16px;">
        👉 Submit Bid Now
    </a>
</p>

@endsection

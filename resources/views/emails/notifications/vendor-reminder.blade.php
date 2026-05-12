@php
    $job = $invitation->opportunity->jobPosting;
    $value = $invitation->opportunity->estimated_annual_contract_value ?? 0;
    $maxAccepts = (int) ($invitation->opportunity->max_accepts ?: 5);
    $location = $job?->location ?? 'Location TBD';
@endphp
@extends('emails.layouts.gasq-base')
@section('content')

<h2 style="margin:0 0 4px;color:#dc3545;">⏰ Reminder: Opportunity Still Open</h2>
<p style="color:#64748b;margin:0 0 18px;">{{ \App\Support\LeadFormatting::moneyShort($value) }} contract — {{ $location }}</p>

<p>Hi {{ $notifiable->name ?? 'Security Professional' }},</p>

<p>You haven't yet responded to this <strong>pre-qualified</strong> security opportunity. Spots are filling on a first-come, first-serve basis — only <strong>{{ $maxAccepts }} qualified vendors</strong> are invited to respond.</p>

<table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%" style="background:#fff3cd;border-radius:6px;padding:14px;margin:18px 0;color:#664d03;">
    <tr><td style="padding:6px 4px;"><strong>Location:</strong> {{ $location }}</td></tr>
    <tr><td style="padding:6px 4px;"><strong>Contract Value:</strong> {{ \App\Support\LeadFormatting::moneyFull($value) }}</td></tr>
    <tr><td style="padding:6px 4px;"><strong>Lead Tier:</strong> {{ strtoupper((string) $invitation->opportunity->lead_tier) }}</td></tr>
</table>

<p style="margin:24px 0;">
    <a href="{{ $url }}"
       style="background:#198754;color:#fff;padding:14px 28px;border-radius:6px;text-decoration:none;display:inline-block;font-weight:700;font-size:16px;">
        👉 Respond Now
    </a>
</p>

<p style="color:#64748b;font-size:13px;">This opportunity has been screened using the GASQ Workforce-to-Post™ qualification system. The buyer is budget-verified and ready to engage.</p>

@endsection

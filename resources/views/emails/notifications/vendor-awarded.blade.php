@php
    $job = $invitation->opportunity->jobPosting;
    $buyer = $job?->user;
@endphp
@extends('emails.layouts.gasq-base')
@section('content')

<h2 style="margin:0 0 4px;color:#198754;">🏆 You've Been Selected</h2>
<p style="color:#64748b;margin:0 0 18px;">{{ $job?->title ?? 'GASQ opportunity' }}</p>

<p>Hi {{ $notifiable->name ?? 'Security Professional' }},</p>

<p style="background:#d1e7dd;color:#0a3622;padding:14px 16px;border-radius:6px;font-size:16px;">
    🎉 <strong>Congratulations</strong> — the buyer has selected you for this GASQ opportunity.
</p>

<table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%" style="background:#f8fafc;border-radius:6px;padding:8px 0;margin:18px 0;">
    <tr><td style="padding:10px 16px;color:#64748b;width:35%;">Job</td><td style="padding:10px 16px;font-weight:600;">{{ $job?->title }}</td></tr>
    <tr><td style="padding:10px 16px;color:#64748b;border-top:1px solid #e2e8f0;">Buyer</td><td style="padding:10px 16px;font-weight:600;border-top:1px solid #e2e8f0;">{{ $buyer?->name }}{{ $buyer?->company ? ' (' . $buyer->company . ')' : '' }}</td></tr>
    <tr><td style="padding:10px 16px;color:#64748b;border-top:1px solid #e2e8f0;">Location</td><td style="padding:10px 16px;font-weight:600;border-top:1px solid #e2e8f0;">{{ $job?->location }}</td></tr>
</table>

<p><strong>Next steps:</strong></p>
<ol style="padding-left:20px;line-height:1.8;">
    <li>Coordinate kickoff directly with the buyer</li>
    <li>Confirm start date and onboarding requirements</li>
    <li>Honor the price-lock terms agreed during bidding</li>
</ol>

<p>This award is backed by the GASQ <strong>Price Lock Guarantee</strong> and <strong>Vendor Replacement Guarantee</strong> — please refer to your vendor terms for full details.</p>

<p style="margin:24px 0;">
    <a href="{{ $url }}"
       style="background:#198754;color:#fff;padding:12px 22px;border-radius:6px;text-decoration:none;display:inline-block;font-weight:600;">
        View Award Details
    </a>
</p>

@endsection

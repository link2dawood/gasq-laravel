@php
    $job = $notification->jobPosting;
    $bid = $notification->bid;
    $buyer = $job?->user;
@endphp
@extends('emails.layouts.gasq-base')
@section('content')

<h2 style="margin:0 0 4px;color:#198754;">🎉 Congratulations — You Were Hired</h2>
<p style="color:#64748b;margin:0 0 18px;">{{ $job?->title }}</p>

<p>Hi {{ $notifiable->name ?? 'there' }},</p>

<p style="background:#d1e7dd;color:#0a3622;padding:14px 16px;border-radius:6px;font-size:16px;">
    The buyer has officially selected you for this GASQ engagement. <strong>Welcome aboard.</strong>
</p>

<table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%" style="background:#f8fafc;border-radius:6px;padding:8px 0;margin:18px 0;">
    <tr><td style="padding:10px 16px;color:#64748b;width:35%;">Buyer</td><td style="padding:10px 16px;font-weight:600;">{{ $buyer?->name }}{{ $buyer?->company ? ' (' . $buyer->company . ')' : '' }}</td></tr>
    <tr><td style="padding:10px 16px;color:#64748b;border-top:1px solid #e2e8f0;">Location</td><td style="padding:10px 16px;font-weight:600;border-top:1px solid #e2e8f0;">{{ $job?->location }}</td></tr>
    <tr><td style="padding:10px 16px;color:#64748b;border-top:1px solid #e2e8f0;">Annual Price</td><td style="padding:10px 16px;font-weight:600;border-top:1px solid #e2e8f0;">${{ number_format((float) ($bid?->annual_price ?? 0), 2) }}</td></tr>
</table>

<p><strong>Immediate next steps:</strong></p>
<ol style="padding-left:20px;line-height:1.8;">
    <li>Reach out to the buyer to coordinate kickoff</li>
    <li>Confirm the start date and onboarding requirements</li>
    <li>Honor the price-lock terms agreed during bidding</li>
</ol>

<p style="margin:24px 0;">
    <a href="{{ route('jobs.show', $job) }}"
       style="background:#198754;color:#fff;padding:12px 22px;border-radius:6px;text-decoration:none;display:inline-block;font-weight:600;">
        Open Engagement
    </a>
</p>

@endsection

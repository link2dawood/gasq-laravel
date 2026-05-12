@php
    $job = $notification->jobPosting;
    $bid = $notification->bid;
    $vendor = $bid?->user;
@endphp
@extends('emails.layouts.gasq-base')
@section('content')

<h2 style="margin:0 0 4px;color:#198754;">🤝 Hire Confirmed</h2>
<p style="color:#64748b;margin:0 0 18px;">{{ $job?->title }}</p>

<p>Hi {{ $notifiable->name ?? 'there' }},</p>

<p>Your selection has been recorded in the GASQ network. The vendor has been notified and will reach out to coordinate kickoff.</p>

<table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%" style="background:#d1e7dd;border-radius:6px;padding:8px 0;margin:18px 0;color:#0a3622;">
    <tr><td style="padding:10px 16px;width:35%;">Vendor</td><td style="padding:10px 16px;font-weight:600;">{{ $vendor?->name }}{{ $vendor?->company ? ' (' . $vendor->company . ')' : '' }}</td></tr>
    <tr><td style="padding:10px 16px;border-top:1px solid #b6dfc4;">Annual Price</td><td style="padding:10px 16px;font-weight:600;border-top:1px solid #b6dfc4;">${{ number_format((float) ($bid?->annual_price ?? 0), 2) }}</td></tr>
    <tr><td style="padding:10px 16px;border-top:1px solid #b6dfc4;">Location</td><td style="padding:10px 16px;font-weight:600;border-top:1px solid #b6dfc4;">{{ $job?->location }}</td></tr>
</table>

<p>This engagement is protected by your GASQ commitments:</p>
<ul>
    <li><strong>Price Lock Guarantee</strong> — your approved pricing is locked through the engagement</li>
    <li><strong>Vendor Replacement Guarantee</strong> — if the selected vendor fails to perform, GASQ steps in</li>
</ul>

<p style="margin:24px 0;">
    <a href="{{ route('jobs.show', $job) }}"
       style="background:#0d6efd;color:#fff;padding:12px 22px;border-radius:6px;text-decoration:none;display:inline-block;font-weight:600;">
        Open Job Dashboard
    </a>
</p>

@endsection

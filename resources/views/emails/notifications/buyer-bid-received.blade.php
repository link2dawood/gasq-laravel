@php
    $job = $invitation->opportunity->jobPosting;
    $vendor = $invitation->vendor;
    $bid = $invitation->bid;
    $annualPrice = $bid?->annual_price;
    $realismLabel = $bid?->realism_label ? ucfirst($bid->realism_label) : 'Pending';
@endphp
@extends('emails.layouts.gasq-base')
@section('content')

<h2 style="margin:0 0 4px;color:#0d6efd;">💰 Vendor Bid Received</h2>
<p style="color:#64748b;margin:0 0 18px;">{{ $job?->title ?? 'Your security service request' }}</p>

<p>Hi {{ $notifiable->name ?? 'there' }},</p>

<p><strong>{{ $vendor?->name ?? 'A vendor' }}</strong>@if($vendor?->company) ({{ $vendor->company }})@endif submitted pricing for your project.</p>

<table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%" style="background:#f8fafc;border-radius:6px;padding:8px 0;margin:18px 0;">
    <tr>
        <td style="padding:10px 16px;color:#64748b;width:50%;">Annual Price</td>
        <td style="padding:10px 16px;font-weight:600;text-align:right;">${{ number_format((float) $annualPrice, 2) }}</td>
    </tr>
    <tr>
        <td style="padding:10px 16px;color:#64748b;border-top:1px solid #e2e8f0;">Realism Review</td>
        <td style="padding:10px 16px;text-align:right;border-top:1px solid #e2e8f0;">
            <span style="background:#dbeafe;color:#1e40af;padding:3px 10px;border-radius:10px;font-size:12px;font-weight:600;">{{ $realismLabel }}</span>
        </td>
    </tr>
</table>

<p>GASQ has reviewed the pricing for realism. Open your dashboard to compare bids and move qualified vendors into the interview phase.</p>

<p style="margin:24px 0;">
    <a href="{{ route('jobs.show', $job) }}"
       style="background:#0d6efd;color:#fff;padding:12px 22px;border-radius:6px;text-decoration:none;display:inline-block;font-weight:600;">
        Review Bid &amp; Schedule Interview
    </a>
</p>

@endsection

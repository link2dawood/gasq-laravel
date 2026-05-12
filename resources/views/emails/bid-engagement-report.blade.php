@php
    $job = $bid->jobPosting;
    $vendor = $bid->user;
@endphp
@extends('emails.layouts.gasq-base')
@section('content')

<h2 style="margin:0 0 4px;color:#0d6efd;">📊 Bid Engagement Report</h2>
<p style="color:#64748b;margin:0 0 18px;">{{ $job?->title ?? 'Your security service request' }}</p>

<p>Hi {{ $job?->user?->name ?? 'there' }},</p>

<p><strong>{{ $vendor?->name ?? 'A vendor' }}</strong>@if($vendor?->company) ({{ $vendor->company }})@endif has submitted a bid for your project. The attached <strong>Bid Engagement Report</strong> shows the full Workforce Absorbed Rate breakdown, side-by-side comparison vs. your internal should-cost, and a snapshot of the vendor's operational capability.</p>

<table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%" style="background:#f8fafc;border-radius:6px;padding:8px 0;margin:18px 0;">
    <tr>
        <td style="padding:10px 16px;color:#64748b;width:50%;">Annual Cost</td>
        <td style="padding:10px 16px;font-weight:600;text-align:right;">${{ number_format((float) ($bid->annual_price ?? 0), 2) }}</td>
    </tr>
    @if($bid->hourly_bill_rate)
    <tr>
        <td style="padding:10px 16px;color:#64748b;border-top:1px solid #e2e8f0;">Hourly Bill Rate</td>
        <td style="padding:10px 16px;font-weight:600;text-align:right;border-top:1px solid #e2e8f0;">${{ number_format((float) $bid->hourly_bill_rate, 2) }}</td>
    </tr>
    @endif
    @if($bid->realism_label)
    <tr>
        <td style="padding:10px 16px;color:#64748b;border-top:1px solid #e2e8f0;">GASQ Realism Review</td>
        <td style="padding:10px 16px;text-align:right;border-top:1px solid #e2e8f0;">
            <span style="background:#dbeafe;color:#1e40af;padding:3px 10px;border-radius:10px;font-size:12px;font-weight:600;">{{ ucfirst($bid->realism_label) }}</span>
        </td>
    </tr>
    @endif
</table>

<p>The PDF attached is also yours to keep — you can share it with stakeholders for review and approval.</p>

<p style="margin:24px 0;">
    <a href="{{ $jobUrl }}"
       style="background:#0d6efd;color:#fff;padding:12px 22px;border-radius:6px;text-decoration:none;display:inline-block;font-weight:600;">
        Review Bid &amp; Schedule Interview
    </a>
</p>

<p style="color:#64748b;font-size:13px;">This bid is backed by the GASQ Price Lock Guarantee and Vendor Replacement Guarantee.</p>

@endsection

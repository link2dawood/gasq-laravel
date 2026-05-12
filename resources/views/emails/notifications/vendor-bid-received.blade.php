@php
    $job = $invitation->opportunity->jobPosting;
    $bid = $invitation->bid;
@endphp
@extends('emails.layouts.gasq-base')
@section('content')

<h2 style="margin:0 0 4px;color:#198754;">✅ Bid Submitted</h2>
<p style="color:#64748b;margin:0 0 18px;">{{ $job?->title ?? 'Your accepted opportunity' }}</p>

<p>Hi {{ $notifiable->name ?? 'Security Professional' }},</p>

<p>Your bid has been received and is now under review.</p>

@if($bid)
<table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%" style="background:#f8fafc;border-radius:6px;padding:8px 0;margin:18px 0;">
    <tr>
        <td style="padding:10px 16px;color:#64748b;width:50%;">Annual Price</td>
        <td style="padding:10px 16px;font-weight:600;text-align:right;">${{ number_format((float) $bid->annual_price, 2) }}</td>
    </tr>
    @if($bid->hourly_bill_rate)
    <tr>
        <td style="padding:10px 16px;color:#64748b;border-top:1px solid #e2e8f0;">Hourly Bill Rate</td>
        <td style="padding:10px 16px;font-weight:600;text-align:right;border-top:1px solid #e2e8f0;">${{ number_format((float) $bid->hourly_bill_rate, 2) }}</td>
    </tr>
    @endif
</table>
@endif

<p><strong>Next steps:</strong></p>
<ol style="padding-left:20px;line-height:1.8;">
    <li>GASQ reviews your pricing for realism alignment</li>
    <li>The buyer reviews qualified submissions and selects vendors for interviews</li>
    <li>You'll be notified when the buyer schedules an interview or makes a selection</li>
</ol>

<p style="margin:24px 0;">
    <a href="{{ $url }}"
       style="background:#0d6efd;color:#fff;padding:12px 22px;border-radius:6px;text-decoration:none;display:inline-block;font-weight:600;">
        View Opportunity
    </a>
</p>

@endsection

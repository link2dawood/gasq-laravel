@php
    /** @var \App\Models\Bid $bid */
    $job = $bid->jobPosting;
    $titles = [
        'submitted' => ['📩 New Bid Received', 'A vendor submitted a bid on your job.'],
        'accepted' => ['🤝 Your Bid Was Accepted', 'The buyer accepted your bid for this opportunity.'],
        'rejected' => ['Bid Update', 'The buyer reviewed your bid and chose another direction.'],
        'updated' => ['📝 Vendor Updated Their Bid', 'A vendor revised their bid on your job.'],
        'counter_offer' => ['💬 Counter Offer from Buyer', 'The buyer sent a counter offer on your bid.'],
        'vendor_accepted' => ['✅ Vendor Accepted Your Offer', 'A vendor accepted your published offer.'],
        'vendor_declined' => ['Vendor Declined Your Offer', 'A vendor declined your published offer.'],
    ];
    [$heading, $sub] = $titles[$type] ?? ['GASQ Update', 'There is an update on your bid.'];
    $isVendorRecipient = in_array($type, ['accepted', 'rejected', 'counter_offer'], true);
    $isBuyerRecipient = in_array($type, ['submitted', 'updated', 'vendor_accepted', 'vendor_declined'], true);
    $href = $isVendorRecipient
        ? route('vendor-leads.index')
        : route('jobs.show', $job);
@endphp
@extends('emails.layouts.gasq-base')
@section('content')

<h2 style="margin:0 0 4px;color:{{ $type === 'rejected' || $type === 'vendor_declined' ? '#64748b' : '#0d6efd' }};">{{ $heading }}</h2>
<p style="color:#64748b;margin:0 0 18px;">{{ $job?->title }}</p>

<p>Hi {{ $notifiable->name ?? 'there' }},</p>
<p>{{ $sub }}</p>

@if(in_array($type, ['submitted', 'updated', 'accepted'], true))
<table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%" style="background:#f8fafc;border-radius:6px;padding:8px 0;margin:18px 0;">
    @if($bid->amount > 0)
    <tr><td style="padding:10px 16px;color:#64748b;width:50%;">Bid Amount</td><td style="padding:10px 16px;font-weight:600;text-align:right;">${{ number_format((float) $bid->amount, 2) }}</td></tr>
    @endif
    @if($bid->message)
    <tr><td style="padding:10px 16px;color:#64748b;border-top:1px solid #e2e8f0;vertical-align:top;">Message</td><td style="padding:10px 16px;text-align:right;border-top:1px solid #e2e8f0;">{{ $bid->message }}</td></tr>
    @endif
</table>
@endif

@if($type === 'counter_offer' && $bid->counter_offer_amount)
<table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%" style="background:#fff3cd;border-radius:6px;padding:8px 0;margin:18px 0;color:#664d03;">
    <tr><td style="padding:10px 16px;width:50%;">Counter Offer Amount</td><td style="padding:10px 16px;font-weight:700;text-align:right;">${{ number_format((float) $bid->counter_offer_amount, 2) }}</td></tr>
    @if($bid->counter_offer_message)
    <tr><td style="padding:10px 16px;border-top:1px solid #f3e0a0;vertical-align:top;">Message</td><td style="padding:10px 16px;text-align:right;border-top:1px solid #f3e0a0;">{{ $bid->counter_offer_message }}</td></tr>
    @endif
</table>
@endif

<p style="margin:24px 0;">
    <a href="{{ $href }}"
       style="background:#0d6efd;color:#fff;padding:12px 22px;border-radius:6px;text-decoration:none;display:inline-block;font-weight:600;">
        @if($isVendorRecipient)View Opportunity@else View Job Dashboard @endif
    </a>
</p>

@endsection

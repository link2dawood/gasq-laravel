@php
    $job = $invitation->opportunity->jobPosting;
    $buyer = $job?->user;
@endphp
@extends('emails.layouts.gasq-base')
@section('content')

<h2 style="margin:0 0 4px;color:#198754;">🔓 Buyer Details Unlocked</h2>
<p style="color:#64748b;margin:0 0 18px;">{{ $job?->title ?? 'Pre-qualified opportunity' }}</p>

<p>Hi {{ $notifiable->name ?? 'Security Professional' }},</p>

<p>You accepted this opportunity. The buyer's full contact details are now available so you can begin scope verification and pricing.</p>

<table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%" style="background:#d1e7dd;border-radius:6px;padding:16px;margin:18px 0;color:#0a3622;">
    <tr><td style="padding:6px 4px;width:35%;"><strong>Buyer Name:</strong></td><td>{{ $buyer?->name ?? '—' }}</td></tr>
    <tr><td style="padding:6px 4px;"><strong>Company:</strong></td><td>{{ $buyer?->company ?? '—' }}</td></tr>
    <tr><td style="padding:6px 4px;"><strong>Email:</strong></td><td>{{ $buyer?->email ?? '—' }}</td></tr>
    <tr><td style="padding:6px 4px;"><strong>Phone:</strong></td><td>{{ $buyer?->phone ?? '—' }}</td></tr>
    <tr><td style="padding:6px 4px;"><strong>Location:</strong></td><td>{{ $job?->location ?? '—' }}</td></tr>
</table>

<p><strong>Next step:</strong> Submit your pricing within the 24-hour window to remain eligible for selection.</p>

<p style="margin:24px 0;">
    <a href="{{ $url }}"
       style="background:#0d6efd;color:#fff;padding:12px 22px;border-radius:6px;text-decoration:none;display:inline-block;font-weight:600;">
        Submit Bid
    </a>
</p>

@endsection

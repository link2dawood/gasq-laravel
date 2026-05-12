@php
    $job = $notification->opportunity->jobPosting;
    $maxAccepts = max(1, (int) ($notification->opportunity->max_accepts ?: 5));
    $acceptedCount = max(0, min($notification->acceptedCount, $maxAccepts));
    $openSlots = max($maxAccepts - $acceptedCount, 0);
    $progressPct = (int) round(($acceptedCount / $maxAccepts) * 100);
@endphp
@extends('emails.layouts.gasq-base')
@section('content')

<h2 style="margin:0 0 4px;color:#0d6efd;">📊 Vendor Acceptance Update</h2>
<p style="color:#64748b;margin:0 0 18px;">{{ $job?->title ?? 'Your security service request' }}</p>

<p>Hi {{ $notifiable->name ?? 'there' }},</p>

<p style="background:#cfe2ff;color:#084298;padding:14px 16px;border-radius:6px;font-size:16px;">
    <strong>{{ $acceptedCount }} of {{ $maxAccepts }}</strong> qualified vendors have accepted your approved bid offer.
</p>

<div style="background:#f1f5f9;border-radius:6px;padding:10px;margin:18px 0;">
    <div style="background:#10b981;height:14px;border-radius:4px;width:{{ $progressPct }}%;"></div>
</div>

<ul style="padding-left:20px;line-height:1.8;">
    <li><strong>Vendors Accepted:</strong> {{ $acceptedCount }} of {{ $maxAccepts }}</li>
    <li><strong>Open Slots Remaining:</strong> {{ $openSlots }}</li>
    <li><strong>Response Activity:</strong> {{ $acceptedCount > 0 ? 'Active' : 'Just released' }}</li>
    <li><strong>Interview Phase:</strong> {{ $acceptedCount > 0 ? 'Scheduling' : 'Pending' }}</li>
</ul>

@if($acceptedCount === 0)
    <p>Your project has been released to the GASQ vendor network. Vendors are currently reviewing your approved bid offer.</p>
@elseif($acceptedCount === 1)
    <p>Good news — your first qualified vendor has accepted. We're continuing to route your project to additional vendors.</p>
@elseif($acceptedCount < $maxAccepts)
    <p>Your project is gaining traction. You may begin reviewing vendor profiles and preparing for interviews.</p>
@else
    <p>🎉 Your project has reached full vendor acceptance. All {{ $maxAccepts }} qualified vendors are ready for interview and final selection.</p>
@endif

<p style="margin:24px 0;">
    <a href="{{ route('jobs.show', $job) }}"
       style="background:#0d6efd;color:#fff;padding:12px 22px;border-radius:6px;text-decoration:none;display:inline-block;font-weight:600;">
        Track Vendor Activity
    </a>
</p>

<p style="color:#64748b;font-size:13px;margin-top:24px;">
    <em>Snapshot at send time — live updates always available on your dashboard.</em>
</p>

@endsection

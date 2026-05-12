@php
    $job = $notification->jobPosting;
@endphp
@extends('emails.layouts.gasq-base')
@section('content')

<h2 style="margin:0 0 4px;color:#64748b;">Opportunity Awarded</h2>
<p style="color:#64748b;margin:0 0 18px;">{{ $job?->title ?? 'GASQ opportunity' }}</p>

<p>Hi {{ $notifiable->name ?? 'Security Professional' }},</p>

<p>This opportunity has been awarded to another vendor. Thank you for participating in the GASQ Vendor Network.</p>

<p>You remain active in our network and will continue to receive pre-qualified leads that match your capabilities.</p>

<p style="background:#e7f1ff;color:#084298;padding:12px 16px;border-radius:6px;">
    💡 <strong>Tip:</strong> Vendors with complete profiles and current certifications get more matches. A few minutes spent updating your profile typically pays off in stronger pipeline volume.
</p>

<p style="margin:24px 0;">
    <a href="{{ route('vendor-leads.index') }}"
       style="background:#0d6efd;color:#fff;padding:12px 22px;border-radius:6px;text-decoration:none;display:inline-block;font-weight:600;">
        View My Opportunities
    </a>
</p>

@endsection

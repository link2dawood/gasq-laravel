@php
    $job = $invitation->opportunity->jobPosting;
@endphp
@extends('emails.layouts.gasq-base')
@section('content')

<h2 style="margin:0 0 4px;color:#64748b;">Opportunity Update</h2>
<p style="color:#64748b;margin:0 0 18px;">{{ $job?->title ?? 'GASQ opportunity' }}</p>

<p>Hi {{ $notifiable->name ?? 'Security Professional' }},</p>

<p>The buyer review process for this opportunity has closed, and another vendor was selected. We appreciate the time and care you put into your bid.</p>

<p>The good news: your profile remains active in the GASQ network. We'll continue routing pre-qualified opportunities that match your capabilities directly to you.</p>

<p style="background:#e7f1ff;color:#084298;padding:12px 16px;border-radius:6px;">
    💡 <strong>Tip:</strong> Vendors with up-to-date profiles, documents, and certifications get matched to more opportunities. Take a moment to ensure your GASQ profile is current.
</p>

<p style="margin:24px 0;">
    <a href="{{ route('vendor-leads.index') }}"
       style="background:#0d6efd;color:#fff;padding:12px 22px;border-radius:6px;text-decoration:none;display:inline-block;font-weight:600;">
        View My Opportunities
    </a>
</p>

@endsection

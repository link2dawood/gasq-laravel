@extends('emails.layouts.gasq-base')
@section('content')

<h2 style="margin:0 0 4px;color:#0d6efd;">⏰ Reminder — Update Your Qualification</h2>
<p style="color:#64748b;margin:0 0 18px;">{{ $job?->title ?? 'Your security service request' }}</p>

<p>Hi {{ $job?->user?->name ?? 'there' }},</p>

<p>It's been a few days since your security service request was placed on <strong>Pending Qualification Status</strong>. Our pre-screened vendor network hasn't seen your project yet because one or more of these core requirements haven't been confirmed:</p>

<ul style="padding-left:20px;line-height:1.8;">
    <li>Verified decision-maker authority</li>
    <li>Confirmed budget or pricing alignment</li>
    <li>Commitment to interview qualified vendors</li>
    <li>Readiness to move forward under established service conditions</li>
</ul>

<p style="background:#fff3cd;color:#664d03;padding:12px 16px;border-radius:6px;">
    Once your questionnaire reflects qualified status, we'll release it immediately to our network — typically within minutes — and you'll begin receiving accepted vendors the same day.
</p>

<p style="margin:24px 0;">
    <a href="{{ $updateUrl }}"
       style="background:#0d6efd;color:#fff;padding:12px 22px;border-radius:6px;text-decoration:none;display:inline-block;font-weight:600;">
        Update My Questionnaire
    </a>
</p>

<p style="color:#64748b;font-size:13px;">If you'd like to talk through your project before resubmitting, simply reply to this email and our team will reach out.</p>

@endsection

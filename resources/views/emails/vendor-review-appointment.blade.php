@extends('emails.layouts.gasq-base', ['title' => 'Your GASQ Estimate Review Appointment', 'preheader' => 'Appointment details for your GASQ estimate review.'])
@php
$timezone = $notice->audience === 'buyer' ? $appointment->buyer_timezone : $appointment->timezone;
$when = $appointment->starts_at->setTimezone($timezone);
@endphp
@section('content')
<p>Dear {{ $notice->audience === 'buyer' ? $appointment->buyer_name : $appointment->vendor_name }},</p>
@if($notice->kind === 'canceled')<p>Your GASQ Estimate &amp; Scope Review appointment has been canceled. Pending reminders for this appointment have been stopped.</p>
@elseif($notice->kind === 'confirmation')<p>Your GASQ Estimate &amp; Scope Review appointment is confirmed.</p>
@else<p>This is your {{ $notice->kind === 'reminder_24h' ? '24-hour' : '1-hour' }} reminder for your GASQ Estimate &amp; Scope Review.</p>@endif
<p><strong>Buyer:</strong> {{ $appointment->buyer_name }}<br><strong>Vendor:</strong> {{ $appointment->vendor_name }}<br><strong>Estimate:</strong> {{ $appointment->delivery->snapshot['number'] }}<br><strong>Date:</strong> {{ $when->format('l, F j, Y') }}<br><strong>Time:</strong> {{ $when->format('g:i A T (P)') }}<br><strong>Time zone:</strong> {{ $timezone }}<br><strong>Duration:</strong> 30 minutes</p>
@if($appointment->status === 'scheduled')
<p><strong>Meeting details:</strong><br>
@if(filter_var($appointment->meeting_details, FILTER_VALIDATE_URL) && parse_url($appointment->meeting_details, PHP_URL_SCHEME) === 'https')<a href="{{ $appointment->meeting_details }}">Join meeting</a>@else{{ $appointment->meeting_details }}@endif</p>
<p>Please have the protected PDF, coverage requirements, current security budget, and anticipated start date available. Include the decision-maker authorized to approve the security budget.</p>
<p>The PDF password remains restricted until the vendor manually releases access after the appointment review.</p>
@endif
<p><a href="{{ $appointment->manageUrl() }}" style="background:#153a81;color:#fff;padding:12px 18px;display:inline-block;text-decoration:none;border-radius:4px;">View or cancel appointment</a></p>
<p>If you need assistance, reply to this email.</p>
<p>Respectfully,<br>{{ $appointment->vendor_name }}<br>Get A Security Quote (GASQ)</p>
@endsection

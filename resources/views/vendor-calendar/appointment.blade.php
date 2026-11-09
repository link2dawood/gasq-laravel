@extends('layouts.app')
@section('title', 'Your Estimate Review Appointment')
@section('content')
<div class="container py-5" style="max-width:800px;">
<h1>{{ $appointment->status === 'scheduled' ? 'Your appointment is confirmed' : 'Your appointment is canceled' }}</h1>
@if(session('status'))<div class="alert alert-success" role="status">{{ session('status') }}</div>@endif
<p>Estimate review with {{ $appointment->vendor_name }}</p>
<p><strong>{{ $appointment->starts_at->setTimezone($appointment->buyer_timezone)->format('l, F j, Y g:i A T (P)') }}</strong><br>Time zone: {{ $appointment->buyer_timezone }}<br>Duration: 30 minutes</p>
@if($appointment->status === 'scheduled')
<p><strong>Meeting details</strong></p>
@if(filter_var($appointment->meeting_details, FILTER_VALIDATE_URL) && parse_url($appointment->meeting_details, PHP_URL_SCHEME) === 'https')<p><a href="{{ $appointment->meeting_details }}" rel="noreferrer">Join meeting</a></p>@else<p style="white-space:pre-wrap;">{{ $appointment->meeting_details }}</p>@endif
<p>Please have your protected PDF, coverage schedule, and current security budget available. Your vendor can release PDF password access after the appointment review.</p>
@if($appointment->starts_at->isFuture())<form method="POST" action="{{ route('vendor-review.cancel', ['token' => $appointment->manage_token]) }}">@csrf<button class="btn btn-outline-danger" type="submit">Cancel appointment</button></form>@endif
@elseif($appointment->delivery->scheduling_expires_at->isFuture())
<p><a href="{{ $appointment->delivery->schedulingUrl() }}">Choose another available time</a></p>
@endif
<p><a href="{{ $appointment->delivery->accessUrl() }}">Protected PDF access</a></p>
<p class="small text-muted mt-4">For assistance, reply to your appointment confirmation email.</p>
</div>
@endsection

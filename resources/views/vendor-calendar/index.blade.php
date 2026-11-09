@extends('layouts.app')
@section('title', 'Vendor Calendar')
@section('header_variant', 'dashboard')
@section('content')
<div class="container py-4">
<h1>Review calendar</h1>
<p class="text-muted">Publish 30-minute Estimate &amp; Scope Review appointments for buyers receiving your estimates.</p>
@if(session('status'))<div class="alert alert-success" role="status">{{ session('status') }}</div>@endif
@if($errors->any())<div class="alert alert-danger"><ul class="mb-0">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif
<section class="border rounded p-3 mb-4">
<h2 class="h4">Calendar settings</h2>
<form method="POST" action="{{ route('vendor-calendar.settings') }}">@csrf
<div class="mb-3"><label class="form-label" for="enabled">Scheduling for new estimate emails</label><select class="form-select" name="enabled" id="enabled"><option value="0" @selected(!old('enabled', $profile->enabled))>Use configured external scheduling</option><option value="1" @selected(old('enabled', $profile->enabled))>Use my GASQ calendar</option></select></div>
<div class="mb-3"><label class="form-label" for="timezone">Your time zone</label><select class="form-select" id="timezone" name="timezone">@foreach(\DateTimeZone::listIdentifiers(\DateTimeZone::ALL) as $zone)<option @selected(old('timezone', $profile->timezone) === $zone)>{{ $zone }}</option>@endforeach</select></div>
<div class="mb-3"><label class="form-label" for="meeting_details">Meeting link, phone number, or location</label><textarea class="form-control" name="meeting_details" id="meeting_details" rows="2" maxlength="2000">{{ old('meeting_details', $profile->meeting_details) }}</textarea><p class="small text-muted">Use your existing meeting link or phone details. These details are included in appointment confirmations.</p></div>
<button class="btn btn-primary" type="submit">Save calendar settings</button>
</form>
</section>
@if($profile->enabled)
<section class="border rounded p-3 mb-4"><h2 class="h4">Publish availability</h2>
<p>Times entered here use <strong>{{ $profile->timezone }}</strong>. Each complete 30-minute period becomes one available appointment.</p>
<form method="POST" action="{{ route('vendor-calendar.publish') }}" class="row g-3 align-items-end">@csrf
<div class="col-md-4"><label class="form-label" for="date">Date</label><input class="form-control" id="date" name="date" type="date" value="{{ old('date') }}" required></div>
<div class="col-md-3"><label class="form-label" for="from">From</label><input class="form-control" id="from" name="from" type="time" value="{{ old('from', '09:00') }}" required></div>
<div class="col-md-3"><label class="form-label" for="until">Until</label><input class="form-control" id="until" name="until" type="time" value="{{ old('until', '12:00') }}" required></div>
<div class="col-md-2"><button class="btn btn-primary" type="submit">Publish slots</button></div>
</form></section>
@endif
<h2 class="h4">Upcoming availability</h2>
<div class="table-responsive"><table class="table"><thead><tr><th>Start</th><th>End</th><th>Status</th><th>Action</th></tr></thead><tbody>
@forelse($slots as $slot)<tr><td>{{ $slot->starts_at->setTimezone($slot->timezone)->format('D, M j, Y g:i A T (P)') }}<br><small>{{ $slot->timezone }}</small></td><td>{{ $slot->ends_at->setTimezone($slot->timezone)->format('g:i A') }}</td><td>{{ $slot->reservation ? 'Booked' : 'Available' }}</td><td>@unless($slot->reservation)<form method="POST" action="{{ route('vendor-calendar.remove', $slot) }}">@csrf @method('DELETE')<button class="btn btn-sm btn-outline-danger" type="submit">Remove slot</button></form>@endunless</td></tr>
@empty<tr><td colspan="4">No upcoming availability. Enable your GASQ calendar and publish slots above.</td></tr>@endforelse
</tbody></table></div>{{ $slots->links() }}
<h2 class="h4 mt-4">Appointments</h2>
@forelse($appointments as $appointment)
<article class="border rounded p-3 mb-3"><h3 class="h5">{{ $appointment->buyer_name }} · {{ ucfirst($appointment->status) }}</h3><p>{{ $appointment->buyer_email }}<br>{{ $appointment->starts_at->setTimezone($appointment->timezone)->format('l, F j, Y g:i A T (P)') }} · {{ $appointment->timezone }}</p>
<p style="white-space:pre-wrap;">{{ $appointment->meeting_details }}</p>
<details><summary>Confirmation and reminder delivery</summary>@forelse($appointment->messages as $message)<p class="small mb-1">{{ ucfirst($message->audience) }} · {{ str_replace('_', ' ', $message->kind) }} · {{ $message->status }} @if($message->sent_at) · {{ $message->sent_at->format('M j, g:i A') }} @endif</p>@empty<p>No messages queued.</p>@endforelse</details>
@if($appointment->status === 'scheduled' && $appointment->starts_at->isFuture())<form class="mt-3" method="POST" action="{{ route('vendor-calendar.cancel', $appointment) }}">@csrf<button class="btn btn-sm btn-outline-danger" type="submit">Cancel appointment</button><p class="small text-muted mt-1">Canceling makes this slot available again. Remove the slot above if you no longer want to offer it.</p></form>@endif
</article>
@empty<p class="text-muted">No appointments yet.</p>@endforelse
{{ $appointments->links() }}
</div>
@endsection

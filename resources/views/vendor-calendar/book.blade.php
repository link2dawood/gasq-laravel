@extends('layouts.app')
@section('title', 'Schedule Your Estimate Review')
@section('content')
<div class="container py-5" style="max-width:850px;">
<h1>Schedule your estimate review</h1>
<p class="lead">Choose a 30-minute appointment with {{ $delivery->snapshot['vendor_name'] }}.</p>
<p>Estimate {{ $delivery->snapshot['number'] }}. Please include the decision-maker authorized to approve your security budget.</p>
@if($errors->any())<div class="alert alert-danger"><ul class="mb-0">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif
<form method="GET" class="mb-4"><label class="form-label" for="timezone">Show times in your time zone</label><div class="d-flex gap-2"><select class="form-select" id="timezone" name="timezone">@foreach(\DateTimeZone::listIdentifiers(\DateTimeZone::ALL) as $zone)<option @selected($timezone === $zone)>{{ $zone }}</option>@endforeach</select><button type="submit" class="btn btn-outline-primary">Update times</button></div></form>
@if($slots->count())
<form method="POST" action="{{ route('vendor-review.book', ['token' => $delivery->scheduling_token]) }}">@csrf
<input type="hidden" name="timezone" value="{{ $timezone }}">
<fieldset><legend class="h4">Available dates and times · {{ $timezone }}</legend>
@foreach($slots as $slot)
<div class="form-check border rounded p-3 ps-5 mb-2"><input class="form-check-input" id="slot-{{ $slot->id }}" name="slot_id" type="radio" value="{{ $slot->id }}" required><label class="form-check-label" for="slot-{{ $slot->id }}">{{ $slot->starts_at->setTimezone($timezone)->format('l, F j, Y g:i A') }} – {{ $slot->ends_at->setTimezone($timezone)->format('g:i A T (P)') }}</label></div>
@endforeach
</fieldset>
<p class="small text-muted">Your selection is reserved when you confirm. You and your vendor will receive an email with the meeting details. The PDF password is provided separately during the review.</p>
<button type="submit" class="btn btn-primary">Confirm appointment</button>
</form>
{{ $slots->links() }}
@else
<div class="alert alert-info">No appointments are currently available. Please reply to your estimate email to arrange a time with your vendor.</div>
@endif
</div>
@endsection

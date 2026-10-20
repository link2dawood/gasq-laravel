@extends('layouts.app')
@section('title', 'Buyer Appraisal Reviews')
@section('content')
<div class="container py-4">
  <h1 class="h3">Buyer Appraisal Reviews</h1>
  <p>Delivery is {{ config('appraisal_reviews.enabled') ? 'active' : 'inactive pending acceptance testing' }}. A protected final report and a current appointment are both required.</p>
  @if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
  @if($errors->any())<div class="alert alert-danger">{{ $errors->first() }}</div>@endif
  <form method="post" action="{{ route('admin.appraisal-reviews.store') }}" class="border rounded p-3 mb-4">
    @csrf
    <h2 class="h5">Create a buyer review</h2>
    <label for="review_state">Approved calculator source</label>
    <select id="review_state" name="calculator_state_id" class="form-select mb-3" required>
      @foreach($states as $state)<option value="{{ $state->id }}">{{ $state->id }} · {{ $state->user?->name }} · {{ $state->updated_at }}</option>@endforeach
    </select>
    <label for="review_name">Buyer's name</label><input id="review_name" name="buyer_name" class="form-control mb-3" required maxlength="255">
    <label for="review_email">Buyer's email</label><input id="review_email" name="buyer_email" type="email" class="form-control mb-3" required maxlength="255">
    <button class="btn btn-primary">Create review</button>
  </form>
  @foreach($reviews as $review)
    <section class="border rounded p-3 mb-3">
      <h2 class="h5">{{ $review->report_number }}</h2><p>{{ $review->buyer_name }} · {{ $review->buyer_email }}</p>
      <p>Report: {{ $review->ready_at ? 'Final PDF ready' : 'Awaiting final approval' }} · Delivery: {{ $review->delivered_at ? 'Sent' : 'Pending' }}</p>
      @if(config('appraisal_reviews.booking_url'))
        <a href="{{ config('appraisal_reviews.booking_url').'?'.http_build_query(['name' => $review->buyer_name, 'email' => $review->buyer_email, 'utm_content' => 'gasq_appraisal_'.$review->id]) }}">Buyer-specific Calendly booking link</a>
      @else<p>Configure the Calendly appraisal booking URL to enable buyer-specific booking links.</p>@endif
      @foreach($review->appointments as $appointment)
        <p>{{ $appointment->status }} · {{ $appointment->starts_at->setTimezone($appointment->timezone)->format('F j, Y g:i A T') }} · {{ $appointment->timezone }} · Attendance: {{ $appointment->confirmed_at ? 'confirmed' : 'not yet confirmed' }}</p>
      @endforeach
      <details class="mt-2"><summary>Link an appointment booked earlier</summary>
        <form method="post" action="{{ route('admin.appraisal-reviews.appointment', $review) }}">
          @csrf
          <label for="invitee_{{ $review->id }}">Calendly invitee API URI (the buyer and event type are verified)</label>
          <input id="invitee_{{ $review->id }}" name="invitee_uri" type="url" class="form-control mb-2" required>
          <button class="btn btn-outline-primary">Verify and link appointment</button>
        </form>
      </details>
      @unless($review->ready_at)
        <form method="post" action="{{ route('admin.appraisal-reviews.finalize', $review) }}" class="mt-3">
          @csrf
          <label for="password_{{ $review->id }}">PDF opening password (retain separately for the live appointment)</label>
          <input id="password_{{ $review->id }}" type="password" name="pdf_password" class="form-control mb-2" minlength="8" maxlength="32" autocomplete="new-password" required>
          <label><input type="checkbox" name="approved_final" value="1" required> I verified the buyer, source figures, and final report; I will provide the password personally during the appointment.</label>
          <div><button class="btn btn-primary mt-2">Finalize protected report</button></div>
        </form>
      @endunless
    </section>
  @endforeach
  {{ $reviews->links() }}
</div>
@endsection

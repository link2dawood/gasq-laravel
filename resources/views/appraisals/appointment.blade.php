<!doctype html>
<html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><meta name="robots" content="noindex,nofollow"><meta name="referrer" content="no-referrer"><title>GASQ Report Review Appointment</title></head>
<body style="font-family:Arial,sans-serif;max-width:680px;margin:40px auto;padding:20px;line-height:1.6">
<h1>GASQ Report Review Appointment</h1>
@if(session('success'))<p role="status">{{ session('success') }}</p>@endif
<p>Status: {{ $appointment->status }}</p>
<p>{{ $appointment->starts_at->setTimezone($appointment->timezone)->format('l, F j, Y · g:i A T (P)') }}<br>Time zone: {{ $appointment->timezone }}</p>
@if($appointment->status === 'scheduled')
  <p>Meeting details: {{ $appointment->meeting_details ?: 'Meeting details are being prepared.' }}</p>
  <p><a href="{{ $appointment->reschedule_url }}" rel="noreferrer">Change Date or Time</a> | <a href="{{ $appointment->cancel_url }}" rel="noreferrer">Cancel Appointment</a></p>
  @if($appointment->starts_at->isFuture())
    <section id="attendance">
      @if($appointment->confirmed_at)<p>Your attendance is confirmed.</p>
      @else
        <form method="post" action="{{ $appointment->viewUrl() }}">
          @csrf
          <label><input name="attendance" type="checkbox" value="1" required> I plan to attend this appointment.</label>
          <button type="submit">Confirm Appointment</button>
        </form>
      @endif
    </section>
  @endif
@else<p>This appointment is no longer active. Refer to your latest booking confirmation for the current appointment.</p>@endif
<p>Your report password will be provided personally during your appointment. Passwords are never released automatically.</p>
<p>Questions? <a href="mailto:{{ config('appraisal_reviews.reply_to') }}">Contact the GASQ team</a>.</p>
</body></html>

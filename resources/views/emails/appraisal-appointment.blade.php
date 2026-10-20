@extends('emails.layouts.gasq-base', ['title' => 'GASQ Certified™ Cost to Protect™ Appraisal Report', 'preheader' => 'Your confidential report review appointment with GASQ.'])
@section('content')
@if($testDelivery)<p><strong>Acceptance test — sample report and appointment. Automatic buyer delivery has not been activated by this test.</strong></p>@endif
<p>Dear {{ $review->buyer_name }},</p>
@if($kind === 'report')
<p>Thank you for the opportunity to prepare your <strong>GASQ Certified™ Cost to Protect™ Appraisal Report</strong>. Your confidential, password-protected report is attached.</p>
<p><strong>Report Number:</strong> {{ $review->report_number }}<br><strong>Date Prepared:</strong> {{ $review->prepared_at->format('F j, Y') }}</p>
@elseif($kind === 'booking')
<p>Your GASQ report review appointment is booked. We will send your protected report when the final PDF is ready.</p>
@elseif($kind === 'rescheduled')
<p>Your GASQ report review appointment has changed. Please use the updated appointment and meeting information below. Keep the report PDF previously sent to you.</p>
@elseif($kind === 'canceled')
<p>Your GASQ report review appointment has been canceled. Pending reminders for this appointment have been stopped.</p>
@else
<p>This is your {{ $kind === 'reminder_24h' ? '24-hour' : '1-hour' }} reminder for your GASQ report review appointment.</p>
@endif
<h2 style="font-size:18px">{{ $kind === 'canceled' ? 'Canceled appointment' : 'Please confirm your report review appointment' }}</h2>
<p><strong>Date:</strong> {{ $when->format('l, F j, Y') }}<br>
<strong>Time:</strong> {{ $when->format('g:i A T (P)') }}<br>
<strong>Time Zone:</strong> {{ $appointment->timezone }}<br>
<strong>Meeting link / phone details:</strong>
@if($appointment->status === 'scheduled' && filter_var($appointment->meeting_details, FILTER_VALIDATE_URL))
<a href="{{ $appointment->meeting_details }}">Join meeting</a>
@elseif($appointment->status === 'scheduled'){{ $appointment->meeting_details }}
@else Canceled @endif</p>
@if($appointment->status === 'scheduled')<p><a href="{{ $appointment->confirmationUrl() }}" style="background:#0d6efd;color:white;padding:12px 18px;display:inline-block;text-decoration:none;border-radius:4px">Confirm Appointment</a></p>@endif
<p><a href="{{ $appointment->viewUrl() }}">View Appointment</a> |
<a href="{{ $appointment->reschedule_url }}">Change Date or Time</a> |
<a href="{{ $appointment->cancel_url }}">Cancel Appointment</a></p>
@if($kind === 'report')<p><strong>{{ $passwordNotice }}</strong></p>
<p>Developed using the GASQ Cost to Protect™ methodology, your appraisal provides a side-by-side analysis of the estimated cost to perform the security function internally versus outsourcing to a qualified security provider.</p>
<p>Your report includes:</p>
<ul><li>Required staffing analysis</li><li>Workforce Availability Gap™ assessment</li><li>Total Cost of Ownership calculations</li><li>In-House vs. Outsourced Cost to Protect™ comparison</li><li>Estimated savings opportunities</li></ul>
<h2 style="font-size:18px">Key Findings Summary</h2>
<ul>
  <li>Estimated In-House Cost to Protect™: {{ \Illuminate\Support\Number::currency($review->findings['inHouseCost'], in: $review->findings['currency']) }}</li>
  <li>Estimated Capital Recovery Annual Savings: {{ \Illuminate\Support\Number::currency($review->findings['capitalRecovery'], in: $review->findings['currency']) }}</li>
  <li>Payback &amp; Recovery Period: {{ number_format($review->findings['paybackMonths'], 1) }} months</li>
</ul>
@else<p>Your PDF password remains withheld. GASQ will provide it personally during your appointment.</p>@endif
<p>During your appointment, we’ll explain the findings, review the assumptions behind these estimates, and answer your questions. If another person approves your security budget or makes the final hiring decision, please invite them to attend.</p>
<p>Our team can also discuss vendor sourcing, bid validation, scope optimization, pricing review, and procurement support.</p>
<p><strong>GASQ CERTIFIED™<br>CONFIDENTIAL • PROPRIETARY • TRACEABLE</strong></p>
<p>The methodologies, calculations, benchmarks, analytical frameworks, and proprietary concepts contained within this report are the intellectual property of GASQ and are intended solely for the use of the named recipient. Your unique Report Number supports authentication and traceability.</p>
<p>Thank you for allowing GASQ to serve as your independent pricing referee and procurement resource. If you need assistance before your appointment, please reply to this email.</p>
<p><strong>Built for CFO-Level Cost Analysis.</strong></p>
<p>Respectfully,<br>Get A Security Quote (GASQ) Team<br>Get A Security Quote™<br>The Financial Procurement System for Security Services™<br>The Industry Pricing Referee™</p>
@endsection

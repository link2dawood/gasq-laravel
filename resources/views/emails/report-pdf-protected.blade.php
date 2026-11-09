@extends('emails.layouts.gasq-base', ['title' => 'Your GASQ Report Is Ready — Schedule Your Review', 'preheader' => 'Your protected report is attached. Choose an appointment to review your findings.'])
@section('content')
<p>Dear {{ $clientName }},</p>
<p>Your confidential, password-protected {{ $reportTitle }} is attached. Thank you for the opportunity to help you evaluate your security requirements.</p>
<p>@if(!empty($reportNumber))<strong>Report number:</strong> {{ $reportNumber }}<br>@endif<strong>Date prepared:</strong> {{ $datePrepared }}</p>
<p>Please schedule an Estimate &amp; Scope Review with the decision-maker authorized to approve the security budget. We will explain the report, confirm your assumptions, and answer your questions.</p>
@include('emails.partials.schedule-review', ['bookingUrl' => $bookingUrl ?? null])
<p>During your review, we will:</p>
<ul><li>Confirm staffing, coverage, and service requirements.</li><li>Explain the financial findings and pricing assumptions.</li><li>Discuss next steps and procurement options.</li></ul>
<p>The PDF password will be provided separately after your review. Please save the attachment and have your current security budget, coverage schedule, and anticipated start date available for the appointment.</p>
<p>If someone else approves the budget, please forward this email and the protected report to them and include them in the appointment. For scheduling assistance, reply to this email.</p>
@if(!empty($surveyorNotes))<p><strong>Notes from your GASQ representative:</strong></p><div style="white-space:pre-line;padding:10px 12px;background:#f1f5f9;">{{ $surveyorNotes }}</div>@endif
@if(!empty($attachmentCount))<p>{{ $attachmentCount }} on-site {{ \Illuminate\Support\Str::plural('file', $attachmentCount) }} (photos/documents) {{ $attachmentCount === 1 ? 'is' : 'are' }} attached alongside your report.</p>@endif
<p>Respectfully,<br>Get A Security Quote (GASQ) Team<br>Get A Security Quote™<br>The Financial Procurement System for Security Services™<br>The Industry Pricing Referee™</p>
<p><strong>Know Before You Buy. Qualifications First. Price Last.</strong></p>
@endsection

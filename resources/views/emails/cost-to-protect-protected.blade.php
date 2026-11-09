@extends('emails.layouts.gasq-base', ['title' => 'Your GASQ Appraisal Is Ready—Schedule Your Review to Receive PDF Access', 'preheader' => 'Schedule your 30-minute appraisal review to receive PDF access.'])
@php
    $maskedMoney = static function ($value): string {
        if (!is_numeric($value)) return 'Available during your review';
        $formatted = \App\Support\Currency::format($value, 0);
        if (!str_contains($formatted, ',')) return preg_replace('/\d/', 'X', $formatted);
        return preg_replace('/\d(?=\d{3}(?:\D|$))|\d(?=\d*,\d{3})/', 'X', $formatted);
    };
@endphp
@section('content')
<p>Dear {{ $clientName ?: 'Valued Client' }},</p>
<p>Thank you for the opportunity to prepare your GASQ Certified™ Cost to Protect™ Appraisal Report.</p>
<p>Your analysis identifies a potential annual Capital Recovery Opportunity. The financial figures below are partially concealed pending your scheduled review.</p>
<p>Your confidential PDF report is attached and password protected. To receive the password, an appointment must be scheduled with the decision-maker and his/her designated representative authorized to approve the security budget. We will release the password after confirming the appointment and the decision-maker’s participation.</p>
<p>If someone else authorizes the final budget, please forward this email and the attached report to them and include them in the appointment.</p>
@include('emails.partials.schedule-review', ['bookingUrl' => $bookingUrl ?? null, 'scheduleLabel' => 'Schedule Your 30-Minute Appraisal & Estimate Review'])
<p>During this review, we will:</p>
<ul>
<li>Reveal and explain the complete financial findings.</li>
<li>Confirm staffing, coverage, and budget assumptions.</li>
<li>Review the estimated capital recovery opportunity and payback period.</li>
<li>Answer questions and outline procurement options.</li>
</ul>
<h2 style="font-size:18px;color:#153a81;">Your Report at a Glance</h2>
<table cellpadding="10" cellspacing="0" width="100%" style="border-collapse:collapse;font-size:13px;">
<thead><tr style="background:#153a81;color:#fff;text-align:left;"><th>Item</th><th>Report Detail</th></tr></thead>
<tbody>
<tr><td>Report number</td><td style="overflow-wrap:anywhere;">{{ $reportNumber ?? 'See attached report' }}</td></tr>
<tr style="background:#f1f5f9;"><td>Date prepared</td><td>{{ $datePrepared }}</td></tr>
<tr><td>Estimated annual in-house Cost to Protect™</td><td>{{ $maskedMoney($inHouseCost ?? null) }}</td></tr>
<tr style="background:#f1f5f9;"><td>Estimated annual Outsourcing Cost to Deliver Protection</td><td>{{ $maskedMoney($outsourcingCost ?? null) }}</td></tr>
<tr><td>Estimated annual Capital Recovery Opportunity</td><td>{{ $maskedMoney($capitalRecovery ?? null) }}</td></tr>
<tr style="background:#f1f5f9;"><td>Estimated payback and recovery period</td><td>Available during your review</td></tr>
</tbody>
</table>
<p>The report includes required staffing analysis, a Workforce Availability Gap™ assessment, Total Cost of Ownership calculations, and a comparison of internal operations with outsourcing to a qualified security provider. Estimates are subject to confirmation of the report’s assumptions and final service requirements.</p>
<p><strong>Please schedule within 24 hours of this email. The estimate is valid for 24 hours from delivery.</strong> If you schedule today, GASQ will honor the quoted estimate through your scheduled review, provided the scope and underlying assumptions remain unchanged.</p>
<p>Please have your current security budget or bill rate, coverage schedule, and anticipated start date available for the appointment. If you need scheduling assistance, simply reply to this email.</p>
@if(!empty($surveyorNotes))
<p><strong>Notes from your GASQ representative:</strong></p>
<div style="padding:10px 12px;background:#f1f5f9;border-left:3px solid #153a81;white-space:pre-line;">{{ $surveyorNotes }}</div>
@endif
@if(!empty($attachmentCount))
<p>{{ $attachmentCount }} on-site {{ \Illuminate\Support\Str::plural('file', $attachmentCount) }} (photos/documents) {{ $attachmentCount === 1 ? 'is' : 'are' }} attached alongside your report.</p>
@endif
<p><strong>GASQ CERTIFIED™<br>CONFIDENTIAL • PROPRIETARY • TRACEABLE</strong></p>
<p>This report is intended for the named recipient and authorized internal decision-makers evaluating this opportunity. GASQ’s methodologies, calculations, benchmarks, and analytical frameworks are proprietary. Please reference your report number in all correspondence.</p>
<p>Thank you for choosing GASQ as your independent pricing referee and procurement resource.</p>
<p>Respectfully,</p>
<p>Get A Security Quote (GASQ) Team<br>Get A Security Quote™<br>The Financial Procurement System for Security Services™<br>The Industry Pricing Referee™</p>
<p><strong>Know Before You Buy. Qualifications First. Price Last.</strong></p>
@endsection

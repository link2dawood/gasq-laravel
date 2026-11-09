@extends('emails.layouts.gasq-base', ['title' => 'Your GASQ Estimate Is Ready — Schedule Your Review', 'preheader' => 'Your protected estimate is attached. Schedule a review to discuss the scope and receive PDF access.'])
@php
    $snapshot = $delivery->snapshot;
    $maskedMoney = static function ($value) use ($snapshot): string {
        $formatted = number_format($value, 0);
        $masked = str_contains($formatted, ',') ? preg_replace('/\d(?=\d{3}(?:\D|$))|\d(?=\d*,\d{3})/', 'X', $formatted) : preg_replace('/\d/', 'X', $formatted);
        return $snapshot['currency'].' '.$masked;
    };
@endphp
@section('content')
<p>Dear {{ $snapshot['buyer']['contact_name'] }},</p>
<p>Your confidential, password-protected security services estimate from {{ $snapshot['vendor_name'] }} is attached.</p>
<p>Thank you for the opportunity to review your requirements. Your estimate outlines the proposed scope of services, staffing hours, pricing assumptions, and terms for {{ $snapshot['buyer']['site_address'] }}.</p>
<h2 style="font-size:18px;color:#153a81;">Your estimate at a glance</h2>
<table width="100%" cellpadding="10" cellspacing="0" style="border-collapse:collapse;font-size:13px;">
<tr style="background:#f1f5f9;"><th align="left">Estimate number</th><td>{{ $snapshot['number'] }}</td></tr>
<tr><th align="left">Estimate</th><td>{{ $snapshot['title'] }}</td></tr>
<tr style="background:#f1f5f9;"><th align="left">Average monthly recurring cost</th><td>{{ $maskedMoney($snapshot['totals']['monthly']) }}</td></tr>
<tr><th align="left">First-year estimated total</th><td>{{ $maskedMoney($snapshot['totals']['first_year']) }}</td></tr>
<tr style="background:#f1f5f9;"><th align="left">Valid through</th><td>{{ \Illuminate\Support\Carbon::parse($snapshot['valid_until'])->format('F j, Y') }}</td></tr>
</table>
<p>These financial figures are partially concealed for confidentiality. Full pricing and assumptions are available in the protected PDF and will be explained during your review.</p>
<p>Please schedule an Estimate &amp; Scope Review with the decision-maker authorized to approve the security budget. During the appointment, we will confirm your requirements, explain the calculations, answer questions, and, after the appointment, manually release access to the PDF password.</p>
<p><a href="{{ $delivery->schedulingUrl() }}" style="background:#153a81;color:#fff;padding:12px 18px;display:inline-block;text-decoration:none;border-radius:4px;">Schedule Your Estimate &amp; Scope Review</a></p>
<p>If someone else authorizes the budget, please forward this email and the protected attachment to that person and include them in the appointment.</p>
<p>Please have your current security budget, coverage schedule, and anticipated start date available. For scheduling assistance or questions about your estimate, reply to this email.</p>
<p>This estimate remains subject to confirmed scope, coverage requirements, site conditions, and agreement between the parties. Please limit distribution to authorized decision-makers and reference your estimate number in correspondence.</p>
<p><a href="{{ $delivery->accessUrl() }}">Protected PDF access and password release</a> — verify the original buyer email after your vendor releases access.</p>
<p>Respectfully,<br>{{ $snapshot['vendor_name'] }}<br>Get A Security Quote (GASQ)</p>
<p><strong>Know Before You Buy. Qualifications First. Price Last.</strong></p>
@endsection

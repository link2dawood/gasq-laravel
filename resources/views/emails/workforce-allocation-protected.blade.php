@extends('emails.layouts.gasq-base', ['title' => 'Your Confidential GASQ Report — Schedule Your Budget Review', 'preheader' => 'Your protected Workforce-to-Post Allocation Report is ready.'])
@php
    $maskedMoney = static function ($value): string {
        if (!is_numeric($value)) return 'Available during your review';
        $formatted = \App\Support\Currency::format($value, 0);
        if (!str_contains($formatted, ',')) return preg_replace('/\d/', 'X', $formatted);
        return preg_replace('/\d(?=\d{3}(?:\D|$))|\d(?=\d*,\d{3})/', 'X', $formatted);
    };
@endphp
@section('content')
<p>Dear {{ $clientName }},</p>
<p>Your password-protected GASQ Certified™ Workforce-to-Post Allocation Report is attached. It explains how the proposed security budget is allocated across staffing, employer obligations, operational support, and business administration.</p>
<h2 style="font-size:18px;color:#153a81;">Executive Budget Summary</h2>
<p><strong>Total Contract / Budget Value:</strong> {{ $maskedMoney($budgetTotal) }}</p>
<ul>
<li><strong>Direct Labor:</strong> {{ $maskedMoney($allocations['directLabor'] ?? null) }} — baseline wages.</li>
<li><strong>Fringe and Employer Burden:</strong> {{ $maskedMoney($allocations['fringeBurden'] ?? null) }} — payroll taxes, workers’ compensation, vacation, and sick leave.</li>
<li><strong>Operations and Contract Support:</strong> {{ $maskedMoney($allocations['operationsSupport'] ?? null) }} — training, uniforms, equipment, supervision, contract management, and liability insurance.</li>
<li><strong>Overhead, General Administration, and Profit:</strong> {{ $maskedMoney($allocations['overheadProfit'] ?? null) }} — corporate support, administrative expenses, and profit/fee.</li>
</ul>
<p>The amounts above are partially concealed for confidentiality. The complete figures are available in your protected report.</p>
<p>The proposed budget accounts for the resources required to deliver security services beyond officer wages alone. Reviewing these allocations will help you evaluate proposals, confirm service expectations, and establish a realistic protection budget.</p>
<p>To receive the PDF password, please schedule an Estimate &amp; Scope Review with the decision-maker authorized to approve the final security budget. We will explain the cost assumptions, confirm your requirements, and answer questions before releasing the password during the appointment.</p>
@include('emails.partials.schedule-review', ['bookingUrl' => $bookingUrl ?? null, 'scheduleLabel' => 'Schedule Your Estimate & Scope Review'])
<p>If someone else authorizes the budget, please forward this email and the protected attachment to that person and include them in the appointment.</p>
<p>Final pricing remains subject to confirmed scope, coverage requirements, site conditions, and vendor acceptance. Please limit distribution to authorized decision-makers.</p>
@if(!empty($surveyorNotes))
<p><strong>Notes from your GASQ representative:</strong></p>
<div style="padding:10px 12px;background:#f1f5f9;border-left:3px solid #153a81;white-space:pre-line;">{{ $surveyorNotes }}</div>
@endif
@if(!empty($attachmentCount))
<p>{{ $attachmentCount }} on-site {{ \Illuminate\Support\Str::plural('file', $attachmentCount) }} (photos/documents) {{ $attachmentCount === 1 ? 'is' : 'are' }} attached alongside your report.</p>
@endif
<p>Respectfully,</p>
<p><strong>Grayling D. Livingston</strong><br>Founder &amp; Principal Consultant<br>Get A Security Quote (GASQ)<br>Know your numbers before you buy.</p>
@endsection

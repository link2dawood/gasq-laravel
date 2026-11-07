@extends('emails.layouts.gasq-base', [
    'title' => 'Your GASQ Weighted Rate Report Is Ready — Schedule Your Review',
    'preheader' => 'Your confidential weighted rate dashboard is ready. Schedule your review for PDF access.',
])
@php
    $maskRate = static fn ($value) => preg_replace('/\d(?=[\d,]*\.)/', 'X', number_format($value, 2));
    $maskMoney = static function ($value) use ($reportCurrency): string {
        $formatted = number_format($value * $reportCurrency['rate'], 0);
        $masked = str_contains($formatted, ',')
            ? preg_replace('/\d(?=\d{3}(?:\D|$))|\d(?=\d*,\d{3})/', 'X', $formatted)
            : preg_replace('/\d/', 'X', $formatted);
        return $reportCurrency['symbol'].$masked;
    };
    $canSchedule = filter_var($bookingUrl ?? null, FILTER_VALIDATE_URL) && parse_url($bookingUrl, PHP_URL_SCHEME) === 'https';
@endphp
@section('content')
<p>Dear {{ $recipientName }},</p>
<p>Attached is your confidential, password-protected GASQ Vendor Weighted Rate Dashboard.</p>
<p>The report summarizes the estimated Cost to Deliver Protection™ using the wages, staffing, and schedules entered for the listed classifications.</p>
<h2 style="font-size:18px;color:#153a81;">Executive Summary</h2>
<ul>
<li><strong>Weighted average pay wage:</strong> {{ $reportCurrency['symbol'].$maskRate($totals['weightedPayWage'] * $reportCurrency['rate']) }} per hour.</li>
<li><strong>Weighted average bill rate:</strong> {{ $reportCurrency['symbol'].$maskRate($totals['weightedBillRate'] * $reportCurrency['rate']) }} per hour.</li>
<li><strong>Staffing and annual role hours:</strong> Included in the protected report.</li>
<li><strong>Estimated monthly bill cost:</strong> {{ $maskMoney($totals['monthlyBillCost']) }}.</li>
<li><strong>Estimated annual bill cost:</strong> {{ $maskMoney($totals['annualBillCost']) }}.</li>
<li><strong>Direct wage share:</strong> {{ is_numeric($totals['overallWageShare']) ? $maskRate($totals['overallWageShare'] * 100).'%' : 'Available during your review' }} of the modeled bill cost.</li>
</ul>
<p>These partially concealed figures provide a preview of your analysis. Full pricing, staffing assumptions, and calculations will be explained during your scheduled review.</p>
<p>Please schedule a review with the decision-maker authorized to approve the security budget. During the appointment, we will explain the calculations, confirm the classifications and scope, and provide the PDF password.</p>
@if($canSchedule)
<p><a href="{{ $bookingUrl }}" style="background:#153a81;color:#fff;padding:12px 18px;display:inline-block;text-decoration:none;border-radius:4px;">Schedule Your Estimate &amp; Scope Review</a></p>
@else
<p><strong>Schedule Your Estimate &amp; Scope Review</strong><br>Please reply to this email to arrange your appointment.</p>
@endif
<p>If someone else authorizes the budget, please forward this email and attachment to that person and include them in the appointment.</p>
<p>This dashboard supports pricing review; it does not constitute a submitted bid or final contract price.</p>
<p>Respectfully,</p>
<p>Get A Security Quote (GASQ) Team</p>
<p>Get A Security Quote™<br>The Financial Procurement System for Security Services™<br>The Industry Pricing Referee™</p>
<p><strong>Know Before You Buy. Qualifications First. Price Last.</strong></p>
@endsection

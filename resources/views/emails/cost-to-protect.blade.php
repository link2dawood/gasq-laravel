@php
    $money = static fn ($v) => '$' . number_format((float) $v, 0);
@endphp
<!DOCTYPE html>
<html>
<head><meta charset="utf-8"><title>GASQ Cost to Protect™ Appraisal Report</title></head>
<body style="font-family: Arial, Helvetica, sans-serif; color:#1e293b; font-size:14px; line-height:1.55;">

<p>Dear {{ $clientName ?? 'Valued Client' }},</p>

<p>Thank you for the opportunity to prepare your GASQ Certified™ Cost to Protect™ Appraisal Report.</p>

<p>Attached is your confidential appraisal report for:</p>

<p style="margin-left:8px;">
    @if(!empty($propertyName))<strong>Property:</strong> {{ $propertyName }}<br>@endif
    @if(!empty($reportNumber))<strong>Report Number:</strong> {{ $reportNumber }}<br>@endif
    <strong>Date Prepared:</strong> {{ $datePrepared }}
</p>

<p>This report was developed using the GASQ Cost to Protect™ methodology and provides a side-by-side analysis of the estimated cost to perform the security function internally versus outsourcing to a qualified security provider.</p>

<p>The report includes:</p>
<ul style="margin-top:0;">
    <li>Required staffing analysis</li>
    <li>Workforce Availability Gap™ assessment</li>
    <li>Total Cost of Ownership calculations</li>
    <li>In-House vs. Outsourced Cost to Protect™ comparison</li>
    <li>Estimated savings opportunities</li>
</ul>

@if(!empty($inHouseCost) || !empty($capitalRecovery) || !empty($paybackPeriod))
<p><strong>Key Findings Summary:</strong></p>
<ul style="margin-top:0;">
    @if(!empty($inHouseCost))<li>Estimated In-House Cost to Protect™: {{ $money($inHouseCost) }}</li>@endif
    @if(!empty($capitalRecovery))<li>Estimated Capital Recovery Annual Savings: {{ $money($capitalRecovery) }}</li>@endif
    @if(!empty($paybackPeriod))<li>Payback &amp; Recovery Period: {{ $paybackPeriod }}</li>@endif
</ul>
@endif

<p>Please note that this report is designated:</p>

<p style="margin-left:8px;">
    <strong>GASQ CERTIFIED™</strong><br>
    CONFIDENTIAL • PROPRIETARY • TRACEABLE
</p>

<p>The methodologies, calculations, benchmarks, analytical frameworks, and proprietary concepts contained within this report are the intellectual property of GASQ and are intended solely for the use of the named recipient.</p>

<p>Your report includes a unique Report Number for authentication and traceability purposes.</p>

<p>If you would like assistance with vendor sourcing, bid validation, scope optimization, pricing review, or procurement support, our team is available to help.</p>

<p>Thank you again for allowing GASQ to serve as your independent pricing referee and procurement resource.</p>

<p><strong>CFO Tested. CFO Approved.</strong></p>

<p>Respectfully,<br>
Get A Security Quote (GASQ) Team</p>

<p style="color:#475569;">
    The Kelley Blue Book of Security Pricing™<br>
    The Industry Pricing Referee™
</p>

<hr style="border:none; border-top:1px solid #cbd5e1; margin:20px 0;">

<p style="font-size:11px; color:#64748b; line-height:1.5;">
    <strong>CONFIDENTIALITY NOTICE:</strong>
    This appraisal report contains proprietary and confidential information intended solely for the named recipient. Unauthorized reproduction, redistribution, reverse engineering, commercial use, or creation of derivative works is prohibited without written authorization from GASQ.
</p>

</body>
</html>

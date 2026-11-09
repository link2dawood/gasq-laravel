@extends('emails.layouts.gasq-base', ['title' => 'Your GASQ Security Cost Summary: Review Your Capital Recovery Opportunity', 'preheader' => 'Your password-protected Cost to Protect estimate is attached. Schedule your review to receive the password.'])
@php
    $e = (array) ($estimate ?? []);
    $num = static fn ($v) => number_format((float) $v);

    $maskConcealed = static function (string $formatted): string {
        [$whole, $cents] = array_pad(explode('.', $formatted, 2), 2, null);
        $cut = strrpos($whole, ',');
        $whole = $cut === false
            ? preg_replace('/\d/', 'X', $whole)
            : preg_replace('/\d/', 'X', substr($whole, 0, $cut)).substr($whole, $cut);

        return $whole.($cents === null ? '' : '.'.$cents);
    };

    /**
     * Conceal the scale of a figure while it still reads as a real amount:
     * the leading digits become X and the final group plus the cents stay,
     * so $782,068.11 prints as $XXX,068.11 and $24.37 as $XX.37. This is the
     * approved presentation for the covering email; the attached PDF carries
     * the complete figures, which is what the scheduled review releases.
     */
    $maskedMoney = static function ($value) use ($maskConcealed): string {
        if (! is_numeric($value)) {
            return 'Available during your review';
        }
        return $maskConcealed(\App\Support\Currency::format($value, 2));
    };

    // The headline sentence quotes the recovery without cents, as the approved
    // message does, while the comparison rows carry them.
    $maskedWhole = static function ($value) use ($maskConcealed): string {
        return is_numeric($value) ? $maskConcealed(\App\Support\Currency::format($value, 0)) : 'Available during your review';
    };

    // Description, buyer-internal value, vendor value. Coverage quantities are
    // not concealed; only money is.
    $comparison = [
        ['Baseline assumption labor rate', $maskedMoney($e['baselineWage'] ?? null), $maskedMoney($e['baselineWage'] ?? null)],
        ['Standard hourly rate', $maskedMoney($e['internalTcoHourly'] ?? null), $maskedMoney($e['vendorTcoHourly'] ?? null)],
        ['Overtime / holiday rate', $maskedMoney($e['internalOtHourly'] ?? null), $maskedMoney($e['vendorOtHourly'] ?? null)],
        ['Annual cost per security professional', $maskedMoney($e['annualPerInternalFte'] ?? null), $maskedMoney($e['annualPerVendorFte'] ?? null)],
        ['Weekly coverage hours', $num($e['weeklyCoverageHours'] ?? 0), $num($e['weeklyCoverageHours'] ?? 0)],
        ['Average monthly coverage hours', $num($e['monthlyCoverageHours'] ?? 0), $num($e['monthlyCoverageHours'] ?? 0)],
        ['Annual coverage hours', $num($e['annualCoverageHours'] ?? 0), $num($e['annualCoverageHours'] ?? 0)],
        ['Weeks of coverage', $num($e['weeksPerYear'] ?? 0), $num($e['weeksPerYear'] ?? 0)],
        ['Months of coverage', $num($e['monthsOfCoverage'] ?? 0), $num($e['monthsOfCoverage'] ?? 0)],
        ['Required workforce', $num($e['ftesRequired'] ?? 0).' FTEs', $num($e['ftesRequired'] ?? 0).' FTEs'],
        ['Estimated weekly cost', $maskedMoney($e['totalWeeklyInternal'] ?? null), $maskedMoney($e['totalWeeklyVendor'] ?? null)],
        ['Estimated monthly cost', $maskedMoney($e['totalMonthlyInternal'] ?? null), $maskedMoney($e['totalMonthlyVendor'] ?? null)],
        ['Estimated annual cost', $maskedMoney($e['totalAnnualInternal'] ?? ($inHouseCost ?? null)), $maskedMoney($e['totalAnnualVendor'] ?? ($outsourcingCost ?? null))],
        ['Potential annual capital recovery', '&mdash;', $maskedMoney($e['annualCapitalRecovery'] ?? ($capitalRecovery ?? null))],
    ];
@endphp
@section('content')
<p>Dear {{ $clientName ?: 'Valued Client' }},</p>
<p>Your confidential, password-protected GASQ Cost to Protect&trade; Estimate Report is attached.
@if(!empty($e['recoveryPct']))Based on the report&rsquo;s assumptions, outsourcing your security coverage could reduce estimated annual costs by {{ $e['recoveryPct'] }}%, creating a potential {{ $maskedWhole($e['annualCapitalRecovery'] ?? ($capitalRecovery ?? null)) }} annual Capital Recovery Opportunity.@endif</p>
<p style="margin-left:8px;">
    @if(!empty($reportNumber))<strong>Report number:</strong> {{ $reportNumber }}<br>@endif
    <strong>Date prepared:</strong> {{ $datePrepared }}
</p>

<h2 style="font-size:18px;color:#153a81;">Detailed Cost Appraisal Comparison</h2>
<table cellpadding="10" cellspacing="0" width="100%" style="border-collapse:collapse;font-size:13px;">
<thead><tr style="background:#153a81;color:#fff;text-align:left;"><th>Description</th><th>Buyer In-House Cost to Protect&trade;</th><th>Vendor Outsourcing Cost</th></tr></thead>
<tbody>
@foreach($comparison as $i => [$label, $internal, $vendor])
<tr @if($i % 2 === 1)style="background:#f1f5f9;"@endif><td>{{ $label }}</td><td>{!! $internal !!}</td><td>{!! $vendor !!}</td></tr>
@endforeach
</tbody>
</table>

<h2 style="font-size:18px;color:#153a81;">What the Estimate Includes</h2>
<p>The estimate accounts for wages, employer payroll taxes, workers&rsquo; compensation, general liability insurance, unemployment insurance, paid holidays, vacation and sick leave, healthcare and fringe benefits, uniforms, equipment, onboarding, training, supervision, quality assurance, administrative support, and 24/7 dispatch capability.</p>
<p>The report also identifies open-post protection, vendor replacement, and price-lock guarantees, subject to final agreement terms.</p>

<h2 style="font-size:18px;color:#153a81;">Next Step: Schedule Your Estimate &amp; Scope Review</h2>
<p>Please forward this email and the attached report to the decision-maker authorized to approve the security budget and include them in the appointment.</p>
<p>During the review, we will explain the detailed comparison, validate the coverage and staffing assumptions, and discuss the potential capital recovery before releasing the report password and complete pricing.</p>
@include('emails.partials.schedule-review', ['bookingUrl' => $bookingUrl ?? null, 'scheduleLabel' => 'Schedule Your Estimate & Scope Review'])
<p>These figures are planning estimates. Final pricing and potential savings depend on the confirmed scope, site assessment, and vendor acceptance.</p>
@if(!empty($surveyorNotes))
<p><strong>Notes from your GASQ representative:</strong></p>
<div style="padding:10px 12px;background:#f1f5f9;border-left:3px solid #153a81;white-space:pre-line;">{{ $surveyorNotes }}</div>
@endif
@if(!empty($attachmentCount))
<p>{{ $attachmentCount }} on-site {{ \Illuminate\Support\Str::plural('file', $attachmentCount) }} (photos/documents) {{ $attachmentCount === 1 ? 'is' : 'are' }} attached alongside your report.</p>
@endif
<p>Get A Security Quote (GASQ) Team<br>Get A Security Quote&trade;<br>The Financial Procurement System for Security Services&trade;<br>The Industry Pricing Referee&trade;</p>
<p><strong>Know Before You Buy. Qualifications First. Price Last.</strong></p>
@endsection

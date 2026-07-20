@php
    /** @var \App\Models\Bid $bid */
    /** @var \App\Models\JobPosting $job */
    /** @var \App\Models\User $vendor */
    /** @var \App\Models\User $buyer */
    use App\Support\LeadFormatting;

    $annualPrice = (float) ($bid->annual_price ?? 0);
    $monthlyPrice = (float) ($bid->monthly_price ?? ($annualPrice > 0 ? $annualPrice / 12 : 0));
    $weeklyPrice = (float) ($bid->weekly_price ?? ($annualPrice > 0 ? $annualPrice / 52 : 0));
    $hourlyRate = (float) ($bid->hourly_bill_rate ?? 0);

    $questionnaire = is_array($job?->questionnaire_data) ? $job->questionnaire_data : [];
    $hoursPerDay = (float) ($questionnaire['hours_per_day'] ?? 0);
    $daysPerWeek = (float) ($questionnaire['days_per_week'] ?? 0);
    $weeksPerYear = (float) ($questionnaire['weeks_per_year'] ?? 52);
    $annualHours = ($hoursPerDay > 0 && $daysPerWeek > 0 && $weeksPerYear > 0)
        ? $hoursPerDay * $daysPerWeek * $weeksPerYear
        : 8736;

    // GASQ Side-by-side TCO formula
    $EMPLOYER_FRINGE_FACTOR = 0.70;
    $PAID_HOURS_PER_FTE = 3744;
    $BILLABLE_HOURS_PER_FTE = 1456;
    $VENDOR_DISCOUNT_FACTOR = 0.70;

    $baselineWage = (float) data_get($questionnaire, 'baseline_wage', 0);
    if ($baselineWage <= 0 && $hourlyRate > 0) {
        $baselineWage = $hourlyRate * $BILLABLE_HOURS_PER_FTE / $PAID_HOURS_PER_FTE;
    }
    if ($baselineWage <= 0) $baselineWage = 25.00;

    $loadedWage = $baselineWage / $EMPLOYER_FRINGE_FACTOR;
    $annualWorkforceCost = $loadedWage * $PAID_HOURS_PER_FTE;
    $internalTcoHourly = $annualWorkforceCost / $BILLABLE_HOURS_PER_FTE;
    $vendorTcoHourly = $internalTcoHourly * $VENDOR_DISCOUNT_FACTOR;
    $capitalRecoveryPerHour = $internalTcoHourly - $vendorTcoHourly;
    $annualCapitalRecovery = $capitalRecoveryPerHour * $annualHours;
    $internalShouldCost = $internalTcoHourly * $annualHours;
    $savingsPct = $internalShouldCost > 0 ? round(100 * $annualCapitalRecovery / $internalShouldCost) : 0;
    $monthlySavings = $annualCapitalRecovery / 12;
    $paybackMonths = $monthlySavings > 0.01 ? (int) ceil($annualCapitalRecovery / $monthlySavings) : 0;

    $vendorCapability = $vendor?->vendorCapability;

    $reportNumber = 'GASQ ' . now()->format('Y-m-d') . '-BID' . str_pad((string) $bid->id, 4, '0', STR_PAD_LEFT);

    $money = fn ($v) => \App\Support\Currency::format($v, 2);
    $moneyK = fn ($v) => \App\Support\Currency::format($v, 0);
@endphp

@extends('pdf.layouts.gasq-report', [
    'title' => 'GASQ Bid Engagement Report',
    'subtitle' => 'Vendor Pricing Submission · ' . ($job?->title ?? ''),
    'reportNumber' => $reportNumber,
    'reportType' => 'Buyer-Facing Bid Packet',
    'contactName' => $vendor?->name,
    'contactCompany' => $vendor?->company ?? $vendor?->vendorProfile?->company_name,
    'contactEmail' => $vendor?->email,
    'contactPhone' => $vendor?->phone,
])

@section('stat_grid')
<table width="100%" cellpadding="0" cellspacing="0">
  <tr>
    <td width="33%" class="stat-grid-label"><p>Annual Bid</p></td>
    <td width="34%" class="stat-grid-label"><p>Hourly Bill Rate</p></td>
    <td width="33%" class="stat-grid-label last"><p>Capital Recovered</p></td>
  </tr>
  <tr>
    <td class="stat-grid-value bg-blue">
      <p class="num">{{ $moneyK($annualPrice) }}</p>
      <p class="sub">vendor pricing</p>
    </td>
    <td class="stat-grid-value bg-purple">
      <p class="num">{{ $money($hourlyRate) }}</p>
      <p class="sub">per service hour</p>
    </td>
    <td class="stat-grid-value bg-green last">
      <p class="num">{{ $moneyK($annualCapitalRecovery) }}</p>
      <p class="sub">{{ $savingsPct }}% vs in-house</p>
    </td>
  </tr>
</table>
@endsection

@section('content')

<table width="100%" cellpadding="0" cellspacing="0" class="gasq-mt">
  <tr><td class="gasq-section-band"><p>Pricing Summary</p></td></tr>
</table>
<table width="100%" cellpadding="0" cellspacing="0" class="gasq-kv">
  <tr><td>Hourly Bill Rate</td><td class="v">{{ $money($hourlyRate) }}</td></tr>
  <tr class="alt"><td>Weekly Cost</td><td class="v">{{ $money($weeklyPrice) }}</td></tr>
  <tr><td>Monthly Cost</td><td class="v">{{ $money($monthlyPrice) }}</td></tr>
  <tr class="total"><td>Annual Cost</td><td class="v">{{ $money($annualPrice) }}</td></tr>
</table>

<table width="100%" cellpadding="0" cellspacing="0" class="gasq-mt">
  <tr><td class="gasq-section-band"><p>GASQ Workforce-to-Post™ Capital Recovery Formula</p></td></tr>
</table>
<table width="100%" cellpadding="0" cellspacing="0" class="gasq-kv">
  <tr><td>Baseline Wage (input)</td><td class="v">{{ $money($baselineWage) }}/hr</td></tr>
  <tr class="alt"><td>1. Loaded Wage (÷ 0.70)</td><td class="v">{{ $money($loadedWage) }}/hr</td></tr>
  <tr><td>2. Annual Workforce Availability Cost (× 3,744)</td><td class="v">{{ $money($annualWorkforceCost) }}</td></tr>
  <tr class="alt"><td>3. Internal True Cost of Ownership (÷ 1,456)</td><td class="v">{{ $money($internalTcoHourly) }}/hr</td></tr>
  <tr><td>4. Vendor TCO / Outsourced Rate (× 0.70)</td><td class="v">{{ $money($vendorTcoHourly) }}/hr</td></tr>
  <tr class="hl"><td>5. Capital Recovery Opportunity / hr</td><td class="v">{{ $money($capitalRecoveryPerHour) }}</td></tr>
  <tr class="total"><td>6. Annual Capital Recovery (× {{ number_format($annualHours, 0) }} hrs)</td><td class="v">{{ $money($annualCapitalRecovery) }}</td></tr>
</table>

<p class="gasq-note">
    <strong>What this means:</strong> By outsourcing at the vendor TCO rate of {{ $money($vendorTcoHourly) }}/hr, the buyer creates a capital recovery opportunity of approximately {{ $money($capitalRecoveryPerHour) }} per service hour, or {{ $money($annualCapitalRecovery) }} annually for a {{ number_format($annualHours, 0) }}-hour post.
</p>

<table width="100%" cellpadding="0" cellspacing="0" class="gasq-mt">
  <tr><td class="gasq-section-band"><p>Side-by-Side Annual Comparison</p></td></tr>
</table>
<table width="100%" cellpadding="0" cellspacing="0" class="gasq-kv">
  <tr style="background:#f0f4fb;">
    <td style="font-weight:bold; color:#1e3558;">Description</td>
    <td class="v" style="color:#1e3558;">Internal should-cost</td>
    <td class="v" style="color:#1e3558;">Vendor TCO</td>
  </tr>
  <tr><td>Hourly Rate</td><td class="v">{{ $money($internalTcoHourly) }}</td><td class="v">{{ $money($vendorTcoHourly) }}</td></tr>
  <tr class="alt"><td>Total Annual Hours of Coverage</td><td class="v">{{ number_format($annualHours, 0) }}</td><td class="v">{{ number_format($annualHours, 0) }}</td></tr>
  <tr><td>Annual Cost</td><td class="v">{{ $money($internalShouldCost) }}</td><td class="v">{{ $money($vendorTcoHourly * $annualHours) }}</td></tr>
  <tr style="background:#e8f5eb;"><td style="font-weight:bold; color:#1e3558; border-top:2px solid #1e3558;">Operational Capital Recovered</td><td class="v" style="border-top:2px solid #1e3558;">—</td><td class="v" style="color:#1e3558; font-size:12px; border-top:2px solid #1e3558;">{{ $money($annualCapitalRecovery) }}</td></tr>
  <tr style="background:#e8f5eb;"><td style="font-weight:bold; color:#1e3558;">Operational Capital Recovered (%)</td><td class="v">—</td><td class="v" style="color:#1e3558;">{{ $savingsPct }}%</td></tr>
  <tr style="background:#e8f5eb;"><td style="font-weight:bold; color:#1e3558;">Payback &amp; Recovery Period</td><td class="v">—</td><td class="v" style="color:#1e3558;">{{ $paybackMonths }} months</td></tr>
</table>

@if($bid->staffing_plan)
<table width="100%" cellpadding="0" cellspacing="0" class="gasq-mt">
  <tr><td class="gasq-section-band"><p>Staffing Plan</p></td></tr>
</table>
<div style="border:1px solid #d8dff0; border-top:none; padding:11px 16px; font-size:10.5px; color:#374151; line-height:1.5;">{{ $bid->staffing_plan }}</div>
@endif

@if($bid->vendor_notes || $bid->message)
<table width="100%" cellpadding="0" cellspacing="0" class="gasq-mt">
  <tr><td class="gasq-section-band"><p>Vendor Notes</p></td></tr>
</table>
<div style="border:1px solid #d8dff0; border-top:none; padding:11px 16px; font-size:10.5px; color:#374151; line-height:1.5;">{{ $bid->vendor_notes ?: $bid->message }}</div>
@endif

<table width="100%" cellpadding="0" cellspacing="0" class="gasq-mt">
  <tr><td class="gasq-section-band"><p>Vendor Capability Snapshot</p></td></tr>
</table>
<table width="100%" cellpadding="0" cellspacing="0" class="gasq-kv">
  <tr><td>Company</td><td class="v">{{ $vendor?->company ?? $vendor?->vendorProfile?->company_name ?? '—' }}</td></tr>
  <tr class="alt"><td>Years in business</td><td class="v">{{ $vendorCapability?->years_in_business ?? '—' }}</td></tr>
  <tr><td>Business license</td><td class="v">{{ $vendorCapability?->business_license_number ?: ($vendorCapability?->license_verified ? 'Verified' : '—') }}</td></tr>
  <tr class="alt"><td>Insurance verified</td><td class="v">{{ $vendorCapability?->insurance_verified ? 'Yes' : 'On file' }}</td></tr>
  <tr><td>Service areas</td><td class="v" style="font-weight:normal; color:#374151;">{{ is_array($vendorCapability?->service_areas) ? implode(', ', $vendorCapability->service_areas) : '—' }}</td></tr>
  <tr class="alt"><td>Core competencies</td><td class="v" style="font-weight:normal; color:#374151;">{{ is_array($vendorCapability?->core_competencies) ? implode(', ', $vendorCapability->core_competencies) : '—' }}</td></tr>
  <tr><td>Contact</td><td class="v" style="font-weight:normal; color:#374151;">{{ $vendor?->email }}{{ $vendor?->phone ? ' · ' . $vendor->phone : '' }}</td></tr>
</table>

<p class="gasq-note">
    This bid is backed by the GASQ Price Lock Guarantee and Vendor Replacement Guarantee. All price calculations include the full cost of workforce staffing and support services.
</p>

@endsection

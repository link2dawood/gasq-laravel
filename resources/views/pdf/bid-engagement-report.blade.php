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

    // GASQ Workforce-to-Post model, used for the BUYER's in-house should-cost only.
    $EMPLOYER_FRINGE_FACTOR = 0.70;
    $PAID_HOURS_PER_FTE = 3744;
    $BILLABLE_HOURS_PER_FTE = 1456;
    $VENDOR_DISCOUNT_FACTOR = 0.70;

    // The comparison needs a baseline wage that came from the BUYER. Deriving one
    // from the vendor's own bid would make the in-house column a restatement of
    // the bid, and the saving against it meaningless, so when the job carries no
    // baseline the comparison is withheld rather than invented.
    $baselineWage = (float) data_get($questionnaire, 'baseline_wage', 0);
    $baselineKnown = $baselineWage > 0;

    $loadedWage = $baselineKnown ? $baselineWage / $EMPLOYER_FRINGE_FACTOR : 0.0;
    $annualWorkforceCost = $loadedWage * $PAID_HOURS_PER_FTE;
    $internalTcoHourly = $baselineKnown ? $annualWorkforceCost / $BILLABLE_HOURS_PER_FTE : 0.0;
    $internalShouldCost = $internalTcoHourly * $annualHours;

    // GASQ benchmark for an outsourced rate. Shown as a benchmark only: the
    // vendor column of the comparison is this vendor's actual bid.
    $benchmarkVendorHourly = $internalTcoHourly * $VENDOR_DISCOUNT_FACTOR;

    // The vendor side of every figure below is the submitted bid.
    $vendorHourly = $hourlyRate > 0
        ? $hourlyRate
        : ($annualPrice > 0 && $annualHours > 0 ? $annualPrice / $annualHours : 0.0);
    $vendorAnnual = $annualPrice > 0 ? $annualPrice : $vendorHourly * $annualHours;

    $capitalRecoveryPerHour = $internalTcoHourly - $vendorHourly;
    $annualCapitalRecovery = $internalShouldCost - $vendorAnnual;
    $savingsPct = $internalShouldCost > 0 ? round(100 * $annualCapitalRecovery / $internalShouldCost) : 0;
    $internalMonthly = $internalShouldCost / 12;
    // Months of the buyer's own in-house run rate that the bid consumes.
    $paybackMonths = $internalMonthly > 0.01 ? round($vendorAnnual / $internalMonthly, 1) : 0;
    $comparable = $baselineKnown && $internalShouldCost > 0 && $vendorAnnual > 0;

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
    <td width="33%" class="stat-grid-label last"><p>{{ ! $comparable || $annualCapitalRecovery >= 0 ? 'Capital Recovered' : 'Above In-House Cost' }}</p></td>
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
    {{-- Headline recovery only when there is a buyer baseline to recover
         against, and stated as additional cost when the bid sits above it. --}}
    <td class="stat-grid-value {{ ! $comparable ? 'bg-blue' : ($annualCapitalRecovery >= 0 ? 'bg-green' : 'bg-purple') }} last">
      @if($comparable)
        <p class="num">{{ $moneyK(abs($annualCapitalRecovery)) }}</p>
        <p class="sub">{{ abs($savingsPct) }}% {{ $annualCapitalRecovery >= 0 ? 'vs in-house' : 'above in-house' }}</p>
      @else
        <p class="num">&mdash;</p>
        <p class="sub">no buyer baseline on file</p>
      @endif
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

@if($comparable)
<table width="100%" cellpadding="0" cellspacing="0" class="gasq-mt">
  <tr><td class="gasq-section-band"><p>GASQ Workforce-to-Post™ Capital Recovery Formula</p></td></tr>
</table>
<table width="100%" cellpadding="0" cellspacing="0" class="gasq-kv">
  <tr><td>Baseline Wage (buyer input)</td><td class="v">{{ $money($baselineWage) }}/hr</td></tr>
  <tr class="alt"><td>1. Loaded Wage (÷ 0.70)</td><td class="v">{{ $money($loadedWage) }}/hr</td></tr>
  <tr><td>2. Annual Workforce Availability Cost (× 3,744)</td><td class="v">{{ $money($annualWorkforceCost) }}</td></tr>
  <tr class="alt"><td>3. Internal True Cost of Ownership (÷ 1,456)</td><td class="v">{{ $money($internalTcoHourly) }}/hr</td></tr>
  <tr><td>4. GASQ Benchmark Outsourced Rate (× 0.70)</td><td class="v">{{ $money($benchmarkVendorHourly) }}/hr</td></tr>
  <tr class="alt"><td>5. This Vendor's Submitted Bid Rate</td><td class="v">{{ $money($vendorHourly) }}/hr</td></tr>
  <tr class="hl"><td>6. Capital Recovery at the Bid Rate / hr</td><td class="v">{{ $money($capitalRecoveryPerHour) }}</td></tr>
  <tr class="total"><td>7. Annual Capital Recovery (× {{ number_format($annualHours, 0) }} hrs)</td><td class="v">{{ $money($annualCapitalRecovery) }}</td></tr>
</table>

<p class="gasq-note">
    <strong>What this means:</strong> Rows 1 to 4 are the GASQ model: what the post costs in-house, and the benchmark rate an outsourced post is expected to come in at. Row 5 is what this vendor actually bid.
    @if($annualCapitalRecovery >= 0)
        At {{ $money($vendorHourly) }}/hr the buyer recovers approximately {{ $money($capitalRecoveryPerHour) }} per service hour, or {{ $money($annualCapitalRecovery) }} annually across {{ number_format($annualHours, 0) }} hours.
    @else
        At {{ $money($vendorHourly) }}/hr the bid sits {{ $money(abs($capitalRecoveryPerHour)) }} per service hour ABOVE the in-house should-cost, or {{ $money(abs($annualCapitalRecovery)) }} annually across {{ number_format($annualHours, 0) }} hours.
    @endif
</p>
@else
<table width="100%" cellpadding="0" cellspacing="0" class="gasq-mt">
  <tr><td class="gasq-section-band"><p>In-House Comparison</p></td></tr>
</table>
<p class="gasq-note">
    This job carries no baseline wage from the buyer, so there is no independent in-house cost to compare this bid against. The pricing above is the vendor's submission in full. Add a baseline wage to the job and the comparison is produced automatically.
</p>
@endif

@if($comparable)
@php
    // One vendor column, one vendor number: the submitted bid, the same figure
    // the Pricing Summary above prints.
    $recovered = $annualCapitalRecovery >= 0;
    $recoverRow = $recovered ? '#e8f5eb' : '#fdf1ec';
@endphp
<table width="100%" cellpadding="0" cellspacing="0" class="gasq-mt">
  <tr><td class="gasq-section-band"><p>Side-by-Side Annual Comparison</p></td></tr>
</table>
<table width="100%" cellpadding="0" cellspacing="0" class="gasq-kv">
  <tr style="background:#f0f4fb;">
    <td style="font-weight:bold; color:#1e3558;">Description</td>
    <td class="v" style="color:#1e3558;">Internal should-cost</td>
    <td class="v" style="color:#1e3558;">This vendor's bid</td>
  </tr>
  <tr><td>Hourly Rate</td><td class="v">{{ $money($internalTcoHourly) }}</td><td class="v">{{ $money($vendorHourly) }}</td></tr>
  <tr class="alt"><td>Total Annual Hours of Coverage</td><td class="v">{{ number_format($annualHours, 0) }}</td><td class="v">{{ number_format($annualHours, 0) }}</td></tr>
  <tr><td>Annual Cost</td><td class="v">{{ $money($internalShouldCost) }}</td><td class="v">{{ $money($vendorAnnual) }}</td></tr>
  <tr style="background:{{ $recoverRow }};"><td style="font-weight:bold; color:#1e3558; border-top:2px solid #1e3558;">{{ $recovered ? 'Operational Capital Recovered' : 'Additional Cost vs In-House' }}</td><td class="v" style="border-top:2px solid #1e3558;">—</td><td class="v" style="color:#1e3558; font-size:12px; border-top:2px solid #1e3558;">{{ $money(abs($annualCapitalRecovery)) }}</td></tr>
  <tr style="background:{{ $recoverRow }};"><td style="font-weight:bold; color:#1e3558;">{{ $recovered ? 'Operational Capital Recovered (%)' : 'Additional Cost (%)' }}</td><td class="v">—</td><td class="v" style="color:#1e3558;">{{ abs($savingsPct) }}%</td></tr>
  <tr style="background:{{ $recoverRow }};"><td style="font-weight:bold; color:#1e3558;">Payback &amp; Recovery Period</td><td class="v">—</td><td class="v" style="color:#1e3558;">{{ $recovered ? number_format($paybackMonths, 1).' months' : 'No recovery at this bid' }}</td></tr>
</table>
@endif

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

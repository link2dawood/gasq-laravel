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
    $annualHours = 0.0;
    $hoursPerDay = (float) ($questionnaire['hours_per_day'] ?? 0);
    $daysPerWeek = (float) ($questionnaire['days_per_week'] ?? 0);
    $weeksPerYear = (float) ($questionnaire['weeks_per_year'] ?? 52);
    if ($hoursPerDay > 0 && $daysPerWeek > 0 && $weeksPerYear > 0) {
        $annualHours = $hoursPerDay * $daysPerWeek * $weeksPerYear;
    }

    // Internal should-cost benchmark (use buyer's confirmed budget if present, otherwise vendor TCO × markup).
    $internalShouldCost = (float) ($opportunity?->estimated_annual_contract_value ?? 0);
    if ($internalShouldCost <= 0 && $annualPrice > 0) {
        $internalShouldCost = $annualPrice * 1.42; // typical internal markup vs vendor TCO
    }
    $savings = max(0, $internalShouldCost - $annualPrice);
    $savingsPct = $internalShouldCost > 0 ? round(100 * $savings / $internalShouldCost) : 0;
    $monthlySavings = $savings / 12;
    $paybackMonths = $monthlySavings > 0.01 ? (int) ceil($savings / $monthlySavings) : 0;

    // Vendor capability summary from their profile.
    $vendorProfile = $vendor?->vendorProfile;
    $vendorCapability = $vendor?->vendorCapability;
@endphp

@extends('pdf.layouts.gasq-base', [
    'title' => 'Vendor Bid Engagement Report',
    'subtitle' => 'GASQ Workforce-to-Post™ Pricing Submission',
    'preparedFor' => ($buyer?->name ?? '') . ($buyer?->company ? ' — ' . $buyer->company : ''),
    'preparedBy' => ($vendor?->name ?? '') . ($vendor?->company ? ' — ' . $vendor->company : ''),
    'reportDate' => ($bid->submitted_at ?? now())->format('F j, Y'),
    'referenceNumber' => 'BID-' . str_pad((string) $bid->id, 6, '0', STR_PAD_LEFT),
])

@section('cover_summary')

<div class="exec-grid">
    <div class="exec-cell">
        <div class="label">Annual Cost</div>
        <div class="value">{{ LeadFormatting::moneyFull($annualPrice) }}</div>
    </div>
    <div class="exec-cell">
        <div class="label">Hourly Bill Rate</div>
        <div class="value">${{ number_format($hourlyRate, 2) }}</div>
    </div>
    <div class="exec-cell">
        <div class="label">Capital Recovered</div>
        <div class="value">{{ LeadFormatting::moneyFull($savings) }}</div>
    </div>
    <div class="exec-cell">
        <div class="label">Recovered %</div>
        <div class="value">{{ $savingsPct }}%</div>
    </div>
</div>

<p style="margin-top:18px;">
    <strong>{{ $vendor?->name ?? 'The vendor' }}</strong>@if($vendor?->company) ({{ $vendor->company }})@endif has submitted a bid for <strong>"{{ $job?->title ?? 'your engagement' }}"</strong>{{ $job?->location ? ' in ' . $job->location : '' }}, priced through the GASQ Workforce-to-Post™ system.
</p>

<p>
    This report shows the vendor's Workforce Absorbed Rate breakdown, side-by-side comparison vs. internal should-cost, and a snapshot of the vendor's operational capability.
</p>

<div class="badge-row">
    @if($bid->realism_label)
        @php
            $realism = strtolower($bid->realism_label);
            $badgeClass = match (true) {
                str_contains($realism, 'aligned') || str_contains($realism, 'ok') || str_contains($realism, 'good') => 'ok',
                str_contains($realism, 'flag') || str_contains($realism, 'risk') => 'warn',
                default => 'info',
            };
        @endphp
        <span class="badge {{ $badgeClass }}">GASQ Realism Review: {{ ucfirst($bid->realism_label) }}</span>
    @endif
</div>

<div class="gasq-protection-block">
    <h3>GASQ Protections in Effect</h3>
    <p class="small" style="margin:0;">
        <strong>Price Lock Guarantee</strong> — the pricing on this bid is locked through the engagement.
        <strong>Vendor Replacement Guarantee</strong> — if the selected vendor fails to perform, GASQ will replace them at the same or higher service level.
    </p>
</div>

@endsection

@section('content')

<h2>Pricing Summary</h2>
<table>
    <thead>
        <tr><th>Period</th><th class="right">Bill Rate / Cost</th></tr>
    </thead>
    <tbody>
        <tr><td>Hourly Bill Rate</td><td class="right mono">${{ number_format($hourlyRate, 2) }}</td></tr>
        <tr><td>Weekly Cost</td><td class="right mono">${{ number_format($weeklyPrice, 2) }}</td></tr>
        <tr><td>Monthly Cost</td><td class="right mono">${{ number_format($monthlyPrice, 2) }}</td></tr>
        <tr class="emphasis"><td><strong>Annual Cost</strong></td><td class="right mono"><strong>${{ number_format($annualPrice, 2) }}</strong></td></tr>
    </tbody>
</table>

<h2>Workforce Appraisal Comparison</h2>
<p class="small muted">Side-by-side view of internal should-cost vs. the vendor's quoted TCO, derived from the GASQ Workforce Absorbed Rate model.</p>
<table>
    <thead>
        <tr>
            <th>Description</th>
            <th class="right">Internal should-cost</th>
            <th class="right">Vendor TCO</th>
        </tr>
    </thead>
    <tbody>
        <tr>
            <td>Annual Contract Value</td>
            <td class="right mono">{{ LeadFormatting::moneyFull($internalShouldCost) }}</td>
            <td class="right mono">{{ LeadFormatting::moneyFull($annualPrice) }}</td>
        </tr>
        @if($annualHours > 0)
        <tr>
            <td>Total Annual Hours of Coverage</td>
            <td class="right mono">{{ number_format($annualHours, 0) }}</td>
            <td class="right mono">{{ number_format($annualHours, 0) }}</td>
        </tr>
        <tr>
            <td>Implied Internal Hourly Rate</td>
            <td class="right mono">${{ number_format($annualHours > 0 ? $internalShouldCost / $annualHours : 0, 2) }}</td>
            <td class="right mono">${{ number_format($hourlyRate, 2) }}</td>
        </tr>
        @endif
    </tbody>
    <tfoot>
        <tr class="emphasis"><td>Operational Capital Recovered</td><td class="right mono">—</td><td class="right mono">{{ LeadFormatting::moneyFull($savings) }}</td></tr>
        <tr class="emphasis"><td>Operational Capital Recovered (%)</td><td class="right mono">—</td><td class="right mono">{{ $savingsPct }}%</td></tr>
        <tr class="emphasis"><td>Payback &amp; Recovery Period</td><td class="right mono">—</td><td class="right mono">{{ $paybackMonths }} months</td></tr>
    </tfoot>
</table>

@if($bid->staffing_plan)
<h2>Staffing Plan</h2>
<p>{{ $bid->staffing_plan }}</p>
@endif

@if($bid->vendor_notes || $bid->message)
<h2>Vendor Notes</h2>
<p>{{ $bid->vendor_notes ?: $bid->message }}</p>
@endif

@if($bid->start_availability)
<h2>Start Availability</h2>
<p>{{ $bid->start_availability }}</p>
@endif

<h2>Vendor Capability Snapshot</h2>
<table class="kv-table">
    <tr><td class="label">Company</td><td><strong>{{ $vendor?->company ?? $vendorProfile?->company_name ?? '—' }}</strong></td></tr>
    <tr><td class="label">Years in business</td><td>{{ $vendorCapability?->years_in_business ?? '—' }}</td></tr>
    <tr><td class="label">Business license</td><td>{{ $vendorCapability?->business_license_number ?: ($vendorCapability?->license_verified ? 'Verified' : '—') }}</td></tr>
    <tr><td class="label">Insurance verified</td><td>{{ $vendorCapability?->insurance_verified ? 'Yes' : 'On file' }}</td></tr>
    <tr><td class="label">Service areas</td><td>{{ is_array($vendorCapability?->service_areas) ? implode(', ', $vendorCapability->service_areas) : '—' }}</td></tr>
    <tr><td class="label">Core competencies</td><td>{{ is_array($vendorCapability?->core_competencies) ? implode(', ', $vendorCapability->core_competencies) : '—' }}</td></tr>
    <tr><td class="label">Contact</td><td>{{ $vendor?->email }}{{ $vendor?->phone ? ' · ' . $vendor->phone : '' }}</td></tr>
</table>

<p class="small muted" style="margin-top:20px;">
    All price calculations include the full cost of workforce staffing and support services, including livable base wages, employer-paid payroll taxes (FICA, FUTA, SUTA), workers' compensation, general liability insurance, unemployment insurance, paid time off, healthcare and fringe benefits, uniforms and equipment, onboarding and training, site supervision, quality assurance oversight, management and administrative support, 24/7 dispatch capability, compliance with local, state, and federal labor laws, and all service-level guarantees (including open post protection, vendor replacement, and price lock guarantees) unless otherwise specified.
</p>

@endsection

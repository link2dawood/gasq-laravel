{{--
    Know Before You Bid dashboard attached to a vendor service estimate.

    Three blocks, as specified by GASQ: a Cost to Protect summary, a budget
    summary, and the budget line items. Every figure comes from
    App\Services\CostToProtectEstimate, the same service behind the master
    estimate dashboard (pdf.cost-to-protect-estimate), so the two documents can
    never report different numbers for the same scenario.

    BUYER EDITION ($masked): the buyer's own in-house cost to protect is shown in
    full; the vendor's cost, the capital recovered and the payback period are
    withheld. Withheld figures keep their shape but lose their digits ($538,769 →
    $•••,•••). PDF encryption is all-or-nothing, so a password cannot reveal
    these figures later: the unmasked edition is a separate render.

    Required: $estimate (with a budget_scenario), $masked.
--}}
@php
    use App\Services\CostToProtectEstimate;
    use App\Support\Currency;

    $d = app(CostToProtectEstimate::class)->build((array) $estimate->budget_scenario, $estimate->vendor);

    // Calculator figures are stored in USD. They are converted into the
    // ESTIMATE's currency, not the ambient Currency::code(): this document is
    // rendered both in a web request (where that resolves to the signed-in
    // vendor) and in a queued mail job (where it falls back to the platform
    // default), and the dashboard must not change currency between the two.
    $dashCurrency = $estimate->currency;
    $rate = Currency::rate($dashCurrency);
    $symbol = Currency::symbol($dashCurrency);
    $money = fn ($v) => $symbol.number_format((float) $v * $rate, 2);
    $moneyK = fn ($v) => $symbol.number_format((float) $v * $rate, 0);
    $num = fn ($v) => number_format((float) $v);
    $numDec = fn ($v, $dp = 1) => number_format((float) $v, $dp);

    // Wrap ONLY vendor-side and savings-side values. Buyer-internal figures are
    // never wrapped: the buyer edition reports those in full.
    $lock = fn (string $formatted) => $masked ? preg_replace('/\d/', '•', $formatted) : $formatted;

    $capturedAt = $estimate->budget_scenario_at?->format('F j, Y');
@endphp

<div style="page-break-before:always;"></div>

<div class="gasq-section-band gasq-mt">
    <p>Cost to Protect Summary · {{ $dashCurrency }}</p>
</div>
<table width="100%" cellpadding="0" cellspacing="0" style="border:1px solid #d8dff0; border-top:none;">
    <tr>
        @foreach([
            'Buyer internal cost to protect',
            'Vendor outsourcing cost to protect',
            'Operational capital recovered',
            'Total staff required',
        ] as $i => $label)
            <td width="25%" class="stat-grid-label {{ $i === 3 ? 'last' : '' }}"><p>{{ $label }}</p></td>
        @endforeach
    </tr>
    <tr>
        @foreach([
            [$moneyK($d['totalAnnualInternal']), 'Total annual in-house cost', 'bg-sky'],
            [$lock($moneyK($d['totalAnnualVendor'])), 'Total annual vendor cost', 'bg-peach'],
            [$lock($moneyK($d['annualCapitalRecovery'])), $lock($num($d['recoveryPct']).'% recovered vs in-house'), 'bg-green'],
            [$num($d['ftesRequired']).' FTE', 'To deliver the proposed scope', 'bg-blue'],
        ] as $i => [$value, $sub, $bg])
            <td width="25%" class="stat-grid-value {{ $bg }} {{ $i === 3 ? 'last' : '' }}">
                <p class="num" style="font-size:19px;">{{ $value }}</p>
                <p class="sub">{{ $sub }}</p>
            </td>
        @endforeach
    </tr>
</table>
@if($masked)
<p class="gasq-note"><strong>Vendor figures withheld in this edition.</strong> Your own cost to protect is reported in full. Contact your vendor to release the complete estimate with the vendor cost, the capital recovered and the payback period.</p>
@endif

<div class="gasq-section-band gasq-mt-sm">
    <p>Budget Summary · {{ $dashCurrency }}</p>
</div>
<table class="gasq-kv" width="100%">
    <thead>
        <tr class="hl">
            <td>Period</td>
            <td class="v">Buyer internal cost to protect</td>
            <td class="v">Vendor outsourcing cost to protect</td>
        </tr>
    </thead>
    <tbody>
        @foreach([
            ['Weekly cost', $d['totalWeeklyInternal'], $d['totalWeeklyVendor']],
            ['Monthly cost', $d['totalMonthlyInternal'], $d['totalMonthlyVendor']],
        ] as $i => [$label, $internal, $vendor])
            <tr class="{{ $i % 2 === 1 ? 'alt' : '' }}">
                <td>{{ $label }}</td>
                <td class="v">{{ $money($internal) }}</td>
                <td class="v">{{ $lock($money($vendor)) }}</td>
            </tr>
        @endforeach
        <tr class="total">
            <td>Annual cost</td>
            <td class="v">{{ $money($d['totalAnnualInternal']) }}</td>
            <td class="v">{{ $lock($money($d['totalAnnualVendor'])) }}</td>
        </tr>
        <tr>
            <td>Operational capital recovered</td>
            <td class="v">&mdash;</td>
            <td class="v">{{ $lock($money($d['annualCapitalRecovery'])) }}</td>
        </tr>
        <tr class="alt">
            <td>Operational capital recovered (%)</td>
            <td class="v">&mdash;</td>
            <td class="v">{{ $lock($num($d['recoveryPct']).'%') }}</td>
        </tr>
        <tr>
            <td>Payback and recovery period</td>
            <td class="v">&mdash;</td>
            <td class="v">{{ $lock($numDec($d['paybackMonths'], 1).' months') }}</td>
        </tr>
    </tbody>
</table>

<div class="gasq-section-band gasq-mt-sm">
    <p>Budget Line Items</p>
</div>
<table class="gasq-kv" width="100%">
    <thead>
        <tr class="hl">
            <td>Description</td>
            <td class="v">Buyer internal</td>
            <td class="v">Vendor outsourcing</td>
        </tr>
    </thead>
    <tbody>
        @foreach([
            ['Workforce baseline assumption labor rate', $money($d['baselineWage']), $lock($money($d['baselineWage']))],
            ['Cost to protect hourly rate', $money($d['internalTcoHourly']), $lock($money($d['vendorTcoHourly']))],
            ['Overtime / holiday rate', $money($d['internalOtHourly']), $lock($money($d['vendorOtHourly']))],
            ['Annual cost per security professional', $money($d['annualPerInternalFte']), $lock($money($d['annualPerVendorFte']))],
            ['Total weekly hours of coverage', $num($d['weeklyCoverageHours']), $num($d['weeklyCoverageHours'])],
            ['Total monthly hours of coverage', $num($d['monthlyCoverageHours']), $num($d['monthlyCoverageHours'])],
            ['Total annual hours of coverage', $num($d['annualCoverageHours']), $num($d['annualCoverageHours'])],
            ['Total weeks of coverage', $num($d['weeksPerYear']), $num($d['weeksPerYear'])],
            ['Total months of coverage', $numDec($d['monthsOfCoverage'], 1), $numDec($d['monthsOfCoverage'], 1)],
            ['Total workforce required for coverage', $num($d['ftesRequired']), $num($d['ftesRequired'])],
        ] as $i => [$label, $internal, $vendor])
            <tr class="{{ $i % 2 === 1 ? 'alt' : '' }}">
                <td>{{ $label }}</td>
                <td class="v">{{ $internal }}</td>
                <td class="v">{{ $vendor }}</td>
            </tr>
        @endforeach
    </tbody>
</table>

{{-- The coverage basis reads as one line rather than a fifth table: the weeks,
     the coverage hours and the baseline wage are already line items above, and a
     block of its own pushed the buyer edition onto a page carrying only a
     footer. --}}
<p class="gasq-note"><strong>Coverage modelled:</strong> {{ $numDec($d['hoursPerDay'], 1) }} hours a day, {{ $numDec($d['daysPerWeek'], 1) }} days a week, {{ $num($d['weeksPerYear']) }} weeks of coverage, {{ $num($d['staffPerShift']) }} on post per shift, on a {{ $money($d['baselineWage']) }} workforce baseline assumption labor rate.</p>

<p class="gasq-note">From the GASQ Know Before You Bid Calculator{{ $capturedAt ? ', scenario saved '.$capturedAt : '' }}, reported in {{ $dashCurrency }} at the dated reference rate. The buyer internal cost to protect is what this scope costs to deliver with the buyer's own workforce, fully loaded: a modelled appraisal of the coverage assumptions above, not a quotation, and independent of the estimate pricing on the preceding page.</p>

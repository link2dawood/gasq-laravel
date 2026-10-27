@props(['summary' => null, 'prefix' => 'cost_summary_', 'showVendor' => true, 'showRecovery' => null, 'showTable' => true, 'showMetrics' => true, 'ids' => [], 'emptyState' => true])
@php
    $id = fn ($key) => $ids[$key] ?? $prefix.$key;
    $money = fn ($key) => \App\Support\Currency::format($summary[$key] ?? 0);
    $number = fn ($value) => rtrim(rtrim(number_format((float) $value, 2), '0'), '.');
@endphp
@if(!$summary && $emptyState)
    <p class="text-gasq-muted mb-3">Save your Budget Calculator inputs to see your cost summary and ROI here.</p>
    <a href="{{ route('budget-calculator.index') }}" class="btn btn-outline-primary btn-sm">Open Budget Calculator</a>
@else
    <div class="row g-2 mb-3">
        <div class="{{ $showVendor ? 'col-sm-6' : 'col-12' }}">
            <div class="p-3 rounded text-center h-100" style="background:#fdf2f2;border:2px solid #b91c1c;color:#7f1d1d">
                <div class="text-uppercase small fw-semibold mb-1">Buyer Cost to Protect In-house</div>
                <div class="h4 fw-bold mb-0" id="{{ $id('inHouseHourly') }}">{{ $money('inHouseHourly') }}</div>
                <div class="small mt-1">per hour (TCO)</div>
                <hr class="my-2">
                <div class="text-uppercase small fw-semibold mb-1">Annual</div>
                <div class="fw-bold" id="{{ $id('inHouseAnnual') }}">{{ $money('inHouseAnnual') }}</div>
            </div>
        </div>
        @if($showVendor)
            <div class="col-sm-6">
                <div class="p-3 rounded text-center h-100" style="background:#d1e7dd;border:2px solid #198754;color:#0a3622">
                    <div class="text-uppercase small fw-semibold mb-1">Buyer Cost to Protect via Outsourcing</div>
                    <div class="h4 fw-bold mb-0" id="{{ $id('vendorHourly') }}">{{ $money('vendorHourly') }}</div>
                    <div class="small mt-1">per hour (Vendor TCO)</div>
                    <hr class="my-2">
                    <div class="text-uppercase small fw-semibold mb-1">Annual</div>
                    <div class="fw-bold" id="{{ $id('vendorAnnual') }}">{{ $money('vendorAnnual') }}</div>
                </div>
            </div>
        @endif
    </div>
    @if($showRecovery ?? $showVendor)
        <div class="p-3 rounded text-center mb-3" style="background:#fff3cd;border:2px solid #f59f00;color:#664d03">
            <div class="text-uppercase small fw-semibold mb-2">Capital Recovery Opportunity</div>
            <div class="row g-2">
                <div class="col-6"><div class="small">Per hour</div><div class="h5 fw-bold mb-0" id="{{ $id('recoveryPerHour') }}">{{ $money('recoveryPerHour') }}</div></div>
                <div class="col-6"><div class="small">Annual</div><div class="h5 fw-bold mb-0" id="{{ $id('costSavings') }}">{{ $money('costSavings') }}</div></div>
            </div>
            <div class="small mt-2">Saved by outsourcing at Vendor TCO instead of in-house TCO</div>
        </div>
    @endif
    @if($showTable)
        <div class="table-responsive rounded mb-2" style="background:var(--gasq-muted-bg)">
            <table class="table table-sm align-middle mb-0">
                <thead><tr><th scope="col">Metric</th><th scope="col" class="text-end">In-House</th>@if($showVendor)<th scope="col" class="text-end">Vendor</th>@endif</tr></thead>
                <tbody>
                    @foreach([
                        ['baselineHourly', 'Workforce Baseline Assumption Labor Rate', true, true],
                        ['Hourly', 'Hourly Bill / Cost to Protect Rate', true, false],
                        ['WeeklyHours', 'Total Weekly Hours', false, true],
                        ['MonthlyHours', 'Total Monthly Hours', false, true],
                        ['AnnualHours', 'Total Annual Hours', false, true],
                        ['StaffRequired', 'Total Staff Required', false, true],
                        ['Weekly', 'Total Weekly Cost', true, false],
                        ['Monthly', 'Total Monthly Cost', true, false],
                        ['Annual', 'Total Annual Cost', true, false],
                    ] as [$key, $label, $isMoney, $shared])
                        @php
                            $sharedKey = $key === 'baselineHourly' ? $key : 'total'.$key;
                            $internal = $summary[$shared ? $sharedKey : 'inHouse'.$key] ?? 0;
                            $vendor = $shared ? $internal : ($summary['vendor'.$key] ?? 0);
                        @endphp
                        <tr @class(['fw-semibold table-light' => $key === 'Annual'])>
                            <th scope="row" class="small fw-normal">{{ $label }}</th>
                            <td class="text-end font-monospace small" id="{{ $id('table_ih'.$key) }}">{{ $isMoney ? \App\Support\Currency::format($internal) : $number($internal) }}</td>
                            @if($showVendor)<td class="text-end font-monospace small" id="{{ $id('table_v'.$key) }}">{{ $isMoney ? \App\Support\Currency::format($vendor) : $number($vendor) }}</td>@endif
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        <div class="form-text mb-3">Staff required is rounded up using 28 hours per staff member per week.</div>
    @endif
    @if($showMetrics && $showVendor)
        <div class="row g-2">
            <div class="col-sm-4"><div class="rounded p-3 text-center h-100" style="background:var(--gasq-muted-bg)"><div class="small text-gasq-muted mb-1">Operational Capital Recovered (%)</div><div class="fw-bold" id="{{ $id('roiPct') }}">{{ $number($summary['roiPct'] ?? 0) }}%</div></div></div>
            <div class="col-sm-4"><div class="rounded p-3 text-center h-100" style="background:var(--gasq-muted-bg)"><div class="small text-gasq-muted mb-1">Payback &amp; Recovery Period</div><div class="fw-bold" id="{{ $id('paybackMonths') }}">{{ number_format($summary['paybackMonths'] ?? 0, 1) }} months</div></div></div>
            <div class="col-sm-4"><div class="rounded p-3 text-center h-100" style="background:var(--gasq-muted-bg)"><div class="small text-gasq-muted mb-1">Dollar for Dollar Return</div><div class="fw-bold" id="{{ $id('dollarForDollarReturn') }}">{{ \App\Support\Currency::symbol() }}{{ number_format($summary['dollarForDollarReturn'] ?? 0, 2) }}</div></div></div>
        </div>
    @endif
@endif

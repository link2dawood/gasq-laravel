{{--
    Side-by-side cost comparison, on screen. The same figures as the PDF, from
    the same service, so the page and the document can never disagree.

    Vars: $r — App\Services\PrivateEstimate\EstimateComparisonReport::build()
--}}
@php
    use App\Support\Currency;

    $c = $r['coverage'];
    $in = $r['internal'];
    $ve = $r['vendor'];
    $rec = $r['recovery'];
    $budget = $r['budget'];
    $agreement = $r['agreement'];
    $scope = $r['scope'];
    $estimate = $r['estimate'];

    $money = fn ($v) => Currency::format($v, 2);
    $moneyK = fn ($v) => Currency::format($v, 0);
    $num = fn ($v) => number_format((float) $v);
    $numDec = fn ($v, $dp = 1) => number_format((float) $v, $dp);

    $serviceLabel = \App\Support\PrivateEstimate::serviceTypes()[$scope->service_type]
        ?? ($scope->service_type ? ucwords(str_replace('_', ' ', $scope->service_type)) : 'Security services');

    $rows = [
        ['Cost to protect hourly rate', $money($in['hourly']), $money($ve['hourly'])],
        ['Overtime / holiday rate', $money($in['otHourly']), $money($ve['otHourly'])],
        ['Annual cost per security professional', $money($in['annualPerFte']), $money($ve['annualPerFte'])],
        ['Total annual hours of coverage', $num($c['annualHours']), $num($c['annualHours'])],
        ['Workforce required for coverage', $num($c['ftesRequired']), $num($c['ftesRequired'])],
        ['Total weekly cost', $money($in['weekly']), $money($ve['weekly'])],
        ['Total monthly cost', $money($in['monthly']), $money($ve['monthly'])],
    ];
@endphp

<style>
    .cmp { --ink:#0b2447; --muted:#6b7280; --line:rgba(15,23,42,.1); }
    .cmp-head { display:flex; flex-wrap:wrap; gap:12px 24px; align-items:baseline; justify-content:space-between; margin-bottom:14px; }
    .cmp-head h2 { margin:0; font-size:1.25rem; color:var(--ink); }
    .cmp-badge { font-size:.7rem; font-weight:700; letter-spacing:.08em; text-transform:uppercase; padding:.3rem .6rem; border-radius:999px; }
    .cmp-badge.agreed { background:rgba(22,121,74,.12); color:#15794a; }
    .cmp-badge.provisional { background:rgba(239,108,31,.14); color:#8a4b10; }
    .cmp-facts { display:flex; flex-wrap:wrap; gap:10px 18px; font-size:.78rem; color:var(--muted); margin-bottom:18px; }
    .cmp-facts b { color:var(--ink); font-weight:600; }
    .cmp-tiles { display:grid; grid-template-columns:repeat(auto-fit,minmax(150px,1fr)); gap:10px; margin-bottom:18px; }
    .cmp-tile { border:1px solid var(--line); border-radius:12px; padding:12px 14px; background:#fff; }
    .cmp-tile .k { font-size:.66rem; text-transform:uppercase; letter-spacing:.09em; color:var(--muted); }
    .cmp-tile .v { font-size:1.35rem; font-weight:700; color:var(--ink); margin-top:4px; line-height:1.1; }
    .cmp-tile .s { font-size:.72rem; color:var(--muted); margin-top:3px; }
    .cmp-tile.good .v { color:#15794a; }
    .cmp-tile.over .v { color:#b3400f; }
    table.cmp-table { width:100%; border-collapse:collapse; font-size:.86rem; background:#fff; }
    table.cmp-table th, table.cmp-table td { padding:9px 10px; border-bottom:1px solid var(--line); text-align:left; }
    table.cmp-table th { font-size:.68rem; text-transform:uppercase; letter-spacing:.07em; color:var(--muted); background:#f4f7fb; }
    table.cmp-table td.n, table.cmp-table th.n { text-align:right; font-variant-numeric:tabular-nums; white-space:nowrap; }
    table.cmp-table tr.total td { font-weight:700; color:var(--ink); background:#f4f7fb; }
    table.cmp-table tr.rec td { background:rgba(22,121,74,.07); font-weight:600; }
    table.cmp-table tr.rec.over td { background:rgba(179,64,15,.07); }
    .cmp-budget { margin-top:18px; border:1px solid var(--line); border-left:4px solid #15794a; border-radius:10px; padding:12px 14px; background:#f6fbf7; font-size:.85rem; }
    .cmp-budget.over { border-left-color:#b3400f; background:#fdf4f0; }
    .cmp-note { font-size:.76rem; color:var(--muted); margin-top:14px; }
</style>

<div class="cmp">
    <div class="cmp-head">
        <h2>Your cost comparison</h2>
        <span class="cmp-badge {{ $agreement['agreed'] ? 'agreed' : 'provisional' }}">
            {{ $agreement['agreed'] ? 'Agreed rate · scope v'.$agreement['scopeVersion'] : 'Provisional · scope v'.$agreement['scopeVersion'] }}
        </span>
    </div>

    <div class="cmp-facts">
        <span>Estimate <b>{{ $estimate->public_id }}</b></span>
        <span>Service <b>{{ $serviceLabel }}</b></span>
        <span>Posts <b>{{ $num($c['posts']) }}</b></span>
        @if($c['hoursPerDay'] > 0)<span>Coverage <b>{{ $numDec($c['hoursPerDay'], 1) }} hrs × {{ $numDec($c['daysPerWeek'], 1) }} days</b></span>@endif
        <span>Annual hours <b>{{ $num($c['annualHours']) }}</b></span>
        @if($agreement['validUntil'])<span>Valid until <b>{{ $agreement['validUntil']->format('M j, Y') }}</b></span>@endif
    </div>

    <div class="cmp-tiles">
        <div class="cmp-tile">
            <div class="k">Your in-house annual cost</div>
            <div class="v">{{ $moneyK($in['annual']) }}</div>
            <div class="s">{{ $in['source'] === 'stated' ? 'As you stated it' : 'GASQ modelled' }}</div>
        </div>
        <div class="cmp-tile">
            <div class="k">Agreed vendor annual cost</div>
            <div class="v">{{ $moneyK($ve['annual']) }}</div>
            <div class="s">{{ $money($ve['hourly']) }} per hour</div>
        </div>
        <div class="cmp-tile {{ $rec['positive'] ? 'good' : 'over' }}">
            <div class="k">{{ $rec['positive'] ? 'Operational capital recovered' : 'Additional annual cost' }}</div>
            <div class="v">{{ $moneyK(abs($rec['annual'])) }}</div>
            <div class="s">{{ $numDec(abs($rec['pct']), 1) }}% vs in-house</div>
        </div>
        <div class="cmp-tile">
            <div class="k">Workforce required</div>
            <div class="v">{{ $num($c['ftesRequired']) }}</div>
            <div class="s">FTEs to deliver scope</div>
        </div>
    </div>

    <table class="cmp-table">
        <thead>
            <tr>
                <th>Description</th>
                <th class="n">{{ $in['label'] }}</th>
                <th class="n">{{ $ve['label'] }}</th>
            </tr>
        </thead>
        <tbody>
            @foreach($rows as [$label, $internal, $vendor])
                <tr>
                    <td>{{ $label }}</td>
                    <td class="n">{{ $internal }}</td>
                    <td class="n">{{ $vendor }}</td>
                </tr>
            @endforeach
            <tr class="total">
                <td>Total annual cost</td>
                <td class="n">{{ $money($in['annual']) }}</td>
                <td class="n">{{ $money($ve['annual']) }}</td>
            </tr>
            <tr class="rec {{ $rec['positive'] ? '' : 'over' }}">
                <td>{{ $rec['positive'] ? 'Operational capital recovered' : 'Additional cost vs in-house' }}</td>
                <td class="n">&mdash;</td>
                <td class="n">{{ $money(abs($rec['annual'])) }} ({{ $numDec(abs($rec['pct']), 1) }}%)</td>
            </tr>
            <tr class="rec {{ $rec['positive'] ? '' : 'over' }}">
                <td>Payback and recovery period</td>
                <td class="n">&mdash;</td>
                <td class="n">{{ $rec['positive'] ? $numDec($rec['paybackMonths'], 1).' months' : 'No recovery at this price' }}</td>
            </tr>
        </tbody>
    </table>

    <div class="cmp-budget {{ $budget['reconciles'] ? '' : 'over' }}">
        @if($budget['known'])
            <strong>{{ $budget['statusLabel'] }}:</strong>
            {{ $moneyK($budget['amount']) }} {{ $budget['basisLabel'] }} ({{ $moneyK($budget['annual']) }} per year).
            Agreed annual cost {{ $moneyK($ve['annual']) }}.
            <strong>{{ $budget['withinBudget'] ? 'Within budget by' : 'Over budget by' }} {{ $moneyK(abs((float) $budget['variance'])) }}.</strong>
        @else
            {{ $budget['statement'] }}
        @endif
    </div>

    <p class="cmp-note">
        {{ $agreement['statement'] }}
        {{ $ve['basis'] }}
        {{ $in['basis'] }}
    </p>
</div>

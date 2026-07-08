@extends('layouts.app')

@section('title', 'Admin Dashboard')

@php
    $money = fn ($v) => '$' . number_format((float) $v, 0);
    $num = fn ($v) => number_format((float) $v, 0);
    $pct = fn ($v) => number_format((float) $v, 1) . '%';
@endphp

@section('content')
<div class="container py-4">

    <div class="d-flex flex-wrap justify-content-between align-items-end gap-2 mb-1">
        <h1 class="h3 mb-0">Performance Dashboard</h1>
        <div class="text-gasq-muted small">Live from real platform data · {{ $now->format('M j, Y g:i A') }}</div>
    </div>
    <p class="text-gasq-muted small mb-4">Internal view. Contract values use the amount frozen at award; older awards fall back to the job budget estimate.</p>

    {{-- Executive summary --}}
    <div class="row g-3 mb-4">
        @php
            $tiles = [
                ['label' => 'Contract Value Won (Lifetime)', 'value' => $money($executive['value_won_total']), 'sub' => $num($executive['contracts_won_total']) . ' contracts awarded'],
                ['label' => 'Contract Value Won (This Year)', 'value' => $money($executive['value_won_year']), 'sub' => 'Since Jan 1'],
                ['label' => 'Active Pipeline Value', 'value' => $money($executive['pipeline_value']), 'sub' => 'Open, unhired opportunities'],
                ['label' => 'Average Contract Value', 'value' => $money($executive['avg_contract_value']), 'sub' => 'Across all awards'],
                ['label' => 'Contracts Awarded (This Month)', 'value' => $num($executive['contracts_won_month']), 'sub' => $now->format('F')],
                ['label' => 'Contracts Awarded (Lifetime)', 'value' => $num($executive['contracts_won_total']), 'sub' => 'All time'],
            ];
        @endphp
        @foreach($tiles as $t)
            <div class="col-6 col-lg-4">
                <div class="card gasq-card h-100">
                    <div class="card-body">
                        <div class="text-uppercase small fw-semibold text-gasq-muted mb-1">{{ $t['label'] }}</div>
                        <div class="h3 fw-bold mb-1">{{ $t['value'] }}</div>
                        <div class="small text-gasq-muted">{{ $t['sub'] }}</div>
                    </div>
                </div>
            </div>
        @endforeach
    </div>

    {{-- Lead funnel --}}
    <div class="card gasq-card mb-4">
        <div class="card-header"><h2 class="h5 card-title mb-0">Lead Pipeline</h2></div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table mb-0 align-middle">
                    <thead class="table-light">
                        <tr><th>Stage</th><th class="text-end">Count</th><th class="text-end">Value</th></tr>
                    </thead>
                    <tbody>
                        @foreach($funnel as $stage)
                            <tr>
                                <td>{{ $stage['label'] }}</td>
                                <td class="text-end font-monospace">{{ $num($stage['count']) }}</td>
                                <td class="text-end font-monospace">{{ $stage['value'] === null ? '—' : $money($stage['value']) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    {{-- Stat groups --}}
    <div class="row g-3 mb-4">
        @php
            $groups = [
                ['title' => 'Buyer Statistics', 'rows' => [
                    ['Total Buyers', $num($buyers['total'])],
                    ['New Buyers (This Month)', $num($buyers['new_month'])],
                    ['Avg Procurement Cycle', $buyers['avg_cycle_days'] ? $num($buyers['avg_cycle_days']) . ' days' : '—'],
                ]],
                ['title' => 'Vendor Statistics', 'rows' => [
                    ['Total Vendors', $num($vendors['total'])],
                    ['Active Vendors (90d)', $num($vendors['active_90d'])],
                    ['Vendors Selected', $num($vendors['selected'])],
                    ['Acceptance Rate', $pct($vendors['accept_rate'])],
                    ['Decline Rate', $pct($vendors['decline_rate'])],
                ]],
                ['title' => 'Procurement Statistics', 'rows' => [
                    ['Appraisals / Quotes', $num($procurement['appraisals'])],
                    ['Job Offers Sent', $num($procurement['offers_sent'])],
                    ['Offers Accepted', $num($procurement['offers_accepted'])],
                    ['Offers Declined', $num($procurement['offers_declined'])],
                ]],
            ];
        @endphp
        @foreach($groups as $group)
            <div class="col-md-6 col-xl-4">
                <div class="card gasq-card h-100">
                    <div class="card-header"><h2 class="h6 card-title mb-0">{{ $group['title'] }}</h2></div>
                    <ul class="list-group list-group-flush">
                        @foreach($group['rows'] as $row)
                            <li class="list-group-item d-flex justify-content-between">
                                <span class="text-gasq-muted">{{ $row[0] }}</span>
                                <span class="fw-semibold font-monospace">{{ $row[1] }}</span>
                            </li>
                        @endforeach
                    </ul>
                </div>
            </div>
        @endforeach
    </div>

    <div class="row g-3 mb-4">
        {{-- Activity --}}
        <div class="col-md-6 col-xl-4">
            <div class="card gasq-card h-100">
                <div class="card-header"><h2 class="h6 card-title mb-0">Activity</h2></div>
                <ul class="list-group list-group-flush">
                    <li class="list-group-item d-flex justify-content-between">
                        <span class="text-gasq-muted">New Leads Today</span>
                        <span class="fw-semibold font-monospace">{{ $num($activity['new_leads_today']) }}</span>
                    </li>
                    <li class="list-group-item d-flex justify-content-between">
                        <span class="text-gasq-muted">Contracts Ready for Award</span>
                        <span class="fw-semibold font-monospace">{{ $num($activity['ready_for_award']) }}</span>
                    </li>
                </ul>
            </div>
        </div>

        {{-- Revenue (admin) --}}
        <div class="col-md-6 col-xl-4">
            <div class="card gasq-card h-100">
                <div class="card-header"><h2 class="h6 card-title mb-0">Revenue <span class="badge bg-secondary ms-1">Admin</span></h2></div>
                <ul class="list-group list-group-flush">
                    <li class="list-group-item d-flex justify-content-between">
                        <span class="text-gasq-muted">Credits Sold</span>
                        <span class="fw-semibold font-monospace">{{ $num($revenue['credits_sold']) }}</span>
                    </li>
                    <li class="list-group-item d-flex justify-content-between">
                        <span class="text-gasq-muted">Purchases</span>
                        <span class="fw-semibold font-monospace">{{ $num($revenue['purchases']) }}</span>
                    </li>
                    <li class="list-group-item d-flex justify-content-between">
                        <span class="text-gasq-muted">Subscription Payments</span>
                        <span class="fw-semibold font-monospace">{{ $num($revenue['subscription_payments']) }}</span>
                    </li>
                </ul>
                <div class="card-footer small text-gasq-muted">Dollar revenue needs Stripe amounts stored (coming next).</div>
            </div>
        </div>

        {{-- Awards trend --}}
        <div class="col-xl-4">
            <div class="card gasq-card h-100">
                <div class="card-header"><h2 class="h6 card-title mb-0">Awards — Last 6 Months</h2></div>
                <div class="card-body">
                    @php $maxC = max(1, (int) ($awardsTrend->max('c') ?? 1)); @endphp
                    @forelse($awardsTrend as $row)
                        @php $label = \Illuminate\Support\Carbon::createFromFormat('Y-m-d', $row->ym . '-01')->format('M Y'); @endphp
                        <div class="mb-2">
                            <div class="d-flex justify-content-between small mb-1">
                                <span class="text-gasq-muted">{{ $label }}</span>
                                <span class="fw-semibold">{{ $num($row->c) }} · {{ $money($row->v) }}</span>
                            </div>
                            <div class="progress" style="height:8px;">
                                <div class="progress-bar bg-primary" style="width: {{ round($row->c / $maxC * 100) }}%"></div>
                            </div>
                        </div>
                    @empty
                        <div class="text-gasq-muted small">No awards recorded yet.</div>
                    @endforelse
                </div>
            </div>
        </div>
    </div>

</div>
@endsection

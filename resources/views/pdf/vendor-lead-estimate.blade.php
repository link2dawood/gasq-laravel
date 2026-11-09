@extends('pdf.layouts.gasq-report', [
    'title' => $estimate->title,
    'subtitle' => 'Proposed scope and service pricing',
    'reportNumber' => $estimate->number,
    'reportType' => 'Vendor — Service Estimate',
    'contactName' => $estimate->vendor->name,
    'contactCompany' => $estimate->vendor->company,
    'contactEmail' => $estimate->vendor->email,
    'contactPhone' => $estimate->vendor->phone,
])
@section('content')
<table class="gasq-kv gasq-mt" width="100%"><tr class="hl"><td colspan="2">Prepared for</td></tr>
<tr><td>Contact / company</td><td class="v">{{ $estimate->buyer['contact_name'] }}{{ !empty($estimate->buyer['company']) ? ' · '.$estimate->buyer['company'] : '' }}</td></tr>
<tr><td>Email / phone</td><td class="v">{{ $estimate->buyer['email'] }} · {{ $estimate->buyer['phone'] }}</td></tr>
@foreach(['site_address' => 'Service location', 'service_type' => 'Service type'] as $key => $label)
<tr><td>{{ $label }}</td><td class="v">{{ $estimate->buyer[$key] ?? '—' }}</td></tr>
@endforeach
<tr><td>Valid through</td><td class="v">{{ $estimate->valid_until->format('F j, Y') }}</td></tr>
</table>
<h2 style="font-size:14px;margin-top:18px;">Proposed scope of services</h2>
<div style="font-size:10.5px;line-height:1.6;overflow-wrap:break-word;">{!! nl2br(e($estimate->scope)) !!}</div>
<table class="gasq-kv gasq-mt" width="100%">
<thead><tr class="hl"><td colspan="2">Estimate pricing · {{ $estimate->currency }}</td></tr></thead>
<tbody>
<tr><td>Hourly bill rate</td><td class="v">{{ $estimate->money($estimate->hourly_rate) }}</td></tr>
<tr class="alt"><td>Weekly service hours</td><td class="v">{{ number_format($estimate->weekly_hours, 2) }}</td></tr>
<tr><td>Service weeks per year</td><td class="v">{{ $estimate->weeks_per_year }}</td></tr>
<tr class="alt"><td>Annual service hours</td><td class="v">{{ number_format($estimate->totals()['annual_hours'], 2) }}</td></tr>
@foreach(['weekly' => 'Weekly recurring cost', 'monthly' => 'Average monthly recurring cost', 'annual' => 'Annual recurring cost'] as $key => $label)
<tr><td>{{ $label }}</td><td class="v">{{ $estimate->money($estimate->totals()[$key]) }}</td></tr>
@endforeach
<tr><td>One-time cost</td><td class="v">{{ $estimate->money($estimate->one_time_cost) }}</td></tr>
<tr class="total"><td>First-year estimated total</td><td class="v">{{ $estimate->money($estimate->totals()['first_year']) }}</td></tr>
</tbody>
</table>
<p class="gasq-note">Weekly cost = hourly bill rate × weekly service hours, rounded to two decimals. Annual cost = weekly cost × service weeks. Average monthly cost = annual cost ÷ 12. One-time costs are included once in the first-year total.</p>
@if($estimate->terms)
<h2 style="font-size:14px;margin-top:18px;">Terms and assumptions</h2><div style="font-size:10.5px;line-height:1.6;overflow-wrap:break-word;">{!! nl2br(e($estimate->terms)) !!}</div>
@endif
<p class="gasq-note">This estimate is based on the proposed scope and vendor-entered pricing. Final pricing remains subject to confirmed scope, coverage requirements, site conditions, and agreement between the parties.</p>
@endsection
@section('report_styles')
<style>
    .gasq-kv td { padding:4px 12px; font-size:10px; }
    .gasq-kv tr { page-break-inside:avoid; }
    .gasq-kv thead { display:table-header-group; }
    .gasq-mt { margin-top:12px; }
    h2 { page-break-after:avoid; }
</style>
@endsection

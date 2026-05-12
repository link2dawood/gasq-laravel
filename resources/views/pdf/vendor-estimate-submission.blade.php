@php
    $snap = is_array($submission->snapshot) ? $submission->snapshot : [];
    $vendor = $submission->vendor;
    $job = $submission->jobPosting;
    $buyer = $submission->buyer;
    $rows = $snap['rows'] ?? [];
    $totals = $snap['totals'] ?? [];
    $serviceLabel = $snap['service_label'] ?? ($job?->category ?? 'Security services');
    $location = $snap['location'] ?? ($job?->location ?? '');

    // Surface a headline number for the cover (best-effort).
    $headlineLabel = null;
    $headlineValue = null;
    foreach ($totals as $row) {
        if (in_array(strtolower($row['label'] ?? ''), ['total', 'annual total', 'total annual cost', 'grand total'], true)) {
            $headlineLabel = $row['label'];
            $headlineValue = $row['value'];
            break;
        }
    }
    if ($headlineValue === null && ! empty($totals)) {
        $headlineLabel = $totals[count($totals) - 1]['label'] ?? null;
        $headlineValue = $totals[count($totals) - 1]['value'] ?? null;
    }
@endphp

@extends('pdf.layouts.gasq-base', [
    'title' => 'Vendor Estimate',
    'subtitle' => $serviceLabel . ($location ? ' · ' . $location : ''),
    'preparedFor' => ($buyer?->name ?? '') . ($buyer?->company ? ' — ' . $buyer->company : ''),
    'preparedBy' => ($vendor?->name ?? '') . ($vendor?->company ? ' — ' . $vendor->company : ''),
    'reportDate' => $submission->created_at?->format('F j, Y'),
    'referenceNumber' => 'VE-' . str_pad((string) $submission->id, 6, '0', STR_PAD_LEFT),
])

@section('cover_summary')

@if($headlineValue !== null)
    <div class="exec-grid">
        <div class="exec-cell" style="width:100%;">
            <div class="label">{{ $headlineLabel ?? 'Estimate Total' }}</div>
            <div class="value">{{ $headlineValue }}</div>
        </div>
    </div>
@endif

<p style="margin-top:18px;">
    <strong>{{ $vendor?->name ?? 'The vendor' }}</strong>@if($vendor?->company) ({{ $vendor->company }})@endif has prepared the following estimate for <strong>"{{ $job?->title ?? 'your project' }}"</strong>.
</p>

@if(!empty($totals))
<h3 style="margin-top:22px;">Summary totals</h3>
<table class="kv-table">
    @foreach($totals as $row)
        <tr>
            <td class="label">{{ $row['label'] ?? '' }}</td>
            <td><strong>{{ $row['value'] ?? '' }}</strong></td>
        </tr>
    @endforeach
</table>
@endif

<div class="gasq-protection-block">
    <h3>GASQ Protections</h3>
    <p class="small" style="margin:0;">
        All estimates routed through GASQ are backed by the <strong>Price Lock Guarantee</strong> and <strong>Vendor Replacement Guarantee</strong>. Pricing here reflects the vendor's good-faith proposal at the time of submission.
    </p>
</div>

@endsection

@section('content')

<h2>Cost breakdown</h2>
@if(!empty($rows))
    <table>
        <thead>
            <tr><th>Line item</th><th class="right">Amount</th></tr>
        </thead>
        <tbody>
            @foreach($rows as $row)
                <tr>
                    <td>{{ $row['label'] ?? '' }}</td>
                    <td class="right mono">{{ $row['value'] ?? '' }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
@else
    <p class="muted">No line items captured.</p>
@endif

@if(!empty($totals))
<h2>Totals</h2>
<table>
    @foreach($totals as $row)
        <tr class="emphasis">
            <td>{{ $row['label'] ?? '' }}</td>
            <td class="right mono"><strong>{{ $row['value'] ?? '' }}</strong></td>
        </tr>
    @endforeach
</table>
@endif

@if(!empty($snap['notes']))
<h2>Vendor notes</h2>
<p>{{ $snap['notes'] }}</p>
@endif

@endsection

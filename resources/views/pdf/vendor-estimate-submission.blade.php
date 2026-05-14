@php
    $snap = is_array($submission->snapshot) ? $submission->snapshot : [];
    $vendor = $submission->vendor;
    $job = $submission->jobPosting;
    $buyer = $submission->buyer;
    $rows = $snap['rows'] ?? [];
    $totals = $snap['totals'] ?? [];
    $serviceLabel = $snap['service_label'] ?? ($job?->category ?? 'Security services');
    $location = $snap['location'] ?? ($job?->location ?? '');

    // Headline total
    $headlineLabel = null;
    $headlineValue = null;
    foreach ($totals as $row) {
        if (in_array(strtolower($row['label'] ?? ''), ['total', 'annual total', 'total annual cost', 'grand total'], true)) {
            $headlineLabel = $row['label']; $headlineValue = $row['value']; break;
        }
    }
    if ($headlineValue === null && ! empty($totals)) {
        $last = end($totals);
        $headlineLabel = $last['label'] ?? null;
        $headlineValue = $last['value'] ?? null;
    }

    $reportNumber = 'GASQ ' . now()->format('Y-m-d') . '-VE' . str_pad((string) $submission->id, 4, '0', STR_PAD_LEFT);
@endphp

@extends('pdf.layouts.gasq-report', [
    'title' => 'GASQ Vendor Estimate',
    'subtitle' => $serviceLabel . ($location ? ' · ' . $location : ''),
    'reportNumber' => $reportNumber,
    'reportType' => 'Buyer-Facing Estimate',
    'contactName' => $vendor?->name,
    'contactCompany' => $vendor?->company ?? $vendor?->vendorProfile?->company_name,
    'contactEmail' => $vendor?->email,
    'contactPhone' => $vendor?->phone,
])

@section('stat_grid')
@if($headlineValue !== null)
<table width="100%" cellpadding="0" cellspacing="0">
  <tr>
    <td class="stat-grid-label last"><p>{{ $headlineLabel ?? 'Estimate Total' }}</p></td>
  </tr>
  <tr>
    <td class="stat-grid-value bg-blue last">
      <p class="num">{{ $headlineValue }}</p>
      <p class="sub">submitted {{ $submission->created_at?->format('M j, Y') }}</p>
    </td>
  </tr>
</table>
@endif
@endsection

@section('content')

<table width="100%" cellpadding="0" cellspacing="0" class="gasq-mt">
  <tr><td class="gasq-section-band"><p>Engagement</p></td></tr>
</table>
<table width="100%" cellpadding="0" cellspacing="0" class="gasq-kv">
  <tr><td>Prepared for</td><td class="v">{{ $buyer?->name ?? '—' }}{{ $buyer?->company ? ' · ' . $buyer->company : '' }}</td></tr>
  <tr class="alt"><td>Job</td><td class="v">{{ $job?->title ?? '—' }}</td></tr>
  @if($location)<tr><td>Location</td><td class="v">{{ $location }}</td></tr>@endif
  <tr class="alt"><td>Service</td><td class="v">{{ $serviceLabel }}</td></tr>
</table>

@if(!empty($totals))
<table width="100%" cellpadding="0" cellspacing="0" class="gasq-mt">
  <tr><td class="gasq-section-band"><p>Summary Totals</p></td></tr>
</table>
<table width="100%" cellpadding="0" cellspacing="0" class="gasq-kv">
  @foreach($totals as $i => $row)
    <tr class="{{ $i % 2 === 1 ? 'alt' : '' }}">
      <td>{{ $row['label'] ?? '' }}</td>
      <td class="v">{{ $row['value'] ?? '' }}</td>
    </tr>
  @endforeach
</table>
@endif

@if(!empty($rows))
<table width="100%" cellpadding="0" cellspacing="0" class="gasq-mt">
  <tr><td class="gasq-section-band"><p>Cost Breakdown</p></td></tr>
</table>
<table width="100%" cellpadding="0" cellspacing="0" class="gasq-kv">
  @foreach($rows as $i => $row)
    <tr class="{{ $i % 2 === 1 ? 'alt' : '' }}">
      <td>{{ $row['label'] ?? '' }}</td>
      <td class="v">{{ $row['value'] ?? '' }}</td>
    </tr>
  @endforeach
</table>
@endif

@if(!empty($snap['notes']))
<table width="100%" cellpadding="0" cellspacing="0" class="gasq-mt">
  <tr><td class="gasq-section-band"><p>Vendor Notes</p></td></tr>
</table>
<div style="border:1px solid #d8dff0; border-top:none; padding:11px 16px; font-size:10.5px; color:#374151; line-height:1.5;">
  {{ $snap['notes'] }}
</div>
@endif

<p class="gasq-note">
    All estimates routed through GASQ are backed by the <strong>Price Lock Guarantee</strong> and <strong>Vendor Replacement Guarantee</strong>. Pricing here reflects the vendor's good-faith proposal at the time of submission.
</p>

@endsection

@php
    $res = $result ?? [];
    $reportNumber = 'GASQ ' . now()->format('Y-m-d') . '-MM' . str_pad((string) (rand(1000, 9999)), 4, '0', STR_PAD_LEFT);
@endphp

@extends('pdf.layouts.gasq-report', [
    'title' => 'GASQ Main Menu Calculator Report',
    'subtitle' => 'Workforce + Cost Snapshot',
    'reportNumber' => $reportNumber,
    'reportType' => 'Vendor — Main Menu Report',
    'contactName' => $user?->name ?? null,
    'contactCompany' => $user?->company ?? null,
    'contactEmail' => $user?->email ?? null,
    'contactPhone' => $user?->phone ?? null,
])

@section('content')

<table width="100%" cellpadding="0" cellspacing="0" class="gasq-mt">
  <tr><td class="gasq-section-band"><p>Result Summary</p></td></tr>
</table>
<table width="100%" cellpadding="0" cellspacing="0" class="gasq-kv">
  @foreach($res as $key => $val)
    <tr class="{{ $loop->iteration % 2 === 0 ? 'alt' : '' }}">
      <td>{{ str_replace('_', ' ', ucfirst((string) $key)) }}</td>
      <td class="v">
        @if(is_array($val))
          {{ implode(', ', array_map(fn($v) => (string) $v, $val)) }}
        @elseif(is_numeric($val) && str_contains((string) $val, '.'))
          {{ number_format((float) $val, 2) }}
        @else
          {{ $val }}
        @endif
      </td>
    </tr>
  @endforeach
</table>

@endsection

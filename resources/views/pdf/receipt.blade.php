@php
    $reportNumber = 'GASQ ' . now()->format('Y-m-d') . '-RCP' . str_pad((string) $transaction->id, 4, '0', STR_PAD_LEFT);
@endphp

@extends('pdf.layouts.gasq-report', [
    'title' => 'GASQ Credit Purchase Receipt',
    'subtitle' => 'Transaction Confirmation',
    'reportNumber' => $reportNumber,
    'reportType' => 'Customer Receipt',
    'contactName' => $user?->name,
    'contactCompany' => $user?->company,
    'contactEmail' => $user?->email,
    'contactPhone' => $user?->phone,
])

@section('stat_grid')
<table width="100%" cellpadding="0" cellspacing="0">
  <tr>
    <td width="50%" class="stat-grid-label"><p>Credits Added</p></td>
    <td width="50%" class="stat-grid-label last"><p>Balance After Transaction</p></td>
  </tr>
  <tr>
    <td class="stat-grid-value bg-green">
      <p class="num">+{{ number_format($transaction->tokens_change) }}</p>
      <p class="sub">credits</p>
    </td>
    <td class="stat-grid-value bg-blue last">
      <p class="num">{{ number_format($transaction->balance_after ?? 0) }}</p>
      <p class="sub">total credits on account</p>
    </td>
  </tr>
</table>
@endsection

@section('content')

<table width="100%" cellpadding="0" cellspacing="0" class="gasq-mt">
  <tr><td class="gasq-section-band"><p>Transaction Details</p></td></tr>
</table>
<table width="100%" cellpadding="0" cellspacing="0" class="gasq-kv">
  <tr><td>Transaction Date</td><td class="v">{{ $transaction->created_at->format('M j, Y g:i A') }}</td></tr>
  <tr class="alt"><td>Description</td><td class="v" style="font-weight:normal; color:#374151;">{{ $transaction->description ?: 'Credit purchase' }}</td></tr>
  <tr><td>Credits Added</td><td class="v">+{{ number_format($transaction->tokens_change) }}</td></tr>
  <tr class="alt"><td>Balance After Transaction</td><td class="v">{{ number_format($transaction->balance_after ?? 0) }} credits</td></tr>
</table>

<p class="gasq-note">Thank you for using GASQ. This document serves as your official receipt for records and reimbursement purposes.</p>

@endsection

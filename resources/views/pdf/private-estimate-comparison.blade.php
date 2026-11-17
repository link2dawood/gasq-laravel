{{--
    GASQ Private Estimate — SIDE-BY-SIDE COMPARISON REPORT.

    The buyer-facing deliverable of the Private Estimate by Invitation flow. Two
    pages, because the buyer only needs two:
      1. Comparison  — scope priced, internal vs agreed vendor cost, capital
                       recovered, and the reconciliation against their budget
      2. Methodology — the build-up behind the agreed rate, certification, IP
                       and disclaimer

    Every figure comes from App\Services\PrivateEstimate\EstimateComparisonReport.
    The vendor column is the rate the platform calculated and both sides agreed;
    it is never the internal column multiplied by a discount factor.

    Vars: $r (the report array), optional $reportNumber / $datePrepared.
--}}
@php
    use App\Support\Currency;

    $estimate = $r['estimate'];
    $parties = $r['parties'];

    $pages = 2;
    $reportNumber = $reportNumber ?? $estimate->public_id;
    $reportDate = $datePrepared ?? now()->format('F j, Y');
    $reportType = $r['agreement']['agreed'] ? 'Agreed Rate — Buyer Comparison' : 'Provisional Rate — Buyer Comparison';
    $orgName = $parties['vendorName'];
    $docTitle = 'GASQ Private Estimate '.$estimate->public_id.' — Cost Comparison';
    $masked = false;

    $money   = fn ($v) => Currency::format($v, 2);
    $moneyK  = fn ($v) => Currency::format($v, 0);
    $num     = fn ($v) => number_format((float) $v);
    $numDec  = fn ($v, $dp = 1) => number_format((float) $v, $dp);
@endphp

@extends('pdf.estimate.layout')

@section('pages')
    @include('pdf.private-estimate._page1')
    @include('pdf.private-estimate._page2')
@endsection

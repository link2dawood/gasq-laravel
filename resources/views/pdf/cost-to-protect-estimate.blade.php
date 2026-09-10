{{--
    GASQ Cost to Protect™ — MASTER ESTIMATE DASHBOARD.

    The paid, buyer-facing estimate a vendor sends to a customer. Four pages:
      1. Estimate Dashboard        — headline KPIs, coverage assumptions, charts
      2. Detailed Cost Appraisal   — full internal vs vendor line comparison
      3. Executive Summary         — findings, what the estimate includes, payback
      4. Certification / IP        — GASQ statements, IP notice, disclaimer

    Every figure comes from App\Services\CostToProtectEstimate so this document and
    the Workforce-to-Post allocation report can never disagree.
--}}
@php
    use App\Services\CostToProtectEstimate;
    use App\Support\Currency;

    $d = app(CostToProtectEstimate::class)->build((array) ($scenario ?? []), $user ?? null);

    // Locked preview: every figure keeps its shape but loses its digits
    // ($538,769 → $•••,•••), so the buyer sees the full analysis and none of the
    // numbers. A PDF cannot reveal content on a password — encryption is
    // all-or-nothing — so the unlocked figures live in a separate document.
    $masked = (bool) ($masked ?? false);

    // ReportService passes the calculator slug as $reportType; the document shows
    // the human label instead.
    $reportType = $masked ? 'Locked Preview — Figures Withheld' : 'Vendor — Full Report';
    $pages = 4;
    $reportNumber = $reportNumber ?? ('GASQ-' . now()->format('Ymd-His') . '-V' . (int) ($vendorId ?? 0));
    $reportDate = now()->format('F j, Y');
    $orgName = $d['contact']['company'] ?: 'GASQ Security';
    $docTitle = 'GASQ Cost to Protect Estimate Dashboard' . ($masked ? ' (Locked Preview)' : '');

    $veil = fn (string $formatted) => $masked
        ? preg_replace('/\d/', '•', $formatted)
        : $formatted;

    $money   = fn ($v) => $veil(Currency::format($v, 2));
    $moneyK  = fn ($v) => $veil(Currency::format($v, 0));
    $num     = fn ($v) => $veil(number_format((float) $v));
    $numDec  = fn ($v, $dp = 1) => $veil(number_format((float) $v, $dp));

    /**
     * Chart axis that lands on human numbers: step snapped to 1/2/2.5/5/10 × the
     * magnitude, so $538,769 over 7 divisions reads $0–$700,000 in $100k steps.
     *
     * @return array{max: float, step: float, labels: list<float>}
     */
    $niceAxis = function (float $max, int $divisions = 7): array {
        $max = max(1.0, $max);
        $raw = $max / $divisions;
        $magnitude = 10 ** floor(log10($raw));
        $ratio = $raw / $magnitude;
        $snap = collect([1, 2, 2.5, 5, 10])->first(fn ($s) => $ratio <= $s) ?? 10;
        $step = $snap * $magnitude;

        return [
            'max' => $step * $divisions,
            'step' => $step,
            'labels' => array_map(fn ($i) => $step * $i, range(0, $divisions)),
        ];
    };
@endphp

@extends('pdf.estimate.layout')

@section('pages')
    @include('pdf.estimate._page1')
    @include('pdf.estimate._page2')
    @include('pdf.estimate._page3')
    @include('pdf.estimate._page4')
@endsection

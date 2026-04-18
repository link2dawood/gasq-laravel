@extends('layouts.app')

@php
  $initialTab = $initialTab ?? 'cfo';
  $routeName = request()->route()?->getName();
  $pageMetaByRoute = [
    'workforce-appraisal-report.index' => [
      'icon' => 'fa-briefcase',
      'title' => 'Workforce Appraisal Report',
      'subtitle' => 'Review the full workforce capital recovery workspace with the same structured summary, input rhythm, and clean right-side analysis pattern used in the Budget calculator.',
    ],
    'post-position-summary.index' => [
      'icon' => 'fa-users',
      'title' => 'Post Position Summary',
      'subtitle' => 'Review scope-driven post rows, weekly and monthly billable hours, and annual labor totals in the same structured workspace style as the Budget calculator.',
    ],
    'appraisal-comparison-summary.index' => [
      'icon' => 'fa-balance-scale',
      'title' => 'Appraisal Comparison Summary',
      'subtitle' => 'Compare internal should-cost against vendor TCO with the same clean summary hierarchy and workspace rhythm used across the Budget calculator.',
    ],
    'price-realism-review.index' => [
      'icon' => 'fa-chart-line',
      'title' => 'Price Realism Review',
      'subtitle' => 'Inspect benchmark rates, module feeds, and realism checks in a calmer summary-driven layout aligned with the Budget calculator UI.',
    ],
    'cfo-bill-rate-breakdown.index' => [
      'icon' => 'fa-file-invoice',
      'title' => 'CFO Bill Rate Breakdown',
      'subtitle' => 'Analyze the full workforce capital recovery stack with the same structured inputs, summary cards, and right-side workspace pattern used in the Budget calculator.',
    ],
  ];
  $pageMeta = $pageMetaByRoute[$routeName] ?? [
    'icon' => 'fa-file-invoice',
    'title' => 'CFO Bill Rate Breakdown',
    'subtitle' => 'Analyze the full workforce capital recovery stack with the same structured inputs, summary cards, and right-side workspace pattern used in the Budget calculator.',
  ];
@endphp

@section('header_variant', 'dashboard')

@section('title', $pageMeta['title'])

@push('styles')
<style>
  .gasq-wa-shell {
    background:
      radial-gradient(circle at top right, rgba(6, 45, 121, 0.08), transparent 28%),
      linear-gradient(180deg, #ffffff 0%, #f8fafc 100%);
  }
  .gasq-wa-sidebar {
    background: linear-gradient(180deg, #fbfcff 0%, #f2f5fb 100%);
  }
  .gasq-wa-sticky {
    position: sticky;
    top: 1.25rem;
  }
  .gasq-wa-kicker {
    font-size: 0.72rem;
    text-transform: uppercase;
    letter-spacing: 0.12em;
    color: var(--gasq-muted);
  }
  .gasq-wa-chip {
    display: inline-flex;
    align-items: center;
    gap: 0.35rem;
    padding: 0.35rem 0.7rem;
    border-radius: 999px;
    background: rgba(6,45,121,0.08);
    color: var(--gasq-primary);
    font-size: 0.78rem;
    font-weight: 600;
  }
  .gasq-wa-section { background: #6b0f1a; color: #fff; font-weight: 600; letter-spacing: 0.02em; }
  .gasq-wa-subbanner { background: #3d4f6b; color: #fff; }
  .gasq-wa-input { background: #fff9c4 !important; }
  .gasq-wa-peach td, .gasq-wa-peach th { background: #ffe4d4 !important; }
  .gasq-wa-table-head { background: #1e3a5f; color: #fff; }
  .gasq-wa-total-row { background: rgba(6,45,121,0.12); font-weight: 600; }
  .gasq-wa-mono { font-variant-numeric: tabular-nums; }
  .gasq-wa-summary-card {
    border: 1px solid rgba(15, 23, 42, 0.08);
    border-radius: 1rem;
    background: #fff;
  }
  .gasq-wa-benchmark-card {
    border: 1px solid rgba(6, 45, 121, 0.12);
    border-radius: 1rem;
    padding: 1rem 1.05rem;
    background:
      radial-gradient(circle at top right, rgba(37, 99, 235, 0.08), transparent 35%),
      linear-gradient(180deg, #ffffff 0%, #f8fbff 100%);
  }
  .gasq-wa-tabs {
    display: flex;
    flex-wrap: wrap;
    gap: 0.75rem;
    padding-left: 0;
    list-style: none;
  }
  .gasq-wa-tabs .nav-item {
    flex: 1 1 170px;
    min-width: 170px;
  }
  .gasq-wa-tabs .nav-link {
    width: 100%;
    height: 100%;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 0.45rem;
    padding: 0.85rem 1rem;
    border-radius: 0.95rem;
    border: 1px solid rgba(6,45,121,0.14);
    background: #fff;
    color: var(--gasq-primary);
    font-weight: 600;
    box-shadow: 0 10px 24px rgba(15, 23, 42, 0.04);
    transition: background-color 0.2s ease, border-color 0.2s ease, color 0.2s ease, box-shadow 0.2s ease;
  }
  .gasq-wa-tabs .nav-link i {
    width: 1rem;
    text-align: center;
    flex: 0 0 auto;
  }
  .gasq-wa-tabs .nav-link span {
    line-height: 1.2;
    text-align: center;
  }
  .gasq-wa-tabs .nav-link:hover,
  .gasq-wa-tabs .nav-link:focus {
    border-color: rgba(6,45,121,0.3);
    color: var(--gasq-primary);
    background: #f8fbff;
  }
  .gasq-wa-tabs .nav-link.active {
    background: var(--gasq-primary);
    border-color: var(--gasq-primary);
    color: #fff;
    box-shadow: 0 14px 28px rgba(6,45,121,0.18);
  }
  @media (max-width: 575.98px) {
    .gasq-wa-tabs {
      flex-direction: column;
    }
    .gasq-wa-tabs .nav-item { min-width: 0; }
  }
  @media (max-width: 1199.98px) {
    .gasq-wa-sticky { position: static; }
  }
  @media print {
    .gasq-wa-no-print { display: none !important; }
  }
</style>
@endpush

@section('content')
<div class="min-vh-100 py-4 px-2 px-md-3" style="background:var(--gasq-background)">
  <div class="container-xl">

    <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-3 gasq-wa-no-print">
      <div class="d-flex align-items-center gap-2">
        <a href="{{ route('main-menu-calculator.index') }}" class="btn btn-outline-secondary btn-sm"><i class="fa fa-arrow-left"></i></a>
        <span class="text-gasq-muted small">V24 compute · <code>workforce-appraisal-report</code></span>
      </div>
    </div>

    <div class="d-flex align-items-center justify-content-between flex-wrap gap-3 mb-4">
      <div>
        <h1 class="h3 fw-bold mb-0 d-flex align-items-center gap-2">
          <i class="fa {{ $pageMeta['icon'] }} text-primary"></i> {{ $pageMeta['title'] }}
        </h1>
        <div class="text-gasq-muted small">{{ $pageMeta['subtitle'] }}</div>
      </div>
      <div class="d-flex flex-wrap gap-2 gasq-wa-no-print">
        <button type="button" class="btn btn-outline-secondary btn-sm" onclick="window.print()"><i class="fa fa-print me-1"></i> Print</button>
      </div>
    </div>

    <div class="bg-white border rounded shadow-sm p-3 mb-3">
      <div class="row g-2 small">
        <div class="col-md-4">
          <label class="form-label fw-medium mb-0">Prepared for</label>
          <input type="text" id="wa_prep" class="form-control form-control-sm gasq-wa-input" value="" placeholder="Client / site" oninput="scheduleCompute()">
        </div>
        <div class="col-md-4">
          <label class="form-label fw-medium mb-0">Report date</label>
          <input type="text" id="wa_date" class="form-control form-control-sm gasq-wa-input" value="{{ date('n/j/Y') }}" oninput="scheduleCompute()">
        </div>
        <div class="col-md-4">
          <label class="form-label fw-medium mb-0">Annual billable hours (derived from Scope of Work)</label>
          <input type="number" id="wa_hours" class="form-control form-control-sm gasq-wa-input" value="8736" step="1" min="1" readonly>
        </div>
      </div>
    </div>

    <div class="card gasq-card gasq-wa-shell overflow-hidden">
      <div class="card-body p-0">
        <div class="row g-0">
          <div class="col-xl-4 border-end gasq-wa-sidebar gasq-wa-no-print" id="wa-left-col">
            <div class="p-3 p-md-4 gasq-wa-sticky">
              <div class="d-flex align-items-start justify-content-between gap-3 mb-4">
                <div>
                  <div class="gasq-wa-kicker mb-2">Shared Inputs</div>
                  <h2 class="h4 fw-bold mb-2">Workforce Appraisal Controls</h2>
                  <p class="small text-gasq-muted mb-0">Every tab on the right reads from this shared appraisal input rail, including CFO Bill Rate, Scope of Work, Appraisal Comparison, and Price Realism.</p>
                </div>
                <span class="gasq-wa-chip"><i class="fa fa-bolt"></i> Live</span>
              </div>

              <div class="card gasq-card h-100 mb-0">
                <div class="card-header gasq-wa-section small text-uppercase">Appraisal drivers</div>
                <div class="card-body small">
            <div class="mb-2">
              <label class="form-label mb-0">Baseline labor ($/hr)</label>
              <div class="d-flex align-items-center gap-2">
                <input type="number" id="wa_baseL" class="form-control form-control-sm gasq-wa-input" value="30.43" step="0.01" oninput="scheduleCompute()">
                <input type="range" id="wa_baseL_range" class="form-range mb-0" min="0" max="150" step="0.01" value="30.43" data-sync="wa_baseL">
              </div>
            </div>
            <div class="mb-2">
              <label class="form-label mb-0">Government should-cost ($/hr)</label>
              <div class="d-flex align-items-center gap-2">
                <input type="number" id="wa_govH" class="form-control form-control-sm gasq-wa-input" value="78.25" step="0.01" oninput="scheduleCompute()">
                <input type="range" id="wa_govH_range" class="form-range mb-0" min="0" max="250" step="0.01" value="78.25" data-sync="wa_govH">
              </div>
            </div>
            <div class="mb-2">
              <label class="form-label mb-0">Vendor TCO ($/hr)</label>
              <div class="d-flex align-items-center gap-2">
                <input type="number" id="wa_vendH" class="form-control form-control-sm gasq-wa-input" value="54.78" step="0.01" oninput="scheduleCompute()">
                <input type="range" id="wa_vendH_range" class="form-range mb-0" min="0" max="250" step="0.01" value="54.78" data-sync="wa_vendH">
              </div>
            </div>
            <div class="mb-2">
              <label class="form-label mb-0">Weekly billable hours</label>
              <input type="number" id="wa_wkH" class="form-control form-control-sm gasq-wa-input" value="168" step="1" readonly>
            </div>
            <div class="mb-2">
              <label class="form-label mb-0">Monthly billable hours</label>
              <input type="number" id="wa_moH" class="form-control form-control-sm gasq-wa-input" value="728" step="1" readonly>
            </div>
            <div class="mb-2">
              <label class="form-label mb-0">FTEs required</label>
              <input type="number" id="wa_ftes" class="form-control form-control-sm gasq-wa-input" value="6" step="1" min="1" readonly>
            </div>
            <div class="mb-2">
              <label class="form-label mb-0">Annual hrs / professional</label>
              <div class="d-flex align-items-center gap-2">
                <input type="number" id="wa_hrProf" class="form-control form-control-sm gasq-wa-input" value="1456" step="1" oninput="scheduleCompute()">
                <input type="range" id="wa_hrProf_range" class="form-range mb-0" min="0" max="3000" step="1" value="1456" data-sync="wa_hrProf">
              </div>
            </div>
            <hr>
            <div class="mb-2">
              <label class="form-label mb-0">Memo — training ($/hr)</label>
              <div class="d-flex align-items-center gap-2">
                <input type="number" id="wa_pr_train" class="form-control form-control-sm gasq-wa-input" value="3.47" step="0.01" oninput="scheduleCompute()">
                <input type="range" id="wa_pr_train_range" class="form-range mb-0" min="0" max="25" step="0.01" value="3.47" data-sync="wa_pr_train">
              </div>
            </div>
            <div class="mb-0">
              <label class="form-label mb-0">Reserved gov rate ($/hr)</label>
              <div class="d-flex align-items-center gap-2">
                <input type="number" id="wa_pr_res" class="form-control form-control-sm gasq-wa-input" value="38.34" step="0.01" oninput="scheduleCompute()">
                <input type="range" id="wa_pr_res_range" class="form-range mb-0" min="0" max="250" step="0.01" value="38.34" data-sync="wa_pr_res">
              </div>
            </div>

            <hr>
            <div class="d-flex align-items-center justify-content-between">
              <div class="fw-semibold">Spreadsheet Inputs (V28)</div>
              <button type="button" class="btn btn-outline-secondary btn-sm py-0 px-2" data-bs-toggle="collapse" data-bs-target="#wa_inputs_collapse">Show</button>
            </div>
            <div class="collapse mt-2" id="wa_inputs_collapse">
              <div class="small text-gasq-muted mb-2">These drive the CFO stack exactly like the workbook Inputs tab: some are <strong>$</strong>, others are <strong>%</strong>.</div>

              <div class="mb-2">
                <label class="form-label mb-0">Direct Labor Wage ($/paid hour)</label>
                <div class="d-flex align-items-center gap-2">
                  <input type="number" id="in_wage" class="form-control form-control-sm gasq-wa-input" value="27.48" step="0.01" oninput="scheduleCompute()">
                  <input type="range" id="in_wage_range" class="form-range mb-0" min="0" max="150" step="0.01" value="27.48" data-sync="in_wage">
                </div>
              </div>

              <div class="mb-2">
                <label class="form-label mb-0">H&amp;W Cash ($/paid hour)</label>
                <div class="d-flex align-items-center gap-2">
                  <input type="number" id="in_hwCash" class="form-control form-control-sm gasq-wa-input" value="4.22" step="0.01" oninput="scheduleCompute()">
                  <input type="range" id="in_hwCash_range" class="form-range mb-0" min="0" max="50" step="0.01" value="4.22" data-sync="in_hwCash">
                </div>
              </div>

              <div class="mb-2">
                <label class="form-label mb-0">Health &amp; Welfare ($/paid hour)</label>
                <div class="d-flex align-items-center gap-2">
                  <input type="number" id="in_hw" class="form-control form-control-sm gasq-wa-input" value="0" step="0.01" oninput="scheduleCompute()">
                  <input type="range" id="in_hw_range" class="form-range mb-0" min="0" max="50" step="0.01" value="0" data-sync="in_hw">
                </div>
              </div>

              <div class="mb-2">
                <label class="form-label mb-0">DON/DOFF minutes per 8-hour shift</label>
                <div class="d-flex align-items-center gap-2">
                  <input type="number" id="in_donDoffMin" class="form-control form-control-sm gasq-wa-input" value="15" step="1" min="0" oninput="scheduleCompute()">
                  <input type="range" id="in_donDoffMin_range" class="form-range mb-0" min="0" max="120" step="1" value="15" data-sync="in_donDoffMin">
                </div>
              </div>

              <div class="row g-2">
                <div class="col-6">
                  <label class="form-label mb-0">Locality Pay %</label>
                  <div class="d-flex align-items-center gap-2">
                    <input type="number" id="in_locality" class="form-control form-control-sm gasq-wa-input" value="0" step="0.0001" min="0" max="1" oninput="scheduleCompute()">
                    <input type="range" id="in_locality_range" class="form-range mb-0" min="0" max="0.5" step="0.0001" value="0" data-sync="in_locality">
                  </div>
                </div>
                <div class="col-6">
                  <label class="form-label mb-0">Labor Market Adj %</label>
                  <div class="d-flex align-items-center gap-2">
                    <input type="number" id="in_lma" class="form-control form-control-sm gasq-wa-input" value="0" step="0.0001" min="0" max="1" oninput="scheduleCompute()">
                    <input type="range" id="in_lma_range" class="form-range mb-0" min="0" max="0.5" step="0.0001" value="0" data-sync="in_lma">
                  </div>
                </div>
                <div class="col-6">
                  <label class="form-label mb-0">Shift Differential %</label>
                  <div class="d-flex align-items-center gap-2">
                    <input type="number" id="in_shiftDiff" class="form-control form-control-sm gasq-wa-input" value="0" step="0.0001" min="0" max="1" oninput="scheduleCompute()">
                    <input type="range" id="in_shiftDiff_range" class="form-range mb-0" min="0" max="0.5" step="0.0001" value="0" data-sync="in_shiftDiff">
                  </div>
                </div>
                <div class="col-6">
                  <label class="form-label mb-0">OT/Holiday Premium %</label>
                  <div class="d-flex align-items-center gap-2">
                    <input type="number" id="in_ot" class="form-control form-control-sm gasq-wa-input" value="0.08" step="0.0001" min="0" max="2" oninput="scheduleCompute()">
                    <input type="range" id="in_ot_range" class="form-range mb-0" min="0" max="0.5" step="0.0001" value="0.08" data-sync="in_ot">
                  </div>
                </div>

                <div class="col-6">
                  <label class="form-label mb-0">FICA / Medicare %</label>
                  <div class="d-flex align-items-center gap-2">
                    <input type="number" id="in_fica" class="form-control form-control-sm gasq-wa-input" value="0.0765" step="0.0001" min="0" max="1" oninput="scheduleCompute()">
                    <input type="range" id="in_fica_range" class="form-range mb-0" min="0" max="0.2" step="0.0001" value="0.0765" data-sync="in_fica">
                  </div>
                </div>
                <div class="col-6">
                  <label class="form-label mb-0">FUTA %</label>
                  <div class="d-flex align-items-center gap-2">
                    <input type="number" id="in_futa" class="form-control form-control-sm gasq-wa-input" value="0.006" step="0.0001" min="0" max="1" oninput="scheduleCompute()">
                    <input type="range" id="in_futa_range" class="form-range mb-0" min="0" max="0.05" step="0.0001" value="0.006" data-sync="in_futa">
                  </div>
                </div>
                <div class="col-6">
                  <label class="form-label mb-0">SUTA %</label>
                  <div class="d-flex align-items-center gap-2">
                    <input type="number" id="in_suta" class="form-control form-control-sm gasq-wa-input" value="0.02" step="0.0001" min="0" max="1" oninput="scheduleCompute()">
                    <input type="range" id="in_suta_range" class="form-range mb-0" min="0" max="0.2" step="0.0001" value="0.02" data-sync="in_suta">
                  </div>
                </div>
                <div class="col-6">
                  <label class="form-label mb-0">Workers Comp %</label>
                  <div class="d-flex align-items-center gap-2">
                    <input type="number" id="in_wc" class="form-control form-control-sm gasq-wa-input" value="0.016" step="0.0001" min="0" max="1" oninput="scheduleCompute()">
                    <input type="range" id="in_wc_range" class="form-range mb-0" min="0" max="0.2" step="0.0001" value="0.016" data-sync="in_wc">
                  </div>
                </div>

                <div class="col-6">
                  <label class="form-label mb-0">Vacation %</label>
                  <div class="d-flex align-items-center gap-2">
                    <input type="number" id="in_vac" class="form-control form-control-sm gasq-wa-input" value="0.02" step="0.0001" min="0" max="1" oninput="scheduleCompute()">
                    <input type="range" id="in_vac_range" class="form-range mb-0" min="0" max="0.2" step="0.0001" value="0.02" data-sync="in_vac">
                  </div>
                </div>
                <div class="col-6">
                  <label class="form-label mb-0">Paid Holidays %</label>
                  <div class="d-flex align-items-center gap-2">
                    <input type="number" id="in_hol" class="form-control form-control-sm gasq-wa-input" value="0.04" step="0.0001" min="0" max="1" oninput="scheduleCompute()">
                    <input type="range" id="in_hol_range" class="form-range mb-0" min="0" max="0.2" step="0.0001" value="0.04" data-sync="in_hol">
                  </div>
                </div>
                <div class="col-6">
                  <label class="form-label mb-0">Sick Leave %</label>
                  <div class="d-flex align-items-center gap-2">
                    <input type="number" id="in_sick" class="form-control form-control-sm gasq-wa-input" value="0.027" step="0.0001" min="0" max="1" oninput="scheduleCompute()">
                    <input type="range" id="in_sick_range" class="form-range mb-0" min="0" max="0.2" step="0.0001" value="0.027" data-sync="in_sick">
                  </div>
                </div>
                <div class="col-6">
                  <label class="form-label mb-0">Profit / Fee %</label>
                  <div class="d-flex align-items-center gap-2">
                    <input type="number" id="in_profit" class="form-control form-control-sm gasq-wa-input" value="0.21" step="0.0001" min="0" max="2" oninput="scheduleCompute()">
                    <input type="range" id="in_profit_range" class="form-range mb-0" min="0" max="1" step="0.0001" value="0.21" data-sync="in_profit">
                  </div>
                </div>
              </div>
            </div>
                </div>
              </div>
            </div>
          </div>

          <div class="col-xl-8" id="wa-right-col">
            <div class="p-3 p-md-4">
        <div class="gasq-wa-summary-card p-3 p-md-4 mb-4">
          <div class="gasq-wa-benchmark-card mb-4">
            <div class="text-uppercase small fw-semibold text-gasq-muted mb-1">Workforce Appraisal Summary</div>
            <div class="h3 fw-bold text-primary mb-1" id="wa_stat_gov">$0.00</div>
            <div class="small text-gasq-muted mb-2">Current government should-cost benchmark from the shared appraisal controls.</div>
            <div class="d-flex justify-content-between align-items-center small gap-3">
              <span class="text-gasq-muted">Annual billable hours</span>
              <span class="fw-semibold" id="wa_stat_hours">0</span>
            </div>
            <div class="mt-2">
              <a href="{{ route('workforce-appraisal-report.index') }}" class="small fw-semibold text-decoration-none">Open Full Workforce Appraisal</a>
            </div>
          </div>

          <div class="row g-3 mb-0">
            <div class="col-6 col-xl-4">
              <div class="gasq-metric-card text-center">
                <div class="metric-desc">FTEs Required</div>
                <div class="metric-value text-primary gasq-wa-mono" id="wa_stat_ftes">0</div>
              </div>
            </div>
            <div class="col-6 col-xl-4">
              <div class="gasq-metric-card text-center">
                <div class="metric-desc">Vendor TCO</div>
                <div class="metric-value gasq-wa-mono" id="wa_stat_vendor">$0.00</div>
              </div>
            </div>
            <div class="col-12 col-xl-4">
              <div class="gasq-metric-card text-center">
                <div class="metric-desc">Report Date</div>
                <div class="metric-value gasq-wa-mono" id="wa_stat_date">{{ date('n/j/Y') }}</div>
              </div>
            </div>
          </div>
        </div>

        <div class="d-flex justify-content-between align-items-center flex-wrap gap-3 mb-3">
          <div>
            <div class="gasq-wa-kicker mb-1">Results Workspace</div>
            <h3 class="h5 fw-bold mb-0">Live Workforce Appraisal Outputs</h3>
          </div>
          <div class="small text-gasq-muted">All tabs below stay connected to the shared input rail on the left.</div>
        </div>

        <div class="card gasq-card mb-3">
          <div class="card-header gasq-wa-subbanner d-flex justify-content-between align-items-center">
            <span>Direct Labor Build-Up</span>
            <span class="small fw-normal opacity-75">Shared across all Workforce Appraisal tabs</span>
          </div>
          <div class="card-body p-0">
            <p class="small text-gasq-muted px-3 pt-3 mb-2">Driven by Spreadsheet Inputs (V28) and current annual billable hours from Scope of Work.</p>
            <div id="wa_dlb_root" class="table-responsive px-3 pb-3"></div>
          </div>
        </div>

        <ul class="nav nav-pills gasq-wa-tabs mb-3 gasq-wa-no-print" id="wa_tablist" role="tablist">
          <li class="nav-item" role="presentation"><a class="nav-link {{ $initialTab === 'cfo' ? 'active' : '' }}" data-bs-toggle="tab" href="#wa-pane-cfo" id="tab-cfo" role="tab" aria-controls="wa-pane-cfo" aria-selected="{{ $initialTab === 'cfo' ? 'true' : 'false' }}"><i class="fa fa-table"></i><span>CFO Bill Rate</span></a></li>
          <li class="nav-item" role="presentation"><a class="nav-link {{ $initialTab === 'posts' ? 'active' : '' }}" data-bs-toggle="tab" href="#wa-pane-posts" id="tab-posts" role="tab" aria-controls="wa-pane-posts" aria-selected="{{ $initialTab === 'posts' ? 'true' : 'false' }}"><i class="fa fa-users"></i><span>Scope of Work</span></a></li>
          <li class="nav-item" role="presentation"><a class="nav-link {{ $initialTab === 'appraisal' ? 'active' : '' }}" data-bs-toggle="tab" href="#wa-pane-appraisal" id="tab-appraisal" role="tab" aria-controls="wa-pane-appraisal" aria-selected="{{ $initialTab === 'appraisal' ? 'true' : 'false' }}"><i class="fa fa-balance-scale"></i><span>Appraisal Comparison</span></a></li>
          <li class="nav-item" role="presentation"><a class="nav-link {{ $initialTab === 'price' ? 'active' : '' }}" data-bs-toggle="tab" href="#wa-pane-price" id="tab-price" role="tab" aria-controls="wa-pane-price" aria-selected="{{ $initialTab === 'price' ? 'true' : 'false' }}"><i class="fa fa-chart-line"></i><span>Price Realism</span></a></li>
        </ul>

        <div class="tab-content" id="wa_results_workspace">
          <div class="tab-pane fade {{ $initialTab === 'cfo' ? 'show active' : '' }}" id="wa-pane-cfo" role="tabpanel">
            <div class="card gasq-card">
              <div class="card-header gasq-wa-section">CFO Bill Rate Breakdown</div>
              <div class="card-body p-0">
                <p class="small text-gasq-muted px-3 pt-3 mb-2">Consolidated line-by-line build — hourly × annual billable hours = annual column.</p>
                <div id="wa_cfo_root" class="table-responsive px-3 pb-3"></div>
              </div>
            </div>
          </div>

          <div class="tab-pane fade {{ $initialTab === 'posts' ? 'show active' : '' }}" id="wa-pane-posts" role="tabpanel">
            <div class="card gasq-card">
              <div class="card-header gasq-wa-table-head d-flex justify-content-between align-items-center">
                <span>SCOPE OF WORK</span>
                <span class="small fw-normal opacity-75">Spreadsheet-aligned inputs drive derived hours</span>
              </div>
              <div class="card-body p-3">
                <div class="row g-3 align-items-end">
                  <div class="col-md-3">
                    <label class="form-label mb-0">Hours of Coverage per Day</label>
                    <input type="number" id="wa_scope_hours_day" class="form-control form-control-sm gasq-wa-input" value="24" step="0.01" min="0" max="24" oninput="scheduleCompute()">
                  </div>
                  <div class="col-md-3">
                    <label class="form-label mb-0">Days of Coverage per Week</label>
                    <input type="number" id="wa_scope_days_week" class="form-control form-control-sm gasq-wa-input" value="7" step="0.01" min="0" max="7" oninput="scheduleCompute()">
                  </div>
                  <div class="col-md-3">
                    <label class="form-label mb-0">Weeks of Coverage</label>
                    <input type="number" id="wa_scope_weeks" class="form-control form-control-sm gasq-wa-input" value="52" step="0.01" min="0" max="52" oninput="scheduleCompute()">
                  </div>
                  <div class="col-md-3">
                    <label class="form-label mb-0">Staff per 8-Hour Shift</label>
                    <input type="number" id="wa_scope_staff" class="form-control form-control-sm gasq-wa-input" value="1" step="0.01" min="0" oninput="scheduleCompute()">
                  </div>
                </div>

                <div class="row g-3 mt-1">
                  <div class="col-md-3">
                    <div class="border rounded p-3 h-100 bg-light-subtle">
                      <div class="small text-uppercase text-gasq-muted fw-semibold">Weekly Coverage Hours</div>
                      <div class="h4 mb-0 font-monospace" id="wa_scope_weekly_coverage">0</div>
                    </div>
                  </div>
                  <div class="col-md-3">
                    <div class="border rounded p-3 h-100 bg-light-subtle">
                      <div class="small text-uppercase text-gasq-muted fw-semibold">Total Annual Hours</div>
                      <div class="h4 mb-0 font-monospace" id="wa_scope_total_annual">0</div>
                    </div>
                  </div>
                  <div class="col-md-3">
                    <div class="border rounded p-3 h-100 bg-warning-subtle">
                      <div class="small text-uppercase text-gasq-muted fw-semibold">Annual Billable Hours</div>
                      <div class="h4 mb-0 font-monospace" id="wa_scope_annual_billable">0</div>
                    </div>
                  </div>
                  <div class="col-md-3">
                    <div class="border rounded p-3 h-100 bg-light-subtle">
                      <div class="small text-uppercase text-gasq-muted fw-semibold">FTEs Required</div>
                      <div class="h4 mb-0 font-monospace" id="wa_scope_ftes">0</div>
                    </div>
                  </div>
                </div>

                <div class="table-responsive mt-3">
                  <table class="table table-sm mb-0">
                    <tbody>
                      <tr>
                        <th class="w-50">Weekly Billable Hours</th>
                        <td class="text-end font-monospace" id="wa_scope_weekly_billable">0</td>
                      </tr>
                      <tr>
                        <th>Monthly Billable Hours</th>
                        <td class="text-end font-monospace" id="wa_scope_monthly_billable">0</td>
                      </tr>
                      <tr>
                        <th>Hours per Professional Annual</th>
                        <td class="text-end font-monospace" id="wa_scope_hours_professional">0</td>
                      </tr>
                    </tbody>
                  </table>
                </div>

                <div class="table-responsive mt-3">
                  <table class="table table-sm align-middle mb-0" id="wa_post_table">
                    <thead class="table-light">
                      <tr class="small">
                        <th>Post Position</th>
                        <th class="text-end">User Pay Rate</th>
                        <th class="text-end">Annual Hours</th>
                        <th class="text-end">Weekly Hours</th>
                        <th class="text-end">Weekly Cost</th>
                        <th class="text-end">Monthly Hours</th>
                        <th class="text-end">Monthly Cost</th>
                        <th class="text-end">Annual Cost</th>
                      </tr>
                    </thead>
                    <tbody id="wa_post_body"></tbody>
                    <tfoot>
                      <tr class="gasq-wa-total-row small" id="wa_post_foot">
                        <td>TOTAL</td>
                        <td class="text-end font-monospace" id="pf_avg">$0.00</td>
                        <td class="text-end font-monospace" id="pf_ah">0</td>
                        <td class="text-end font-monospace" id="pf_wh">0</td>
                        <td class="text-end font-monospace" id="pf_wc">$0.00</td>
                        <td class="text-end font-monospace" id="pf_mh">0</td>
                        <td class="text-end font-monospace" id="pf_mc">$0.00</td>
                        <td class="text-end font-monospace" id="pf_ac">$0.00</td>
                      </tr>
                    </tfoot>
                  </table>
                </div>
              </div>
            </div>
          </div>

          <div class="tab-pane fade {{ $initialTab === 'appraisal' ? 'show active' : '' }}" id="wa-pane-appraisal" role="tabpanel">
            <div class="card gasq-card mb-3">
              <div class="card-header gasq-wa-section">Appraisal Comparison Summary</div>
              <div class="card-body p-0">
                <div class="table-responsive">
                  <table class="table table-bordered mb-0" id="wa_ap_tbl">
                    <thead class="table-light">
                      <tr>
                        <th>Description</th>
                        <th class="text-end gasq-wa-mono">Internal should-cost</th>
                        <th class="text-end gasq-wa-mono">Vendor TCO</th>
                      </tr>
                    </thead>
                    <tbody id="wa_ap_body"></tbody>
                    <tbody id="wa_ap_foot"></tbody>
                  </table>
                </div>
              </div>
            </div>
            <div class="card gasq-card">
              <div class="card-header gasq-wa-section">Coverage Statement</div>
              <div class="card-body small text-gasq-muted" id="wa_coverage_text"></div>
            </div>
            <div class="text-center small text-gasq-muted mt-3 mb-2">CFO Tested · CFO Approved · (470) 633-2816 · info@getasecurityquote.com · getasecurityquotenow.com</div>
          </div>

          <div class="tab-pane fade {{ $initialTab === 'price' ? 'show active' : '' }}" id="wa-pane-price" role="tabpanel">
            <div class="row g-3">
              <div class="col-md-6">
                <div class="card gasq-card h-100">
                  <div class="card-header gasq-wa-subbanner small fw-semibold">Module feeds (memo)</div>
                  <div class="card-body p-0" id="wa_pr_left"></div>
                </div>
              </div>
              <div class="col-md-6">
                <div class="card gasq-card h-100">
                  <div class="card-header gasq-wa-table-head small fw-semibold">GASQ price realism review</div>
                  <div class="card-body p-0 small" id="wa_pr_right"></div>
                </div>
              </div>
            </div>
          </div>
        </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <x-report-actions reportType="workforce-appraisal-report" />

  </div>
</div>
@endsection

@push('scripts')
<script>
(function(){
  const savedScenario = window.__gasqCalculatorState?.scenario || null;
  const masterInputs = window.__gasqMasterInputs || {};
  const POST_ROWS = 10;
  const computeUrl = @json(route('backend.standalone.v24.compute', ['type' => 'workforce-appraisal-report']));
  let debounce = null;
  const defaultPosts = [
    { positionTitle: 'Unarmed S/O', blendedPayRate: 19.25, annualHours: 2080 },
    { positionTitle: 'Supervisor', blendedPayRate: 24.50, annualHours: 2080 },
    { positionTitle: 'Roving Patrol Officer', blendedPayRate: 21.00, annualHours: 2496 },
  ];

  function money(n){
    return new Intl.NumberFormat('en-US',{style:'currency',currency:'USD',minimumFractionDigits:2,maximumFractionDigits:2}).format(n||0);
  }
  function num(n){ return (n===null||n===undefined||Number.isNaN(n))?'—':Number(n).toLocaleString('en-US'); }

  function round2(n){
    return Math.round((Number(n) || 0) * 100) / 100;
  }

  function buildPostBody(seedRows = null){
    const tb = document.getElementById('wa_post_body');
    if(!tb) return;

    tb.innerHTML = '';
    for(let i=0;i<POST_ROWS;i++){
      const d = seedRows?.[i] || defaultPosts[i] || { positionTitle:'', blendedPayRate:0, annualHours:0 };
      const tr = document.createElement('tr');
      tr.innerHTML = `
        <td><input type="text" class="form-control form-control-sm gasq-wa-input wa-p-title" data-i="${i}" value="${d.positionTitle || ''}" placeholder="Post position"></td>
        <td><input type="number" class="form-control form-control-sm gasq-wa-input text-end wa-p-pay" data-i="${i}" value="${d.blendedPayRate || ''}" min="0" step="0.01"></td>
        <td><input type="number" class="form-control form-control-sm gasq-wa-input text-end wa-p-ann" data-i="${i}" value="${d.annualHours || ''}" min="0" step="1"></td>
        <td class="text-end font-monospace small wa-p-out wh">0</td>
        <td class="text-end font-monospace small wa-p-out wc">${money(0)}</td>
        <td class="text-end font-monospace small wa-p-out mh">0</td>
        <td class="text-end font-monospace small wa-p-out mc">${money(0)}</td>
        <td class="text-end font-monospace small wa-p-out ac">${money(0)}</td>`;
      tb.appendChild(tr);
    }

    tb.querySelectorAll('input').forEach((el) => el.addEventListener('input', scheduleCompute));
  }

  function hydrateSavedState(){
    const meta = savedScenario?.meta || {};
    const inputs = Object.keys(meta.inputs || {}).length ? (meta.inputs || {}) : masterInputs;
    const scope = meta.scope || {};
    const appraisal = meta.appraisal || {};
    const priceRealism = meta.priceRealism || {};
    const posts = Array.isArray(meta.posts) ? meta.posts : [];

    const map = {
      wa_prep: appraisal.preparedFor,
      wa_date: appraisal.reportDate,
      wa_baseL: appraisal.baselineLaborRate,
      wa_govH: appraisal.governmentShouldCostHourly,
      wa_vendH: appraisal.vendorTcoHourly,
      wa_hrProf: appraisal.hoursPerProfessionalAnnual ?? inputs.annualPaidHoursPerFte ?? inputs.annualPaidHoursPerFTE,
      wa_pr_train: priceRealism.trainingProgramPerHour,
      wa_pr_res: priceRealism.reservedGovernmentRateHourly,
      wa_scope_hours_day: scope.hoursOfCoveragePerDay,
      wa_scope_days_week: scope.daysOfCoveragePerWeek,
      wa_scope_weeks: scope.weeksOfCoverage,
      wa_scope_staff: scope.staffPerShift,
      in_wage: inputs.directLaborWage,
      in_hwCash: inputs.hwCashPerHour,
      in_hw: inputs.healthWelfarePerHour,
      in_donDoffMin: inputs.donDoffMinutesPerShift,
      in_locality: inputs.localityPayPct,
      in_lma: inputs.laborMarketAdjPct,
      in_shiftDiff: inputs.shiftDifferentialPct,
      in_ot: inputs.otHolidayPremiumPct,
      in_fica: inputs.ficaMedicarePct,
      in_futa: inputs.futaPct,
      in_suta: inputs.sutaPct,
      in_wc: inputs.workersCompPct,
      in_vac: inputs.vacationPct,
      in_hol: inputs.paidHolidaysPct,
      in_sick: inputs.sickLeavePct,
      in_profit: inputs.profitFeePct,
    };

    Object.entries(map).forEach(([id, value]) => {
      if(value === undefined || value === null) return;
      const el = document.getElementById(id);
      if(el) el.value = value;
    });

    const seededPosts = Array.from({ length: POST_ROWS }, (_, index) => {
      const savedRow = posts.find((row) => Number(row.index || 0) === index);
      if (!savedRow) {
        return null;
      }

      return {
        positionTitle: savedRow.positionTitle || '',
        blendedPayRate: savedRow.blendedPayRate || 0,
        annualHours: savedRow.annualHours || 0,
      };
    });

    return seededPosts.some(Boolean) ? seededPosts : null;
  }

  function collectPosts(){
    const rows = [];
    document.querySelectorAll('#wa_post_body tr').forEach((tr) => {
      const rowIndex = parseInt(tr.querySelector('.wa-p-title')?.dataset.i || '0', 10) || 0;
      const title = tr.querySelector('.wa-p-title')?.value?.trim() || '';
      const pay = parseFloat(tr.querySelector('.wa-p-pay')?.value || '0') || 0;
      const annualHours = parseFloat(tr.querySelector('.wa-p-ann')?.value || '0') || 0;
      if(title || pay || annualHours){
        rows.push({
          index: rowIndex,
          positionTitle: title || '—',
          qty: 1,
          blendedPayRate: pay,
          annualHours,
        });
      }
    });
    return rows.length ? rows : null;
  }

  function deriveScopeMetrics(){
    const hoursOfCoveragePerDay = parseFloat(document.getElementById('wa_scope_hours_day')?.value || '0') || 0;
    const daysOfCoveragePerWeek = parseFloat(document.getElementById('wa_scope_days_week')?.value || '0') || 0;
    const weeksOfCoverage = parseFloat(document.getElementById('wa_scope_weeks')?.value || '0') || 0;
    const staffPerShift = parseFloat(document.getElementById('wa_scope_staff')?.value || '0') || 0;
    const hoursPerProfessionalAnnual = parseFloat(document.getElementById('wa_hrProf')?.value || '0') || 0;

    const weeklyCoverageHours = hoursOfCoveragePerDay * daysOfCoveragePerWeek;
    const totalAnnualHours = weeklyCoverageHours * weeksOfCoverage;
    const weeklyBillableHours = weeklyCoverageHours * staffPerShift;
    const annualBillableHours = totalAnnualHours * staffPerShift;
    const monthlyBillableHours = annualBillableHours / 12;
    const ftesRequired = hoursPerProfessionalAnnual > 0 ? annualBillableHours / hoursPerProfessionalAnnual : 0;

    return {
      inputs: {
        hoursOfCoveragePerDay,
        daysOfCoveragePerWeek,
        weeksOfCoverage,
        staffPerShift,
      },
      weeklyCoverageHours: round2(weeklyCoverageHours),
      totalAnnualHours: round2(totalAnnualHours),
      weeklyBillableHours: round2(weeklyBillableHours),
      monthlyBillableHours: round2(monthlyBillableHours),
      annualBillableHours: round2(annualBillableHours),
      ftesRequired: round2(ftesRequired),
      ftesRequiredRoundedUp: Math.max(1, Math.ceil(ftesRequired || 0)),
      hoursPerProfessionalAnnual: round2(hoursPerProfessionalAnnual),
    };
  }

  function syncDerivedScopeFields(scope){
    const setValue = (id, value) => {
      const el = document.getElementById(id);
      if(el){ el.value = value; }
    };
    const setText = (id, value) => {
      const el = document.getElementById(id);
      if(el){ el.textContent = value; }
    };

    setValue('wa_hours', Math.round(scope.annualBillableHours));
    setValue('wa_wkH', Math.round(scope.weeklyBillableHours));
    setValue('wa_moH', Math.round(scope.monthlyBillableHours));
    setValue('wa_ftes', scope.ftesRequiredRoundedUp);

    setText('wa_scope_weekly_coverage', num(scope.weeklyCoverageHours));
    setText('wa_scope_total_annual', num(scope.totalAnnualHours));
    setText('wa_scope_annual_billable', num(scope.annualBillableHours));
    setText('wa_scope_ftes', scope.ftesRequired.toFixed(2));
    setText('wa_scope_weekly_billable', num(scope.weeklyBillableHours));
    setText('wa_scope_monthly_billable', num(scope.monthlyBillableHours));
    setText('wa_scope_hours_professional', num(scope.hoursPerProfessionalAnnual));
    setText('wa_stat_hours', num(scope.annualBillableHours));
    setText('wa_stat_ftes', scope.ftesRequired.toFixed(2));
    setText('wa_stat_gov', money(parseFloat(document.getElementById('wa_govH')?.value || '0') || 0));
    setText('wa_stat_vendor', money(parseFloat(document.getElementById('wa_vendH')?.value || '0') || 0));
    setText('wa_stat_date', document.getElementById('wa_date')?.value || '');
  }

  function buildPayload(){
    const scope = deriveScopeMetrics();
    const H = scope.annualBillableHours || 1;
    return {
      version: 'v24',
      scenario: {
        meta: {
          annualBillableHours: H,
          inputs: {
            // Inputs tab (V28) values – keep as decimals for % fields (e.g. 0.0765 for 7.65%).
            directLaborWage: parseFloat(document.getElementById('in_wage')?.value)||0,
            localityPayPct: parseFloat(document.getElementById('in_locality')?.value)||0,
            shiftDifferentialPct: parseFloat(document.getElementById('in_shiftDiff')?.value)||0,
            otHolidayPremiumPct: parseFloat(document.getElementById('in_ot')?.value)||0,
            laborMarketAdjPct: parseFloat(document.getElementById('in_lma')?.value)||0,
            hwCashPerHour: parseFloat(document.getElementById('in_hwCash')?.value)||0,
            healthWelfarePerHour: parseFloat(document.getElementById('in_hw')?.value)||0,
            donDoffMinutesPerShift: parseFloat(document.getElementById('in_donDoffMin')?.value)||0,

            ficaMedicarePct: parseFloat(document.getElementById('in_fica')?.value)||0,
            futaPct: parseFloat(document.getElementById('in_futa')?.value)||0,
            sutaPct: parseFloat(document.getElementById('in_suta')?.value)||0,
            workersCompPct: parseFloat(document.getElementById('in_wc')?.value)||0,
            vacationPct: parseFloat(document.getElementById('in_vac')?.value)||0,
            paidHolidaysPct: parseFloat(document.getElementById('in_hol')?.value)||0,
            sickLeavePct: parseFloat(document.getElementById('in_sick')?.value)||0,

            profitFeePct: parseFloat(document.getElementById('in_profit')?.value)||0,
          },
          scope: {
            hoursOfCoveragePerDay: scope.inputs.hoursOfCoveragePerDay,
            daysOfCoveragePerWeek: scope.inputs.daysOfCoveragePerWeek,
            weeksOfCoverage: scope.inputs.weeksOfCoverage,
            staffPerShift: scope.inputs.staffPerShift,
          },
          appraisal: {
            preparedFor: document.getElementById('wa_prep').value||'',
            reportDate: document.getElementById('wa_date').value||'',
            baselineLaborRate: parseFloat(document.getElementById('wa_baseL').value)||0,
            governmentShouldCostHourly: parseFloat(document.getElementById('wa_govH').value)||0,
            vendorTcoHourly: parseFloat(document.getElementById('wa_vendH').value)||0,
            totalWeeklyHours: scope.weeklyBillableHours,
            totalMonthlyHours: scope.monthlyBillableHours,
            totalAnnualHours: H,
            ftesRequired: scope.ftesRequiredRoundedUp,
            hoursPerProfessionalAnnual: parseFloat(document.getElementById('wa_hrProf').value)||0,
          },
          priceRealism: {
            trainingProgramPerHour: parseFloat(document.getElementById('wa_pr_train').value)||0,
            reservedGovernmentRateHourly: parseFloat(document.getElementById('wa_pr_res').value)||0,
          },
          posts: collectPosts(),
        }
      }
    };
  }

  function buildCfoMarkup(cfo){
    if(!cfo || !cfo.sections){
      return '<p class="text-danger small px-3 py-2 mb-0">No data</p>';
    }

    let html = '<table class="table table-sm table-bordered align-middle gasq-wa-mono">';
    html += `<thead><tr><th>Description</th><th class="text-end">Hourly</th><th class="text-end text-success">Annual</th></tr></thead>`;
    for(const sec of cfo.sections){
      html += `<tr><td colspan="3" class="gasq-wa-section small">${sec.title}</td></tr>`;
      for(const r of sec.rows||[]){
        const hl = r.highlight ? ' table-warning' : '';
        html += `<tr class="${hl}"><td>${r.label}</td><td class="text-end">${money(r.hourly)}</td><td class="text-end text-success">${money(r.annual)}</td></tr>`;
      }
      const st = sec.subtotal||{};
      html += `<tr class="table-warning fw-semibold"><td>${st.label}</td><td class="text-end">${money(st.hourly)}</td><td class="text-end text-success">${money(st.annual)}</td></tr>`;
      if(sec.laborPlusFringe){
        const l = sec.laborPlusFringe;
        html += `<tr class="table-warning fw-bold"><td>${l.label}</td><td class="text-end">${money(l.hourly)}</td><td class="text-end text-success">${money(l.annual)}</td></tr>`;
      }
    }
    const g = cfo.grandTotal||{};
    html += `<tr class="fw-bold table-primary"><td>${g.label}</td><td class="text-end">${money(g.hourly)}</td><td class="text-end text-success">${money(g.annual)}</td></tr>`;
    html += '</table>';
    html += `<div class="small text-gasq-muted mt-2">Annual billable hours: <span class="fw-semibold">${num(cfo.annualBillableHours)}</span></div>`;
    return html;
  }

  function renderCfo(cfo){
    const el = document.getElementById('wa_cfo_root');
    if(el){ el.innerHTML = buildCfoMarkup(cfo); }
  }

  function renderDirectLaborBuildUp(cfo){
    const el = document.getElementById('wa_dlb_root');
    if(el){ el.innerHTML = buildCfoMarkup(cfo); }
  }

  function fillPostOut(rows, totals){
    const rowMap = new Map((rows || []).map((row) => [Number(row.index || 0), row]));
    document.querySelectorAll('#wa_post_body tr').forEach((tr, i) => {
      const r = rowMap.get(i);
      const wh = tr.querySelector('.wh');
      const wc = tr.querySelector('.wc');
      const mh = tr.querySelector('.mh');
      const mc = tr.querySelector('.mc');
      const ac = tr.querySelector('.ac');

      if(!r){
        wh.textContent = '0';
        wc.textContent = money(0);
        mh.textContent = '0';
        mc.textContent = money(0);
        ac.textContent = money(0);
        return;
      }

      wh.textContent = num(r.weeklyHours);
      wc.textContent = money(r.weeklyCost);
      mh.textContent = num(r.monthlyHours);
      mc.textContent = money(r.monthlyCost);
      ac.textContent = money(r.annualDirectLaborCost);
    });

    const t = totals || {};
    document.getElementById('pf_avg').textContent = money(t.blendedPayRateAvg);
    document.getElementById('pf_ah').textContent = num(t.annualHours);
    document.getElementById('pf_wh').textContent = num(t.weeklyHours);
    document.getElementById('pf_wc').textContent = money(t.weeklyCost);
    document.getElementById('pf_mh').textContent = num(t.monthlyHours);
    document.getElementById('pf_mc').textContent = money(t.monthlyCost);
    document.getElementById('pf_ac').textContent = money(t.annualDirectLaborCost);
  }

  function renderAppraisal(a){
    const b = document.getElementById('wa_ap_body');
    const f = document.getElementById('wa_ap_foot');
    b.innerHTML = '';
    f.innerHTML = '';
    if(!a||!a.rows) return;
    for(const r of a.rows){
      const vInt = (typeof r.internal==='number') ? money(r.internal) : r.internal;
      const vVen = (typeof r.vendor==='number') ? money(r.vendor) : r.vendor;
      b.innerHTML += `<tr><td>${r.description}</td><td class="text-end gasq-wa-mono">${vInt}</td><td class="text-end gasq-wa-mono">${vVen}</td></tr>`;
    }
    for(const r of (a.footerRows||[])){
      const suf = r.suffix||'';
      let vVen = '—';
      if(r.vendor !== null && r.vendor !== undefined){
        if(r.isPercent){ vVen = `${Number(r.vendor).toFixed(0)}%`; }
        else if(typeof r.vendor === 'number'){
          vVen = r.description.includes('Payback') ? `${r.vendor}${suf}` : `${money(r.vendor)}${suf}`;
        }
      }
      f.innerHTML += `<tr class="gasq-wa-peach fw-semibold"><td>${r.description}</td><td class="text-end gasq-wa-mono">—</td><td class="text-end gasq-wa-mono">${vVen}</td></tr>`;
    }
    document.getElementById('wa_coverage_text').textContent = a.coverageStatement||'';
  }

  function renderPriceRealism(p){
    const L = document.getElementById('wa_pr_left');
    const R = document.getElementById('wa_pr_right');
    if(!p){ L.innerHTML=R.innerHTML=''; return; }
    let l = '<table class="table table-sm mb-0">';
    for(const r of (p.moduleFeeds||[])){
      l += `<tr><td>${r.label}</td><td class="text-end gasq-wa-mono">${money(r.hourly)}</td><td class="text-end text-success gasq-wa-mono">${money(r.annual)}</td></tr>`;
    }
    for(const r of (p.leftSummary||[])){
      const cl = r.rateClass||''; const ca = r.annualClass||'';
      const fw = r.strong ? ' fw-bold' : '';
      l += `<tr class="${fw}"><td class="${cl}">${r.label}</td><td class="text-end gasq-wa-mono ${cl}">${money(r.hourly)}</td><td class="text-end gasq-wa-mono ${ca}">${money(r.annual)}</td></tr>`;
    }
    l += '</table>';
    L.innerHTML = l;
    let r = '<table class="table table-sm mb-0"><thead><tr><th>Benchmark rates</th><th class="text-end">Rate</th><th class="text-end">Total</th></tr></thead>';
    for(const row of (p.benchmark||[])){
      const cl = row.rateClass||''; const ca = row.annualClass||'';
      const fw = row.strong ? ' fw-bold' : '';
      r += `<tr class="${fw}"><td class="${cl}">${row.label}</td><td class="text-end gasq-wa-mono ${cl}">${money(row.hourly)}</td><td class="text-end gasq-wa-mono ${ca}">${money(row.annual)}</td></tr>`;
    }
    r += '</table>';
    R.innerHTML = r;
  }

  async function runCompute(){
    const scope = deriveScopeMetrics();
    syncDerivedScopeFields(scope);

    const res = await fetch(computeUrl, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
        'Accept': 'application/json'
      },
      body: JSON.stringify(buildPayload())
    });
    const data = await res.json();
    if(!res.ok||!data.ok){ console.error(data); return; }
    const k = data.kpis||{};
    renderDirectLaborBuildUp(k.cfoBillRate);
    renderCfo(k.cfoBillRate);
    const p = k.postPositionSummary||{};
    fillPostOut(p.rows||[], p.totals||{});
    renderAppraisal(k.appraisalComparison||{});
    renderPriceRealism(k.priceRealism||{});
  }

  window.scheduleCompute = function(){
    clearTimeout(debounce);
    debounce = setTimeout(runCompute, 260);
  };

  function initSliderSync(){
    document.querySelectorAll('input[type="range"][data-sync]').forEach((rangeEl)=>{
      const id = rangeEl.getAttribute('data-sync');
      const numEl = document.getElementById(id);
      if(!numEl) return;

      // Initialize slider from number, then keep them in sync both ways.
      const clamp = (v, min, max) => Math.min(max, Math.max(min, v));
      const syncRangeFromNumber = () => {
        const min = parseFloat(rangeEl.min || '0');
        const max = parseFloat(rangeEl.max || '100');
        const v = parseFloat(numEl.value || rangeEl.value || '0');
        rangeEl.value = String(clamp(v, min, max));
      };
      const syncNumberFromRange = () => {
        numEl.value = rangeEl.value;
      };

      syncRangeFromNumber();

      rangeEl.addEventListener('input', () => {
        syncNumberFromRange();
        scheduleCompute();
      });
      numEl.addEventListener('input', () => {
        syncRangeFromNumber();
      });
    });
  }

  document.addEventListener('DOMContentLoaded', ()=>{
    const seededPosts = hydrateSavedState();
    buildPostBody(seededPosts);
    initSliderSync();
    const tab = @json($initialTab);
    const map = { cfo:'tab-cfo', posts:'tab-posts', appraisal:'tab-appraisal', price:'tab-price' };
    const id = map[tab] || 'tab-cfo';
    const btn = document.getElementById(id);
    if(btn && window.bootstrap){
      bootstrap.Tab.getOrCreateInstance(btn).show();
    }

    syncDerivedScopeFields(deriveScopeMetrics());
    runCompute();
  });
})();
</script>
@endpush

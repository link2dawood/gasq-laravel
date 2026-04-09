@extends('layouts.app')
@section('title', 'Security Billing Calculator')
@section('header_variant', 'dashboard')

@push('styles')
<style>
  .sb-shell {
    background:
      radial-gradient(circle at top right, rgba(6, 45, 121, 0.08), transparent 28%),
      linear-gradient(180deg, #ffffff 0%, #f7f9fc 100%);
  }
  .sb-sidebar {
    background: linear-gradient(180deg, #fbfcff 0%, #f2f5fb 100%);
  }
  .sb-sticky {
    position: sticky;
    top: 1.25rem;
  }
  .sb-kicker {
    font-size: 0.72rem;
    letter-spacing: 0.12em;
    text-transform: uppercase;
    color: var(--gasq-muted);
  }
  .sb-section + .sb-section {
    margin-top: 1.5rem;
    padding-top: 1.5rem;
    border-top: 1px solid rgba(15, 23, 42, 0.08);
  }
  .sb-stat {
    border: 1px solid rgba(6, 45, 121, 0.08);
    border-radius: 1rem;
    padding: 1rem;
    background: #fff;
  }
  .sb-stat-label {
    font-size: 0.76rem;
    text-transform: uppercase;
    letter-spacing: 0.08em;
    color: var(--gasq-muted);
  }
  .sb-stat-value {
    font-size: 1.55rem;
    font-weight: 700;
    color: var(--gasq-primary);
  }
  .sb-panel {
    border: 1px solid rgba(15, 23, 42, 0.08);
    border-radius: 1rem;
    background: #fff;
  }
  .sb-panel-muted {
    background: rgba(6, 45, 121, 0.04);
  }
  .sb-mono {
    font-variant-numeric: tabular-nums;
  }
  .sb-chip {
    display: inline-flex;
    align-items: center;
    gap: 0.35rem;
    padding: 0.35rem 0.7rem;
    border-radius: 999px;
    background: rgba(6, 45, 121, 0.08);
    color: var(--gasq-primary);
    font-size: 0.78rem;
    font-weight: 600;
  }
  @media (max-width: 1199.98px) {
    .sb-sticky {
      position: static;
    }
  }
</style>
@endpush

@section('content')
<div class="py-4 px-3 px-md-4" style="background:var(--gasq-background)">
<div class="container-xl">

  <div class="d-flex align-items-center justify-content-between flex-wrap gap-3 mb-4">
    <div class="d-flex align-items-center gap-3">
      <a href="{{ route('main-menu-calculator.index') }}" class="btn btn-outline-secondary btn-sm"><i class="fa fa-arrow-left"></i></a>
      <div>
        <h1 class="h3 fw-bold mb-0 d-flex align-items-center gap-2">
          <i class="fa fa-file-invoice-dollar text-primary"></i> Security Billing Calculator
        </h1>
        <div class="text-gasq-muted small">Budget-style calculator shell with shared inputs and live billing results</div>
      </div>
    </div>
    <div class="d-flex flex-wrap gap-2 d-print-none">
      <button class="btn btn-outline-secondary btn-sm" onclick="resetAll()"><i class="fa fa-rotate me-1"></i> Reset</button>
      <button class="btn btn-outline-secondary btn-sm" onclick="window.print()"><i class="fa fa-print me-1"></i> Print</button>
      <button class="btn btn-outline-secondary btn-sm" onclick="window.print()"><i class="fa fa-download me-1"></i> Download PDF</button>
    </div>
  </div>

  <div class="card gasq-card sb-shell overflow-hidden">
    <div class="card-body p-0">
      <div class="row g-0">
        <div class="col-xl-4 border-end sb-sidebar">
          <div class="p-3 p-md-4 sb-sticky">
            <div class="d-flex align-items-start justify-content-between gap-3 mb-4">
              <div>
                <div class="sb-kicker mb-2">Shared Inputs</div>
                <h2 class="h4 fw-bold mb-2">Billing Model Controls</h2>
                <p class="small text-gasq-muted mb-0">Every tab on the right reads from this same input set, so any change updates the live billing view immediately.</p>
              </div>
              <span class="sb-chip"><i class="fa fa-wave-square"></i> Live</span>
            </div>

            <div class="sb-section">
              <div class="d-flex align-items-center gap-2 mb-3">
                <i class="fa fa-address-card text-primary"></i>
                <h5 class="mb-0 fw-semibold">Contact Information</h5>
              </div>
              <div class="row g-3">
                <div class="col-12">
                  <label class="form-label small fw-medium">Customer Name</label>
                  <input type="text" id="sb_custName" class="form-control form-control-sm" placeholder="John Doe" oninput="scheduleSB()">
                </div>
                <div class="col-12">
                  <label class="form-label small fw-medium">Company Name</label>
                  <input type="text" id="sb_compName" class="form-control form-control-sm" placeholder="ABC Security" oninput="scheduleSB()">
                </div>
                <div class="col-md-6">
                  <label class="form-label small fw-medium">Email</label>
                  <input type="email" id="sb_email" class="form-control form-control-sm" placeholder="john@example.com" oninput="scheduleSB()">
                </div>
                <div class="col-md-6">
                  <label class="form-label small fw-medium">Phone</label>
                  <input type="tel" id="sb_phone" class="form-control form-control-sm" placeholder="(555) 123-4567" oninput="scheduleSB()">
                </div>
              </div>
            </div>

            <div class="sb-section">
              <div class="d-flex align-items-center gap-2 mb-3">
                <i class="fa fa-sliders text-primary"></i>
                <h5 class="mb-0 fw-semibold">Billing Parameters</h5>
              </div>
              <div class="d-flex flex-column gap-3">
                <div>
                  <label class="form-label fw-medium">Base Pay Rate ($/hr)</label>
                  <div class="input-group">
                    <span class="input-group-text">$</span>
                    <input type="number" id="sb_basePay" class="form-control" value="18.00" step="0.01" oninput="scheduleSB()">
                  </div>
                </div>

                <div class="row g-3">
                  <div class="col-md-6">
                    <label class="form-label fw-medium">Hours per Week</label>
                    <input type="number" id="sb_hours" class="form-control" value="40" step="0.5" oninput="scheduleSB()">
                  </div>
                  <div class="col-md-6">
                    <label class="form-label fw-medium">Weeks per Year</label>
                    <input type="number" id="sb_weeks" class="form-control" value="52" oninput="scheduleSB()">
                  </div>
                </div>
              </div>
            </div>

            <div class="sb-section">
              <div class="d-flex align-items-center gap-2 mb-3">
                <i class="fa fa-layer-group text-primary"></i>
                <h5 class="mb-0 fw-semibold">Cost Components</h5>
              </div>
              <div class="row g-3">
                <div class="col-md-6">
                  <label class="form-label small fw-medium">FICA &amp; Medicare (%)</label>
                  <input type="number" id="sb_fica" class="form-control form-control-sm" value="7.65" step="0.01" oninput="scheduleSB()">
                </div>
                <div class="col-md-6">
                  <label class="form-label small fw-medium">FUTA (%)</label>
                  <input type="number" id="sb_futa" class="form-control form-control-sm" value="0.8" step="0.01" oninput="scheduleSB()">
                </div>
                <div class="col-md-6">
                  <label class="form-label small fw-medium">SUTA (%)</label>
                  <input type="number" id="sb_suta" class="form-control form-control-sm" value="5.76" step="0.01" oninput="scheduleSB()">
                </div>
                <div class="col-md-6">
                  <label class="form-label small fw-medium">Overhead (%)</label>
                  <input type="number" id="sb_overhead" class="form-control form-control-sm" value="35" step="0.1" oninput="scheduleSB()">
                </div>
                <div class="col-md-6">
                  <label class="form-label small fw-medium">Profit Margin (%)</label>
                  <input type="number" id="sb_profitPct" class="form-control form-control-sm" value="15" step="0.1" oninput="scheduleSB()">
                </div>
                <div class="col-md-6">
                  <label class="form-label small fw-medium">Uniform Cost ($)</label>
                  <input type="number" id="sb_uniformCost" class="form-control form-control-sm" value="75" step="0.01" oninput="scheduleSB()">
                </div>
                <div class="col-md-6">
                  <label class="form-label small fw-medium">Uniforms per Employee</label>
                  <input type="number" id="sb_uniformQty" class="form-control form-control-sm" value="2" oninput="scheduleSB()">
                </div>
                <div class="col-md-6">
                  <label class="form-label small fw-medium">Training Cost per Hire ($)</label>
                  <input type="number" id="sb_trainingCost" class="form-control form-control-sm" value="500" oninput="scheduleSB()">
                </div>
              </div>
            </div>
          </div>
        </div>

        <div class="col-xl-8">
          <div class="p-3 p-md-4">
            <div class="row g-3 mb-4">
              <div class="col-md-4">
                <div class="sb-stat">
                  <div class="sb-stat-label mb-2">Total Bill Rate</div>
                  <div class="sb-stat-value sb-mono" id="sb_r_totalBillRate">$0.00</div>
                  <div class="small text-gasq-muted">Current live billing model</div>
                </div>
              </div>
              <div class="col-md-4">
                <div class="sb-stat">
                  <div class="sb-stat-label mb-2">Weekly Total</div>
                  <div class="sb-stat-value sb-mono" id="sb_r_weekly">$0.00</div>
                  <div class="small text-gasq-muted">Driven by shared left-side inputs</div>
                </div>
              </div>
              <div class="col-md-4">
                <div class="sb-stat">
                  <div class="sb-stat-label mb-2">Annual Total</div>
                  <div class="sb-stat-value sb-mono" id="sb_r_annual">$0.00</div>
                  <div class="small text-gasq-muted">Updated instantly as inputs change</div>
                </div>
              </div>
            </div>

            <div class="d-flex justify-content-between align-items-center flex-wrap gap-3 mb-3">
              <div>
                <div class="sb-kicker mb-1">Results Workspace</div>
                <h3 class="h5 fw-bold mb-0">Live Security Billing Outputs</h3>
              </div>
              <div class="small text-gasq-muted">Scenario A below always mirrors the current shared inputs.</div>
            </div>

            <div class="gasq-tabs-scroll mb-3 d-print-none">
              <ul class="gasq-tabs-pill mb-0" role="tablist">
                <li class="nav-item"><a class="nav-link active" data-bs-toggle="tab" href="#sb-summary"><i class="fa fa-calculator me-1"></i> Live Results</a></li>
                <li class="nav-item"><a class="nav-link" data-bs-toggle="tab" href="#sb-comparison"><i class="fa fa-code-compare me-1"></i> Comparison</a></li>
                <li class="nav-item"><a class="nav-link" data-bs-toggle="tab" href="#sb-profile"><i class="fa fa-user me-1"></i> Profile &amp; Rates</a></li>
              </ul>
            </div>

            <div class="tab-content">
              <div class="tab-pane fade show active" id="sb-summary">
                <div class="row g-3">
                  <div class="col-lg-6">
                    <div class="sb-panel p-3 h-100">
                      <h5 class="fw-semibold mb-3">Hourly Rates</h5>
                      <div class="d-flex justify-content-between mb-2"><span class="text-gasq-muted small">Base Pay Rate</span><span class="fw-medium sb-mono" id="sb_r_basePay">$0.00/hr</span></div>
                      <div class="d-flex justify-content-between mb-2"><span class="text-gasq-muted small">Cost with Payroll Taxes</span><span class="fw-medium sb-mono" id="sb_r_withTaxes">$0.00/hr</span></div>
                      <div class="d-flex justify-content-between mb-2"><span class="text-gasq-muted small">Cost with Overhead</span><span class="fw-medium sb-mono" id="sb_r_withOverhead">$0.00/hr</span></div>
                      <div class="d-flex justify-content-between mb-2"><span class="text-gasq-muted small">OT Bill Rate (1.5x)</span><span class="fw-medium sb-mono" id="sb_r_otRate">$0.00/hr</span></div>
                      <div class="d-flex justify-content-between"><span class="text-gasq-muted small">Holiday Bill Rate (1.5x)</span><span class="fw-medium sb-mono" id="sb_r_holidayRate">$0.00/hr</span></div>
                    </div>
                  </div>
                  <div class="col-lg-6">
                    <div class="sb-panel sb-panel-muted p-3 h-100">
                      <h5 class="fw-semibold mb-3">Totals</h5>
                      <div class="d-flex justify-content-between mb-2"><span class="text-gasq-muted small">Monthly Total</span><span class="fw-medium sb-mono" id="sb_r_monthly">$0.00</span></div>
                      <div class="d-flex justify-content-between mb-2"><span class="text-gasq-muted small">Uniform Cost</span><span class="fw-medium sb-mono" id="sb_r_uniforms">$0.00</span></div>
                      <div class="d-flex justify-content-between mb-2"><span class="text-gasq-muted small">Training Cost (amortized/hr)</span><span class="fw-medium sb-mono" id="sb_r_trainingHr">$0.00/hr</span></div>
                      <hr class="my-3">
                      <div class="rounded-4 p-4 text-white text-center" style="background:var(--gasq-primary)">
                        <div class="small mb-1" style="opacity:.8">Recommended Bill Rate</div>
                        <div class="display-5 fw-bold sb-mono" id="sb_r_billRate">$0.00/hr</div>
                        <div class="small mt-1" style="opacity:.7">Applied everywhere on this page</div>
                      </div>
                    </div>
                  </div>
                  <div class="col-12">
                    <div class="sb-panel p-3">
                      <h5 class="fw-semibold mb-3">Billing Cost Stack</h5>
                      <div class="row g-3">
                        <div class="col-md-4">
                          <div class="rounded-4 p-3 h-100" style="background:rgba(6,45,121,0.05)">
                            <div class="small text-gasq-muted mb-1">Payroll Burden</div>
                            <div class="fs-4 fw-bold sb-mono" id="sb_stack_taxRate">0.00%</div>
                            <div class="small text-gasq-muted">FICA + FUTA + SUTA from shared inputs</div>
                          </div>
                        </div>
                        <div class="col-md-4">
                          <div class="rounded-4 p-3 h-100" style="background:rgba(34,197,94,0.08)">
                            <div class="small text-gasq-muted mb-1">Overhead Rate</div>
                            <div class="fs-4 fw-bold sb-mono" id="sb_stack_overhead">0.00%</div>
                            <div class="small text-gasq-muted">Applied after payroll taxes</div>
                          </div>
                        </div>
                        <div class="col-md-4">
                          <div class="rounded-4 p-3 h-100" style="background:rgba(249,115,22,0.08)">
                            <div class="small text-gasq-muted mb-1">Profit Margin</div>
                            <div class="fs-4 fw-bold sb-mono" id="sb_stack_profit">0.00%</div>
                            <div class="small text-gasq-muted">Current target billing margin</div>
                          </div>
                        </div>
                      </div>
                    </div>
                  </div>
                </div>
              </div>

              <div class="tab-pane fade" id="sb-comparison">
                <div class="row g-3">
                  <div class="col-lg-6">
                    <div class="sb-panel p-3 h-100">
                      <div class="d-flex align-items-center gap-2 mb-3">
                        <span class="rounded-circle d-inline-block" style="width:12px;height:12px;background:#3b82f6"></span>
                        <h5 class="mb-0 fw-semibold">Scenario A</h5>
                      </div>
                      <div class="small text-gasq-muted mb-3">This scenario mirrors the shared left-side inputs in real time.</div>
                      <div class="d-flex justify-content-between mb-2"><span class="text-gasq-muted small">Base Pay</span><span class="fw-medium sb-mono" id="cmpA_basePay">$0.00/hr</span></div>
                      <div class="d-flex justify-content-between mb-2"><span class="text-gasq-muted small">Hours / Week</span><span class="fw-medium sb-mono" id="cmpA_hours">0</span></div>
                      <div class="d-flex justify-content-between mb-2"><span class="text-gasq-muted small">Overhead</span><span class="fw-medium sb-mono" id="cmpA_overhead">0%</span></div>
                      <div class="d-flex justify-content-between mb-2"><span class="text-gasq-muted small">Profit</span><span class="fw-medium sb-mono" id="cmpA_profit">0%</span></div>
                      <hr class="my-3">
                      <div class="rounded-4 p-4 text-white text-center" style="background:var(--gasq-primary)">
                        <div class="small mb-1" style="opacity:.82">Scenario A Bill Rate</div>
                        <div class="fs-2 fw-bold sb-mono" id="cmpRateA">$0.00</div>
                        <div class="small mt-1" style="opacity:.72">Annual: <span id="cmpAnnualA">$0.00</span></div>
                      </div>
                    </div>
                  </div>
                  <div class="col-lg-6">
                    <div class="sb-panel p-3 h-100">
                      <div class="d-flex align-items-center gap-2 mb-3">
                        <span class="rounded-circle d-inline-block" style="width:12px;height:12px;background:#22c55e"></span>
                        <h5 class="mb-0 fw-semibold">Scenario B</h5>
                      </div>
                      <div class="small text-gasq-muted mb-3">Use the current tax structure from the left and test an alternate pay, hours, overhead, and profit mix here.</div>
                      <div class="row g-3 mb-3">
                        <div class="col-md-6">
                          <label class="form-label x-sm fw-medium">Base Pay $/hr</label>
                          <input type="number" id="cmpB_basePay" class="form-control form-control-sm" value="20.00" step="0.01" oninput="calcCmp()">
                        </div>
                        <div class="col-md-6">
                          <label class="form-label x-sm fw-medium">Hours/week</label>
                          <input type="number" id="cmpB_hours" class="form-control form-control-sm" value="40" oninput="calcCmp()">
                        </div>
                        <div class="col-md-6">
                          <label class="form-label x-sm fw-medium">Overhead %</label>
                          <input type="number" id="cmpB_overhead" class="form-control form-control-sm" value="35" oninput="calcCmp()">
                        </div>
                        <div class="col-md-6">
                          <label class="form-label x-sm fw-medium">Profit %</label>
                          <input type="number" id="cmpB_profit" class="form-control form-control-sm" value="15" oninput="calcCmp()">
                        </div>
                      </div>
                      <div class="rounded-4 p-4 text-white text-center" style="background:#16a34a">
                        <div class="small mb-1" style="opacity:.82">Scenario B Bill Rate</div>
                        <div class="fs-2 fw-bold sb-mono" id="cmpRateB">$0.00</div>
                        <div class="small mt-1" style="opacity:.72">Annual: <span id="cmpAnnualB">$0.00</span></div>
                      </div>
                    </div>
                  </div>
                  <div class="col-12">
                    <div class="sb-panel sb-panel-muted p-3">
                      <h5 class="fw-semibold mb-3">Difference (B vs A)</h5>
                      <div class="row g-3">
                        <div class="col-md-4 text-center"><div class="small text-gasq-muted mb-1">Hourly Rate</div><div class="fs-5 fw-bold sb-mono" id="cmpDiffRate">$0.00</div></div>
                        <div class="col-md-4 text-center"><div class="small text-gasq-muted mb-1">Weekly</div><div class="fs-5 fw-bold sb-mono" id="cmpDiffWeekly">$0.00</div></div>
                        <div class="col-md-4 text-center"><div class="small text-gasq-muted mb-1">Annual</div><div class="fs-5 fw-bold sb-mono" id="cmpDiffAnnual">$0.00</div></div>
                      </div>
                    </div>
                  </div>
                </div>
              </div>

              <div class="tab-pane fade" id="sb-profile">
                <div class="row g-3">
                  <div class="col-lg-6">
                    <div class="sb-panel p-3 h-100">
                      <h5 class="fw-semibold mb-3">Billing Profile Snapshot</h5>
                      <div class="d-flex justify-content-between mb-2"><span class="text-gasq-muted small">Customer</span><span class="fw-medium text-end" id="profile_name">—</span></div>
                      <div class="d-flex justify-content-between mb-2"><span class="text-gasq-muted small">Company</span><span class="fw-medium text-end" id="profile_company">—</span></div>
                      <div class="d-flex justify-content-between mb-2"><span class="text-gasq-muted small">Email</span><span class="fw-medium text-end" id="profile_email">—</span></div>
                      <div class="d-flex justify-content-between"><span class="text-gasq-muted small">Phone</span><span class="fw-medium text-end" id="profile_phone">—</span></div>
                    </div>
                  </div>
                  <div class="col-lg-6">
                    <div class="sb-panel p-3 h-100">
                      <h5 class="fw-semibold mb-3">Live Rate Sheet</h5>
                      <div class="d-flex justify-content-between mb-2"><span class="text-gasq-muted small">Base Pay</span><span class="fw-medium sb-mono" id="profile_basePay">$0.00/hr</span></div>
                      <div class="d-flex justify-content-between mb-2"><span class="text-gasq-muted small">Payroll Burden</span><span class="fw-medium sb-mono" id="profile_taxPct">0.00%</span></div>
                      <div class="d-flex justify-content-between mb-2"><span class="text-gasq-muted small">Bill Rate</span><span class="fw-medium sb-mono" id="profile_billRate">$0.00/hr</span></div>
                      <div class="d-flex justify-content-between mb-2"><span class="text-gasq-muted small">OT Bill Rate</span><span class="fw-medium sb-mono" id="profile_ot">$0.00/hr</span></div>
                      <div class="d-flex justify-content-between mb-2"><span class="text-gasq-muted small">Monthly Total</span><span class="fw-medium sb-mono" id="profile_monthly">$0.00</span></div>
                      <div class="d-flex justify-content-between"><span class="text-gasq-muted small">Annual Total</span><span class="fw-semibold sb-mono" id="profile_annual">$0.00</span></div>
                    </div>
                  </div>
                  <div class="col-12">
                    <div class="sb-panel sb-panel-muted p-3">
                      <h5 class="fw-semibold mb-3">Active Cost Inputs</h5>
                      <div class="row g-3">
                        <div class="col-md-3"><div class="small text-gasq-muted mb-1">FICA / Medicare</div><div class="fw-semibold sb-mono" id="profile_fica">0.00%</div></div>
                        <div class="col-md-3"><div class="small text-gasq-muted mb-1">FUTA</div><div class="fw-semibold sb-mono" id="profile_futa">0.00%</div></div>
                        <div class="col-md-3"><div class="small text-gasq-muted mb-1">SUTA</div><div class="fw-semibold sb-mono" id="profile_suta">0.00%</div></div>
                        <div class="col-md-3"><div class="small text-gasq-muted mb-1">Overhead / Profit</div><div class="fw-semibold sb-mono" id="profile_overheadProfit">0.00% / 0.00%</div></div>
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
  </div>

  <x-report-actions reportType="security-billing" />

</div>
</div>
@endsection

@push('scripts')
<style>.x-sm{font-size:0.75rem}</style>
<script>
let sbDebounce = null;
window._sbOut = {};
window._sbState = {};

function fmt(v){return new Intl.NumberFormat('en-US',{style:'currency',currency:'USD',minimumFractionDigits:2}).format(v || 0);}
function g(id){return parseFloat(document.getElementById(id)?.value) || 0;}
function txt(id){return (document.getElementById(id)?.value || '').trim();}
function setText(id,v){const el=document.getElementById(id);if(el)el.textContent=v;}
function pct(v){return `${Number(v || 0).toFixed(2)}%`;}

function getSharedState(){
  const basePay = g('sb_basePay');
  const hours = g('sb_hours');
  const weeks = g('sb_weeks');
  const fica = g('sb_fica');
  const futa = g('sb_futa');
  const suta = g('sb_suta');
  const overhead = g('sb_overhead');
  const profitPct = g('sb_profitPct');

  return {
    customerName: txt('sb_custName'),
    companyName: txt('sb_compName'),
    email: txt('sb_email'),
    phone: txt('sb_phone'),
    basePay,
    hours,
    weeks,
    fica,
    futa,
    suta,
    overhead,
    profitPct,
    uniformCost: g('sb_uniformCost'),
    uniformQty: g('sb_uniformQty'),
    trainingCost: g('sb_trainingCost'),
    taxRatePct: fica + futa + suta,
  };
}

function renderProfile(out, state){
  setText('profile_name', state.customerName || '—');
  setText('profile_company', state.companyName || '—');
  setText('profile_email', state.email || '—');
  setText('profile_phone', state.phone || '—');

  setText('profile_basePay', `${fmt(state.basePay)}/hr`);
  setText('profile_taxPct', pct(state.taxRatePct));
  setText('profile_billRate', `${fmt(out.billRate || 0)}/hr`);
  setText('profile_ot', `${fmt(out.otBillRate || 0)}/hr`);
  setText('profile_monthly', fmt(out.monthlyTotal || 0));
  setText('profile_annual', fmt(out.annualTotal || 0));
  setText('profile_fica', pct(state.fica));
  setText('profile_futa', pct(state.futa));
  setText('profile_suta', pct(state.suta));
  setText('profile_overheadProfit', `${pct(state.overhead)} / ${pct(state.profitPct)}`);
}

function renderScenarioA(state, out){
  setText('cmpA_basePay', `${fmt(state.basePay)}/hr`);
  setText('cmpA_hours', state.hours.toFixed(2));
  setText('cmpA_overhead', pct(state.overhead));
  setText('cmpA_profit', pct(state.profitPct));
  setText('cmpRateA', fmt(out.totalBillRate || 0));
  setText('cmpAnnualA', fmt(out.annualTotal || 0));
}

function calcCmp(){
  const state = window._sbState || getSharedState();
  const outA = window._sbOut || {};

  const taxRate = (state.taxRatePct || 0) / 100;
  const weeks = state.weeks || 52;
  const basePay = g('cmpB_basePay');
  const hours = g('cmpB_hours');
  const overhead = g('cmpB_overhead') / 100;
  const profitPct = g('cmpB_profit') / 100;

  const withTaxes = basePay * (1 + taxRate);
  const withOverhead = withTaxes * (1 + overhead);
  const billRate = (1 - profitPct) > 0 ? withOverhead / (1 - profitPct) : 0;
  const annual = billRate * hours * weeks;

  setText('cmpRateB', fmt(billRate));
  setText('cmpAnnualB', fmt(annual));

  const diffRate = billRate - (outA.totalBillRate || 0);
  const diffWeekly = (billRate * hours) - (outA.weeklyTotal || 0);
  const diffAnnual = annual - (outA.annualTotal || 0);

  function styleN(id, v){
    const el = document.getElementById(id);
    if(!el) return;
    el.textContent = fmt(v);
    el.className = `fs-5 fw-bold sb-mono ${v > 0 ? 'text-danger' : v < 0 ? 'text-success' : ''}`;
  }

  styleN('cmpDiffRate', diffRate);
  styleN('cmpDiffWeekly', diffWeekly);
  styleN('cmpDiffAnnual', diffAnnual);
}

async function calcSB(){
  const state = getSharedState();
  window._sbState = state;

  const payload = {
    version: 'v24',
    scenario: {
      meta: {
        basePayRate: state.basePay,
        hoursPerWeek: state.hours,
        weeksPerYear: state.weeks,
        ficaPct: state.fica,
        futaPct: state.futa,
        sutaPct: state.suta,
        overheadPct: state.overhead,
        profitPct: state.profitPct,
        uniformCostPerUniform: state.uniformCost,
        uniformsPerEmployee: state.uniformQty,
        trainingCostPerHire: state.trainingCost
      }
    }
  };

  const res = await fetch('{{ route('backend.security-billing.v24.compute') }}', {
    method: 'POST',
    headers: {
      'Content-Type': 'application/json',
      'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
      'Accept': 'application/json',
    },
    body: JSON.stringify(payload),
  });
  const data = await res.json();
  if(!res.ok || !data || !data.ok){
    console.error(data);
    return;
  }

  const out = data.kpis || {};
  window._sbOut = out;

  setText('sb_r_basePay', `${fmt(state.basePay)}/hr`);
  setText('sb_r_withTaxes', `${fmt(out.costWithPayrollTaxes || 0)}/hr`);
  setText('sb_r_withOverhead', `${fmt(out.costWithOverhead || 0)}/hr`);
  setText('sb_r_billRate', `${fmt(out.billRate || 0)}/hr`);
  setText('sb_r_otRate', `${fmt(out.otBillRate || 0)}/hr`);
  setText('sb_r_holidayRate', `${fmt(out.holidayBillRate || 0)}/hr`);
  setText('sb_r_weekly', fmt(out.weeklyTotal || 0));
  setText('sb_r_monthly', fmt(out.monthlyTotal || 0));
  setText('sb_r_annual', fmt(out.annualTotal || 0));
  setText('sb_r_uniforms', fmt(out.uniformTotal || 0));
  setText('sb_r_trainingHr', `${fmt(out.trainingCostPerHour || 0)}/hr`);
  setText('sb_r_totalBillRate', fmt(out.totalBillRate || 0));
  setText('sb_stack_taxRate', pct(state.taxRatePct));
  setText('sb_stack_overhead', pct(state.overhead));
  setText('sb_stack_profit', pct(state.profitPct));

  renderScenarioA(state, out);
  renderProfile(out, state);
  calcCmp();
}

window.scheduleSB = function(){
  clearTimeout(sbDebounce);
  sbDebounce = setTimeout(calcSB, 180);
};

function resetAll(){
  const defaults = {
    sb_custName: '',
    sb_compName: '',
    sb_email: '',
    sb_phone: '',
    sb_basePay: 18,
    sb_hours: 40,
    sb_weeks: 52,
    sb_fica: 7.65,
    sb_futa: 0.8,
    sb_suta: 5.76,
    sb_overhead: 35,
    sb_profitPct: 15,
    sb_uniformCost: 75,
    sb_uniformQty: 2,
    sb_trainingCost: 500,
    cmpB_basePay: 20,
    cmpB_hours: 40,
    cmpB_overhead: 35,
    cmpB_profit: 15,
  };

  Object.entries(defaults).forEach(([id, value]) => {
    const el = document.getElementById(id);
    if(el){ el.value = value; }
  });

  calcSB();
}

document.addEventListener('DOMContentLoaded', () => {
  calcSB();
});
</script>
@endpush

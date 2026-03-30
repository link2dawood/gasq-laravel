@extends('layouts.app')
@section('title', 'Main Menu Calculator')
@section('header_variant', 'dashboard')

@section('content')
<div class="py-4 px-3 px-md-4" style="background:var(--gasq-background)">
<div class="container-xl">

  {{-- Page header --}}
  <div class="d-flex align-items-center justify-content-between flex-wrap gap-3 mb-4">
    <div>
      <h1 class="h3 fw-bold mb-0 d-flex align-items-center gap-2">
        <i class="fa fa-calculator text-primary"></i> Main Menu Calculator
      </h1>
      <div class="text-gasq-muted small">Security workforce planning &amp; cost analysis</div>
    </div>
    <div class="d-flex flex-wrap gap-2 d-print-none">
      <button class="btn btn-outline-secondary btn-sm" onclick="window.print()"><i class="fa fa-print me-1"></i> Print</button>
      <button class="btn btn-outline-secondary btn-sm" id="btnDownload" onclick="downloadReport()"><i class="fa fa-download me-1"></i> Download</button>
      <button class="btn btn-primary btn-sm" onclick="emailReport()"><i class="fa fa-envelope me-1"></i> Email Report</button>
    </div>
  </div>

  {{-- Tabs --}}
  <div class="card gasq-card">
    <div class="card-header px-3 px-md-4 pt-3 pb-0 d-print-none" style="background:transparent;border-bottom:none">
      <div class="gasq-tabs-scroll">
        <ul class="gasq-tabs-pill" role="tablist" id="mainTabs">
          <li class="nav-item"><a class="nav-link active" data-bs-toggle="tab" href="#tab-security"><i class="fa fa-shield me-1"></i> Security Cost</a></li>
          <li class="nav-item"><a class="nav-link" data-bs-toggle="tab" href="#tab-manpower"><i class="fa fa-users me-1"></i> Manpower Hours</a></li>
          <li class="nav-item"><a class="nav-link" data-bs-toggle="tab" href="#tab-justification"><i class="fa fa-chart-line me-1"></i> Economic Justification</a></li>
          <li class="nav-item"><a class="nav-link" data-bs-toggle="tab" href="#tab-billrate"><i class="fa fa-dollar-sign me-1"></i> Bill Rate</a></li>
          <li class="nav-item"><a class="nav-link" data-bs-toggle="tab" href="#tab-components"><i class="fa fa-chart-pie me-1"></i> Bill Rate Components</a></li>
          <li class="nav-item"><a class="nav-link" data-bs-toggle="tab" href="#tab-summary"><i class="fa fa-file-alt me-1"></i> Contract Summary</a></li>
        </ul>
      </div>
    </div>

    <div class="card-body p-4">
      <div class="tab-content">

      {{-- ========== SECURITY COST TAB ========== --}}
      <div class="tab-pane fade show active" id="tab-security">
        <div class="row g-4">
          <div class="col-lg-5">
            <h5 class="fw-semibold mb-3 d-flex align-items-center gap-2"><i class="fa fa-shield text-primary"></i> Security Cost Calculator</h5>
            <p class="text-gasq-muted small mb-4">Calculate estimated security guard costs based on location, hours, and number of guards.</p>

            <div class="mb-3">
              <label class="form-label fw-medium">Location / State</label>
              <select id="sc_location" class="form-select" onchange="calcSecurity()">
                <option value="california">California</option>
                <option value="new-york">New York</option>
                <option value="texas">Texas</option>
                <option value="florida">Florida</option>
                <option value="illinois">Illinois</option>
              </select>
            </div>
            <div class="mb-3">
              <label class="form-label fw-medium">Service Type</label>
              <select id="sc_serviceType" class="form-select" onchange="calcSecurity()">
                <option value="unarmed">Unarmed Guard</option>
                <option value="armed">Armed Guard</option>
                <option value="patrol">Mobile Patrol</option>
              </select>
            </div>
            <div class="mb-3">
              <label class="form-label fw-medium">Hours per Week</label>
              <input type="number" id="sc_hours" class="form-control" value="40" oninput="calcSecurity()">
            </div>
            <div class="mb-3">
              <label class="form-label fw-medium">Number of Guards</label>
              <input type="number" id="sc_guards" class="form-control" value="1" min="1" oninput="calcSecurity()">
            </div>
          </div>
          <div class="col-lg-7">
            <h5 class="fw-semibold mb-3 d-flex align-items-center gap-2"><i class="fa fa-chart-bar text-primary"></i> Results</h5>
            <div class="row g-3 mb-4">
              <div class="col-6">
                <div class="gasq-metric-card text-center">
                  <div class="metric-desc">Hourly Rate</div>
                  <div class="metric-value text-primary" id="sc_r_hourly">$0.00</div>
                  <div class="gasq-progress"><div class="gasq-progress-fill" id="sc_p_hourly" style="width:0%"></div></div>
                </div>
              </div>
              <div class="col-6">
                <div class="gasq-metric-card text-center">
                  <div class="metric-desc">Weekly Total</div>
                  <div class="metric-value text-primary" id="sc_r_weekly">$0.00</div>
                  <div class="gasq-progress"><div class="gasq-progress-fill" id="sc_p_weekly" style="width:0%"></div></div>
                </div>
              </div>
              <div class="col-6">
                <div class="gasq-metric-card text-center">
                  <div class="metric-desc">Monthly Total</div>
                  <div class="metric-value text-primary" id="sc_r_monthly">$0.00</div>
                  <div class="gasq-progress"><div class="gasq-progress-fill" id="sc_p_monthly" style="width:0%"></div></div>
                </div>
              </div>
              <div class="col-6">
                <div class="gasq-metric-card text-center">
                  <div class="metric-desc">Annual Total</div>
                  <div class="metric-value text-primary" id="sc_r_annual">$0.00</div>
                  <div class="gasq-progress"><div class="gasq-progress-fill fill-success" id="sc_p_annual" style="width:0%"></div></div>
                </div>
              </div>
            </div>
            <div class="rounded p-3" style="background:rgba(6,45,121,0.06);border:1px solid rgba(6,45,121,0.15)">
              <div class="small text-gasq-muted mb-2"><i class="fa fa-info-circle me-1"></i>Based on MIT Living Wage data + 30% benefits/overhead</div>
              <div class="d-flex justify-content-between small mb-1">
                <span class="text-gasq-muted">Living wage base</span>
                <span id="sc_r_livingWage">$0.00/hr</span>
              </div>
              <div class="d-flex justify-content-between small">
                <span class="text-gasq-muted">Total with overhead</span>
                <span id="sc_r_withOverhead">$0.00/hr</span>
              </div>
            </div>
          </div>
        </div>
      </div>

      {{-- ========== MANPOWER HOURS TAB ========== --}}
      <div class="tab-pane fade" id="tab-manpower">
        <div class="row g-4">
          <div class="col-lg-5">
            <h5 class="fw-semibold mb-3 d-flex align-items-center gap-2"><i class="fa fa-users text-primary"></i> Manpower Hours Calculator</h5>
            <p class="text-gasq-muted small mb-4">Determine staffing requirements based on site coverage and shift patterns.</p>

            <div class="mb-3">
              <label class="form-label fw-medium">Site Coverage (hours/day)</label>
              <input type="number" id="mp_coverage" class="form-control" value="24" step="0.5" oninput="calcManpower()">
            </div>
            <div class="mb-3">
              <label class="form-label fw-medium">Shift Pattern</label>
              <select id="mp_shift" class="form-select" onchange="calcManpower()">
                <option value="8-hour">8-hour shifts</option>
                <option value="10-hour">10-hour shifts</option>
                <option value="12-hour">12-hour shifts</option>
                <option value="16-hour">16-hour shifts</option>
                <option value="24-hour">24-hour shifts</option>
              </select>
            </div>
            <div class="mb-3">
              <label class="form-label fw-medium">Scheduling Factor</label>
              <input type="number" id="mp_factor" class="form-control" value="1.4" step="0.1" oninput="calcManpower()">
              <div class="form-text">Accounts for days off, sick leave, etc. (typically 1.4–1.7)</div>
            </div>
          </div>
          <div class="col-lg-7">
            <h5 class="fw-semibold mb-3">Results</h5>
            <div class="row g-3 mb-4">
              <div class="col-4">
                <div class="gasq-metric-card text-center">
                  <div class="metric-desc">Weekly Hours</div>
                  <div class="metric-value text-primary" id="mp_r_weekly">0</div>
                  <div class="gasq-progress"><div class="gasq-progress-fill" style="width:0%"></div></div>
                </div>
              </div>
              <div class="col-4">
                <div class="gasq-metric-card text-center">
                  <div class="metric-desc">Monthly Hours</div>
                  <div class="metric-value text-primary" id="mp_r_monthly">0</div>
                  <div class="gasq-progress"><div class="gasq-progress-fill" style="width:0%"></div></div>
                </div>
              </div>
              <div class="col-4">
                <div class="gasq-metric-card text-center">
                  <div class="metric-desc">Annual Hours</div>
                  <div class="metric-value text-primary" id="mp_r_annual">0</div>
                  <div class="gasq-progress"><div class="gasq-progress-fill fill-success" style="width:0%"></div></div>
                </div>
              </div>
            </div>
            <div class="gasq-input-section">
              <h6 class="fw-semibold mb-2">Staffing Details</h6>
              <div class="d-flex justify-content-between small mb-1">
                <span class="text-gasq-muted">Estimated guards required (part-time 28hr/wk)</span>
                <span id="mp_r_guards">0</span>
              </div>
              <div class="d-flex justify-content-between small">
                <span class="text-gasq-muted">Shift multiplier used</span>
                <span id="mp_r_multiplier">0</span>
              </div>
            </div>
          </div>
        </div>
      </div>

      {{-- ========== ECONOMIC JUSTIFICATION TAB ========== --}}
      <div class="tab-pane fade" id="tab-justification">
        <div class="row g-4">
          <div class="col-lg-6">
            <h5 class="fw-semibold mb-3 d-flex align-items-center gap-2"><i class="fa fa-chart-line text-primary"></i> Economic Justification</h5>

            <div class="row g-3 mb-3">
              <div class="col-md-6">
                <label class="form-label fw-medium">Company Name</label>
                <input type="text" id="ej_company" class="form-control" value="ABC COMPANY" oninput="calcEJ()">
              </div>
              <div class="col-md-6">
                <label class="form-label fw-medium">Prepared By</label>
                <input type="text" id="ej_preparedBy" class="form-control" placeholder="Your name" oninput="calcEJ()">
              </div>
              <div class="col-md-6">
                <label class="form-label fw-medium">Employee True Hourly Cost</label>
                <div class="input-group">
                  <span class="input-group-text">$</span>
                  <input type="number" id="ej_employeeCost" class="form-control" value="133.00" step="0.01" oninput="calcEJ()">
                </div>
              </div>
              <div class="col-md-6">
                <label class="form-label fw-medium">Weekly Hours Performed</label>
                <input type="number" id="ej_weeklyHours" class="form-control" value="168" oninput="calcEJ()">
              </div>
              <div class="col-md-6">
                <label class="form-label fw-medium">Weeks in Year</label>
                <input type="number" id="ej_weeksInYear" class="form-control" value="52" oninput="calcEJ()">
              </div>
              <div class="col-md-6">
                <label class="form-label fw-medium">Months in Year</label>
                <input type="number" id="ej_monthsInYear" class="form-control" value="12" oninput="calcEJ()">
              </div>
            </div>
          </div>

          <div class="col-lg-6">
            <h5 class="fw-semibold mb-3">ROI Analysis</h5>

            {{-- Two-column comparison table --}}
            <div class="table-responsive rounded" style="background:var(--gasq-muted-bg)">
              <table class="table table-sm mb-0">
                <thead>
                  <tr>
                    <th class="small fw-semibold">Metric</th>
                    <th class="small fw-semibold text-center">In-House</th>
                    <th class="small fw-semibold text-center">Vendor</th>
                  </tr>
                </thead>
                <tbody>
                  <tr><td class="small text-gasq-muted">Hourly Cost</td><td class="text-center small fw-medium" id="ej_r_ihHourly">$0.00</td><td class="text-center small fw-medium" id="ej_r_vHourly">$0.00</td></tr>
                  <tr><td class="small text-gasq-muted">Weekly Cost</td><td class="text-center small fw-medium" id="ej_r_ihWeekly">$0.00</td><td class="text-center small fw-medium" id="ej_r_vWeekly">$0.00</td></tr>
                  <tr><td class="small text-gasq-muted">Monthly Cost</td><td class="text-center small fw-medium" id="ej_r_ihMonthly">$0.00</td><td class="text-center small fw-medium" id="ej_r_vMonthly">$0.00</td></tr>
                  <tr class="table-light fw-semibold"><td class="small">Annual Cost</td><td class="text-center small" id="ej_r_ihAnnual">$0.00</td><td class="text-center small" id="ej_r_vAnnual">$0.00</td></tr>
                </tbody>
              </table>
            </div>

            <div class="row g-3 mt-2">
              <div class="col-6">
                <div class="rounded p-3 text-center" style="background:rgba(34,197,94,0.1);border:1px solid rgba(34,197,94,0.3)">
                  <div class="small text-gasq-muted mb-1">Cost Savings</div>
                  <div class="fs-5 fw-bold text-success" id="ej_r_savings">$0.00</div>
                </div>
              </div>
              <div class="col-6">
                <div class="rounded p-3 text-center" style="background:rgba(6,45,121,0.08);border:1px solid rgba(6,45,121,0.2)">
                  <div class="small text-gasq-muted mb-1">ROI %</div>
                  <div class="fs-5 fw-bold text-primary" id="ej_r_roi">0.0%</div>
                </div>
              </div>
              <div class="col-6">
                <div class="rounded p-3 text-center" style="background:var(--gasq-muted-bg)">
                  <div class="small text-gasq-muted mb-1">Payback Period</div>
                  <div class="fs-5 fw-bold" id="ej_r_payback">0.0 mo</div>
                </div>
              </div>
              <div class="col-6">
                <div class="rounded p-3 text-center" style="background:var(--gasq-muted-bg)">
                  <div class="small text-gasq-muted mb-1">Dollar for Dollar Return</div>
                  <div class="fs-5 fw-bold" id="ej_r_dollar">$0.00</div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>

      {{-- ========== BILL RATE TAB ========== --}}
      <div class="tab-pane fade" id="tab-billrate">
        <div class="row g-4">
          <div class="col-lg-5">
            <h5 class="fw-semibold mb-3 d-flex align-items-center gap-2"><i class="fa fa-dollar-sign text-primary"></i> Bill Rate Calculator</h5>
            <div class="mb-3">
              <label class="form-label fw-medium">Base Pay Rate ($/hr)</label>
              <div class="input-group">
                <span class="input-group-text">$</span>
                <input type="number" id="br_basePay" class="form-control" value="" placeholder="e.g. 18.00" step="0.01" oninput="calcBillRate()">
              </div>
            </div>
            <div class="mb-3">
              <label class="form-label fw-medium">Overhead (%)</label>
              <input type="number" id="br_overhead" class="form-control" value="35" step="0.1" oninput="calcBillRate()">
            </div>
            <div class="mb-3">
              <label class="form-label fw-medium">Profit Margin (%)</label>
              <input type="number" id="br_profit" class="form-control" value="15" step="0.1" oninput="calcBillRate()">
            </div>
          </div>
          <div class="col-lg-7">
            <h5 class="fw-semibold mb-3">Results</h5>
            <div class="row g-3">
              <div class="col-6">
                <div class="rounded p-3 text-center" style="background:var(--gasq-muted-bg)">
                  <div class="small text-gasq-muted mb-1">Cost with Benefits</div>
                  <div class="fs-5 fw-bold" id="br_r_withBenefits">$0.00</div>
                </div>
              </div>
              <div class="col-6">
                <div class="rounded p-3 text-center" style="background:rgba(6,45,121,0.08);border:1px solid rgba(6,45,121,0.2)">
                  <div class="small text-gasq-muted mb-1">Final Bill Rate</div>
                  <div class="fs-5 fw-bold text-primary" id="br_r_billRate">$0.00</div>
                </div>
              </div>
              <div class="col-6">
                <div class="rounded p-3 text-center" style="background:var(--gasq-muted-bg)">
                  <div class="small text-gasq-muted mb-1">Weekly (40hr)</div>
                  <div class="fs-5 fw-bold" id="br_r_weekly">$0.00</div>
                </div>
              </div>
              <div class="col-6">
                <div class="rounded p-3 text-center" style="background:var(--gasq-muted-bg)">
                  <div class="small text-gasq-muted mb-1">Markup %</div>
                  <div class="fs-5 fw-bold" id="br_r_markup">0.0%</div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>

      {{-- ========== BILL RATE COMPONENTS TAB ========== --}}
      <div class="tab-pane fade" id="tab-components">
        <div class="row g-4">
          <div class="col-lg-6">
            <h5 class="fw-semibold mb-3 d-flex align-items-center gap-2"><i class="fa fa-chart-pie text-primary"></i> Bill Rate Components</h5>
            <p class="text-gasq-muted small mb-4">Enter each cost component to see the total bill rate and composition breakdown.</p>

            <div class="row g-3">
              <div class="col-6"><label class="form-label small fw-medium">Wages &amp; Benefits ($/hr)</label><input type="number" id="bc_wages" class="form-control form-control-sm" value="41.05" step="0.01" oninput="calcComponents()"></div>
              <div class="col-6"><label class="form-label small fw-medium">Taxes &amp; Insurance ($/hr)</label><input type="number" id="bc_taxes" class="form-control form-control-sm" value="10.96" step="0.01" oninput="calcComponents()"></div>
              <div class="col-6"><label class="form-label small fw-medium">Training Costs ($/hr)</label><input type="number" id="bc_training" class="form-control form-control-sm" value="2.02" step="0.01" oninput="calcComponents()"></div>
              <div class="col-6"><label class="form-label small fw-medium">Recruiting &amp; Screening ($/hr)</label><input type="number" id="bc_recruiting" class="form-control form-control-sm" value="0.09" step="0.01" oninput="calcComponents()"></div>
              <div class="col-6"><label class="form-label small fw-medium">Uniforms &amp; Equipment ($/hr)</label><input type="number" id="bc_uniforms" class="form-control form-control-sm" value="1.47" step="0.01" oninput="calcComponents()"></div>
              <div class="col-6"><label class="form-label small fw-medium">Overhead ($/hr)</label><input type="number" id="bc_overhead" class="form-control form-control-sm" value="0.50" step="0.01" oninput="calcComponents()"></div>
              <div class="col-6"><label class="form-label small fw-medium">Profit ($/hr)</label><input type="number" id="bc_profit" class="form-control form-control-sm" value="3.07" step="0.01" oninput="calcComponents()"></div>
            </div>
          </div>

          <div class="col-lg-6">
            <h5 class="fw-semibold mb-3">Breakdown</h5>
            <div class="rounded p-3 mb-3 text-center" style="background:var(--gasq-primary)">
              <div class="small text-white mb-1" style="opacity:.85">Total Bill Rate</div>
              <div class="display-5 fw-bold text-white" id="bc_r_total">$0.00</div>
              <div class="small text-white mt-1" style="opacity:.7">per hour</div>
            </div>
            <div id="bc_r_breakdown" class="d-flex flex-column gap-2"></div>
          </div>
        </div>
      </div>

      {{-- ========== CONTRACT SUMMARY TAB ========== --}}
      <div class="tab-pane fade" id="tab-summary">
        <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-4 d-print-none">
          <h5 class="fw-semibold mb-0 d-flex align-items-center gap-2"><i class="fa fa-file-text text-primary"></i> Contract Summary</h5>
          <div class="d-flex gap-2">
            <button class="btn btn-outline-secondary btn-sm" onclick="window.print()"><i class="fa fa-print me-1"></i> Print</button>
            <button class="btn btn-primary btn-sm" onclick="downloadReport()"><i class="fa fa-download me-1"></i> Download PDF</button>
          </div>
        </div>

        <div class="row g-3 mb-4">
          <div class="col-md-4">
            <label class="form-label small fw-medium">Vehicle/Pass-Through Billings ($/yr)</label>
            <input type="number" id="cs_vehPassthrough" class="form-control form-control-sm" value="12000" oninput="calcContractSummary()">
          </div>
          <div class="col-md-4">
            <label class="form-label small fw-medium">Vehicle/Pass-Through Costs ($/yr)</label>
            <input type="number" id="cs_vehCosts" class="form-control form-control-sm" value="163286" oninput="calcContractSummary()">
          </div>
          <div class="col-md-4">
            <label class="form-label small fw-medium">Working Capital Req ($)</label>
            <input type="number" id="cs_workingCapital" class="form-control form-control-sm" value="0" oninput="calcContractSummary()">
          </div>
        </div>

        <div class="table-responsive rounded mb-4" style="background:var(--gasq-muted-bg)">
          <table class="table table-sm align-middle mb-0">
            <tbody id="cs_table"></tbody>
          </table>
        </div>

        <div class="row g-3">
          <div class="col-md-4">
            <div class="rounded p-3 text-center" style="background:rgba(34,197,94,0.12);border:1px solid rgba(34,197,94,0.3)">
              <div class="small text-gasq-muted mb-1">Contributory Profit</div>
              <div class="fs-4 fw-bold text-success" id="cs_r_profit">$0.00</div>
            </div>
          </div>
          <div class="col-md-4">
            <div class="rounded p-3 text-center" style="background:rgba(6,45,121,0.08);border:1px solid rgba(6,45,121,0.2)">
              <div class="small text-gasq-muted mb-1">Profit as % of Revenue</div>
              <div class="fs-4 fw-bold text-primary" id="cs_r_profitPct">0.0%</div>
            </div>
          </div>
          <div class="col-md-4">
            <div class="rounded p-3 text-center" style="background:var(--gasq-muted-bg)">
              <div class="small text-gasq-muted mb-1">Profit per Hour</div>
              <div class="fs-4 fw-bold" id="cs_r_profitPerHr">$0.00</div>
            </div>
          </div>
        </div>
      </div>

      </div><!-- /tab-content -->
    </div><!-- /card-body -->
  </div><!-- /card -->

</div>
</div>
@endsection

@push('scripts')
<script>
function fmt(v, dec = 2) {
  return new Intl.NumberFormat('en-US', { style: 'currency', currency: 'USD', minimumFractionDigits: dec, maximumFractionDigits: dec }).format(v);
}
function fmtN(v, dec = 0) {
  return new Intl.NumberFormat('en-US', { minimumFractionDigits: dec, maximumFractionDigits: dec }).format(v);
}
function gNum(id) {
  const el = document.getElementById(id);
  const n = el ? parseFloat(el.value) : NaN;
  return Number.isFinite(n) ? n : 0;
}
function gVal(id) {
  const el = document.getElementById(id);
  return el ? (el.value ?? '') : '';
}
function setText(id, v) {
  const el = document.getElementById(id);
  if (el) el.textContent = v;
}

async function computeMainMenu() {
  const payload = {
    version: 'v24',
    scenario: {
      assumptions: {},
      scope: {
        hoursOfCoveragePerDay: gNum('mp_coverage'),
        daysOfCoveragePerWeek: 7,
        weeksOfCoverage: 52,
        staffPerShift: 1,
      },
      posts: [
        {
          postName: 'Post 1',
          positionTitle: gVal('sc_serviceType'),
          weeklyHours: gNum('sc_hours'),
          qtyRequired: Math.max(1, gNum('sc_guards')),
          wageMode: 'AUTO',
          manualPayWage: null,
          manualBillRate: null,
        },
      ],
      vehicle: {},
      uniform: {},
      meta: {
        locationState: gVal('sc_location'),
        serviceType: gVal('sc_serviceType'),
        hoursPerWeek: gNum('sc_hours'),
        guards: Math.max(1, gNum('sc_guards')),
        siteCoverageHoursPerDay: gNum('mp_coverage'),
        shiftPattern: gVal('mp_shift'),
        schedulingFactor: gNum('mp_factor'),
        employeeTrueHourlyCost: gNum('ej_employeeCost'),
        weeklyHoursPerformed: gNum('ej_weeklyHours'),
        weeksInYear: gNum('ej_weeksInYear') || 52,
        monthsInYear: gNum('ej_monthsInYear') || 12,
        basePayRate: gNum('br_basePay'),
        profitMarginPct: gNum('br_profit') || 15,
        components: {
          wages: gNum('bc_wages'),
          taxes: gNum('bc_taxes'),
          training: gNum('bc_training'),
          recruiting: gNum('bc_recruiting'),
          uniforms: gNum('bc_uniforms'),
          overhead: gNum('bc_overhead'),
          profit: gNum('bc_profit'),
        },
        vehiclePassthroughBillingsAnnual: gNum('cs_vehPassthrough'),
        vehiclePassthroughCostsAnnual: gNum('cs_vehCosts'),
        workingCapitalRequirement: gNum('cs_workingCapital'),
      },
    },
  };

  const res = await fetch('{{ route('backend.main-menu.compute') }}', {
    method: 'POST',
    headers: {
      'Content-Type': 'application/json',
      'X-CSRF-TOKEN': document.querySelector('meta[name=\"csrf-token\"]').getAttribute('content'),
      'Accept': 'application/json',
    },
    body: JSON.stringify(payload),
  });

  const data = await res.json();
  if (!res.ok || !data || !data.ok) {
    throw new Error((data && (data.message || data.error)) || 'Compute failed');
  }
  return data;
}

function renderMainMenu(data) {
  const tabs = data.tabs || {};

  const sc = tabs.securityCost || {};
  setText('sc_r_hourly', fmt(sc.hourlyRate || 0));
  setText('sc_r_weekly', fmt(sc.weeklyTotal || 0));
  setText('sc_r_monthly', fmt(sc.monthlyTotal || 0));
  setText('sc_r_annual', fmt(sc.annualTotal || 0));
  setText('sc_r_livingWage', fmt(sc.livingWageBase || 0) + '/hr');
  setText('sc_r_withOverhead', fmt(sc.withOverheadHourly || 0) + '/hr');

  const mp = tabs.manpowerHours || {};
  setText('mp_r_weekly', fmtN(mp.weeklyHours || 0, 1));
  setText('mp_r_monthly', fmtN(mp.monthlyHours || 0, 1));
  setText('mp_r_annual', fmtN(mp.annualHours || 0, 1));
  setText('mp_r_guards', mp.estimatedGuardsPartTime28hr || 0);
  setText('mp_r_multiplier', (mp.shiftMultiplierUsed || 0).toFixed(1) + 'x');

  const ej = tabs.economicJustification || {};
  setText('ej_r_ihHourly', fmt(ej.inHouseHourly || 0));
  setText('ej_r_vHourly', fmt(ej.vendorHourly || 0));
  setText('ej_r_ihWeekly', fmt(ej.inHouseWeekly || 0));
  setText('ej_r_vWeekly', fmt(ej.vendorWeekly || 0));
  setText('ej_r_ihMonthly', fmt(ej.inHouseMonthly || 0));
  setText('ej_r_vMonthly', fmt(ej.vendorMonthly || 0));
  setText('ej_r_ihAnnual', fmt(ej.inHouseAnnual || 0));
  setText('ej_r_vAnnual', fmt(ej.vendorAnnual || 0));
  setText('ej_r_savings', fmt(ej.costSavings || 0));
  setText('ej_r_roi', (ej.roiPct || 0).toFixed(1) + '%');
  setText('ej_r_payback', (ej.paybackMonths || 0).toFixed(1) + ' mo');
  setText('ej_r_dollar', fmt(ej.dollarForDollarReturn || 0));

  const br = tabs.billRate || {};
  setText('br_r_withBenefits', fmt(br.costWithBenefits || 0));
  setText('br_r_billRate', fmt(br.finalBillRate || 0));
  setText('br_r_weekly', fmt(br.weeklyAt40 || 0));
  setText('br_r_markup', (br.markupPct || 0).toFixed(1) + '%');

  const bc = tabs.billRateComponents || {};
  setText('bc_r_total', fmt(bc.totalBillRate || 0));
  const bd = document.getElementById('bc_r_breakdown');
  if (bd) {
    bd.innerHTML = (bc.components || []).filter(c => (c.value || 0) > 0).map(c => {
      return `<div>
        <div class="d-flex justify-content-between small mb-1">
          <div class="d-flex align-items-center gap-2">
            <span class="rounded-circle d-inline-block" style="width:10px;height:10px;background:var(--gasq-primary)"></span>
            <span class="text-gasq-muted">${c.label}</span>
          </div>
          <span class="fw-medium">${fmt(c.value || 0)}</span>
        </div>
        <div class="progress" style="height:6px">
          <div class="progress-bar" style="width:${(c.pct || 0).toFixed(1)}%;background:var(--gasq-primary)"></div>
        </div>
      </div>`;
    }).join('');
  }

  const cs = tabs.contractSummary || {};
  const tbody = document.getElementById('cs_table');
  if (tbody) {
    tbody.innerHTML = (cs.tableRows || []).map(r => {
      const bold = r.highlight ? 'fw-semibold table-active' : '';
      return `<tr class="${bold}"><td class="small py-1">${r.label}</td><td class="small py-1 text-end font-monospace">${fmt(r.value || 0)}</td></tr>`;
    }).join('');
  }
  setText('cs_r_profit', fmt(cs.contributoryProfit || 0));
  setText('cs_r_profitPct', (cs.profitPctOfRevenue || 0).toFixed(1) + '%');
  setText('cs_r_profitPerHr', fmt(cs.profitPerHour || 0));
}

let computeTimer = null;
function scheduleCompute() {
  clearTimeout(computeTimer);
  computeTimer = setTimeout(async () => {
    try {
      const data = await computeMainMenu();
      renderMainMenu(data);
    } catch (e) {
      // Keep UI stable; log for debugging
      console.error(e);
    }
  }, 150);
}

function downloadReport(){ window.print(); }
function emailReport(){ alert('Email functionality: connect to POST /api/spa/mail/calculator-pdf'); }

document.addEventListener('DOMContentLoaded', function () {
  // Recompute on any input change on this page.
  document.querySelectorAll('input,select,textarea').forEach(el => {
    el.addEventListener('input', scheduleCompute);
    el.addEventListener('change', scheduleCompute);
  });
  scheduleCompute();
});
</script>
@endpush

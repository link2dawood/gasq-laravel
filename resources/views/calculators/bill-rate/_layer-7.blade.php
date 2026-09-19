{{-- Layer 7 — Five-Year Pricing Plan (forecast only; never an extra charge). --}}
<div class="brb-panel mb-3">
  <div class="d-flex align-items-center gap-3 mb-2">
    <span class="brb-num">7</span>
    <div>
      <div class="fw-bold">Layer 7 — Five-Year Pricing Plan</div>
      <div class="small text-gasq-muted">Projects the active Year 1 pricing forward. It does not add a seventh cost to the hourly rate.</div>
    </div>
  </div>

  <div class="row g-3 mt-1">
    <div class="col-6 col-md-3">
      <label class="form-label small fw-medium">Contract Term (years)</label>
      <input type="number" class="form-control" min="1" max="10" step="1" data-path="forecast.termYears" data-rerender="1">
    </div>
    <div class="col-6 col-md-3">
      <label class="form-label small fw-medium">Escalation</label>
      <select class="form-select" data-kind="text" data-path="forecast.escalationMode" data-rerender="1">
        <option value="fixed">Fixed percentage</option>
        <option value="custom">Custom by year</option>
      </select>
    </div>
    <div class="col-6 col-md-3">
      <label class="form-label small fw-medium">Annual Wage Escalation (%)</label>
      <input type="number" class="form-control" min="0" step="0.01" data-path="forecast.wageEscPct" data-rerender="1">
    </div>
    <div class="col-6 col-md-3">
      <label class="form-label small fw-medium">Annual Rate Escalation (%)</label>
      <input type="number" class="form-control" min="0" step="0.01" data-path="forecast.rateEscPct" data-rerender="1">
    </div>
  </div>
</div>

<h6 class="fw-bold mb-2">Year-by-Year Controls</h6>
<div class="table-responsive border rounded-3 mb-2">
  <table class="table brb-table">
    <thead><tr><th>Year</th><th>Wage Esc. %</th><th>Rate Esc. %</th><th title="No escalation / price lock">Price Lock</th><th>Annual Hours</th><th>Rate Override</th><th>Status</th></tr></thead>
    <tbody id="brb_years_body"></tbody>
  </table>
</div>
<div class="small text-gasq-muted mb-4">Year 1 is the active Final Bill Rate. Each later year escalates from the prior year's rate, including any override. A price-locked year holds the rate flat (0%).</div>

<h6 class="fw-bold mb-2">Five-Year Dashboard</h6>
<div class="row g-2 mb-4">
  <div class="col-6 col-md-4"><div class="brb-stat"><div class="l"><span id="brb_f_term">5</span>-Year Contract Value</div><div class="v" id="brb_f_contract"></div></div></div>
  <div class="col-6 col-md-4"><div class="brb-stat"><div class="l">Capital Recovery (term)</div><div class="v" id="brb_f_cro"></div></div></div>
  <div class="col-6 col-md-4"><div class="brb-stat"><div class="l">Average Bill Rate</div><div class="v" id="brb_f_avg"></div></div></div>
  <div class="col-6 col-md-4"><div class="brb-stat"><div class="l">Total Protective Hours</div><div class="v" id="brb_f_hours"></div></div></div>
  <div class="col-6 col-md-4"><div class="brb-stat"><div class="l">Required Headcount</div><div class="v" id="brb_f_headcount"></div></div></div>
  <div class="col-6 col-md-4"><div class="brb-stat"><div class="l">Staffing Reserve Capacity</div><div class="v" id="brb_f_reserve"></div></div></div>
  <div class="col-6 col-md-4"><div class="brb-stat"><div class="l">Wage Growth</div><div class="v" id="brb_f_wage"></div></div></div>
  <div class="col-6 col-md-4"><div class="brb-stat"><div class="l">Rate Growth</div><div class="v" id="brb_f_rate"></div></div></div>
  <div class="col-12 col-md-4"><div class="brb-stat"><div class="l">Embedded Profit (term)</div><div class="v" id="brb_f_profit"></div></div></div>
</div>

<h6 class="fw-bold mb-2">Rates, Contract Value &amp; Capital Recovery</h6>
<div class="table-responsive border rounded-3 mb-4">
  <table class="table brb-table">
    <thead><tr><th>Year</th><th class="text-end">Weighted Wage</th><th class="text-end">Employer Cost</th><th class="text-end">Final Bill Rate</th><th class="text-end">Annual Hours</th><th class="text-end">Annual Contract</th><th class="text-end">Cost to Protect</th><th class="text-end">CRO / hr</th><th class="text-end">Annual CRO</th></tr></thead>
    <tbody id="brb_forecast_body"></tbody>
    <tfoot id="brb_forecast_foot"></tfoot>
  </table>
</div>

<h6 class="fw-bold mb-2">Staffing Forecast</h6>
<div class="table-responsive border rounded-3">
  <table class="table brb-table">
    <thead><tr><th>Year</th><th class="text-end">Protective Hours</th><th class="text-end">24/7 Posts</th><th class="text-end">Required FTE</th><th class="text-end">Headcount</th><th class="text-end">Capacity</th><th class="text-end">Reserve Hrs</th></tr></thead>
    <tbody id="brb_staffing_body"></tbody>
  </table>
</div>

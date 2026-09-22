{{-- Layer 2 — Employer Labor Burden (spec 4). --}}
<div class="d-flex gap-3 flex-wrap mb-3 small">
  <label class="form-check"><input class="form-check-input" type="radio" name="brb_burden_method" value="ratio" data-path="burden.method" data-rerender="1"> <span class="form-check-label">Wage-share ratio (approved 70% wage / 30% burden)</span></label>
  <label class="form-check"><input class="form-check-input" type="radio" name="brb_burden_method" value="detailed" data-path="burden.method" data-rerender="1"> <span class="form-check-label">Detailed line items</span></label>
</div>

<div id="brb_burden_ratio" class="row g-3 mb-3">
  <div class="col-sm-5">
    <label class="form-label small fw-medium">Wage Share of Employer Cost (%)</label>
    <input type="number" class="form-control" min="0" max="100" step="0.1" data-path="burden.wageSharePct">
    <div class="form-text">The burden share is the remainder: 70% wage gives 30% burden.</div>
  </div>
</div>

<div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-2">
  <h6 class="fw-bold mb-0">Employer Burden Allocation <span class="fw-normal small text-gasq-muted">(shares of the employer full-burden cost)</span></h6>
  <span class="brb-badge calc" id="brb_l2_badge" hidden></span>
</div>
<div class="table-responsive border rounded-3 mb-2">
  <table class="table brb-table">
    <thead><tr><th>Line Item</th><th>Method</th><th>Value</th><th class="text-end">Share of Employer Cost</th><th class="text-end">Hourly Allocation</th></tr></thead>
    <tbody id="brb_burden_body"></tbody>
    <tfoot><tr><td>Total Employer Burden</td><td></td><td></td><td class="calc" id="brb_l2_total_share"></td><td class="calc" id="brb_l2_total_hourly"></td></tr></tfoot>
  </table>
</div>
<div class="small text-gasq-muted mb-3" id="brb_burden_items">In detailed mode these percentages set the employer cost and must reconcile to the configured burden share. In ratio mode they are shown as an analysis of the calculated burden.</div>

<div class="brb-note warn mb-3">Cash health and welfare, locality pay, shift differential and position premiums entered in Layer 1 must not be entered again here.</div>

<div class="row g-2">
  <div class="col-md-3"><div class="brb-stat"><div class="l">Employer Full-Burden Cost</div><div class="v" data-out="layer2.fullBurden" data-fmt="money"></div></div></div>
  <div class="col-md-3"><div class="brb-stat"><div class="l">Incremental Burden</div><div class="v" data-out="layer2.incrementalBurden" data-fmt="money"></div></div></div>
  <div class="col-md-3"><div class="brb-stat"><div class="l">Wage Share</div><div class="v" data-out="layer2.wageShare" data-fmt="pct"></div></div></div>
  <div class="col-md-3"><div class="brb-stat"><div class="l">Burden Share</div><div class="v" data-out="layer2.burdenShare" data-fmt="pct"></div></div></div>
</div>
<details class="brb-how mt-2"><summary>How calculated</summary><div class="f" id="brb_how_l2"></div></details>

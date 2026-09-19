{{-- Layer 2 — Employer Labor Burden. --}}
<div class="d-flex gap-3 flex-wrap mb-3 small">
  <label class="form-check"><input class="form-check-input" type="radio" name="brb_burden_method" value="ratio" data-path="burden.method" data-rerender="1"> <span class="form-check-label">Wage-share / burden ratio (approved 70% / 30%)</span></label>
  <label class="form-check"><input class="form-check-input" type="radio" name="brb_burden_method" value="lineItems" data-path="burden.method" data-rerender="1"> <span class="form-check-label">Detailed line items</span></label>
</div>

<div id="brb_burden_ratio" class="row g-3 mb-3">
  <div class="col-sm-5">
    <label class="form-label small fw-medium">Wage Share of Full-Burden Cost (%)</label>
    <input type="number" class="form-control" min="0" max="100" step="0.1" data-path="burden.wageSharePct">
    <div class="form-text">Burden share is the remainder (70% wage → 30% burden).</div>
  </div>
</div>

<div id="brb_burden_items" hidden>
  <div class="table-responsive border rounded-3 mb-2">
    <table class="table brb-table">
      <thead><tr><th>Layer 2 Line Item</th><th>Method</th><th>Value</th><th class="text-end">$ / hr</th></tr></thead>
      <tbody id="brb_burden_body"></tbody>
    </table>
  </div>
</div>

<div class="brb-note warn mb-3">Cash Health &amp; Welfare, locality pay, shift differential, or other compensation already entered in Layer 1 must not be charged again here.</div>

<div class="row g-2">
  <div class="col-md-4"><div class="brb-stat"><div class="l">Employer Full-Burden Cost</div><div class="v" data-out="layer2.fullBurden" data-fmt="money"></div></div></div>
  <div class="col-md-4"><div class="brb-stat"><div class="l">Incremental Employer Burden</div><div class="v" data-out="layer2.incrementalBurden" data-fmt="money"></div></div></div>
  <div class="col-md-4"><div class="brb-stat"><div class="l">Wage Share</div><div class="v" data-out="layer2.wageShare" data-fmt="pct"></div></div></div>
</div>
<details class="brb-how mt-2"><summary>How calculated</summary><div class="f" id="brb_how_l2"></div></details>

{{-- Layer 3 — Workforce Maintenance and the approved baseline Final Bill Rate. --}}
<div class="row g-3 mb-3">
  <div class="col-sm-4">
    <label class="form-label small fw-medium">WMC Hours per Employee</label>
    <div class="form-control bg-light brb-mono" data-out="layer3.wmcHoursCalculated" data-fmt="int"></div>
    <div class="form-text">Paid hours − available protective hours · <span id="brb_wmc_status"></span></div>
  </div>
  <div class="col-sm-3">
    <label class="form-label small fw-medium">Authorised Override</label>
    <input type="number" class="form-control" min="0" step="1" data-path="workforce.wmcHoursOverride" data-nullable="1" placeholder="None">
  </div>
  <div class="col-sm-5">
    <label class="form-label small fw-medium">Override Reason</label>
    <input type="text" class="form-control" data-kind="text" data-path="workforce.wmcOverrideReason" placeholder="Required when overriding">
  </div>
</div>

<div class="row g-2 mb-3">
  <div class="col-6 col-md-3"><div class="brb-stat"><div class="l">WMC hrs / post</div><div class="v" data-out="layer3.wmcPerPost" data-fmt="dec2"></div></div></div>
  <div class="col-6 col-md-4"><div class="brb-stat"><div class="l">Workforce Maintenance Value</div><div class="v" data-out="layer3.wmcValue" data-fmt="money"></div></div></div>
  <div class="col-12 col-md-5"><div class="brb-stat" style="border-color:var(--gasq-primary)"><div class="l">Final Bill Rate (Layer 3 baseline)</div><div class="v"><span data-out="layer3.finalBillRate" data-fmt="money"></span>/hr</div></div></div>
</div>

<div class="brb-note mb-3" data-mode-only="approved">For the approved baseline and Five-Year Plan this is the Final Bill Rate. Layers 4-6 are <strong>not</strong> added on top of it unless you switch to Full Cost Build-Up Mode.</div>
<div class="brb-note mb-3" data-mode-only="buildup" hidden>In Full Cost Build-Up Mode this rate is the <strong>labor subtotal</strong>; Layers 4-6 are added to it.</div>

<div id="brb_rate_override" class="row g-3 mb-4">
  <div class="col-sm-4">
    <label class="form-label small fw-medium">Final Bill Rate Override ({{ $cur }}/hr)</label>
    <input type="number" class="form-control" min="0" step="0.01" data-path="finalRateOverride.value" data-nullable="1" placeholder="Use calculated">
  </div>
  <div class="col-sm-8">
    <label class="form-label small fw-medium">Override Reason</label>
    <input type="text" class="form-control" data-kind="text" data-path="finalRateOverride.reason" placeholder="Required when overriding">
  </div>
</div>

<div class="d-flex justify-content-between align-items-center mb-2">
  <h6 class="fw-bold mb-0">Workforce Maintenance Categories <span class="fw-normal small text-gasq-muted">(hours per employee)</span></h6>
  <span class="brb-badge calc" id="brb_wmc_badge"></span>
</div>
<div class="table-responsive border rounded-3 mb-2">
  <table class="table brb-table">
    <thead><tr><th>Category</th><th>Hours / Employee</th><th class="text-end">Share</th><th class="text-end">Rate Allocation</th><th>Cost Treatment</th></tr></thead>
    <tbody id="brb_wmc_body"></tbody>
    <tfoot><tr>
      <td>Total Workforce Maintenance</td>
      <td class="calc text-start" data-out="layer3.categoryTotal" data-fmt="dec2"></td>
      <td class="calc">100.00%</td>
      <td class="calc" id="brb_l3_total_alloc"></td>
      <td></td>
    </tr></tfoot>
  </table>
</div>
<div class="row g-3 mb-2">
  <div class="col-sm-8">
    <label class="form-label small fw-medium">Category Override Reason <span class="fw-normal text-gasq-muted">(required if the categories do not total the maintenance hours)</span></label>
    <input type="text" class="form-control" data-kind="text" data-path="wmcCategoryOverrideReason" placeholder="None">
  </div>
</div>
<details class="brb-how mt-2"><summary>How calculated</summary><div class="f" id="brb_how_l3"></div></details>

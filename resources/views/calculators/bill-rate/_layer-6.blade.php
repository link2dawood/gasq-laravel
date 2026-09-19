{{-- Layer 6 — Profit (margin gross-up). --}}
<div class="row g-3 mb-3">
  <div class="col-sm-4">
    <label class="form-label small fw-medium">Profit Margin (%)</label>
    <input type="number" class="form-control" min="0" max="99.99" step="0.1" data-path="profit.marginPct">
    <div class="form-text">Final price = pre-profit cost / (1 − margin).</div>
  </div>
</div>
<div class="row g-2">
  <div class="col-6 col-md-3"><div class="brb-stat"><div class="l">Margin divisor</div><div class="v" data-out="layer6.marginDivisor" data-fmt="dec4"></div></div></div>
  <div class="col-6 col-md-3"><div class="brb-stat"><div class="l" data-mode-only="approved">Max pre-profit</div><div class="l" data-mode-only="buildup" hidden>Pre-profit cost</div><div class="v" data-out="layer6.preProfit" data-fmt="money"></div></div></div>
  <div class="col-6 col-md-3"><div class="brb-stat"><div class="l">Profit / hr</div><div class="v" data-out="layer6.profitHourly" data-fmt="money"></div></div></div>
  <div class="col-6 col-md-3"><div class="brb-stat"><div class="l">Annual profit</div><div class="v" data-out="layer6.annualProfit" data-fmt="money0"></div></div></div>
</div>
<div class="brb-note mt-3" data-mode-only="approved">Profit is back-solved from the approved rate as an allocation check. It is not an extra charge.</div>
<details class="brb-how mt-2"><summary>How calculated</summary><div class="f" id="brb_how_l6"></div></details>

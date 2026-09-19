{{-- Buyer Cost to Protect benchmark and Capital Recovery — separate from the vendor layers. --}}
<div class="brb-panel mt-2">
  <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-1">
    <h6 class="fw-bold mb-0"><i class="fa fa-shield-halved text-primary me-1"></i> Buyer Cost to Protect &amp; Capital Recovery</h6>
    <span class="brb-badge embedded">Benchmark · not added to rate</span>
  </div>
  <p class="small text-gasq-muted mb-3">Cost to Protect is the buyer's in-house benchmark. It is compared with the Final Bill Rate, never added to it.</p>
  <div class="row g-2">
    <div class="col-6 col-md-3"><div class="brb-stat"><div class="l">Cost to Protect</div><div class="v"><span data-out="summary.costToProtect" data-fmt="money"></span></div></div></div>
    <div class="col-6 col-md-3"><div class="brb-stat"><div class="l">Final Bill Rate</div><div class="v" data-out="summary.finalBillRate" data-fmt="money"></div></div></div>
    <div class="col-6 col-md-3" data-show="cro"><div class="brb-stat"><div class="l">Capital Recovery / hr</div><div class="v" data-out="summary.croHourly" data-fmt="money"></div></div></div>
    <div class="col-6 col-md-3" data-show="cro"><div class="brb-stat"><div class="l">Annual Capital Recovery</div><div class="v" data-out="summary.annualCro" data-fmt="money0"></div></div></div>
    <div class="col-12 col-md-6" data-show="premium" hidden><div class="brb-stat"><div class="l">Premium Above Cost to Protect</div><div class="v"><span data-out="summary.premiumAboveCtp" data-fmt="money"></span>/hr · <span data-out="summary.annualPremiumAboveCtp" data-fmt="money0"></span>/yr</div></div></div>
  </div>
  <details class="brb-how mt-2"><summary>How calculated</summary><div class="f" id="brb_how_ctp"></div></details>
</div>

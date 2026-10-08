<div class="brb-panel mb-3">
  <div class="d-flex justify-content-between align-items-start flex-wrap gap-2 mb-3">
    <div>
      <h2 class="h5 fw-bold mb-1">Staffing &amp; Weighted Rate</h2>
      <p class="small text-gasq-muted mb-0">Model a three-position roster, solve a missing pay rate, or generate distinct rates around a target. Paid roster hours determine weighted pay; 24/7 post coverage hours determine the cost to deliver protection.</p>
    </div>
    <button type="button" class="btn btn-outline-secondary btn-sm d-print-none" id="wr_reset">Reset template</button>
  </div>
  <div class="row g-3">
    <div class="col-md-4">
      <label class="form-label small" for="wr_mode">Rate entry</label>
      <select class="form-select" id="wr_mode" data-path="weightedRates.mode" data-rerender="1">
        <option value="manual">Enter all three pay rates</option>
        <option value="solve">Solve one pay rate from a target</option>
        <option value="generate">Generate three distinct pay rates</option>
      </select>
    </div>
    <div class="col-md-4">
      <label class="form-label small" for="wr_hours">Weekly hours per person</label>
      <input class="form-control" type="number" id="wr_hours" min="0.01" max="168" step="any" data-path="weightedRates.weeklyHoursPerPerson">
    </div>
    <div class="col-md-4">
      <label class="form-label small" for="wr_weeks">Annual weeks</label>
      <input class="form-control" type="number" id="wr_weeks" min="0.01" max="53" step="any" data-path="weightedRates.annualWeeks">
    </div>
    <div class="col-md-4">
      <label class="form-label small" for="wr_posts">24/7 posts to cover</label>
      <input class="form-control" type="number" id="wr_posts" min="1" max="1000000" step="1" data-path="weightedRates.coveragePosts" aria-describedby="wr_posts_help">
      <div class="form-text" id="wr_posts_help">Coverage hours = posts × 24 × 7 × annual weeks.</div>
    </div>
    <div class="col-md-6">
      <label class="form-label small" for="wr_burden">Employer burden share of total employer cost (%)</label>
      <input class="form-control" type="number" id="wr_burden" min="0" max="99.99" step="0.01" data-path="weightedRates.employerCostSharePct" aria-describedby="wr_burden_help">
      <div class="form-text" id="wr_burden_help">At 30%, wages represent 70% of employer cost: pay ÷ 0.70.</div>
    </div>
    <div class="col-md-6">
      <label class="form-label small" for="wr_markup">Markup on employer cost (%)</label>
      <input class="form-control" type="number" id="wr_markup" min="0" max="10000" step="0.01" data-path="weightedRates.markupPct" aria-describedby="wr_markup_help">
      <div class="form-text" id="wr_markup_help">Bill rate per paid hour = employer cost × (1 + markup ÷ 100). The coverage-hour rate divides annual bill cost by post coverage hours.</div>
    </div>
    <div class="col-md-4" data-wr-target hidden>
      <label class="form-label small" for="wr_target_type">Target type</label>
      <select class="form-select" id="wr_target_type" data-path="weightedRates.targetType">
        <option value="pay">Weighted pay rate / paid hour</option>
        <option value="bill">Weighted bill rate / paid hour</option>
        <option value="coverageBill">Consolidated bill rate / coverage hour</option>
      </select>
    </div>
    <div class="col-md-4" data-wr-target hidden>
      <label class="form-label small" for="wr_target">Target rate ({{ $cur }}/hr)</label>
      <input class="form-control" type="number" id="wr_target" min="0" step="any" data-path="weightedRates.targetRate">
    </div>
    <div class="col-md-4" data-wr-solve hidden>
      <label class="form-label small" for="wr_solve">Position to solve</label>
      <select class="form-select" id="wr_solve" data-path="weightedRates.solveIndex" data-kind="number" data-rerender="1">
        @foreach($brbDefaults['weightedRates']['positions'] as $i => $position)
          <option value="{{ $i }}">{{ $position['name'] }}</option>
        @endforeach
      </select>
    </div>
    <div class="col-md-6" data-wr-generate hidden>
      <label class="form-label small" for="wr_senior_premium">Senior pay above officer ({{ $cur }}/hr)</label>
      <input class="form-control" type="number" id="wr_senior_premium" min="0.01" step="any" data-path="weightedRates.seniorPremium">
    </div>
    <div class="col-md-6" data-wr-generate hidden>
      <label class="form-label small" for="wr_supervisor_premium">Supervisor pay above officer ({{ $cur }}/hr)</label>
      <input class="form-control" type="number" id="wr_supervisor_premium" min="0.01" step="any" data-path="weightedRates.supervisorPremium">
    </div>
  </div>
  <p class="brb-note mt-3 mb-0">The supplied template uses a multiplier of 18 ÷ 7: a 30% employer-cost share and <strong>80% markup</strong>. With its original wages, a 50% markup gives <strong>{{ $cur }}18.31 per paid hour</strong>. Generated and solved rates retain full precision; displayed rates are rounded.</p>
</div>

<div id="wr_errors" class="alert alert-warning" role="alert" hidden></div>
<div id="wr_warnings" class="alert alert-warning" role="status" hidden></div>
<div class="table-responsive border rounded-3 mb-3">
  <table class="table brb-table" id="wr_table">
    <caption class="px-2 small">Annual bill costs calculated using unrounded rates. Rounded role totals may differ from the consolidated total by a cent.</caption>
    <thead><tr>
      <th scope="col">Position</th><th scope="col">Count</th><th scope="col">Pay/hr</th><th scope="col">Combined hourly wages</th>
      <th scope="col">Time share</th><th scope="col">Wage share</th><th scope="col">Weekly paid hours</th><th scope="col">Annual role paid hours</th>
      <th scope="col">Employer cost / paid hr</th><th scope="col">Protection bill rate / paid hr</th><th scope="col">Annual bill cost</th>
    </tr></thead>
    <tbody>
      @foreach($brbDefaults['weightedRates']['positions'] as $i => $position)
        <tr data-wr-row="{{ $i }}">
          <th scope="row">{{ $position['name'] }}</th>
          <td><input class="form-control" type="number" min="0" step="1" data-path="weightedRates.positions.{{ $i }}.count" aria-label="{{ $position['name'] }} count"></td>
          <td>
            <input class="form-control" type="number" min="0" step="any" data-path="weightedRates.positions.{{ $i }}.payRate" data-wr-pay-input="{{ $i }}" aria-label="{{ $position['name'] }} pay per hour">
            <span class="brb-mono" data-wr-pay-output="{{ $i }}" data-wr-col="payRate" data-fmt="money" hidden></span>
          </td>
          @foreach(['combinedHourlyWages' => 'money', 'timeShare' => 'pct', 'wageShare' => 'pct', 'weeklyHours' => 'dec2', 'annualHours' => 'dec2', 'employerCost' => 'money', 'billRate' => 'money', 'annualBillCost' => 'money'] as $column => $format)
            <td class="calc" data-wr-col="{{ $column }}" data-fmt="{{ $format }}">—</td>
          @endforeach
        </tr>
      @endforeach
    </tbody>
    <tfoot><tr>
      <th scope="row">Total / weighted rate</th>
      @foreach(['count' => 'int', 'weightedPayRate' => 'money', 'combinedHourlyWages' => 'money', 'timeShare' => 'pct', 'wageShare' => 'pct', 'weeklyHours' => 'dec2', 'annualHours' => 'dec2', 'employerCost' => 'money', 'weightedBillRate' => 'money', 'annualBillCost' => 'money'] as $column => $format)
        <td class="calc" data-out="weightedRates.totals.{{ $column }}" data-fmt="{{ $format }}">—</td>
      @endforeach
    </tr></tfoot>
  </table>
</div>

<div id="wr_results" hidden aria-live="polite">
  <div class="row g-3 mb-3">
    <div class="col-md-4"><div class="brb-stat"><div class="l">Weighted pay / paid hour</div><div class="v"><span data-out="weightedRates.totals.weightedPayRate" data-fmt="money"></span>/hr</div></div></div>
    <div class="col-md-4"><div class="brb-stat"><div class="l">Consolidated bill / coverage hour</div><div class="v"><span data-out="weightedRates.totals.coverageBillRate" data-fmt="money"></span>/hr</div></div></div>
    <div class="col-md-4"><div class="brb-stat"><div class="l">Annual bill cost · unrounded rates</div><div class="v" data-out="weightedRates.totals.annualBillCost" data-fmt="money"></div></div></div>
  </div>
  <div class="brb-panel brb-panel-muted mb-3">
    <h3 class="h6 fw-bold">Paid staffing vs. post coverage</h3>
    <div class="brb-kv"><span>Annual paid roster hours</span><span data-out="weightedRates.totals.annualPaidHours" data-fmt="dec2"></span></div>
    <div class="brb-kv"><span>Annual 24/7 coverage hours</span><span data-out="weightedRates.totals.annualCoverageHours" data-fmt="dec2"></span></div>
    <div class="brb-kv"><span>Paid hours per coverage hour</span><span data-out="weightedRates.totals.paidToCoverageRatio" data-fmt="dec4"></span></div>
    <div class="brb-kv"><span>Weighted bill rate per paid hour</span><span data-out="weightedRates.totals.weightedBillRate" data-fmt="money"></span></div>
    <div class="brb-kv"><span>Employer cost per coverage hour</span><span data-out="weightedRates.totals.employerCostPerCoverageHour" data-fmt="money"></span></div>
    <p class="small text-gasq-muted mt-2 mb-0">Paid hours include the full roster. Coverage hours measure posts that must be staffed. The extra paid hours are not additional post coverage; scheduling and relief requirements determine how they are used.</p>
  </div>
  <div class="brb-note mb-3" id="wr_rounding"></div>
  <details class="brb-how"><summary>How calculated</summary><div class="f" id="wr_formula"></div></details>
</div>
<p class="small text-gasq-muted mt-3">These are mathematical modeling pay rates; validate pay assumptions for the actual project before using them.</p>

{{-- Layer 1 — Direct Labor: coverage, manpower, positions, weighted wage. --}}
<h6 class="fw-bold mb-2">Coverage &amp; Staffing Inputs</h6>
<div class="d-flex gap-3 flex-wrap mb-3 small">
  <label class="form-check"><input class="form-check-input" type="radio" name="brb_cov_mode" value="annual" data-path="coverage.mode" data-rerender="1"> <span class="form-check-label">Enter annual protective hours</span></label>
  <label class="form-check"><input class="form-check-input" type="radio" name="brb_cov_mode" value="schedule" data-path="coverage.mode" data-rerender="1"> <span class="form-check-label">Calculate from shift schedule</span></label>
</div>
<div class="row g-3 mb-3">
  <div class="col-sm-6 col-lg-4">
    <label class="form-label small fw-medium">Annual Protective Hours Required</label>
    <input type="number" class="form-control" min="0" step="1" data-path="coverage.annualHours">
  </div>
  <div class="col-sm-6 col-lg-4">
    <label class="form-label small fw-medium">Weeks per Year</label>
    <input type="number" class="form-control" min="0" step="1" data-path="coverage.weeksPerYear">
  </div>
  <div class="col-sm-6 col-lg-4">
    <label class="form-label small fw-medium">Paid Hours per Employee</label>
    <input type="number" class="form-control" min="0" step="1" data-path="workforce.paidHoursPerEmployee">
  </div>
  <div class="col-sm-6 col-lg-4">
    <label class="form-label small fw-medium">Available Protective Hours per Employee</label>
    <input type="number" class="form-control" min="0" step="1" data-path="workforce.availableHoursPerEmployee">
  </div>
</div>
<div class="row g-3 mb-3" id="brb_schedule_fields" hidden>
  <div class="col-6 col-lg"><label class="form-label small fw-medium">Shift Length (hrs)</label><input type="number" class="form-control" min="0" step="0.5" data-path="coverage.shiftLength"></div>
  <div class="col-6 col-lg"><label class="form-label small fw-medium">Guards per Shift</label><input type="number" class="form-control" min="0" step="1" data-path="coverage.guardsPerShift"></div>
  <div class="col-6 col-lg"><label class="form-label small fw-medium">Shifts per Day</label><input type="number" class="form-control" min="0" step="1" data-path="coverage.shiftsPerDay"></div>
  <div class="col-6 col-lg"><label class="form-label small fw-medium">Days per Week</label><input type="number" class="form-control" min="0" max="7" step="1" data-path="coverage.daysPerWeek"></div>
  <div class="col-12 small text-gasq-muted">Schedule total: <span class="fw-semibold brb-mono" data-out="coverage.scheduleHours" data-fmt="int"></span> hours/year, used as the annual protective hours.</div>
</div>

<h6 class="fw-bold mb-2 mt-4">Manpower Required per Post</h6>
<div class="row g-2 mb-2">
  <div class="col-6 col-md-3"><div class="brb-stat"><div class="l">Available hrs / week</div><div class="v" data-out="staffing.availablePerWeek" data-fmt="dec2"></div></div></div>
  <div class="col-6 col-md-3"><div class="brb-stat"><div class="l">Manpower per 24/7 post</div><div class="v" data-out="staffing.manpowerPerPost" data-fmt="dec2"></div></div></div>
  <div class="col-6 col-md-3"><div class="brb-stat"><div class="l">Equivalent 24/7 posts</div><div class="v" data-out="staffing.equivalentPosts" data-fmt="dec3"></div></div></div>
  <div class="col-6 col-md-3"><div class="brb-stat"><div class="l">Required FTE</div><div class="v" data-out="staffing.requiredFte" data-fmt="dec4"></div></div></div>
  <div class="col-6 col-md-4"><div class="brb-stat"><div class="l">Required headcount</div><div class="v" data-out="staffing.roundedHeadcount" data-fmt="int"></div></div></div>
  <div class="col-6 col-md-4"><div class="brb-stat"><div class="l">Available protective capacity</div><div class="v" data-out="staffing.availableCapacity" data-fmt="int"></div></div></div>
  <div class="col-12 col-md-4"><div class="brb-stat"><div class="l">Staffing Reserve Capacity</div><div class="v"><span data-out="staffing.staffingReserveCapacity" data-fmt="int"></span> <span style="font-size:.85rem">hrs</span></div></div></div>
</div>
<div class="brb-note mb-4">Staffing Reserve Capacity is a staffing buffer caused by whole-person rounding. These hours are <strong>not</strong> automatically billable.</div>

<div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-2">
  <h6 class="fw-bold mb-0">Position Table</h6>
  <div class="dropdown d-print-none">
    <button class="btn btn-sm btn-outline-primary dropdown-toggle" type="button" data-bs-toggle="dropdown"><i class="fa fa-plus me-1"></i> Add Position</button>
    <ul class="dropdown-menu dropdown-menu-end" style="max-width:360px">
      @foreach([
        ['Dedicated Field Supervisor', 'Supervision'], ['Dedicated Account Manager', 'Dedicated Support'],
        ['Dispatcher / Control Room Operator', 'Specialized'], ['Mobile Patrol Officer', 'Core Coverage'],
        ['Armed Officer', 'Specialized'], ['Screening / Access-Control Officer', 'Specialized'],
        ['Dedicated Training Officer', 'Dedicated Support'], ['Custom Position', 'Custom'],
      ] as $i => [$name, $type])
        <li><button type="button" class="dropdown-item small" data-add-position="{{ $i }}">{{ $name }} <span class="text-gasq-muted">· {{ $type }}</span></button></li>
      @endforeach
    </ul>
  </div>
</div>
<div class="table-responsive border rounded-3 mb-2">
  <table class="table brb-table">
    <thead>
      <tr>
        <th>Position</th><th>Type</th><th>Employees</th><th>Weekly Paid Hrs</th><th>Base Wage</th>
        <th>Locality</th><th>H&amp;W Cash</th><th>Shift Diff.</th><th>Premium</th>
        <th class="text-end">Total Cash Wage</th><th class="text-end">Annual Paid Hrs</th><th class="text-end">Annual Payroll</th><th class="text-end">Wage Share</th><th class="text-end">Position Bill Rate</th><th class="text-end">Protective Capacity</th>
        <th title="Counts toward protective coverage">Covers?</th><th>Status</th><th>Notes</th><th></th>
      </tr>
    </thead>
    <tbody id="brb_positions_body"></tbody>
    <tfoot>
      <tr>
        <td>Total / Weighted Average</td><td></td>
        <td class="calc text-start" data-out="layer1.totalEmployees" data-fmt="int"></td>
        <td class="calc text-start" data-out="layer1.totalWeeklyPaidHours" data-fmt="int"></td>
        <td></td><td></td><td></td><td></td><td></td>
        <td class="calc" data-out="layer1.weightedWage" data-fmt="money"></td>
        <td class="calc" data-out="layer1.totalAnnualPaidHours" data-fmt="int"></td>
        <td class="calc" data-out="layer1.totalPayroll" data-fmt="money0"></td>
        <td class="calc">100.00%</td>
        <td class="calc" data-out="summary.finalBillRate" data-fmt="money"></td>
        <td class="calc" data-out="layer1.positionCapacity" data-fmt="int"></td>
        <td></td><td></td><td></td><td></td>
      </tr>
    </tfoot>
  </table>
</div>
<div class="small text-gasq-muted mb-4">Core Coverage and Relief normally count toward protective coverage; Dedicated Support normally does not. Supervision, Specialized and Custom are your call based on scope.</div>

<div class="brb-note warn mb-3">Cash health and welfare, locality pay, shift differential and position premiums belong to the position that earns them. Entering the same amount again as an employer benefit in Layer 2 is a duplicate cost.</div>

<div class="brb-panel brb-panel-muted d-flex justify-content-between align-items-center">
  <span class="fw-semibold">Weighted Baseline Wage</span>
  <span class="fs-5 fw-bold text-primary brb-mono" data-out="layer1.weightedWage" data-fmt="money"></span>
</div>
<details class="brb-how mt-2"><summary>How calculated</summary><div class="f" id="brb_how_l1"></div></details>

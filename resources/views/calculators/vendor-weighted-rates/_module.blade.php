@php
  $vwrPresets = \App\Services\VendorWeightedRateEngine::presets();
  $vwrClasses = \App\Services\VendorWeightedRateEngine::CLASSIFICATIONS;
@endphp
<details class="card gasq-card mt-4" id="vendor-weighted-rates">
  <summary class="card-header p-3 fw-semibold">Vendor Weighted Rates</summary>
  <div class="card-body p-3 p-md-4">
    <p class="text-gasq-muted small">Enter your staffing mix and current bill rates or actual wages. The weighted rates use annual staffing hours. Applying them uses the main calculator’s billable service hours for the project estimate.</p>
    <div class="row g-3 mb-3">
      <div class="col-md-6">
        <label class="form-label" for="vwr_mode">Calculation mode</label>
        <select class="form-select" id="vwr_mode" data-vwr-path="mode">
          <option value="current">Current Bill Rate Analysis</option>
          <option value="wage_based">GASQ Wage-Based Pricing</option>
        </select>
        <div class="small text-danger" data-vwr-error="mode"></div>
      </div>
      <div class="col-md-3">
        <label class="form-label" for="vwr_hours">Weekly hours per employee</label>
        <input class="form-control" type="number" min="0" max="168" step="any" id="vwr_hours" data-vwr-path="weeklyHoursPerEmployee">
        <div class="small text-danger" data-vwr-error="weeklyHoursPerEmployee"></div>
      </div>
      <div class="col-md-3">
        <label class="form-label" for="vwr_weeks">Number of weeks</label>
        <input class="form-control" type="number" min="0" max="53" step="any" id="vwr_weeks" data-vwr-path="weeksPerYear">
        <div class="small text-danger" data-vwr-error="weeksPerYear"></div>
      </div>
    </div>
    <div class="rounded border p-3 small mb-3" id="vwr_mode_help"></div>
    <p class="small text-gasq-muted">GASQ 6:1 model rate = (pay wage ÷ 0.70) × 3,744 ÷ 2,080 = pay wage × 18 ÷ 7. These model constants stay fixed when the roster or its hours change.</p>
    <div class="d-flex flex-wrap align-items-center gap-2 mb-3 d-print-none">
      <label for="vwr_preset" class="small">Preset</label>
      <select class="form-select form-select-sm w-auto" id="vwr_preset"><option value="sample">Attached sample · 145 employees</option><option value="six_person">GASQ 6-person team</option></select>
      <button class="btn btn-outline-secondary btn-sm" type="button" id="vwr_load_preset">Load preset</button>
      <button class="btn btn-outline-primary btn-sm" type="button" id="vwr_add">Add role</button>
    </div>
    <div class="small text-danger mb-2" data-vwr-error="roles"></div>
    <div class="table-responsive border rounded mb-3">
      <table class="table table-sm align-middle mb-0 vwr-table">
        <caption class="px-2 small">Full precision is used for calculations. Currency and percentages are rounded only for display. Monthly staffing hours are annual role hours ÷ 12.</caption>
        <thead><tr>
          <th>Position / classification</th><th>Headcount</th><th>Pay rate/hr</th><th>Combined hourly wages</th><th>Wage share of bill rate</th><th>Weekly hours</th><th>Monthly hours</th><th>Annual role hours</th><th>Bill rate/hr</th><th>Total annual bill cost</th><th></th>
        </tr></thead>
        <tbody id="vwr_roles"></tbody>
        <tfoot><tr class="fw-semibold">
          <th scope="row">Total / weighted rate</th>
          @foreach(['headcount' => 'number', 'weightedPayWage' => 'money', 'combinedHourlyWages' => 'money', 'overallWageShare' => 'percent', 'weeklyHours' => 'number', 'monthlyHours' => 'decimal', 'annualRoleHours' => 'number', 'weightedBillRate' => 'money', 'annualBillCost' => 'money'] as $field => $format)
            <td class="text-end text-nowrap" data-vwr-total="{{ $field }}" data-format="{{ $format }}">—</td>
          @endforeach
          <td></td>
        </tr></tfoot>
      </table>
    </div>
    <div class="row g-3 mb-3" id="vwr_totals">
      <div class="col-md-4"><div class="small text-gasq-muted">Weighted pay wage</div><strong data-vwr-total="weightedPayWage" data-format="money">—</strong><span>/hr</span><div class="small" id="vwr_wage_source"></div></div>
      <div class="col-md-4"><div class="small text-gasq-muted">Weighted bill rate</div><strong data-vwr-total="weightedBillRate" data-format="money">—</strong><span>/hr</span><div class="small" id="vwr_rate_source"></div></div>
      <div class="col-md-4"><div class="small text-gasq-muted">Total annual wages</div><strong data-vwr-total="annualWages" data-format="money">—</strong></div>
    </div>
    <div class="d-flex flex-wrap gap-2 d-print-none mb-3">
      <button type="button" class="btn btn-outline-primary" id="vwr_save">Recalculate &amp; Save Estimate</button>
      <button type="button" class="btn btn-primary" id="vwr_review" disabled>Review Results to Apply</button>
    </div>
    <div id="vwr_status" class="small mb-3" role="status" aria-live="polite"></div>
    <form id="vwr_signature_form" class="border rounded p-3 mb-3 d-print-none">
      <h3 class="h6 fw-semibold">Electronically sign this report</h3>
      <p class="small text-gasq-muted">Review the saved rates and staffing schedule, then sign as the vendor. Your signature is saved with this report version. Changes to its rates, staffing, or report details require a new signature.</p>
      <div class="row g-3">
        <div class="col-md-4"><label for="vwr_signer_name" class="form-label small">Signer’s full name</label><input id="vwr_signer_name" class="form-control" maxlength="120" required value="{{ auth()->user()?->name }}" autocomplete="name"></div>
        <div class="col-md-4"><label for="vwr_signer_title" class="form-label small">Title (optional)</label><input id="vwr_signer_title" class="form-control" maxlength="120" autocomplete="organization-title"></div>
        <div class="col-md-4"><label for="vwr_signature_method" class="form-label small">Signature method</label><select id="vwr_signature_method" class="form-select"><option value="typed">Type my name</option><option value="drawn">Draw my signature</option></select></div>
      </div>
      <div id="vwr_draw_area" class="mt-3" hidden>
        <label for="vwr_signature_canvas" class="form-label small">Draw with your mouse, finger, or pen</label>
        <canvas id="vwr_signature_canvas" width="720" height="180" class="border rounded d-block bg-white" style="width:100%;max-width:720px;height:160px;touch-action:none" aria-label="Draw your signature"></canvas>
        <button id="vwr_signature_clear" type="button" class="btn btn-link btn-sm ps-0">Clear drawing</button>
      </div>
      <label class="d-flex align-items-start gap-2 small my-3"><input id="vwr_signature_consent" type="checkbox" class="form-check-input mt-0" required><span>{{ \App\Services\VendorReportSigningService::CONSENT }}</span></label>
      <button id="vwr_signature_submit" class="btn btn-outline-primary" type="submit" disabled>Sign Saved Report</button>
      <div id="vwr_signature_status" class="small mt-2" role="status" aria-live="polite"></div>
    </form>
    <form id="vwr_report_form" class="border rounded p-3 mb-3 d-print-none">
      <h3 class="h6 fw-semibold">Vendor Weighted Rate PDF</h3>
      <p class="small text-gasq-muted">Download the saved draft as a private dashboard and staffing schedule. A password is required to open the PDF.</p>
      <div class="d-flex flex-wrap align-items-end gap-2">
        <div>
          <label for="vwr_pdf_password" class="form-label small">PDF open password</label>
          <input id="vwr_pdf_password" class="form-control" type="password" minlength="8" maxlength="32" required autocomplete="new-password" aria-describedby="vwr_report_status" placeholder="8–32 letters, numbers or symbols">
        </div>
        <button id="vwr_report_download" class="btn btn-outline-primary" type="submit" disabled>Download Protected PDF</button>
      </div>
      <div id="vwr_report_status" class="small mt-2" role="status" aria-live="polite"></div>
    </form>
    <div class="border rounded p-3 mb-3" id="vwr_preview" hidden>
      <h3 class="h6 fw-semibold">Values to transfer to the main calculator</h3>
      <dl class="row small mb-2">
        <dt class="col-sm-6">Consolidated weighted pay wage</dt><dd class="col-sm-6" id="vwr_transfer_pay"></dd>
        <dt class="col-sm-6">Consolidated weighted bill rate</dt><dd class="col-sm-6" id="vwr_transfer_bill"></dd>
        <dt class="col-sm-6">Rate source / calculation mode</dt><dd class="col-sm-6" id="vwr_transfer_source"></dd>
        <dt class="col-sm-6">Wage source</dt><dd class="col-sm-6" id="vwr_transfer_wage_source"></dd>
        <dt class="col-sm-6">Main calculator billable service hours</dt><dd class="col-sm-6" id="vwr_transfer_service"></dd>
        <dt class="col-sm-6">Roster payroll hours</dt><dd class="col-sm-6" id="vwr_transfer_payroll"></dd>
        <dt class="col-sm-6">Project annual estimate</dt><dd class="col-sm-6" id="vwr_transfer_project"></dd>
        <dt class="col-sm-6">Buyer baseline wage</dt><dd class="col-sm-6" id="vwr_transfer_baseline"></dd>
      </dl>
      <ul class="small" id="vwr_transfer_staffing"></ul>
      <p class="small text-gasq-muted">These rates become separate vendor comparison inputs. The buyer’s baseline wage and the main calculator’s service-hour assumptions are preserved.</p>
      <button type="button" class="btn btn-primary d-print-none" id="vwr_apply">Apply to Main Calculator</button>
    </div>
    <p class="small text-gasq-muted mb-0">Private vendor working data. Applying rates does not submit or reveal pricing to a buyer. Buyer-facing price disclosure uses the platform’s existing sealed-price reveal process.</p>
  </div>
</details>
@push('styles')
<style>
  .vwr-table { font-size:.82rem; font-variant-numeric:tabular-nums; }
  .vwr-table th { vertical-align:top; }
  .vwr-table input { min-width:85px; }
  .vwr-table .vwr-classification { min-width:230px; }
  .vwr-table .vwr-wage { min-width:190px; }
  .vwr-table .form-control, .vwr-table .form-select { font-size:.82rem; }
  .vwr-table [data-vwr-calculated] { white-space:nowrap; text-align:right; }
  #vwr_preview[hidden] { display:none !important; }
</style>
@endpush
@include('calculators.vendor-weighted-rates._script')

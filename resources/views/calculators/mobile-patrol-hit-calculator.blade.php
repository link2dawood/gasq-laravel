@extends('layouts.app')
@section('title', 'Mobile Patrol Hit Service Calculator')
@section('header_variant', 'dashboard')

@push('styles')
<style>
  .mphc2-page {
    min-height: calc(100vh - 5rem);
    background:
      radial-gradient(circle at top left, rgba(6, 45, 121, 0.07), transparent 28rem),
      linear-gradient(180deg, #f3f5f8 0%, #eef2f7 100%);
  }
  .mphc2-hero {
    border-radius: 1.75rem;
    background: #050505;
    color: #fff;
    padding: 2rem;
    box-shadow: 0 24px 48px -24px rgba(0,0,0,.45);
  }
  .mphc2-card {
    border-radius: 1.5rem;
    background: rgba(255,255,255,.96);
    border: 1px solid rgba(15,23,42,.06);
    box-shadow: 0 18px 36px -28px rgba(15,23,42,.28);
  }
  .mphc2-section-title {
    font-size: 1.1rem;
    font-weight: 700;
    letter-spacing: -.02em;
    color: #111827;
  }
  .mphc2-sub-title {
    font-size: .85rem;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: .07em;
    color: #062d79;
    margin: 1.25rem 0 .65rem;
  }
  .mphc2-sub-title:first-child { margin-top: 0; }
  .mphc2-label {
    display: block;
    font-size: .85rem;
    font-weight: 600;
    color: #1f2937;
    margin-bottom: .4rem;
  }
  .mphc2-input {
    width: 100%;
    border-radius: .85rem;
    border: 1px solid #d1d5db;
    background: #fff;
    padding: .75rem 1rem;
    font-size: .92rem;
    color: #111827;
    transition: border-color .15s ease, box-shadow .15s ease;
  }
  .mphc2-input:focus {
    outline: none;
    border-color: #062d79;
    box-shadow: 0 0 0 3px rgba(6,45,121,.1);
  }
  .mphc2-input[readonly] {
    background: #f3f5f8;
    color: #6b7280;
    cursor: default;
  }
  .mphc2-hint { margin-top: .35rem; font-size: .75rem; color: #6b7280; }
  .mphc2-divider { border: none; border-top: 1px solid rgba(15,23,42,.08); margin: 1.25rem 0; }

  /* Stat cards */
  .mphc2-results { display: grid; gap: .75rem; }
  .mphc2-stat-grid { display: grid; grid-template-columns: 1fr 1fr; gap: .75rem; }
  .mphc2-stat {
    border-radius: 1.1rem;
    padding: 1rem 1.1rem;
    background: #f7f8fa;
  }
  .mphc2-stat-label { font-size: .72rem; text-transform: uppercase; letter-spacing: .08em; color: #6b7280; font-weight: 700; margin-bottom: .35rem; }
  .mphc2-stat-value { font-size: 1.45rem; font-weight: 700; color: #062d79; font-variant-numeric: tabular-nums; }
  .mphc2-stat-sub { font-size: .75rem; color: #6b7280; margin-top: .2rem; }

  .mphc2-stat-hero {
    border-radius: 1.25rem;
    padding: 1.25rem 1.5rem;
    background: #062d79;
    color: #fff;
    text-align: center;
  }
  .mphc2-stat-hero .mphc2-stat-label { color: rgba(255,255,255,.65); }
  .mphc2-stat-hero .mphc2-stat-value { font-size: 2.4rem; color: #fff; }
  .mphc2-stat-hero .mphc2-stat-sub { color: rgba(255,255,255,.6); }

  /* Result rows */
  .mphc2-row {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 1rem;
    border-radius: .85rem;
    padding: .75rem 1rem;
    background: #f7f8fa;
    font-size: .88rem;
    color: #111827;
  }
  .mphc2-row strong { font-variant-numeric: tabular-nums; }
  .mphc2-row-dark { background: #050505; color: #fff; }
  .mphc2-row-dark strong { color: #fff; }
  .mphc2-row-success { background: #ecfdf3; color: #065f46; }
  .mphc2-row-success strong { color: #065f46; }

  /* Actions */
  .mphc2-actions { display: flex; flex-wrap: wrap; gap: .75rem; align-items: center; }
  .mphc2-email { min-width: 200px; flex: 1 1 220px; }

  @media (max-width: 575.98px) {
    .mphc2-stat-grid { grid-template-columns: 1fr; }
  }
</style>
@endpush

@section('content')
<div class="mphc2-page py-4 py-md-5 px-3 px-md-4">
  <div class="container-xl">

    <div class="d-flex align-items-start gap-3 mb-4">
      <a href="{{ url()->previous() }}" class="mp24-back" style="width:2.75rem;height:2.75rem;border-radius:.9rem;display:inline-flex;align-items:center;justify-content:center;border:1px solid rgba(15,23,42,.12);background:rgba(255,255,255,.8);color:#062d79;text-decoration:none;">
        <i class="fa fa-arrow-left"></i>
      </a>
      <div>
        <div class="text-uppercase small fw-semibold text-gasq-muted" style="letter-spacing:.08em">Patrol Calculator</div>
        <h1 class="h2 fw-bold mb-0">Mobile Patrol Hit Service Calculator</h1>
      </div>
    </div>

    <div class="mphc2-hero mb-4">
      <h2 class="h3 fw-semibold mb-2">Hit Service Pricing Calculator</h2>
      <p class="mb-0 text-white-50" style="max-width:56rem;">
        Price patrol checks by service type and frequency. Enter minutes per check, labor rates, overhead, G&amp;A, and markup
        to generate a per-check sell price with weekly, monthly, and annual revenue totals.
      </p>
    </div>

    <div id="mphc2Status" class="alert d-none mb-4" role="alert"></div>

    {{-- Contact / Site Info --}}
    <div class="mphc2-card p-4 p-md-5 mb-4">
      <h2 class="mphc2-section-title mb-1">Client &amp; Site Information</h2>
      <p class="text-gasq-muted small mb-4">This information will appear on the PDF report.</p>
      <div class="row g-3">
        <div class="col-sm-6 col-lg-4">
          <label class="mphc2-label" for="mphc2-siteName">Site Name</label>
          <input id="mphc2-siteName" type="text" class="mphc2-input" placeholder="Demo Property">
        </div>
        <div class="col-sm-6 col-lg-4">
          <label class="mphc2-label" for="mphc2-contactName">Contact Name</label>
          <input id="mphc2-contactName" type="text" class="mphc2-input" placeholder="Full name">
        </div>
        <div class="col-sm-6 col-lg-4">
          <label class="mphc2-label" for="mphc2-companyName">Company Name</label>
          <input id="mphc2-companyName" type="text" class="mphc2-input" placeholder="Company">
        </div>
        <div class="col-sm-6 col-lg-4">
          <label class="mphc2-label" for="mphc2-contactAddress">Address</label>
          <input id="mphc2-contactAddress" type="text" class="mphc2-input" placeholder="Street address, city, state">
        </div>
        <div class="col-sm-6 col-lg-4">
          <label class="mphc2-label" for="mphc2-contactEmail">Email</label>
          <input id="mphc2-contactEmail" type="email" class="mphc2-input" placeholder="Email address">
        </div>
        <div class="col-sm-6 col-lg-4">
          <label class="mphc2-label" for="mphc2-contactPhone">Phone</label>
          <input id="mphc2-contactPhone" type="tel" class="mphc2-input" placeholder="Phone number">
        </div>
      </div>
    </div>

    <div class="row g-4">

      {{-- Left: Inputs --}}
      <div class="col-lg-5">
        <div class="mphc2-card p-4 p-md-5 h-100">
          <h2 class="mphc2-section-title mb-4">Inputs</h2>

          <div class="mphc2-sub-title">Service Volume</div>
          <div class="row g-3">
            <div class="col-sm-6">
              <label class="mphc2-label" for="mphc2-serviceType">Service Type</label>
              <select id="mphc2-serviceType" class="mphc2-input">
                <option>Drive By Check</option>
                <option selected>Park &amp; Walk Thru Check</option>
                <option>Park &amp; Make Contact Check</option>
              </select>
            </div>
            <div class="col-sm-6">
              <label class="mphc2-label" for="mphc2-weeklyChecks">Weekly Checks</label>
              <select id="mphc2-weeklyChecks" class="mphc2-input">
                <option value="21">21</option>
                <option value="28">28</option>
                <option value="42">42</option>
                <option value="56">56</option>
                <option value="84" selected>84</option>
              </select>
              <div class="mphc2-hint">Checks per week at this site</div>
            </div>
            <div class="col-sm-6">
              <label class="mphc2-label" for="mphc2-weeksPerYear">Weeks Per Year</label>
              <input id="mphc2-weeksPerYear" type="number" class="mphc2-input" value="52" min="1" max="52" step="1">
              <div class="mphc2-hint">1–52 weeks</div>
            </div>
            <div class="col-sm-6">
              <label class="mphc2-label">Checks Per Day</label>
              <input type="text" class="mphc2-input" id="mphc2-checksPerDay" readonly>
              <div class="mphc2-hint">Auto: weekly ÷ 7</div>
            </div>
            <div class="col-sm-6">
              <label class="mphc2-label" for="mphc2-minutesOnSite">Avg Minutes On Site</label>
              <input id="mphc2-minutesOnSite" type="number" class="mphc2-input" value="15" min="0" step="1">
              <div class="mphc2-hint">Per check</div>
            </div>
            <div class="col-sm-6">
              <label class="mphc2-label" for="mphc2-minutesTravel">Avg Travel / Dispatch Min</label>
              <input id="mphc2-minutesTravel" type="number" class="mphc2-input" value="10" min="0" step="1">
              <div class="mphc2-hint">Per check</div>
            </div>
            <div class="col-sm-6">
              <label class="mphc2-label">Total Billable Minutes</label>
              <input type="text" class="mphc2-input" id="mphc2-totalMinutes" readonly>
              <div class="mphc2-hint">Auto: on-site + travel</div>
            </div>
          </div>

          <hr class="mphc2-divider">
          <div class="mphc2-sub-title">Labor &amp; Operating Costs</div>
          <div class="row g-3">
            <div class="col-sm-6">
              <label class="mphc2-label" for="mphc2-officerPayRate">Officer Pay Rate / Hr</label>
              <input id="mphc2-officerPayRate" type="number" class="mphc2-input" value="25" min="0" step="0.01">
            </div>
            <div class="col-sm-6">
              <label class="mphc2-label" for="mphc2-payrollBurdenPct">Payroll Burden %</label>
              <input id="mphc2-payrollBurdenPct" type="number" class="mphc2-input" value="30" min="0" step="0.1">
              <div class="mphc2-hint">Example: 30 for 30%</div>
            </div>
            <div class="col-sm-6">
              <label class="mphc2-label">Fully Burdened Rate / Hr</label>
              <input type="text" class="mphc2-input" id="mphc2-burdenedRate" readonly>
              <div class="mphc2-hint">Auto: pay × (1 + burden)</div>
            </div>
            <div class="col-sm-6">
              <label class="mphc2-label" for="mphc2-vehicleCostPerHour">Vehicle Cost / Hr</label>
              <input id="mphc2-vehicleCostPerHour" type="number" class="mphc2-input" value="6.50" min="0" step="0.01">
            </div>
            <div class="col-sm-6">
              <label class="mphc2-label" for="mphc2-fuelCostPerHour">Fuel Cost / Hr</label>
              <input id="mphc2-fuelCostPerHour" type="number" class="mphc2-input" value="2.25" min="0" step="0.01">
            </div>
            <div class="col-sm-6">
              <label class="mphc2-label" for="mphc2-equipmentCostPerHour">Equipment / Tech / Hr</label>
              <input id="mphc2-equipmentCostPerHour" type="number" class="mphc2-input" value="1.75" min="0" step="0.01">
            </div>
            <div class="col-sm-6">
              <label class="mphc2-label" for="mphc2-supervisionCostPerHour">Supervision / Admin / Hr</label>
              <input id="mphc2-supervisionCostPerHour" type="number" class="mphc2-input" value="4.00" min="0" step="0.01">
            </div>
            <div class="col-sm-6">
              <label class="mphc2-label">Total Operating Cost / Hr</label>
              <input type="text" class="mphc2-input" id="mphc2-totalOpCost" readonly>
              <div class="mphc2-hint">Auto: all cost categories</div>
            </div>
          </div>

          <hr class="mphc2-divider">
          <div class="mphc2-sub-title">Pricing</div>
          <div class="row g-3">
            <div class="col-sm-6">
              <label class="mphc2-label" for="mphc2-overheadPct">Overhead %</label>
              <input id="mphc2-overheadPct" type="number" class="mphc2-input" value="10" min="0" step="0.1">
            </div>
            <div class="col-sm-6">
              <label class="mphc2-label" for="mphc2-gaPct">G&amp;A %</label>
              <input id="mphc2-gaPct" type="number" class="mphc2-input" value="10" min="0" step="0.1">
            </div>
            <div class="col-sm-6">
              <label class="mphc2-label" for="mphc2-profitPct">Profit / Markup %</label>
              <input id="mphc2-profitPct" type="number" class="mphc2-input" value="10" min="0" step="0.1">
            </div>
            <div class="col-sm-6">
              <label class="mphc2-label" for="mphc2-minimumCharge">Minimum Charge / Check</label>
              <input id="mphc2-minimumCharge" type="number" class="mphc2-input" value="23" min="0" step="0.01">
            </div>
            <div class="col-sm-12">
              <label class="mphc2-label" for="mphc2-addOnCost">Add-On Cost / Check</label>
              <input id="mphc2-addOnCost" type="number" class="mphc2-input" value="0" min="0" step="0.01">
              <div class="mphc2-hint">Optional fixed add-on per patrol check</div>
            </div>
          </div>
        </div>
      </div>

      {{-- Right: Results --}}
      <div class="col-lg-7">
        <div class="d-grid gap-4">

          {{-- Hero stat --}}
          <div class="mphc2-stat-hero">
            <div class="mphc2-stat-label">Final Sell Price Per Check</div>
            <div class="mphc2-stat-value" id="mphc2-out-pricePerCheck">$0.00</div>
            <div class="mphc2-stat-sub">Includes overhead, G&amp;A, profit &amp; minimum floor</div>
          </div>

          {{-- Volume stats --}}
          <div class="mphc2-stat-grid">
            <div class="mphc2-stat">
              <div class="mphc2-stat-label">Total Weekly Checks</div>
              <div class="mphc2-stat-value" id="mphc2-out-weeklyChecks">0</div>
            </div>
            <div class="mphc2-stat">
              <div class="mphc2-stat-label">Total Monthly Checks</div>
              <div class="mphc2-stat-value" id="mphc2-out-monthlyChecks">0</div>
            </div>
            <div class="mphc2-stat">
              <div class="mphc2-stat-label">Total Annual Checks</div>
              <div class="mphc2-stat-value" id="mphc2-out-annualChecks">0</div>
            </div>
            <div class="mphc2-stat">
              <div class="mphc2-stat-label">Profit Margin</div>
              <div class="mphc2-stat-value" id="mphc2-out-profitMargin">0%</div>
            </div>
          </div>

          {{-- Revenue breakdown --}}
          <div class="mphc2-card p-4">
            <h3 class="mphc2-section-title mb-3">Revenue Summary</h3>
            <div class="mphc2-results">
              <div class="mphc2-row"><span>Total Weekly Revenue</span><strong id="mphc2-out-weeklyRevenue">$0.00</strong></div>
              <div class="mphc2-row"><span>Total Monthly Revenue</span><strong id="mphc2-out-monthlyRevenue">$0.00</strong></div>
              <div class="mphc2-row mphc2-row-dark"><span>Total Annual Revenue</span><strong id="mphc2-out-annualRevenue">$0.00</strong></div>
            </div>
          </div>

          {{-- Cost breakdown --}}
          <div class="mphc2-card p-4">
            <h3 class="mphc2-section-title mb-3">Per-Check Cost Breakdown</h3>
            <div class="mphc2-results">
              <div class="mphc2-row"><span>Hours Per Check</span><strong id="mphc2-out-hoursPerCheck">0.00</strong></div>
              <div class="mphc2-row"><span>Base Cost Per Check</span><strong id="mphc2-out-baseCost">$0.00</strong></div>
              <div class="mphc2-row"><span>Overhead Per Check</span><strong id="mphc2-out-overhead">$0.00</strong></div>
              <div class="mphc2-row"><span>G&amp;A Per Check</span><strong id="mphc2-out-ga">$0.00</strong></div>
              <div class="mphc2-row"><span>Subtotal Cost Per Check</span><strong id="mphc2-out-subtotal">$0.00</strong></div>
              <div class="mphc2-row"><span>Add-On Per Check</span><strong id="mphc2-out-addon">$0.00</strong></div>
              <div class="mphc2-row"><span>Pre-Markup Cost Per Check</span><strong id="mphc2-out-preMkup">$0.00</strong></div>
              <div class="mphc2-row"><span>Profit Amount Per Check</span><strong id="mphc2-out-profitAmt">$0.00</strong></div>
              <div class="mphc2-row mphc2-row-success"><span>Final Sell Price Per Check</span><strong id="mphc2-out-finalPrice">$0.00</strong></div>
            </div>
          </div>

          {{-- Report Actions --}}
          <div class="mphc2-card p-4 p-md-5">
            <div class="d-flex justify-content-between align-items-center flex-wrap gap-3 mb-3">
              <div>
                <h3 class="mphc2-section-title mb-1">Report Actions</h3>
                <p class="text-gasq-muted small mb-0">Download the PDF or email this report to your customer.</p>
              </div>
            </div>
            <div class="mphc2-actions">
              <button type="button" class="btn btn-outline-secondary" id="mphc2ResetBtn">
                <i class="fa fa-rotate me-1"></i> Reset
              </button>
              <button type="button" class="btn btn-outline-primary" id="mphc2DownloadBtn">
                <i class="fa fa-download me-1"></i> Download PDF
              </button>
              <input
                type="email"
                id="mphc2Email"
                class="form-control mphc2-email"
                placeholder="Email address"
                value="{{ auth()->user()?->email }}"
              >
              <button type="button" class="btn btn-primary" id="mphc2EmailBtn">
                <i class="fa fa-envelope me-1"></i> Email Report
              </button>
            </div>
          </div>

        </div>
      </div>
    </div>

    <form id="mphc2EmailForm" action="{{ route('reports.email') }}" method="POST" class="d-none">
      @csrf
      <input type="hidden" name="type" value="mobile-patrol-hit-calculator">
      <input type="hidden" name="email" id="mphc2EmailTarget" value="{{ auth()->user()?->email }}">
    </form>

  </div>
</div>
@endsection

@push('scripts')
<script>
const MPHC2_STORAGE_KEY = 'gasq.mobilePatrolHits.v2';
const MPHC2_REPORT_TYPE = 'mobile-patrol-hit-calculator';
const MPHC2_REPORT_DOWNLOAD_URL = @json(route('reports.download', ['type' => 'mobile-patrol-hit-calculator']));
const MPHC2_REPORT_PAYLOAD_URL = @json(route('backend.report-payload.store'));

const MPHC2_DEFAULTS = {
  siteName: '',
  serviceType: 'Park & Walk Thru Check',
  weeklyChecks: 84,
  weeksPerYear: 52,
  minutesOnSite: 15,
  minutesTravel: 10,
  officerPayRate: 25,
  payrollBurdenPct: 30,
  vehicleCostPerHour: 6.50,
  fuelCostPerHour: 2.25,
  equipmentCostPerHour: 1.75,
  supervisionCostPerHour: 4.00,
  overheadPct: 10,
  gaPct: 10,
  profitPct: 10,
  minimumCharge: 23,
  addOnCost: 0,
  contactName: '',
  companyName: '',
  contactAddress: '',
  contactEmail: '',
  contactPhone: '',
};

let mphc2Inputs = { ...MPHC2_DEFAULTS };
let mphc2PersistTimer = null;

function $id(id) { return document.getElementById(id); }

const fmt = new Intl.NumberFormat('en-US', { style: 'currency', currency: 'USD', minimumFractionDigits: 2, maximumFractionDigits: 2 });
const fmtN = (v, d = 2) => new Intl.NumberFormat('en-US', { minimumFractionDigits: d, maximumFractionDigits: d }).format(v || 0);
const money = (v) => fmt.format(v || 0);
const pct = (v) => fmtN(v * 100, 2) + '%';

function mphc2Read() {
  const textIds = ['siteName','serviceType','contactName','companyName','contactAddress','contactEmail','contactPhone'];
  const numIds = ['weeklyChecks','weeksPerYear','minutesOnSite','minutesTravel','officerPayRate','payrollBurdenPct',
    'vehicleCostPerHour','fuelCostPerHour','equipmentCostPerHour','supervisionCostPerHour',
    'overheadPct','gaPct','profitPct','minimumCharge','addOnCost'];

  textIds.forEach(k => {
    const el = $id(`mphc2-${k}`);
    if (el) mphc2Inputs[k] = el.value;
  });
  numIds.forEach(k => {
    const el = $id(`mphc2-${k}`);
    if (el) mphc2Inputs[k] = parseFloat(el.value) || 0;
  });
}

function mphc2Calculate() {
  const i = mphc2Inputs;
  const weeklyChecks = Math.max(0, parseInt(i.weeklyChecks) || 0);
  const weeksPerYear = Math.max(1, i.weeksPerYear);
  const checksPerDay = weeklyChecks / 7;
  const totalMinutes = (i.minutesOnSite || 0) + (i.minutesTravel || 0);
  const hoursPerCheck = totalMinutes / 60;

  const burdenedRate = i.officerPayRate * (1 + (i.payrollBurdenPct / 100));
  const totalOpCostPerHour = burdenedRate + i.vehicleCostPerHour + i.fuelCostPerHour + i.equipmentCostPerHour + i.supervisionCostPerHour;

  const baseCostPerCheck = totalOpCostPerHour * hoursPerCheck;
  const overheadPerCheck = baseCostPerCheck * (i.overheadPct / 100);
  const gaPerCheck = baseCostPerCheck * (i.gaPct / 100);
  const subtotalCostPerCheck = baseCostPerCheck + overheadPerCheck + gaPerCheck;
  const preMkupCostPerCheck = subtotalCostPerCheck + (i.addOnCost || 0);
  const profitAmountPerCheck = preMkupCostPerCheck * (i.profitPct / 100);
  const calculatedPricePerCheck = preMkupCostPerCheck + profitAmountPerCheck;
  const finalPricePerCheck = Math.max(calculatedPricePerCheck, i.minimumCharge || 0);

  const monthlyChecks = weeklyChecks * (weeksPerYear / 12);
  const annualChecks = weeklyChecks * weeksPerYear;
  const weeklyRevenue = finalPricePerCheck * weeklyChecks;
  const monthlyRevenue = finalPricePerCheck * monthlyChecks;
  const annualRevenue = finalPricePerCheck * annualChecks;

  const grossProfitPerCheck = finalPricePerCheck - preMkupCostPerCheck;
  const profitMarginPct = finalPricePerCheck > 0 ? grossProfitPerCheck / finalPricePerCheck : 0;

  return {
    checksPerDay, totalMinutes, hoursPerCheck, burdenedRate, totalOpCostPerHour,
    baseCostPerCheck, overheadPerCheck, gaPerCheck, subtotalCostPerCheck,
    preMkupCostPerCheck, profitAmountPerCheck, calculatedPricePerCheck, finalPricePerCheck,
    weeklyChecks, monthlyChecks, annualChecks,
    weeklyRevenue, monthlyRevenue, annualRevenue,
    grossProfitPerCheck, profitMarginPct,
  };
}

function mphc2Render() {
  const r = mphc2Calculate();

  // Auto-computed readonly fields
  const setVal = (id, v) => { const el = $id(id); if (el) el.value = v; };
  setVal('mphc2-checksPerDay', fmtN(r.checksPerDay, 2));
  setVal('mphc2-totalMinutes', fmtN(r.totalMinutes, 0) + ' min');
  setVal('mphc2-burdenedRate', money(r.burdenedRate) + '/hr');
  setVal('mphc2-totalOpCost', money(r.totalOpCostPerHour) + '/hr');

  // Stat cards
  const set = (id, v) => { const el = $id(id); if (el) el.textContent = v; };
  set('mphc2-out-pricePerCheck', money(r.finalPricePerCheck));
  set('mphc2-out-weeklyChecks', fmtN(r.weeklyChecks, 0));
  set('mphc2-out-monthlyChecks', fmtN(r.monthlyChecks, 0));
  set('mphc2-out-annualChecks', fmtN(r.annualChecks, 0));
  set('mphc2-out-profitMargin', pct(r.profitMarginPct));

  // Revenue
  set('mphc2-out-weeklyRevenue', money(r.weeklyRevenue));
  set('mphc2-out-monthlyRevenue', money(r.monthlyRevenue));
  set('mphc2-out-annualRevenue', money(r.annualRevenue));

  // Breakdown
  set('mphc2-out-hoursPerCheck', fmtN(r.hoursPerCheck, 4) + ' hrs');
  set('mphc2-out-baseCost', money(r.baseCostPerCheck));
  set('mphc2-out-overhead', money(r.overheadPerCheck));
  set('mphc2-out-ga', money(r.gaPerCheck));
  set('mphc2-out-subtotal', money(r.subtotalCostPerCheck));
  set('mphc2-out-addon', money(mphc2Inputs.addOnCost));
  set('mphc2-out-preMkup', money(r.preMkupCostPerCheck));
  set('mphc2-out-profitAmt', money(r.profitAmountPerCheck));
  set('mphc2-out-finalPrice', money(r.finalPricePerCheck));

  return r;
}

function mphc2PersistLocal() {
  try { localStorage.setItem(MPHC2_STORAGE_KEY, JSON.stringify(mphc2Inputs)); } catch {}
}

function mphc2LoadLocal() {
  try {
    const raw = localStorage.getItem(MPHC2_STORAGE_KEY);
    return raw ? JSON.parse(raw) : null;
  } catch { return null; }
}

function mphc2HydrateInputs() {
  const textIds = ['siteName','serviceType','contactName','companyName','contactAddress','contactEmail','contactPhone'];
  textIds.forEach(k => {
    const el = $id(`mphc2-${k}`);
    if (el && mphc2Inputs[k] !== undefined) {
      if (el.tagName === 'SELECT') {
        [...el.options].forEach(o => { o.selected = o.value === mphc2Inputs[k] || o.text === mphc2Inputs[k]; });
      } else {
        el.value = mphc2Inputs[k];
      }
    }
  });
  const numIds = ['weeklyChecks','weeksPerYear','minutesOnSite','minutesTravel','officerPayRate','payrollBurdenPct',
    'vehicleCostPerHour','fuelCostPerHour','equipmentCostPerHour','supervisionCostPerHour',
    'overheadPct','gaPct','profitPct','minimumCharge','addOnCost'];
  numIds.forEach(k => {
    const el = $id(`mphc2-${k}`);
    if (el && mphc2Inputs[k] !== undefined) {
      if (el.tagName === 'SELECT') {
        [...el.options].forEach(o => { o.selected = parseFloat(o.value) === mphc2Inputs[k]; });
      } else {
        el.value = mphc2Inputs[k];
      }
    }
  });
}

function mphc2ScenarioPayload(results) {
  return {
    meta: { ...mphc2Inputs },
    contact: {
      siteName: mphc2Inputs.siteName,
      contactName: mphc2Inputs.contactName,
      companyName: mphc2Inputs.companyName,
      contactAddress: mphc2Inputs.contactAddress,
      contactEmail: mphc2Inputs.contactEmail,
      contactPhone: mphc2Inputs.contactPhone,
    },
  };
}

function mphc2ResultPayload(results) {
  return { kpis: results };
}

function mphc2ShowStatus(type, msg) {
  const el = $id('mphc2Status');
  if (!el) return;
  if (!msg) { el.className = 'alert d-none mb-4'; el.textContent = ''; return; }
  el.className = `alert alert-${type} mb-4`;
  el.textContent = msg;
}

async function mphc2PersistReportPayload(results) {
  const res = await fetch(MPHC2_REPORT_PAYLOAD_URL, {
    method: 'POST',
    headers: {
      'Content-Type': 'application/json',
      'Accept': 'application/json',
      'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
    },
    body: JSON.stringify({
      type: MPHC2_REPORT_TYPE,
      scenario: mphc2ScenarioPayload(results),
      result: mphc2ResultPayload(results),
    }),
  });
  if (!res.ok) throw new Error('Could not prepare the report right now.');
}

async function mphc2Download() {
  try {
    const results = mphc2Calculate();
    await mphc2PersistReportPayload(results);
    window.location.href = MPHC2_REPORT_DOWNLOAD_URL;
  } catch (e) {
    mphc2ShowStatus('danger', e.message || 'Unable to prepare the PDF right now.');
  }
}

async function mphc2EmailReport() {
  const email = $id('mphc2Email').value.trim();
  if (!email) { mphc2ShowStatus('warning', 'Enter an email address before sending.'); return; }
  try {
    const results = mphc2Calculate();
    await mphc2PersistReportPayload(results);
    $id('mphc2EmailTarget').value = email;
    $id('mphc2EmailForm').submit();
  } catch (e) {
    mphc2ShowStatus('danger', e.message || 'Unable to send the report right now.');
  }
}

function mphc2Reset() {
  mphc2Inputs = { ...MPHC2_DEFAULTS };
  mphc2HydrateInputs();
  mphc2Render();
  mphc2PersistLocal();
  mphc2ShowStatus('success', 'Calculator reset to defaults.');
}

document.addEventListener('DOMContentLoaded', () => {
  const saved = mphc2LoadLocal();
  mphc2Inputs = { ...MPHC2_DEFAULTS, ...(saved || {}) };
  mphc2HydrateInputs();
  mphc2Render();

  document.querySelectorAll('[id^="mphc2-"]:not([readonly])').forEach(el => {
    el.addEventListener('input', () => {
      mphc2Read();
      mphc2Render();
      mphc2PersistLocal();
      clearTimeout(mphc2PersistTimer);
      mphc2PersistTimer = setTimeout(async () => {
        try { await mphc2PersistReportPayload(mphc2Calculate()); } catch {}
      }, 400);
    });
  });

  $id('mphc2ResetBtn').addEventListener('click', mphc2Reset);
  $id('mphc2DownloadBtn').addEventListener('click', mphc2Download);
  $id('mphc2EmailBtn').addEventListener('click', mphc2EmailReport);
});
</script>
@endpush

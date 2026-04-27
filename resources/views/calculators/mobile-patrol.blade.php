@extends('layouts.app')
@section('title', 'Mobile Patrol Calculator')
@section('header_variant', 'dashboard')

@push('styles')
<style>
  .mp24-page {
    min-height: calc(100vh - 5rem);
    background:
      radial-gradient(circle at top left, rgba(6, 45, 121, 0.08), transparent 28rem),
      linear-gradient(180deg, #f3f5f8 0%, #eef2f7 100%);
  }

  .mp24-hero {
    border-radius: 1.75rem;
    background: #050505;
    color: #fff;
    padding: 2rem;
    box-shadow: 0 24px 48px -24px rgba(0, 0, 0, 0.45);
  }

  .mp24-back {
    width: 2.75rem;
    height: 2.75rem;
    border-radius: .9rem;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    border: 1px solid rgba(15, 23, 42, 0.12);
    background: rgba(255, 255, 255, 0.8);
    color: var(--gasq-primary, #062d79);
    text-decoration: none;
  }

  .mp24-card {
    border-radius: 1.5rem;
    background: rgba(255, 255, 255, 0.96);
    border: 1px solid rgba(15, 23, 42, 0.06);
    box-shadow: 0 18px 36px -28px rgba(15, 23, 42, 0.28);
  }

  .mp24-section-title {
    font-size: 1.25rem;
    font-weight: 700;
    letter-spacing: -0.02em;
    color: #111827;
  }

  .mp24-label {
    display: block;
    font-size: .9rem;
    font-weight: 600;
    color: #1f2937;
    margin-bottom: .55rem;
  }

  .mp24-input {
    width: 100%;
    border-radius: 1rem;
    border: 1px solid #d1d5db;
    background: #fff;
    padding: .9rem 1rem;
    font-size: .95rem;
    color: #111827;
    transition: border-color .15s ease, box-shadow .15s ease;
  }

  .mp24-input:focus {
    outline: none;
    border-color: #111827;
    box-shadow: 0 0 0 4px rgba(17, 24, 39, 0.08);
  }

  .mp24-hint {
    margin-top: .45rem;
    font-size: .78rem;
    color: #6b7280;
  }

  .mp24-results {
    display: grid;
    gap: .8rem;
  }

  .mp24-row {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 1rem;
    border-radius: 1rem;
    padding: .95rem 1rem;
    background: #f7f8fa;
    color: #111827;
    font-size: .92rem;
  }

  .mp24-row strong {
    font-variant-numeric: tabular-nums;
    font-size: .98rem;
  }

  .mp24-row-dark {
    background: #050505;
    color: #fff;
    padding-top: 1rem;
    padding-bottom: 1rem;
  }

  .mp24-row-dark strong {
    color: #fff;
  }

  .mp24-row-success {
    background: #ecfdf3;
    color: #065f46;
    padding-top: 1rem;
    padding-bottom: 1rem;
  }

  .mp24-row-success strong {
    color: #065f46;
    font-size: 1.08rem;
  }

  .mp24-note-list p {
    margin-bottom: .55rem;
    font-size: .92rem;
    color: #374151;
  }

  .mp24-actions {
    display: flex;
    flex-wrap: wrap;
    gap: .75rem;
    align-items: center;
  }

  .mp24-email {
    min-width: 220px;
    flex: 1 1 240px;
  }

  @media (max-width: 991.98px) {
    .mp24-hero {
      padding: 1.5rem;
    }

    .mp24-row {
      flex-direction: column;
      align-items: flex-start;
    }
  }
</style>
@endpush

@section('content')
<div class="mp24-page py-4 py-md-5 px-3 px-md-4">
  <div class="container-xl">
    <div class="d-flex align-items-start gap-3 mb-4">
      <a href="{{ url()->previous() }}" class="mp24-back">
        <i class="fa fa-arrow-left"></i>
      </a>
      <div>
        <div class="text-uppercase small fw-semibold text-gasq-muted" style="letter-spacing:.08em">Patrol Calculator</div>
        <h1 class="h2 fw-bold mb-0">Mobile Patrol Calculator</h1>
      </div>
    </div>

    <div class="mp24-hero mb-4">
      <h2 class="h3 fw-semibold mb-2">Mobile Patrol Calculator</h2>
      <p class="mb-0 text-white-50" style="max-width: 56rem;">
        Built from your Mobile Patrolman Formula. Enter your pay rate, patrol assumptions, vehicle cost inputs,
        and target return on sales to generate an estimated hourly bill rate.
      </p>
    </div>

    <div id="mobilePatrolStatus" class="alert d-none mb-4" role="alert"></div>

    <div class="mp24-card p-4 p-md-5 mb-4">
      <h2 class="mp24-section-title mb-1">Contact Information</h2>
      <p class="text-gasq-muted small mb-4">This information will appear on the PDF report.</p>
      <div class="row g-3" id="mp24ContactGrid"></div>
    </div>

    <div class="row g-4">
      <div class="col-lg-6">
        <div class="mp24-card p-4 p-md-5 h-100">
          <h2 class="mp24-section-title mb-4">Inputs</h2>
          <div class="row g-3" id="mp24InputGrid"></div>
        </div>
      </div>

      <div class="col-lg-6">
        <div class="d-grid gap-4">
          <div class="mp24-card p-4 p-md-5">
            <h2 class="mp24-section-title mb-4">Calculated Results</h2>
            <div class="mp24-results" id="mp24Results"></div>
          </div>

          @if(auth()->user()?->isAdmin())
          <div class="mp24-card p-4 p-md-5">
            <h3 class="mp24-section-title mb-3">Formula Notes</h3>
            <div class="mp24-note-list">
              <p><strong>Employer Cost Per Hour</strong> = Baseline Hourly Pay Rate ÷ Divisor</p>
              <p><strong>Annual Labor Cost</strong> = Employer Cost Per Hour × Annual Hours</p>
              <p><strong>Miles Per Day</strong> = MPH × Hours Per Day</p>
              <p><strong>Miles Per Year</strong> = Miles Per Day × 365</p>
              <p><strong>Gallons Per Year</strong> = Miles Per Year ÷ MPG</p>
              <p><strong>Annual Fuel Cost</strong> = Gallons Per Year × Fuel Cost Per Gallon</p>
              <p><strong>Annual Tire Cost</strong> = Tire Sets Per Year × Tire Cost Per Set</p>
              <p><strong>Annual Oil Cost</strong> = Ceiling(Miles Per Year ÷ Oil Change Interval) × Oil Change Cost</p>
              <p><strong>Total Annual Cost</strong> = Labor + Fuel + Maintenance + Tires + Auto Lease and Insurance + Oil</p>
              <p><strong>Return on Sales Amount</strong> = Total Annual Cost × Return on Sales %</p>
              <p><strong>Total Annual Cost + Return on Sales</strong> = Total Annual Cost + Return on Sales Amount</p>
              <p><strong>Hourly Bill Rate</strong> = (Total Annual Cost + Return on Sales) ÷ Annual Hours</p>
            </div>
          </div>
          @endif

          <div class="mp24-card p-4 p-md-5">
            <div class="d-flex justify-content-between align-items-center flex-wrap gap-3 mb-3">
              <div>
                <h3 class="mp24-section-title mb-1">Report Actions</h3>
                <p class="text-gasq-muted small mb-0">Persist this calculator state, download the PDF, or email the report.</p>
              </div>
            </div>
            <div class="mp24-actions">
              <button type="button" class="btn btn-outline-secondary" id="mp24ResetButton">
                <i class="fa fa-rotate me-1"></i> Reset
              </button>
              <button type="button" class="btn btn-outline-secondary" id="mp24PrintButton">
                <i class="fa fa-print me-1"></i> Print
              </button>
              <button type="button" class="btn btn-outline-primary" id="mp24DownloadVendorButton">
                <i class="fa fa-download me-1"></i> Vendor PDF
              </button>
              <button type="button" class="btn btn-outline-secondary" id="mp24DownloadBuyerButton">
                <i class="fa fa-user me-1"></i> Buyer PDF
              </button>
              <input
                type="email"
                id="mp24Email"
                class="form-control mp24-email"
                placeholder="Email address"
                value="{{ auth()->user()?->email }}"
              >
              <button type="button" class="btn btn-primary" id="mp24EmailButton">
                <i class="fa fa-envelope me-1"></i> Email Report
              </button>
            </div>
          </div>
        </div>
      </div>
    </div>

    <form id="mp24EmailForm" action="{{ route('reports.email') }}" method="POST" class="d-none">
      @csrf
      <input type="hidden" name="type" value="mobile-patrol">
      <input type="hidden" name="email" id="mp24EmailTarget" value="{{ auth()->user()?->email }}">
    </form>
  </div>
</div>
@endsection

@push('scripts')
<script>
const MP24_STORAGE_KEY = 'gasq.mobilePatrolFormula.v2';
const MP24_REPORT_TYPE = 'mobile-patrol';
const MP24_REPORT_DOWNLOAD_URL = @json(route('reports.download', ['type' => 'mobile-patrol']));
const MP24_BUYER_REPORT_DOWNLOAD_URL = @json(route('reports.download', ['type' => 'mobile-patrol-buyer']));
const MP24_REPORT_PAYLOAD_URL = @json(route('backend.report-payload.store'));

const MP24_DEFAULTS = {
  baselinePayRate: 25,
  divisor: 0.70,
  annualHours: 8736,
  mph: 25,
  hoursPerDay: 24,
  mpg: 25,
  fuelCostPerGallon: 4.11,
  annualMaintenance: 0,
  tireSetsPerYear: 4,
  tireCostPerSet: 0,
  autoInsurance: 0,
  oilChangeIntervalMiles: 7500,
  oilChangeCost: 100,
  returnOnSalesPct: 0.25,
};

const MP24_FIELDS = [
  ['Baseline Hourly Pay Rate', 'baselinePayRate', 'Example: 25.00'],
  ['Divisor', 'divisor', 'Default: 0.70'],
  ['Annual Hours', 'annualHours', 'Default: 8736'],
  ['Driving Speed (MPH)', 'mph', 'Example: 25'],
  ['Hours Per Day', 'hoursPerDay', 'Default: 24'],
  ['Miles Per Gallon', 'mpg', 'Example: 25'],
  ['Fuel Cost Per Gallon', 'fuelCostPerGallon', 'Example: 4.11'],
  ['Projected Annual Maintenance / Repair', 'annualMaintenance', 'Annual total'],
  ['Tire Sets Per Year', 'tireSetsPerYear', 'Default: 4'],
  ['Tire Cost Per Set', 'tireCostPerSet', 'Per set'],
  ['Auto Lease and Insurance', 'autoInsurance', 'Annual total'],
  ['Oil Change Interval (Miles)', 'oilChangeIntervalMiles', 'Default: 7500'],
  ['Oil Change Cost', 'oilChangeCost', 'Default: 100'],
  ['Return on Sales %', 'returnOnSalesPct', 'Example: 0.25 or 25 for 25%'],
];

const MP24_CONTACT_DEFAULTS = {
  contactName: '',
  companyName: '',
  contactAddress: '',
  contactEmail: '',
  contactPhone: '',
};

const MP24_CONTACT_FIELDS = [
  ['contactName',    'Contact Name',  'text',  'Full name'],
  ['companyName',    'Company Name',  'text',  'Company'],
  ['contactAddress', 'Address',       'text',  'Street address, city, state'],
  ['contactEmail',   'Email',         'email', 'Email address'],
  ['contactPhone',   'Phone',         'tel',   'Phone number'],
];

let mp24Inputs = { ...MP24_DEFAULTS };
let mp24Contact = { ...MP24_CONTACT_DEFAULTS };
let mp24PersistTimer = null;

function mp24ById(id) {
  return document.getElementById(id);
}

function mp24Money(value) {
  return new Intl.NumberFormat('en-US', {
    style: 'currency',
    currency: 'USD',
    maximumFractionDigits: 2,
  }).format(Number.isFinite(value) ? value : 0);
}

function mp24Number(value, digits = 2) {
  return new Intl.NumberFormat('en-US', {
    minimumFractionDigits: digits,
    maximumFractionDigits: digits,
  }).format(Number.isFinite(value) ? value : 0);
}

function mp24NormalizeRosRate(value) {
  const numeric = Number.isFinite(value) ? value : 0;

  if (numeric <= 0) {
    return 0;
  }

  return numeric > 1 ? numeric / 100 : numeric;
}

function mp24ShowStatus(type, message) {
  const alert = mp24ById('mobilePatrolStatus');
  if (!alert) {
    return;
  }

  if (!message) {
    alert.className = 'alert d-none mb-4';
    alert.textContent = '';
    return;
  }

  alert.className = `alert alert-${type} mb-4`;
  alert.textContent = message;
}

function mp24ReadInputsFromDom() {
  MP24_FIELDS.forEach(([, key]) => {
    const input = mp24ById(`mp24-${key}`);
    if (!input) {
      return;
    }

    const value = input.value === '' ? 0 : Number(input.value);
    mp24Inputs[key] = Number.isFinite(value) ? value : 0;
  });
}

function mp24Calculate() {
  const returnOnSalesRate = mp24NormalizeRosRate(mp24Inputs.returnOnSalesPct);
  const employerCostHourly = mp24Inputs.divisor > 0
    ? mp24Inputs.baselinePayRate / mp24Inputs.divisor
    : 0;

  const annualLaborCost = employerCostHourly * mp24Inputs.annualHours;
  const milesPerDay = mp24Inputs.mph * mp24Inputs.hoursPerDay;
  const milesPerYear = milesPerDay * 365;
  const gallonsPerYear = mp24Inputs.mpg > 0 ? milesPerYear / mp24Inputs.mpg : 0;
  const annualFuelCost = gallonsPerYear * mp24Inputs.fuelCostPerGallon;
  const oilChangesPerYear = mp24Inputs.oilChangeIntervalMiles > 0
    ? Math.ceil(milesPerYear / mp24Inputs.oilChangeIntervalMiles)
    : 0;
  const annualOilCost = oilChangesPerYear * mp24Inputs.oilChangeCost;
  const annualTireCost = mp24Inputs.tireSetsPerYear * mp24Inputs.tireCostPerSet;
  const totalAnnualCost = annualLaborCost
    + annualFuelCost
    + mp24Inputs.annualMaintenance
    + annualTireCost
    + mp24Inputs.autoInsurance
    + annualOilCost;
  const returnOnSalesAmount = totalAnnualCost * returnOnSalesRate;
  const totalAnnualCostWithReturnOnSales = totalAnnualCost + returnOnSalesAmount;
  const costPerHour = mp24Inputs.annualHours > 0 ? totalAnnualCostWithReturnOnSales / mp24Inputs.annualHours : 0;
  const hourlyBillRate = costPerHour;

  return {
    returnOnSalesRate,
    returnOnSalesPercentDisplay: returnOnSalesRate * 100,
    employerCostHourly,
    annualLaborCost,
    milesPerDay,
    milesPerYear,
    gallonsPerYear,
    annualFuelCost,
    oilChangesPerYear,
    annualOilCost,
    annualTireCost,
    totalAnnualCost,
    returnOnSalesAmount,
    totalAnnualCostWithReturnOnSales,
    costPerHour,
    hourlyBillRate,
  };
}

function mp24ResultRow(label, value, className = '') {
  return `
    <div class="mp24-row ${className}">
      <span>${label}</span>
      <strong>${value}</strong>
    </div>
  `;
}

function mp24RenderResults() {
  const results = mp24Calculate();
  const resultsEl = mp24ById('mp24Results');

  resultsEl.innerHTML = [
    mp24ResultRow('1. Employer Cost Per Hour', mp24Money(results.employerCostHourly)),
    mp24ResultRow('2. Annual Labor Cost', mp24Money(results.annualLaborCost)),
    mp24ResultRow('3. Miles Per Day', mp24Number(results.milesPerDay, 0)),
    mp24ResultRow('4. Miles Per Year', mp24Number(results.milesPerYear, 0)),
    mp24ResultRow('5. Gallons Per Year', mp24Number(results.gallonsPerYear, 0)),
    mp24ResultRow('6. Annual Fuel Cost', mp24Money(results.annualFuelCost)),
    mp24ResultRow('7. Annual Maintenance / Repair', mp24Money(mp24Inputs.annualMaintenance)),
    mp24ResultRow('8. Annual Tire Cost', mp24Money(results.annualTireCost)),
    mp24ResultRow('9. Auto Lease and Insurance', mp24Money(mp24Inputs.autoInsurance)),
    mp24ResultRow('10. Oil Changes / Year', `${mp24Number(results.oilChangesPerYear, 0)} (${mp24Money(results.annualOilCost)})`),
    mp24ResultRow('11. Total Annual Cost', mp24Money(results.totalAnnualCost), 'mp24-row-dark'),
    mp24ResultRow(`12. Return on Sales Amount (${mp24Number(results.returnOnSalesPercentDisplay, 2)}%)`, mp24Money(results.returnOnSalesAmount)),
    mp24ResultRow('13. Total Annual Cost + Return on Sales', mp24Money(results.totalAnnualCostWithReturnOnSales), 'mp24-row-dark'),
    mp24ResultRow('14. Hourly Bill Rate', mp24Money(results.costPerHour), 'mp24-row-success'),
  ].join('');

  window.__gasqMobilePatrol = {
    inputs: { ...mp24Inputs },
    results,
  };
}

function mp24RenderInputs() {
  const grid = mp24ById('mp24InputGrid');
  grid.innerHTML = '';

  MP24_FIELDS.forEach(([label, key, placeholder]) => {
    const col = document.createElement('div');
    col.className = 'col-sm-6';
    col.innerHTML = `
      <label class="mp24-label" for="mp24-${key}">${label}</label>
      <input
        id="mp24-${key}"
        type="number"
        step="any"
        class="mp24-input"
        value="${mp24Inputs[key] ?? 0}"
        placeholder="${placeholder}"
      />
      <div class="mp24-hint">${placeholder}</div>
    `;
    grid.appendChild(col);
  });

  grid.querySelectorAll('input').forEach((input) => {
    input.addEventListener('input', () => {
      mp24ReadInputsFromDom();
      mp24RenderResults();
      mp24PersistLocal();
      mp24ScheduleReportPersist();
    });
  });
}

function mp24RenderContactFields() {
  const grid = mp24ById('mp24ContactGrid');
  grid.innerHTML = '';

  MP24_CONTACT_FIELDS.forEach(([key, label, type, placeholder]) => {
    const col = document.createElement('div');
    col.className = 'col-sm-6';
    col.innerHTML = `
      <label class="mp24-label" for="mp24c-${key}">${label}</label>
      <input id="mp24c-${key}" type="${type}" class="mp24-input" value="${mp24Contact[key] ?? ''}" placeholder="${placeholder}" />
    `;
    grid.appendChild(col);
  });

  grid.querySelectorAll('input').forEach((input) => {
    const key = input.id.replace('mp24c-', '');
    input.addEventListener('input', () => {
      mp24Contact[key] = input.value;
      mp24PersistLocal();
      mp24ScheduleReportPersist();
    });
  });
}

function mp24PersistLocal() {
  try {
    window.localStorage.setItem(MP24_STORAGE_KEY, JSON.stringify({ inputs: mp24Inputs, contact: mp24Contact }));
  } catch (error) {
    console.warn('Unable to save mobile patrol calculator state.', error);
  }
}

function mp24LoadLocal() {
  try {
    const raw = window.localStorage.getItem(MP24_STORAGE_KEY);
    if (raw) {
      const parsed = JSON.parse(raw);
      if (parsed && typeof parsed === 'object' && parsed.inputs) {
        return parsed;
      }
      return { inputs: parsed };
    }
  } catch (error) {
    console.warn('Unable to restore mobile patrol calculator state.', error);
  }

  return null;
}

function mp24LoadSavedScenario() {
  const meta = window.__gasqCalculatorState?.scenario?.meta || {};
  const map = {
    baselinePayRate: meta.baselinePayRate,
    divisor: meta.divisor,
    annualHours: meta.annualHours,
    mph: meta.mph,
    hoursPerDay: meta.hoursPerDay,
    mpg: meta.mpg,
    fuelCostPerGallon: meta.fuelCostPerGallon,
    annualMaintenance: meta.annualMaintenance,
    tireSetsPerYear: meta.tireSetsPerYear,
    tireCostPerSet: meta.tireCostPerSet,
    autoInsurance: meta.autoInsurance,
    oilChangeIntervalMiles: meta.oilChangeIntervalMiles,
    oilChangeCost: meta.oilChangeCost,
    returnOnSalesPct: meta.returnOnSalesPct,
  };

  const filled = Object.values(map).some((value) => value !== undefined && value !== null);
  return filled ? map : null;
}

function mp24ScenarioPayload() {
  return {
    meta: { ...mp24Inputs },
    contact: { ...mp24Contact },
  };
}

function mp24ResultPayload() {
  return {
    kpis: { ...mp24Calculate() },
  };
}

async function mp24PersistReportPayload() {
  const response = await fetch(MP24_REPORT_PAYLOAD_URL, {
    method: 'POST',
    headers: {
      'Content-Type': 'application/json',
      'Accept': 'application/json',
      'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
    },
    body: JSON.stringify({
      type: MP24_REPORT_TYPE,
      scenario: mp24ScenarioPayload(),
      result: mp24ResultPayload(),
    }),
  });

  if (!response.ok) {
    throw new Error('We could not prepare the mobile patrol report right now.');
  }
}

function mp24ScheduleReportPersist() {
  clearTimeout(mp24PersistTimer);
  mp24PersistTimer = setTimeout(async () => {
    try {
      await mp24PersistReportPayload();
    } catch (error) {
      console.error(error);
    }
  }, 350);
}

function mp24Reset() {
  mp24Inputs = { ...MP24_DEFAULTS };
  mp24Contact = { ...MP24_CONTACT_DEFAULTS };
  mp24RenderInputs();
  mp24RenderContactFields();
  mp24RenderResults();
  mp24PersistLocal();
  mp24ScheduleReportPersist();
  mp24ShowStatus('success', 'Mobile patrol calculator reset to defaults.');
}

async function mp24DownloadReport(url) {
  try {
    await mp24PersistReportPayload();
    window.location.href = url;
  } catch (error) {
    mp24ShowStatus('danger', error.message || 'Unable to prepare the PDF right now.');
  }
}

async function mp24EmailReport() {
  const email = mp24ById('mp24Email').value.trim();
  if (!email) {
    mp24ShowStatus('warning', 'Enter an email address before sending the report.');
    return;
  }

  try {
    await mp24PersistReportPayload();
    mp24ById('mp24EmailTarget').value = email;
    mp24ById('mp24EmailForm').submit();
  } catch (error) {
    mp24ShowStatus('danger', error.message || 'Unable to prepare the email report right now.');
  }
}

document.addEventListener('DOMContentLoaded', () => {
  const saved = mp24LoadLocal();
  const savedScenario = mp24LoadSavedScenario();

  mp24Inputs = {
    ...MP24_DEFAULTS,
    ...(saved?.inputs || {}),
    ...(savedScenario || {}),
  };

  mp24Contact = {
    ...MP24_CONTACT_DEFAULTS,
    ...(saved?.contact || {}),
  };

  mp24RenderContactFields();
  mp24RenderInputs();
  mp24RenderResults();
  mp24ScheduleReportPersist();

  mp24ById('mp24ResetButton').addEventListener('click', mp24Reset);
  mp24ById('mp24PrintButton').addEventListener('click', () => window.print());
  mp24ById('mp24DownloadVendorButton').addEventListener('click', () => mp24DownloadReport(MP24_REPORT_DOWNLOAD_URL));
  mp24ById('mp24DownloadBuyerButton').addEventListener('click', () => mp24DownloadReport(MP24_BUYER_REPORT_DOWNLOAD_URL));
  mp24ById('mp24EmailButton').addEventListener('click', mp24EmailReport);
});
</script>
@endpush

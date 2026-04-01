@extends('layouts.app')

@section('title', 'GASQ Direct Labor Build-Up')
@section('header_variant', 'dashboard')

@push('styles')
<style>
  .dlb-banner { background:#1e3a5f; color:#fff; }
  .dlb-sub { background:#6b0f1a; color:#fff; }
  .dlb-input { background:#fff9c4 !important; }
  .dlb-mono { font-variant-numeric: tabular-nums; }
</style>
@endpush

@section('content')
<div class="min-vh-100 py-4 px-3 px-md-4" style="background:var(--gasq-background)">
  <div class="container-xl">
    <div class="d-flex align-items-center justify-content-between flex-wrap gap-3 mb-3">
      <div class="d-flex align-items-center gap-2">
        <a href="{{ route('workforce-appraisal-report.index') }}" class="btn btn-outline-secondary btn-sm"><i class="fa fa-arrow-left"></i></a>
        <span class="text-gasq-muted small">V24 compute · <code>gasq-direct-labor-build-up</code></span>
      </div>
      <button class="btn btn-outline-secondary btn-sm d-print-none" onclick="window.print()"><i class="fa fa-print me-1"></i> Print</button>
    </div>

    <div class="card gasq-card">
      <div class="card-header dlb-banner py-3">
        <div class="d-flex align-items-center justify-content-between flex-wrap gap-2">
          <div class="fw-bold">GASQ Direct Labor Build-Up</div>
          <div class="small opacity-75">GASQ-branded operating model worksheet</div>
        </div>
      </div>
      <div class="card-body">
        <div class="row g-3 align-items-end mb-3">
          <div class="col-md-4">
            <label class="form-label small fw-medium">Annual billable hours</label>
            <input type="number" id="dlb_hours" class="form-control dlb-input" value="21322" min="1" step="1">
          </div>
          <div class="col-md-8 small text-gasq-muted">
            Hourly inputs match your spreadsheet values; Annual Amount is computed as hourly × annual hours.
          </div>
        </div>

        <div class="table-responsive">
          <table class="table table-bordered align-middle mb-0 dlb-mono">
            <thead class="table-light">
              <tr>
                <th style="min-width: 340px;">Line Item</th>
                <th style="width: 190px;">Input / Formula</th>
                <th class="text-end" style="width: 160px;">Hourly Amount</th>
                <th class="text-end" style="width: 190px;">Annual Amount</th>
              </tr>
            </thead>
            <tbody id="dlb_body"></tbody>
          </table>
        </div>
      </div>
    </div>

  </div>
</div>
@endsection

@push('scripts')
<script>
(() => {
  const url = @json(route('backend.standalone.v24.compute', ['type' => 'gasq-direct-labor-build-up']));
  let t = null;

  const money = (n) => new Intl.NumberFormat('en-US',{style:'currency',currency:'USD',minimumFractionDigits:2,maximumFractionDigits:2}).format(n||0);

  // Map engine keys → spreadsheet-friendly labels
  const LABELS = {
    baseBlended: 'Base Consolidated Blended Direct Labor Wage',
    localityPay: 'Locality Pay',
    laborMarketAdj: 'Labor Market Adjustment',
    hwCash: 'H&W (Cash)',
    shiftDifferential: 'Shift Differential',
    otHolidayPremium: 'OT/Holiday Premium',
    donDoff: 'DON/DOFF',
    ficaMedicare: 'FICA / Medicare',
    futa: 'FUTA',
    suta: 'SUTA',
    workersComp: 'Workers Compensation',
    healthWelfare: 'Health & Welfare',
    vacation: 'Vacation',
    paidHolidays: 'Paid Holidays',
    sickLeave: 'Sick Leave',
    recruiting: 'Recruiting / Hiring',
    training: 'Training / Certification',
    uniformsEquipment: 'Uniforms / Equipment',
    fieldSupervision: 'Field Supervision',
    contractManagement: 'Contract Management',
    qualityAssurance: 'Quality Assurance',
    vehiclesPatrol: 'Vehicles / Patrol',
    technologySystems: 'Technology / Systems',
    generalLiability: 'General Liability Insurance',
    umbrellaInsurance: 'Umbrella / Other Insurance',
    adminHrPayroll: 'Administrative / HR / Payroll',
    accountingLegal: 'Accounting / Legal',
    corporateOverhead: 'Corporate Overhead',
    ga: 'G&A',
    profitFee: 'Profit / Fee',
  };

  function payload(){
    return {
      version: 'v24',
      scenario: {
        meta: {
          annualBillableHours: parseFloat(dlb_hours.value)||21322,
          cfoHourlyOverrides: collectOverrides(),
        }
      }
    };
  }

  function collectOverrides(){
    const ov = {};
    document.querySelectorAll('[data-ov-key]').forEach(el => {
      const k = el.getAttribute('data-ov-key');
      const v = parseFloat(el.value);
      if(!Number.isNaN(v)) ov[k] = v;
    });
    return ov;
  }

  function render(stack){
    const body = document.getElementById('dlb_body');
    body.innerHTML = '';
    const sections = (stack && stack.sections) ? stack.sections : [];
    for(const sec of sections){
      body.innerHTML += `<tr><td colspan="4" class="dlb-sub fw-semibold">${sec.title}</td></tr>`;
      for(const r of (sec.rows||[])){
        const label = LABELS[r.key] || r.label;
        const editable = (r.key !== 'total');
        body.innerHTML += `
          <tr>
            <td>${label}</td>
            <td><input type="number" class="form-control form-control-sm dlb-input" data-ov-key="${r.key}" value="${(r.hourly??0).toFixed(2)}" step="0.01"></td>
            <td class="text-end">${money(r.hourly)}</td>
            <td class="text-end text-success">${money(r.annual)}</td>
          </tr>`;
      }
      const st = sec.subtotal||{};
      body.innerHTML += `<tr class="table-warning fw-semibold"><td>${st.label}</td><td></td><td class="text-end">${money(st.hourly)}</td><td class="text-end text-success">${money(st.annual)}</td></tr>`;
      if(sec.laborPlusFringe){
        const l = sec.laborPlusFringe;
        body.innerHTML += `<tr class="table-warning fw-bold"><td>${l.label}</td><td></td><td class="text-end">${money(l.hourly)}</td><td class="text-end text-success">${money(l.annual)}</td></tr>`;
      }
    }
    const g = (stack && stack.grandTotal) ? stack.grandTotal : null;
    if(g){
      body.innerHTML += `<tr class="table-primary fw-bold"><td>${g.label}</td><td></td><td class="text-end">${money(g.hourly)}</td><td class="text-end text-success">${money(g.annual)}</td></tr>`;
    }

    // Hook inputs
    body.querySelectorAll('input').forEach(el => el.addEventListener('input', schedule));
  }

  async function compute(){
    const res = await fetch(url, {
      method:'POST',
      headers:{
        'Content-Type':'application/json',
        'Accept':'application/json',
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
      },
      body: JSON.stringify(payload())
    });
    const data = await res.json();
    if(!res.ok || !data.ok){ console.error(data); return; }
    render((data.kpis||{}).stack);
  }

  function schedule(){ clearTimeout(t); t=setTimeout(compute, 250); }

  document.addEventListener('DOMContentLoaded', () => {
    dlb_hours.addEventListener('input', schedule);
    compute();
  });
})();
</script>
@endpush


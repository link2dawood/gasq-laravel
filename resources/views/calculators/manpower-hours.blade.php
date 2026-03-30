@extends('layouts.app')
@section('title', 'Manpower Hours Calculator')
@section('header_variant', 'dashboard')

@section('content')
<div class="min-vh-100 py-4 px-3 px-md-4" style="background:var(--gasq-background)">
<div class="container-xl">

  <div class="d-flex align-items-center justify-content-between flex-wrap gap-3 mb-4">
    <div class="d-flex align-items-center gap-3">
      <a href="{{ route('main-menu-calculator.index') }}" class="btn btn-outline-secondary btn-sm"><i class="fa fa-arrow-left"></i></a>
      <div>
        <h1 class="h3 fw-bold mb-0 d-flex align-items-center gap-2">
          <i class="fa fa-users text-primary"></i> Manpower Hours Calculator
        </h1>
        <div class="text-gasq-muted small">Determine workforce requirements based on site coverage and shift patterns</div>
      </div>
    </div>
    <div class="d-flex flex-wrap gap-2 d-print-none">
      <button class="btn btn-outline-secondary btn-sm" onclick="window.print()"><i class="fa fa-print me-1"></i> Print</button>
    </div>
  </div>

  <div class="row g-4">
    <div class="col-lg-5">
      <div class="card gasq-card h-100">
        <div class="card-header py-3"><h5 class="card-title mb-0 fw-semibold d-flex align-items-center gap-2"><i class="fa fa-sliders text-primary"></i> Site Parameters</h5></div>
        <div class="card-body d-flex flex-column gap-4">

          <div>
            <label class="form-label fw-medium">Site Coverage Hours per Day</label>
            <input type="number" id="mh_coverage" class="form-control" value="24" step="0.5" min="1" max="24" oninput="calcMH()">
            <div class="form-text">How many hours per day does this site need coverage?</div>
          </div>

          <div>
            <label class="form-label fw-medium">Shift Pattern</label>
            @foreach(['8-hour'=>['3x','8-hour shifts (3 shifts/day)'],'10-hour'=>['2.4x','10-hour shifts (2.4 shifts/day)'],'12-hour'=>['2x','12-hour shifts (2 shifts/day)'],'16-hour'=>['1.5x','16-hour shifts (1.5 shifts/day)'],'24-hour'=>['1x','24-hour shifts (1 shift/day)']] as $val=>[$mult,$desc])
            <div class="form-check border rounded p-2 mb-2 cursor-pointer {{ $val==='8-hour'?'border-primary':'' }}" onclick="this.querySelector('input').click();calcMH()">
              <input class="form-check-input" type="radio" name="mh_shift" id="shift_{{ $val }}" value="{{ $val }}" {{ $val==='8-hour'?'checked':'' }} onchange="calcMH()">
              <label class="form-check-label d-flex justify-content-between w-100" for="shift_{{ $val }}">
                <span class="fw-medium">{{ ucfirst(str_replace('-',' ',$val)) }}</span>
                <span class="badge bg-secondary">{{ $mult }} multiplier</span>
              </label>
              <div class="small text-gasq-muted">{{ $desc }}</div>
            </div>
            @endforeach
          </div>

          <div>
            <label class="form-label fw-medium">Scheduling Factor</label>
            <input type="number" id="mh_factor" class="form-control" value="1.4" step="0.05" min="1" max="2.5" oninput="calcMH()">
            <div class="form-text">Accounts for days off, sick leave, vacations, training (typical: 1.4–1.7)</div>
          </div>

          <div>
            <label class="form-label fw-medium">Max Hours per Guard per Week</label>
            <input type="number" id="mh_maxHrs" class="form-control" value="40" step="4" oninput="calcMH()">
          </div>

        </div>
      </div>
    </div>

    <div class="col-lg-7">
      <div class="card gasq-card mb-4">
        <div class="card-header py-3"><h5 class="card-title mb-0 fw-semibold">Hours &amp; Staffing Results</h5></div>
        <div class="card-body">

          {{-- Summary cards --}}
          <div class="row g-3 mb-4">
            <div class="col-6 col-md-3">
              <div class="rounded p-3 text-center" style="background:var(--gasq-muted-bg)">
                <div class="x-sm text-gasq-muted mb-1">Daily Hours</div>
                <div class="fs-4 fw-bold text-primary" id="r_daily">0</div>
              </div>
            </div>
            <div class="col-6 col-md-3">
              <div class="rounded p-3 text-center" style="background:var(--gasq-muted-bg)">
                <div class="x-sm text-gasq-muted mb-1">Weekly Hours</div>
                <div class="fs-4 fw-bold text-primary" id="r_weekly">0</div>
              </div>
            </div>
            <div class="col-6 col-md-3">
              <div class="rounded p-3 text-center" style="background:var(--gasq-muted-bg)">
                <div class="x-sm text-gasq-muted mb-1">Monthly Hours</div>
                <div class="fs-4 fw-bold" id="r_monthly">0</div>
              </div>
            </div>
            <div class="col-6 col-md-3">
              <div class="rounded p-3 text-center" style="background:var(--gasq-muted-bg)">
                <div class="x-sm text-gasq-muted mb-1">Annual Hours</div>
                <div class="fs-4 fw-bold" id="r_annual">0</div>
              </div>
            </div>
          </div>

          {{-- Staffing required --}}
          <div class="rounded p-4 text-white text-center mb-4" style="background:var(--gasq-primary)">
            <div class="small mb-1" style="opacity:.85">Guards Required</div>
            <div class="display-4 fw-bold" id="r_guards">0</div>
            <div class="small mt-1" style="opacity:.75">Based on <span id="r_maxHrs">40</span> hrs/guard/week</div>
          </div>

          {{-- Detail breakdown --}}
          <div class="rounded p-3 mb-3" style="background:var(--gasq-muted-bg)">
            <h6 class="fw-semibold mb-2">Calculation Details</h6>
            <div class="d-flex justify-content-between small mb-1"><span class="text-gasq-muted">Coverage hours/day</span><span id="d_coverage">24</span></div>
            <div class="d-flex justify-content-between small mb-1"><span class="text-gasq-muted">Shift multiplier</span><span id="d_multiplier">3.0x</span></div>
            <div class="d-flex justify-content-between small mb-1"><span class="text-gasq-muted">Scheduling factor</span><span id="d_factor">1.40x</span></div>
            <div class="d-flex justify-content-between small mb-1"><span class="text-gasq-muted">Required daily labor hours</span><span id="d_requiredHrs">0</span></div>
            <div class="d-flex justify-content-between small"><span class="text-gasq-muted">Required weekly labor hours</span><span id="d_weeklyRequired">0</span></div>
          </div>

          {{-- Scheduling matrix --}}
          <h6 class="fw-semibold mb-2">Staffing by Shift Pattern</h6>
          <div class="table-responsive">
            <table class="table table-sm">
              <thead class="table-light">
                <tr><th>Shift Pattern</th><th class="text-center">Multiplier</th><th class="text-center">Weekly Hrs</th><th class="text-center">Guards Needed</th></tr>
              </thead>
              <tbody id="mh_matrix"></tbody>
            </table>
          </div>

        </div>
      </div>
    </div>
  </div>

</div>
</div>
@endsection

@push('scripts')
<style>.x-sm{font-size:0.75rem;line-height:1.2} .cursor-pointer{cursor:pointer}</style>
<script>
const SHIFT_MULTIPLIERS = {'8-hour':3,'10-hour':2.4,'12-hour':2,'16-hour':1.5,'24-hour':1};
const SHIFTS_LIST = Object.entries(SHIFT_MULTIPLIERS);

function fmtN(v,dec=1){return new Intl.NumberFormat('en-US',{minimumFractionDigits:dec,maximumFractionDigits:dec}).format(v);}
function g(id){return parseFloat(document.getElementById(id).value)||0;}
function setText(id,v){const el=document.getElementById(id);if(el)el.textContent=v;}

function calcMH(){
  const coverage = g('mh_coverage');
  const shiftEl = document.querySelector('[name=mh_shift]:checked');
  const shiftVal = shiftEl?.value||'8-hour';
  const shiftMul = SHIFT_MULTIPLIERS[shiftVal]||3;
  const factor = g('mh_factor');
  const maxHrs = g('mh_maxHrs')||40;

  const requiredHrsPerDay = coverage * shiftMul * factor;
  const weekly = requiredHrsPerDay * 7;
  const monthly = requiredHrsPerDay * 30;
  const annual = requiredHrsPerDay * 365;
  const guards = Math.ceil(weekly / maxHrs);

  setText('r_daily', fmtN(requiredHrsPerDay,1));
  setText('r_weekly', fmtN(weekly,1));
  setText('r_monthly', fmtN(monthly,1));
  setText('r_annual', fmtN(annual,1));
  setText('r_guards', guards);
  setText('r_maxHrs', maxHrs);
  setText('d_coverage', coverage+' hrs');
  setText('d_multiplier', shiftMul.toFixed(1)+'x');
  setText('d_factor', factor.toFixed(2)+'x');
  setText('d_requiredHrs', fmtN(requiredHrsPerDay,1)+' hrs');
  setText('d_weeklyRequired', fmtN(weekly,1)+' hrs');

  // Matrix
  const tbody = document.getElementById('mh_matrix');
  tbody.innerHTML = SHIFTS_LIST.map(([shiftName, mul])=>{
    const wkHrs = coverage * mul * factor * 7;
    const gRequired = Math.ceil(wkHrs / maxHrs);
    const active = shiftName === shiftVal;
    return `<tr class="${active?'table-primary fw-semibold':''}">
      <td>${shiftName.charAt(0).toUpperCase()+shiftName.slice(1)}</td>
      <td class="text-center">${mul.toFixed(1)}x</td>
      <td class="text-center">${fmtN(wkHrs,1)}</td>
      <td class="text-center"><span class="badge ${active?'bg-primary':'text-bg-secondary'}">${gRequired}</span></td>
    </tr>`;
  }).join('');
}

document.addEventListener('DOMContentLoaded', calcMH);
</script>
@endpush

@extends('layouts.app')
@section('title', 'GASQ Instant Estimator')
@section('header_variant', 'dashboard')

@section('content')
<div class="min-vh-100 py-4 px-3 px-md-4" style="background:var(--gasq-background)">
<div class="container-xl">

  {{-- Header --}}
  <div class="d-flex align-items-center justify-content-between flex-wrap gap-3 mb-4">
    <div class="d-flex align-items-center gap-3">
      <a href="{{ route('main-menu-calculator.index') }}" class="btn btn-outline-secondary btn-sm"><i class="fa fa-arrow-left"></i></a>
      <div>
        <h1 class="h3 fw-bold mb-0 d-flex align-items-center gap-2">
          <i class="fa fa-bolt text-primary"></i> GASQ Instant Estimator
        </h1>
        <div class="text-gasq-muted small">Quick security cost estimate by location, hours, and team size</div>
      </div>
    </div>
    <div class="d-flex flex-wrap gap-2 d-print-none">
      <button class="btn btn-outline-secondary btn-sm" onclick="resetForm()"><i class="fa fa-rotate me-1"></i> Reset</button>
      <button class="btn btn-outline-secondary btn-sm" onclick="window.print()"><i class="fa fa-print me-1"></i> Print</button>
      <button class="btn btn-primary btn-sm" onclick="emailEstimate()"><i class="fa fa-envelope me-1"></i> Email Estimate</button>
    </div>
  </div>

  <div class="row g-4">

    {{-- Left: Inputs --}}
    <div class="col-lg-5">
      <div class="card gasq-card h-100">
        <div class="card-header py-3">
          <h5 class="card-title mb-0 fw-semibold d-flex align-items-center gap-2">
            <i class="fa fa-sliders text-primary"></i> Estimate Parameters
          </h5>
        </div>
        <div class="card-body d-flex flex-column gap-4">

          <div>
            <label class="form-label fw-medium">Location / State</label>
            <select id="loc" class="form-select" onchange="calculate()">
              @foreach($locations as $l)
                <option value="{{ $l }}">{{ ucwords(str_replace('-', ' ', $l)) }}</option>
              @endforeach
            </select>
            <div class="form-text">Based on MIT Living Wage data</div>
          </div>

          <div>
            <label class="form-label fw-medium">Hours per Week</label>
            <div class="d-flex align-items-center gap-3">
              <input type="range" id="hoursSlider" class="form-range flex-grow-1" min="4" max="168" step="4" value="40" oninput="syncSlider('hoursSlider','hours');calculate()">
              <input type="number" id="hours" class="form-control text-center" style="max-width:100px" value="40" min="4" max="168" oninput="syncInput('hoursSlider','hours');calculate()">
            </div>
          </div>

          <div>
            <label class="form-label fw-medium">Number of Guards</label>
            <div class="d-flex align-items-center gap-3">
              <input type="range" id="guardsSlider" class="form-range flex-grow-1" min="1" max="50" step="1" value="1" oninput="syncSlider('guardsSlider','guards');calculate()">
              <input type="number" id="guards" class="form-control text-center" style="max-width:80px" value="1" min="1" max="50" oninput="syncInput('guardsSlider','guards');calculate()">
            </div>
          </div>

          <div>
            <label class="form-label fw-medium">Service Type</label>
            <div class="d-flex flex-column gap-2">
              @foreach(['unarmed'=>['Unarmed Guard','1.0x'],'armed'=>['Armed Guard','1.25x'],'patrol'=>['Mobile Patrol','1.15x']] as $val=>[$label,$mult])
              <div class="form-check border rounded p-2 cursor-pointer {{ $val==='unarmed'?'border-primary':'' }}" onclick="selectService('{{ $val }}', this)">
                <input class="form-check-input" type="radio" name="serviceType" id="svc_{{ $val }}" value="{{ $val }}" {{ $val==='unarmed'?'checked':'' }} onchange="calculate()">
                <label class="form-check-label d-flex justify-content-between w-100" for="svc_{{ $val }}">
                  <span>{{ $label }}</span>
                  <span class="badge bg-secondary">{{ $mult }}</span>
                </label>
              </div>
              @endforeach
            </div>
          </div>

          <div>
            <label class="form-label fw-medium">Contact Email (for report)</label>
            <input type="email" id="reportEmail" class="form-control" placeholder="you@example.com">
          </div>

        </div>
      </div>
    </div>

    {{-- Right: Results --}}
    <div class="col-lg-7">
      <div class="card gasq-card mb-4">
        <div class="card-header py-3">
          <h5 class="card-title mb-0 fw-semibold d-flex align-items-center gap-2">
            <i class="fa fa-dollar-sign text-primary"></i> Cost Estimate
          </h5>
        </div>
        <div class="card-body">

          {{-- Location info bar --}}
          <div class="d-flex align-items-center gap-2 p-3 mb-4 gasq-input-section">
            <i class="fa fa-map-marker-alt text-primary"></i>
            <span class="small">Estimating for: <span class="fw-semibold" id="r_location">—</span></span>
            <span class="ms-auto small text-gasq-muted">Living wage: <span id="r_livingWage">$0.00/hr</span></span>
          </div>

          {{-- Main stats --}}
          <div class="row g-3 mb-4">
            <div class="col-6 col-md-3">
              <div class="gasq-metric-card text-center">
                <div class="metric-desc">Hourly Rate</div>
                <div class="metric-value text-primary" id="r_hourly">$0.00</div>
              </div>
            </div>
            <div class="col-6 col-md-3">
              <div class="gasq-metric-card text-center">
                <div class="metric-desc">Weekly</div>
                <div class="metric-value" id="r_weekly">$0.00</div>
              </div>
            </div>
            <div class="col-6 col-md-3">
              <div class="gasq-metric-card text-center">
                <div class="metric-desc">Monthly</div>
                <div class="metric-value" id="r_monthly">$0.00</div>
              </div>
            </div>
            <div class="col-6 col-md-3">
              <div class="gasq-metric-card text-center" style="border-color:rgba(6,45,121,0.2)">
                <div class="metric-desc">Annual</div>
                <div class="metric-value text-primary" id="r_annual">$0.00</div>
                <div class="gasq-progress"><div class="gasq-progress-fill fill-success" style="width:100%"></div></div>
              </div>
            </div>
          </div>

          {{-- Big rate card --}}
          <div class="rounded p-4 text-white text-center mb-4" style="background:var(--gasq-primary)">
            <div class="small mb-1" style="opacity:.85">Estimated Bill Rate per Guard per Hour</div>
            <div class="display-4 fw-bold" id="r_bigRate">$0.00</div>
            <div class="small mt-2" style="opacity:.75">For <span id="r_guards">1</span> guard(s) · <span id="r_hours">40</span> hrs/week</div>
          </div>

          {{-- Cost breakdown --}}
          <h6 class="fw-semibold mb-3">Cost Breakdown</h6>
          <div class="d-flex flex-column gap-2">
            <div class="d-flex justify-content-between small p-2 rounded" style="background:var(--gasq-muted-bg)">
              <span class="text-gasq-muted">Base living wage</span>
              <span id="r_bLivingWage">$0.00/hr</span>
            </div>
            <div class="d-flex justify-content-between small p-2 rounded" style="background:var(--gasq-muted-bg)">
              <span class="text-gasq-muted">Benefits &amp; overhead (+30%)</span>
              <span id="r_bOverhead">$0.00/hr</span>
            </div>
            <div class="d-flex justify-content-between small p-2 rounded" style="background:var(--gasq-muted-bg)">
              <span class="text-gasq-muted">Service type multiplier</span>
              <span id="r_bMultiplier">1.00x</span>
            </div>
            <div class="d-flex justify-content-between small p-2 rounded fw-semibold" style="background:var(--gasq-muted-bg)">
              <span>Estimated hourly bill rate</span>
              <span class="text-primary" id="r_bFinal">$0.00/hr</span>
            </div>
          </div>

          {{-- Disclaimer --}}
          <div class="rounded p-3 mt-4 small" style="background:rgba(6,45,121,0.04);border:1px solid rgba(6,45,121,0.12)">
            <i class="fa fa-info-circle text-primary me-1"></i>
            <strong>Estimate only.</strong> Actual costs vary by vendor, contract terms, location-specific regulations, and service complexity. Use the full <a href="{{ route('main-menu-calculator.index') }}">Main Menu Calculator</a> for detailed analysis.
          </div>

        </div>
      </div>
    </div>

  </div><!-- /row -->
</div>
</div>
@endsection

@push('scripts')
<style>.x-sm{font-size:0.75rem;line-height:1.2} .cursor-pointer{cursor:pointer}</style>
<script>
const savedScenario = window.__gasqCalculatorState?.scenario || null;
function fmt(v){return new Intl.NumberFormat('en-US',{style:'currency',currency:'USD',minimumFractionDigits:2,maximumFractionDigits:2}).format(v);}
function setText(id,v){const el=document.getElementById(id);if(el)el.textContent=v;}
function syncSlider(sid,iid){document.getElementById(sid).value=document.getElementById(iid).value;}
function syncInput(sid,iid){document.getElementById(iid).value=document.getElementById(sid).value;}

function selectService(val, container){
  document.querySelectorAll('[name=serviceType]').forEach(r=>{ r.closest('.form-check').classList.remove('border-primary'); r.closest('.form-check').classList.add('border'); });
  container.classList.add('border-primary');
  calculate();
}

async function calculate(){
  const loc = document.getElementById('loc').value;
  const hours = parseFloat(document.getElementById('hours').value)||40;
  const guards = parseFloat(document.getElementById('guards').value)||1;
  const svcEl = document.querySelector('[name=serviceType]:checked');
  const serviceType = svcEl ? svcEl.value : 'unarmed';

  const payload = {
    version: 'v24',
    scenario: {
      posts: [
        { postName: 'Post 1', positionTitle: serviceType, weeklyHours: hours, qtyRequired: guards }
      ],
      meta: {
        locationState: loc,
        serviceType: serviceType,
        hoursPerWeek: hours,
        guards: guards
      }
    }
  };

  const res = await fetch('{{ route('backend.instant-estimator.compute') }}', {
    method: 'POST',
    headers: {
      'Content-Type': 'application/json',
      'X-CSRF-TOKEN': document.querySelector('meta[name=\"csrf-token\"]').getAttribute('content'),
      'Accept': 'application/json',
    },
    body: JSON.stringify(payload),
  });
  const data = await res.json();
  if(!res.ok || !data || !data.ok){
    console.error(data);
    return;
  }

  const k = data.kpis || {};
  const locLabel = loc.replace(/-/g,' ').replace(/\\b\\w/g,c=>c.toUpperCase());
  setText('r_location', locLabel);
  setText('r_livingWage', fmt(k.livingWageBase||0)+'/hr');
  setText('r_hourly', fmt(k.estimatedHourlyRate||0));
  setText('r_weekly', fmt(k.estimatedWeeklyTotal||0));
  setText('r_monthly', fmt(k.estimatedMonthlyTotal||0));
  setText('r_annual', fmt(k.estimatedAnnualTotal||0));
  setText('r_bigRate', fmt(k.estimatedHourlyRate||0));
  setText('r_guards', guards);
  setText('r_hours', hours);
  setText('r_bLivingWage', fmt(k.livingWageBase||0)+'/hr');
  setText('r_bOverhead', fmt((k.withOverheadHourly||0)-(k.livingWageBase||0))+'/hr');
  setText('r_bMultiplier', (k.serviceMultiplier||1).toFixed(2)+'x');
  setText('r_bFinal', fmt(k.estimatedHourlyRate||0)+'/hr');
}

function resetForm(){
  document.getElementById('loc').value = '{{ $locations[0] ?? "california" }}';
  document.getElementById('hours').value = 40;
  document.getElementById('hoursSlider').value = 40;
  document.getElementById('guards').value = 1;
  document.getElementById('guardsSlider').value = 1;
  document.getElementById('svc_unarmed').checked = true;
  document.querySelectorAll('.form-check').forEach(c=>{c.classList.remove('border-primary');c.classList.add('border');});
  document.querySelector('#svc_unarmed').closest('.form-check').classList.add('border-primary');
  calculate();
}

function emailEstimate(){
  const email = document.getElementById('reportEmail').value;
  if(!email){ alert('Please enter an email address first.'); return; }
  alert('Estimate report would be emailed to: ' + email);
}

function hydrateSavedInstantEstimator(){
  const meta = savedScenario?.meta || {};
  const map = {
    loc: meta.locationState,
    hours: meta.hoursPerWeek,
    guards: meta.guards,
  };

  Object.entries(map).forEach(([id, value]) => {
    if(value === undefined || value === null) return;
    const el = document.getElementById(id);
    if(el) el.value = value;
  });

  const serviceType = meta.serviceType;
  if(serviceType){
    const radio = document.querySelector(`[name="serviceType"][value="${serviceType}"]`);
    if(radio){
      radio.checked = true;
      document.querySelectorAll('.form-check').forEach(c=>{c.classList.remove('border-primary');c.classList.add('border');});
      radio.closest('.form-check')?.classList.add('border-primary');
    }
  }
}

document.addEventListener('DOMContentLoaded', () => {
  hydrateSavedInstantEstimator();
  calculate();
});
</script>
@endpush

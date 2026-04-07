@extends('layouts.app')
@section('title', 'Master Inputs')
@section('header_variant', 'dashboard')

@section('content')
<div class="min-vh-100 py-4 px-3 px-md-4" style="background:var(--gasq-background)">
<div class="container-xl">

  <div class="d-flex align-items-center justify-content-between flex-wrap gap-3 mb-4">
    <div>
      <h1 class="h3 fw-bold mb-0 d-flex align-items-center gap-2">
        <i class="fa fa-sliders text-primary"></i> Master Inputs (Spreadsheet “Inputs” Tab)
      </h1>
      <div class="text-gasq-muted small">
        Set these once. They auto-save and are reused across all calculators.
      </div>
    </div>
    <div class="d-flex flex-wrap gap-2">
      <button type="button" class="btn btn-outline-secondary btn-sm" onclick="resetInputs()">Reset to defaults</button>
      <button type="button" class="btn btn-primary btn-sm" onclick="markComplete()">Continue to calculators</button>
    </div>
  </div>

  <div class="alert alert-danger d-none" id="mi_err" role="alert"></div>
  <div class="alert alert-success d-none" id="mi_ok" role="alert"></div>

  <div class="row g-4">
    <div class="col-lg-6">
      <div class="card gasq-card">
        <div class="card-header py-3"><h5 class="card-title mb-0 fw-semibold">Core Controls</h5></div>
        <div class="card-body">
          <div class="mb-3">
            <label class="form-label fw-medium mb-1">Direct Labor Wage ($/paid hour)</label>
            <div class="d-flex gap-2 align-items-center">
              <input type="number" class="form-control gasq-wa-input" id="mi_directLaborWage" step="0.01">
              <input type="range" class="form-range mb-0" min="0" max="150" step="0.01" data-sync="mi_directLaborWage">
            </div>
          </div>
          <div class="row g-3">
            <div class="col-md-6">
              <label class="form-label fw-medium mb-1">Annual Paid Hours per FTE</label>
              <div class="d-flex gap-2 align-items-center">
                <input type="number" class="form-control gasq-wa-input" id="mi_annualPaidHoursPerFte" step="1" min="0">
                <input type="range" class="form-range mb-0" min="0" max="4000" step="1" data-sync="mi_annualPaidHoursPerFte">
              </div>
            </div>
            <div class="col-md-6">
              <label class="form-label fw-medium mb-1">Annual Productive Coverage Hours per FTE</label>
              <div class="d-flex gap-2 align-items-center">
                <input type="number" class="form-control gasq-wa-input" id="mi_annualProductiveCoverageHoursPerFte" step="1" min="0">
                <input type="range" class="form-range mb-0" min="0" max="4000" step="1" data-sync="mi_annualProductiveCoverageHoursPerFte">
              </div>
            </div>
          </div>
          <hr>
          <div class="row g-3">
            @php
              $pctFields = [
                ['k'=>'localityPayPct','label'=>'Locality Pay %','max'=>0.5],
                ['k'=>'shiftDifferentialPct','label'=>'Shift Differential %','max'=>0.5],
                ['k'=>'otHolidayPremiumPct','label'=>'OT/Holiday Premium %','max'=>0.5],
                ['k'=>'laborMarketAdjPct','label'=>'Labor Market Adjustment %','max'=>0.5],
              ];
            @endphp
            @foreach($pctFields as $f)
              <div class="col-md-6">
                <label class="form-label fw-medium mb-1">{{ $f['label'] }}</label>
                <div class="d-flex gap-2 align-items-center">
                  <input type="number" class="form-control gasq-wa-input" id="mi_{{ $f['k'] }}" step="0.0001" min="0" max="2">
                  <input type="range" class="form-range mb-0" min="0" max="{{ $f['max'] }}" step="0.0001" data-sync="mi_{{ $f['k'] }}">
                </div>
                <div class="small text-gasq-muted">Enter as decimal (e.g. 0.08 = 8%)</div>
              </div>
            @endforeach
          </div>
          <hr>
          <div class="row g-3">
            <div class="col-md-6">
              <label class="form-label fw-medium mb-1">H&amp;W Cash ($/paid hour)</label>
              <div class="d-flex gap-2 align-items-center">
                <input type="number" class="form-control gasq-wa-input" id="mi_hwCashPerHour" step="0.01" min="0">
                <input type="range" class="form-range mb-0" min="0" max="50" step="0.01" data-sync="mi_hwCashPerHour">
              </div>
            </div>
            <div class="col-md-6">
              <label class="form-label fw-medium mb-1">DON/DOFF Minutes per 8-Hour Shift</label>
              <div class="d-flex gap-2 align-items-center">
                <input type="number" class="form-control gasq-wa-input" id="mi_donDoffMinutesPerShift" step="1" min="0">
                <input type="range" class="form-range mb-0" min="0" max="120" step="1" data-sync="mi_donDoffMinutesPerShift">
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <div class="col-lg-6">
      <div class="card gasq-card">
        <div class="card-header py-3"><h5 class="card-title mb-0 fw-semibold">Fringe / Burden + Corporate Controls</h5></div>
        <div class="card-body">
          @php
            $burden = [
              ['k'=>'ficaMedicarePct','label'=>'FICA / Medicare %','max'=>0.2],
              ['k'=>'futaPct','label'=>'FUTA %','max'=>0.05],
              ['k'=>'sutaPct','label'=>'SUTA %','max'=>0.2],
              ['k'=>'workersCompPct','label'=>'Workers Compensation %','max'=>0.2],
              ['k'=>'vacationPct','label'=>'Vacation %','max'=>0.2],
              ['k'=>'paidHolidaysPct','label'=>'Paid Holidays %','max'=>0.2],
              ['k'=>'sickLeavePct','label'=>'Sick Leave %','max'=>0.2],
              ['k'=>'generalLiabilityPct','label'=>'General Liability Insurance %','max'=>0.2],
              ['k'=>'umbrellaInsurancePct','label'=>'Umbrella / Other Insurance %','max'=>0.05],
              ['k'=>'corporateOverheadPct','label'=>'Corporate Overhead %','max'=>0.3],
              ['k'=>'gaPct','label'=>'G&A %','max'=>0.3],
              ['k'=>'profitFeePct','label'=>'Profit / Fee %','max'=>1.0],
            ];
          @endphp

          <div class="row g-3">
            @foreach($burden as $f)
              <div class="col-md-6">
                <label class="form-label fw-medium mb-1">{{ $f['label'] }}</label>
                <div class="d-flex gap-2 align-items-center">
                  <input type="number" class="form-control gasq-wa-input" id="mi_{{ $f['k'] }}" step="0.0001" min="0" max="2">
                  <input type="range" class="form-range mb-0" min="0" max="{{ $f['max'] }}" step="0.0001" data-sync="mi_{{ $f['k'] }}">
                </div>
              </div>
            @endforeach
            <div class="col-md-6">
              <label class="form-label fw-medium mb-1">Health &amp; Welfare ($/paid hour)</label>
              <div class="d-flex gap-2 align-items-center">
                <input type="number" class="form-control gasq-wa-input" id="mi_healthWelfarePerHour" step="0.01" min="0">
                <input type="range" class="form-range mb-0" min="0" max="50" step="0.01" data-sync="mi_healthWelfarePerHour">
              </div>
            </div>
          </div>

          <hr>
          <div class="small text-gasq-muted">
            Autosave runs in the background. These values will be used by every calculator unless overridden.
          </div>
        </div>
      </div>
    </div>
  </div>

</div>
</div>
@endsection

@push('scripts')
<script>
(() => {
  const DEFAULTS = @json($inputs ?? []);
  const apiUrl = @json(route('api.master-inputs.update'));
  const showUrl = @json(route('api.master-inputs.show'));
  let saveT = null;
  let inflight = null;

  const setErr = (msg) => {
    const el = document.getElementById('mi_err');
    el.textContent = msg || '';
    el.classList.toggle('d-none', !msg);
  };
  const flashOk = (msg) => {
    const el = document.getElementById('mi_ok');
    el.textContent = msg || '';
    el.classList.remove('d-none');
    setTimeout(()=> el.classList.add('d-none'), 1200);
  };

  const clamp = (v, min, max) => Math.min(max, Math.max(min, v));

  function initSliderSync(){
    document.querySelectorAll('input[type="range"][data-sync]').forEach((rangeEl)=>{
      const id = rangeEl.getAttribute('data-sync');
      const numEl = document.getElementById(id);
      if(!numEl) return;

      const syncRangeFromNumber = () => {
        const min = parseFloat(rangeEl.min || '0');
        const max = parseFloat(rangeEl.max || '100');
        const v = parseFloat(numEl.value || rangeEl.value || '0');
        rangeEl.value = String(clamp(v, min, max));
      };
      const syncNumberFromRange = () => { numEl.value = rangeEl.value; };

      syncRangeFromNumber();

      rangeEl.addEventListener('input', () => {
        syncNumberFromRange();
        scheduleSave();
      });
      numEl.addEventListener('input', () => {
        syncRangeFromNumber();
        scheduleSave();
      });
    });
  }

  function collectInputs(){
    const out = {};
    Object.keys(DEFAULTS).forEach((k)=>{
      const el = document.getElementById('mi_'+k);
      if(!el) return;
      const v = parseFloat(el.value);
      out[k] = Number.isFinite(v) ? v : DEFAULTS[k];
    });
    return out;
  }

  async function saveNow(isComplete = null){
    try{
      setErr('');
      if(inflight){ inflight.abort(); }
      inflight = new AbortController();
      const body = { inputs: collectInputs() };
      if(isComplete !== null){ body.is_complete = !!isComplete; }
      const res = await fetch(apiUrl, {
        method: 'PUT',
        signal: inflight.signal,
        headers: {
          'Content-Type': 'application/json',
          'Accept': 'application/json',
          'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
        },
        body: JSON.stringify(body),
      });
      const data = await res.json().catch(()=>null);
      if(!res.ok || !data || !data.ok){
        setErr((data && data.message) ? data.message : 'Unable to save inputs right now.');
        return false;
      }
      return true;
    }catch(e){
      if(e?.name === 'AbortError') return false;
      setErr('Unable to save inputs right now.');
      return false;
    }
  }

  function scheduleSave(){
    clearTimeout(saveT);
    saveT = setTimeout(()=>{ void saveNow(); }, 350);
  }

  async function loadFromServer(){
    try{
      const res = await fetch(showUrl, { headers: { 'Accept': 'application/json' } });
      const data = await res.json().catch(()=>null);
      if(!res.ok || !data || !data.ok) return;
      const inputs = data.inputs || {};
      Object.entries(inputs).forEach(([k,v])=>{
        const el = document.getElementById('mi_'+k);
        if(el){ el.value = v; }
      });
    }catch(e){
      return;
    }
  }

  window.resetInputs = async function(){
    Object.entries(DEFAULTS).forEach(([k,v])=>{
      const el = document.getElementById('mi_'+k);
      if(el){ el.value = v; }
    });
    initSliderSync();
    const ok = await saveNow(false);
    if(ok) flashOk('Reset saved');
  };

  window.markComplete = async function(){
    const ok = await saveNow(true);
    if(ok){
      flashOk('Saved. Redirecting...');
      window.location.href = @json(route('calculator.index'));
    }
  };

  document.addEventListener('DOMContentLoaded', async ()=>{
    // Prefill from server (in case defaults changed or profile already exists)
    await loadFromServer();
    initSliderSync();
    // If not filled server-side, fall back to defaults
    Object.entries(DEFAULTS).forEach(([k,v])=>{
      const el = document.getElementById('mi_'+k);
      if(el && (el.value === '' || el.value === null)){ el.value = v; }
    });
    initSliderSync();
  });
})();
</script>
@endpush


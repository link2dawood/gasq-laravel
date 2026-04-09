@extends('layouts.app')
@section('title', 'Buyer Fit Index')
@section('header_variant', 'dashboard')

@section('content')
<div class="min-vh-100 py-4 px-3 px-md-4" style="background:var(--gasq-background)">
<div class="container-xl">

  <div class="d-flex align-items-center justify-content-between flex-wrap gap-3 mb-4">
    <div class="d-flex align-items-center gap-3">
      <a href="{{ route('calculator.index') }}" class="btn btn-outline-secondary btn-sm"><i class="fa fa-arrow-left"></i></a>
      <div>
        <h1 class="h3 fw-bold mb-0 d-flex align-items-center gap-2">
          <i class="fa fa-clipboard-check text-primary"></i> Buyer Fit Index
        </h1>
        <div class="text-gasq-muted small">Answer a few questions to generate a fit score and guidance.</div>
      </div>
    </div>
    <div class="d-flex flex-wrap gap-2 d-print-none">
      <button class="btn btn-outline-secondary btn-sm" onclick="resetAll()"><i class="fa fa-rotate me-1"></i> Reset</button>
      <button class="btn btn-outline-secondary btn-sm" onclick="window.print()"><i class="fa fa-print me-1"></i> Print</button>
    </div>
  </div>

  <div class="alert alert-danger d-none" id="bfi_error" role="alert"></div>

  <div class="row g-4">
    <div class="col-lg-6">
      <div class="card gasq-card h-100">
        <div class="card-header py-3"><h5 class="card-title mb-0 fw-semibold">Inputs</h5></div>
        <div class="card-body">
          <div class="row g-3">
            <div class="col-12">
              <label class="form-label fw-medium">Contract complexity (1–5)</label>
              <input type="range" class="form-range" id="bfi_complexity" min="1" max="5" step="1" value="3" oninput="scheduleCompute()">
              <div class="small text-gasq-muted">Value: <span id="bfi_complexity_v">3</span></div>
            </div>
            <div class="col-12">
              <label class="form-label fw-medium">Operational risk (1–5)</label>
              <input type="range" class="form-range" id="bfi_risk" min="1" max="5" step="1" value="3" oninput="scheduleCompute()">
              <div class="small text-gasq-muted">Value: <span id="bfi_risk_v">3</span></div>
            </div>
            <div class="col-12">
              <label class="form-label fw-medium">Budget sensitivity (1–5)</label>
              <input type="range" class="form-range" id="bfi_budget" min="1" max="5" step="1" value="3" oninput="scheduleCompute()">
              <div class="small text-gasq-muted">Value: <span id="bfi_budget_v">3</span></div>
            </div>
            <div class="col-12">
              <label class="form-label fw-medium">Preferred engagement</label>
              <select class="form-select" id="bfi_mode" oninput="scheduleCompute()">
                <option value="benchmark">Benchmark & validate</option>
                <option value="procure">Procure services</option>
                <option value="optimize">Optimize existing program</option>
              </select>
            </div>
          </div>
          <p class="small text-gasq-muted mt-3 mb-0">
            This page uses the V24 engine type <code>buyer-fit-index</code>. Run once to enable PDF/email.
          </p>
        </div>
      </div>
    </div>

    <div class="col-lg-6">
      <div class="card gasq-card h-100">
        <div class="card-header py-3"><h5 class="card-title mb-0 fw-semibold">Results</h5></div>
        <div class="card-body">
          <div class="row g-3">
            <div class="col-6">
              <div class="gasq-metric-card text-center">
                <div class="metric-desc">Fit Score</div>
                <div class="metric-value text-primary" id="bfi_score">—</div>
              </div>
            </div>
            <div class="col-6">
              <div class="gasq-metric-card text-center">
                <div class="metric-desc">Recommendation</div>
                <div class="metric-value" id="bfi_rec">—</div>
              </div>
            </div>
          </div>
          <div class="mt-3">
            <div class="small text-gasq-muted mb-1">Details</div>
            <pre class="small p-3 rounded border bg-light mb-0" id="bfi_raw" style="min-height:140px;white-space:pre-wrap"></pre>
          </div>
        </div>
      </div>

      <x-report-actions reportType="buyer-fit-index" />
    </div>
  </div>

</div>
</div>
@endsection

@push('scripts')
<script>
(() => {
  const savedScenario = window.__gasqCalculatorState?.scenario || null;
  const url = @json(route('backend.standalone.v24.compute', ['type' => 'buyer-fit-index']));
  let t = null;
  let inflight = null;

  const setError = (msg) => {
    const el = document.getElementById('bfi_error');
    if (!el) return;
    el.textContent = msg || '';
    el.classList.toggle('d-none', !msg);
  };

  const payload = () => ({
    version: 'v24',
    scenario: {
      meta: {
        complexity: parseInt(document.getElementById('bfi_complexity').value || '3', 10),
        risk: parseInt(document.getElementById('bfi_risk').value || '3', 10),
        budget: parseInt(document.getElementById('bfi_budget').value || '3', 10),
        mode: document.getElementById('bfi_mode').value || 'benchmark',
      }
    }
  });

  function hydrateSavedState(){
    const meta = savedScenario?.meta || {};
    const map = {
      bfi_complexity: meta.complexity,
      bfi_risk: meta.risk,
      bfi_budget: meta.budget,
      bfi_mode: meta.mode,
    };

    Object.entries(map).forEach(([id, value]) => {
      if(value === undefined || value === null) return;
      const el = document.getElementById(id);
      if(el) el.value = value;
    });
  }

  function scheduleCompute(){
    document.getElementById('bfi_complexity_v').textContent = document.getElementById('bfi_complexity').value;
    document.getElementById('bfi_risk_v').textContent = document.getElementById('bfi_risk').value;
    document.getElementById('bfi_budget_v').textContent = document.getElementById('bfi_budget').value;
    clearTimeout(t);
    t = setTimeout(runCompute, 250);
  }

  async function runCompute(){
    try{
      setError('');
      if(inflight){ inflight.abort(); }
      inflight = new AbortController();
      const res = await fetch(url, {
        method: 'POST',
        signal: inflight.signal,
        headers: {
          'Content-Type': 'application/json',
          'Accept': 'application/json',
          'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
        },
        body: JSON.stringify(payload()),
      });

      let data = null;
      try { data = await res.json(); } catch { data = null; }
      if(!res.ok || !data || !data.ok){
        if(data && data.error === 'insufficient_credits'){
          setError(data.message || 'Not enough credits to run this calculator.');
        } else {
          setError('Unable to calculate right now. Please try again.');
        }
        return;
      }

      const k = (data.kpis || {});
      document.getElementById('bfi_score').textContent = (k.score ?? '—');
      document.getElementById('bfi_rec').textContent = (k.recommendation ?? k.recommendationLabel ?? '—');
      document.getElementById('bfi_raw').textContent = JSON.stringify(k, null, 2);
    } catch(e){
      if(e?.name === 'AbortError') return;
      setError('Unable to calculate right now. Please try again.');
    }
  }

  function resetAll(){
    document.getElementById('bfi_complexity').value = 3;
    document.getElementById('bfi_risk').value = 3;
    document.getElementById('bfi_budget').value = 3;
    document.getElementById('bfi_mode').value = 'benchmark';
    scheduleCompute();
  }

  window.resetAll = resetAll;
  document.addEventListener('DOMContentLoaded', () => {
    hydrateSavedState();
    scheduleCompute();
  });
})();
</script>
@endpush

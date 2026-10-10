@push('scripts')
<script>
(() => {
  const presets = {{ \Illuminate\Support\Js::from($vwrPresets) }};
  const classifications = {{ \Illuminate\Support\Js::from($vwrClasses) }};
  const clone = value => JSON.parse(JSON.stringify(value));
  let inputs = clone(window.__gasqCalculatorState?.scenario?.meta?.vendorWeightedRates?.draft || presets.sample);
  let response = null, timer = null, inflight = null, revision = 0;
  const root = document.getElementById('vendor-weighted-rates');
  let signing = false, drawing = false, hasInk = false;
  const canvas = document.getElementById('vwr_signature_canvas');
  const context = canvas.getContext('2d');
  const text = (id, value) => { document.getElementById(id).textContent = value; };
  const esc = value => String(value ?? '').replace(/[&<>"']/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));
  function format(value, kind = 'money') {
    if(value == null) return 'Unavailable';
    if(kind === 'money') return fmt(value);
    if(kind === 'percent') return new Intl.NumberFormat('en-US', {style:'percent',minimumFractionDigits:2,maximumFractionDigits:2}).format(value);
    return new Intl.NumberFormat('en-US', {maximumFractionDigits:2,minimumFractionDigits:kind === 'decimal' ? 2 : 0}).format(value);
  }
  function cell(path, type, options = '') {
    const id = 'vwr_' + path.replaceAll('.', '_');
    const name = path.split('.').pop();
    const label = name.replace(/([A-Z])/g, ' $1').toLowerCase();
    const attributes = `id="${id}" data-vwr-path="${path}" aria-label="Role ${+path.split('.')[1] + 1} ${esc(label)}" aria-describedby="${id}_error"`;
    const input = type === 'select'
      ? `<select class="form-select form-select-sm" ${attributes}>${Object.entries(classifications).map(([key,label]) => `<option value="${key}">${esc(label)}</option>`).join('')}</select>`
      : `<input class="form-control form-control-sm" type="${type}" ${attributes} ${options}>`;
    return input + `<div class="small text-danger" id="${id}_error" data-vwr-error="${path}"></div>`;
  }
  function writeInputs() {
    root.querySelectorAll('[data-vwr-path]').forEach(el => {
      el.value = el.dataset.vwrPath.split('.').reduce((value,key) => value?.[key], inputs) ?? '';
    });
  }
  function renderRows() {
    const current = inputs.mode === 'current';
    text('vwr_mode_help', current
      ? 'Enter current role bill rates. Leave actual wage blank to show “Implied pay wage — model estimate” using bill rate × 7 ÷ 18. Enter an actual wage to replace the estimate; the current bill rate stays unchanged.'
      : 'Enter actual pay wages. Each bill rate is calculated using the fixed GASQ 6:1 model. Model rates describe pricing assumptions; they do not verify employee compensation.');
    document.getElementById('vwr_roles').innerHTML = inputs.roles.map((role,i) => `
      <tr data-vwr-row="${i}">
        <td class="vwr-classification">${cell(`roles.${i}.classification`, 'select')}
          <div class="mt-1">${cell(`roles.${i}.label`, 'text', 'placeholder="Classification label / custom role"')}</div>
          <details class="small mt-2"><summary>Role hour overrides</summary>
            <label class="mt-1" for="vwr_roles_${i}_weeklyHoursPerEmployee">Hours per employee/week</label>
            ${cell(`roles.${i}.weeklyHoursPerEmployee`, 'number', `min="0" max="168" step="any" placeholder="Default: ${esc(inputs.weeklyHoursPerEmployee)}"`)}
            <label class="mt-1" for="vwr_roles_${i}_weeksPerYear">Weeks</label>
            ${cell(`roles.${i}.weeksPerYear`, 'number', `min="0" max="53" step="any" placeholder="Default: ${esc(inputs.weeksPerYear)}"`)}
            <div class="text-gasq-muted">Leave blank to use the shared assumptions.</div>
          </details>
        </td>
        <td>${cell(`roles.${i}.headcount`, 'number', 'min="0" step="1"')}</td>
        <td class="vwr-wage">${cell(`roles.${i}.actualWage`, 'number', `min="0" step="any" placeholder="${current ? 'Actual wage (optional)' : 'Actual wage (required)'}"`)}<div class="small mt-1" data-vwr-wage-label></div></td>
        <td data-vwr-calculated="combinedHourlyWages" data-format="money">—</td>
        <td data-vwr-calculated="wageShareOfBillRate" data-format="percent">—</td>
        <td data-vwr-calculated="weeklyHours" data-format="number">—</td>
        <td data-vwr-calculated="monthlyHours" data-format="decimal">—</td>
        <td data-vwr-calculated="annualRoleHours" data-format="number">—</td>
        <td>${current ? cell(`roles.${i}.billRate`, 'number', 'min="0" step="any"') : '<span data-vwr-calculated="billRate" data-format="money">—</span><div class="small text-gasq-muted">GASQ 6:1 model rate</div>'}</td>
        <td data-vwr-calculated="annualBillCost" data-format="money">—</td>
        <td><button class="btn btn-link btn-sm text-danger d-print-none" type="button" data-vwr-remove="${i}" aria-label="Remove role ${i + 1}">Remove</button></td>
      </tr>`).join('');
    writeInputs();
  }
  function showErrors(fields = {}) {
    root.querySelectorAll('[data-vwr-error]').forEach(el => el.textContent = (fields[el.dataset.vwrError] || []).join(' '));
    root.querySelectorAll('[data-vwr-path]').forEach(el => el.setAttribute('aria-invalid', fields[el.dataset.vwrPath] ? 'true' : 'false'));
  }
  function invalidate() {
    revision++;
    inflight?.abort();
    response = null;
    document.getElementById('vwr_review').disabled = true;
    document.getElementById('vwr_report_download').disabled = true;
    document.getElementById('vwr_signature_submit').disabled = true;
    document.getElementById('vwr_signature_consent').checked = false;
    text('vwr_signature_status','Save the current report before signing.');
    document.getElementById('vwr_preview').hidden = true;
    root.querySelectorAll('[data-vwr-total],[data-vwr-calculated],[data-vwr-wage-label]').forEach(el => el.textContent = '—');
    text('vwr_wage_source', ''); text('vwr_rate_source', '');
    text('vwr_status','Inputs changed. Recalculate and save before applying or downloading.');
    text('vwr_report_status','');
    showErrors();
  }
  function schedule() {
    clearTimeout(timer);
    timer = setTimeout(() => calculate(false), 500);
  }
  async function calculate(apply) {
    clearTimeout(timer);
    inflight?.abort();
    const controller = new AbortController(); inflight = controller;
    const submittedRevision = revision;
    text('vwr_status', apply ? 'Applying vendor comparison…' : 'Calculating and saving estimate…');
    document.getElementById('vwr_apply').disabled = true;
    try {
      const res = await fetch(apply ? '{{ route('backend.vendor-weighted-rates.apply') }}' : '{{ route('backend.vendor-weighted-rates.compute') }}', {
        method:'POST', signal:controller.signal,
        headers:{'Content-Type':'application/json','Accept':'application/json','X-CSRF-TOKEN':document.querySelector('meta[name="csrf-token"]').content},
        body:JSON.stringify({inputs,scenario:buildMainScenario()}),
      });
      const data = await res.json();
      if(submittedRevision !== revision) return;
      if(!res.ok || !data.ok) {
        showErrors(data.result?.fieldErrors || data.errors || {});
        text('vwr_status', res.status === 422 ? 'Correct the fields shown above before applying.' : (data.message || 'Unable to save. Please try again.'));
        return;
      }
      response = data;
      mainScenario = data.scenario;
      renderMainMenu(data.main);
      root.querySelectorAll('[data-vwr-total]').forEach(el => el.textContent = format(data.result.totals[el.dataset.vwrTotal],el.dataset.format));
      root.querySelectorAll('[data-vwr-row]').forEach(tr => {
        const role = data.result.roles[+tr.dataset.vwrRow];
        tr.querySelectorAll('[data-vwr-calculated]').forEach(el => el.textContent = format(role[el.dataset.vwrCalculated],el.dataset.format));
        tr.querySelector('[data-vwr-wage-label]').textContent = `${role.paySourceLabel}: ${format(role.payRate)}/hr`;
      });
      text('vwr_wage_source', data.result.wageSource); text('vwr_rate_source', data.result.rateSource);
      showErrors();
      document.getElementById('vwr_review').disabled = false;
      document.getElementById('vwr_report_download').disabled = false;
      renderSignature(data);
      text('vwr_status', apply ? 'Applied and saved as separate vendor comparison inputs.' : 'Estimate saved. Review the values below before applying to the main calculator.');
      if(apply) document.getElementById('vwr_preview').hidden = true;
    } catch(e) {
      if(e.name !== 'AbortError') text('vwr_status','Unable to calculate or save. Please try again.');
    } finally {
      if(submittedRevision === revision) document.getElementById('vwr_apply').disabled = false;
    }
  }
  root.addEventListener('input', e => {
    const el = e.target.closest('[data-vwr-path]'); if(!el) return;
    const path = el.dataset.vwrPath.split('.');
    const parent = path.slice(0,-1).reduce((value,key) => value[key],inputs);
    parent[path.at(-1)] = el.type === 'number' ? (el.value === '' ? null : Number(el.value)) : el.value;
    if(path.at(-1) === 'classification') inputs.roles[+path[1]].label = el.value === 'custom' ? '' : classifications[el.value];
    invalidate();
    if(el.id === 'vwr_mode' || path.at(-1) === 'classification') renderRows();
    schedule();
  });
  document.getElementById('vwr_load_preset').addEventListener('click', () => {
    inputs = clone(presets[document.getElementById('vwr_preset').value]);
    invalidate(); renderRows(); schedule();
  });
  document.getElementById('vwr_add').addEventListener('click', () => {
    inputs.roles.push({classification:'custom',label:'',headcount:1,actualWage:null,billRate:null,weeklyHoursPerEmployee:null,weeksPerYear:null});
    invalidate(); renderRows();
    text('vwr_status','Enter a classification and its rates, then recalculate.');
  });
  root.addEventListener('click', e => {
    const remove = e.target.closest('[data-vwr-remove]'); if(!remove) return;
    inputs.roles.splice(+remove.dataset.vwrRemove,1);
    invalidate(); renderRows(); schedule();
  });
  document.getElementById('vwr_save').addEventListener('click', () => { invalidate(); calculate(false); });
  document.getElementById('vwr_review').addEventListener('click', () => {
    if(!response) return;
    const p = response.preview;
    text('vwr_transfer_pay',format(p.weightedPayWage)+'/hr'); text('vwr_transfer_bill',format(p.weightedBillRate)+'/hr');
    text('vwr_transfer_source',p.rateSource+' / '+p.mode); text('vwr_transfer_wage_source',p.wageSource);
    text('vwr_transfer_service',format(p.annualBillableServiceHours,'number')); text('vwr_transfer_payroll',format(p.annualPayrollHours,'number'));
    text('vwr_transfer_project',format(p.projectAnnualEstimate)); text('vwr_transfer_baseline',p.buyerBaselineWage == null ? 'No baseline wage entered; it will remain unchanged.' : format(p.buyerBaselineWage)+'/hr');
    document.getElementById('vwr_transfer_staffing').innerHTML = response.result.roles.map(role => `<li>${esc(role.label)}: ${role.headcount} employees × ${format(role.weeklyHoursPerEmployee,'number')} hours/week × ${format(role.weeksPerYear,'number')} weeks = ${format(role.annualRoleHours,'number')} payroll hours</li>`).join('');
    document.getElementById('vwr_preview').hidden = false;
    document.getElementById('vwr_preview').scrollIntoView({block:'nearest',behavior:'smooth'});
  });
  document.getElementById('vwr_apply').addEventListener('click', () => { if(response) calculate(true); });
  function renderSignature(data) {
    document.getElementById('vwr_signature_submit').disabled = !response || signing;
    if(data.signatureStatus === 'signed') {
      const signature = data.signature;
      text('vwr_signature_status',`Signed by ${signature.signerName} on ${new Date(signature.signedAt).toLocaleString()}. The protected PDF will include this signature.`);
    } else {
      text('vwr_signature_status',data.signatureStatus === 'changed'
        ? 'The report changed since it was signed. Review and sign this version before downloading a signed PDF.'
        : 'This saved report has not been signed.');
    }
  }
  function clearDrawing() {
    context.fillStyle = '#fff'; context.fillRect(0,0,canvas.width,canvas.height);
    context.strokeStyle = '#19334f'; context.lineWidth = 3; context.lineCap = 'round'; context.lineJoin = 'round';
    hasInk = false;
  }
  function point(event) {
    const bounds = canvas.getBoundingClientRect();
    return [(event.clientX - bounds.left) * canvas.width / bounds.width,(event.clientY - bounds.top) * canvas.height / bounds.height];
  }
  canvas.addEventListener('pointerdown', event => {
    if(event.button !== 0 || !event.isPrimary) return;
    drawing = true; canvas.setPointerCapture(event.pointerId);
    context.beginPath(); context.moveTo(...point(event)); event.preventDefault();
  });
  canvas.addEventListener('pointermove', event => {
    if(!drawing) return;
    context.lineTo(...point(event)); context.stroke(); hasInk = true; event.preventDefault();
  });
  canvas.addEventListener('pointerup', () => drawing = false);
  canvas.addEventListener('pointercancel', () => drawing = false);
  document.getElementById('vwr_signature_clear').addEventListener('click',clearDrawing);
  document.getElementById('vwr_signature_method').addEventListener('change', event => {
    document.getElementById('vwr_draw_area').hidden = event.target.value !== 'drawn';
  });
  clearDrawing();
  document.getElementById('vwr_signature_form').addEventListener('submit', async event => {
    event.preventDefault();
    if(!response || signing) return;
    const method = document.getElementById('vwr_signature_method').value;
    if(method === 'drawn' && !hasInk) {
      text('vwr_signature_status','Draw your signature first, or choose Type my name.'); return;
    }
    signing = true;
    const submittedRevision = revision;
    document.getElementById('vwr_signature_submit').disabled = true;
    text('vwr_signature_status','Saving your electronic signature…');
    try {
      const res = await fetch('{{ route('backend.vendor-weighted-rates.sign') }}', {
        method:'POST',
        headers:{'Content-Type':'application/json','Accept':'application/json','X-CSRF-TOKEN':document.querySelector('meta[name="csrf-token"]').content},
        body:JSON.stringify({reportHash:response.reportHash,signerName:document.getElementById('vwr_signer_name').value,
          signerTitle:document.getElementById('vwr_signer_title').value,method,
          ...(method === 'drawn' ? {signatureImage:canvas.toDataURL('image/png')} : {}),
          consent:document.getElementById('vwr_signature_consent').checked}),
      });
      const data = await res.json();
      if(!res.ok || !data.ok) throw new Error(Object.values(data.errors || {}).flat().join(' ') || data.message || 'Unable to sign. Please try again.');
      if(submittedRevision !== revision) return;
      response = {...response,...data};
      renderSignature(response);
      document.getElementById('vwr_signature_consent').checked = false;
    } catch(error) {
      if(submittedRevision === revision) text('vwr_signature_status',error.message);
    } finally {
      signing = false;
      document.getElementById('vwr_signature_submit').disabled = !response;
    }
  });
  document.getElementById('vwr_report_form').addEventListener('submit', async e => {
    e.preventDefault();
    if(!response) return;
    const passwordField = document.getElementById('vwr_pdf_password');
    const password = passwordField.value.trim();
    if(password.length < 8 || password.length > 32 || !/^[\x20-\x7E]+$/.test(password)) {
      text('vwr_report_status','Use an 8–32 character password with standard letters, numbers, or symbols.');
      return;
    }
    const button = document.getElementById('vwr_report_download');
    button.disabled = true;
    text('vwr_report_status','Preparing protected PDF…');
    try {
      const res = await fetch('{{ route('reports.download') }}', {
        method:'POST',
        headers:{'Content-Type':'application/json','Accept':'application/json','X-CSRF-TOKEN':document.querySelector('meta[name="csrf-token"]').content},
        body:JSON.stringify({type:'vendor-weighted-rates',pdf_password:password}),
      });
      if(!res.ok || !res.headers.get('Content-Type')?.includes('application/pdf')) {
        const data = await res.json().catch(() => ({}));
        throw new Error(Object.values(data.errors || {}).flat().join(' ') || data.message || 'Unable to download the report. Please try again.');
      }
      const url = URL.createObjectURL(await res.blob());
      const link = document.createElement('a');
      link.href = url;
      link.download = res.headers.get('Content-Disposition')?.match(/filename="?([^";]+)/)?.[1] || 'GASQ-Vendor-Weighted-Rate-Dashboard.pdf';
      document.body.appendChild(link); link.click(); link.remove();
      setTimeout(() => URL.revokeObjectURL(url), 30000);
      passwordField.value = '';
      text('vwr_report_status','Protected PDF downloaded. Keep the password separately; it is not saved with the estimate.');
    } catch(error) {
      text('vwr_report_status',error.message);
    } finally {
      button.disabled = !response;
    }
  });
  window.VendorWeightedRates = {
    refreshContext(){ invalidate(); schedule(); },
    requestReport(){
      root.open = true;
      document.getElementById('vwr_report_form').scrollIntoView({block:'center',behavior:'smooth'});
      document.getElementById('vwr_pdf_password').focus({preventScroll:true});
    },
  };
  document.addEventListener('DOMContentLoaded', () => {
    renderRows();
    if(location.hash === '#vendor-weighted-rates') root.open = true;
    schedule();
  });
})();
</script>
@endpush

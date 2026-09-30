{{-- The secure viewer.

     Pages are drawn to a canvas from an authorised byte route, so there is no
     file URL to copy and no download unless GASQ allows one. The watermark is
     drawn onto each page after it renders, carrying the viewer's own address,
     so a screenshot stays traceable to this session.

     Known limit, stated plainly: the page bytes still reach the browser, so a
     determined viewer can keep a copy. Server-side page rendering removes that,
     and slots in behind the same route once the host supports it. --}}
@extends('secure-documents._shell', [
    'title' => $document->public_id,
    'barMeta' => $document->public_id . ' · ' . $recipient->email,
])

@section('full')
<div class="sdv">
    <div class="sdv-head">
        <div>
            <div class="sd-kicker">{{ $document->typeLabel() }}</div>
            <h1 class="sdv-title">{{ $document->title }}</h1>
            <div class="sd-meta">
                {{ $document->public_id }}
                @if($version) · version {{ $version->version_number }}@endif
                @if($document->expires_at) · access until {{ $document->expires_at->format('F j, Y') }}@endif
            </div>
        </div>
        <div class="sdv-actions">
            <span class="sdv-pager">Page <span id="sdv_page">1</span> of <span id="sdv_pages">…</span></span>
            <button type="button" class="sd-btn secondary" id="sdv_prev" aria-label="Previous page">‹</button>
            <button type="button" class="sd-btn secondary" id="sdv_next" aria-label="Next page">›</button>
            @if($document->allow_download)
                <a class="sd-btn" href="{{ route('secure-documents.file') }}" download>Download</a>
            @endif
        </div>
    </div>

    <div class="sdv-stage" id="sdv_stage">
        <div class="sdv-loading" id="sdv_loading">Preparing your document…</div>
        <div class="sdv-page-wrap" id="sdv_pagewrap" hidden>
            <canvas id="sdv_canvas"></canvas>
        </div>
    </div>

    <p class="sdv-foot">
        Confidential and prepared for {{ $recipient->email }}.
        @unless($document->allow_download) This document is not available for download.@endunless
        @unless($document->allow_print) Printing is not enabled for this document.@endunless
    </p>
</div>

<style>
    .sdv { max-width: 62rem; margin: 0 auto; padding: 18px 16px 48px; }
    .sdv-head { display:flex; justify-content:space-between; align-items:flex-end; gap:1rem; flex-wrap:wrap; margin-bottom:14px; }
    .sdv-title { font-size:1.25rem; margin:.15rem 0 .2rem; }
    .sdv-actions { display:flex; align-items:center; gap:.4rem; flex-wrap:wrap; }
    .sdv-actions .sd-btn { padding:8px 14px; font-size:.9rem; }
    .sdv-pager { font-size:.82rem; color:var(--muted); margin-right:.3rem; font-variant-numeric:tabular-nums; }
    .sdv-stage { background:#fff; border:1px solid var(--line); border-radius:12px; padding:14px; min-height:60vh;
                 display:flex; align-items:center; justify-content:center; overflow:auto; }
    .sdv-loading { color:var(--muted); font-size:.9rem; }
    .sdv-page-wrap { position:relative; }
    #sdv_canvas { max-width:100%; height:auto; display:block; box-shadow:0 1px 6px rgba(15,23,42,.12); }
    .sdv-foot { margin-top:14px; font-size:.78rem; color:var(--muted); text-align:center; }
    @media print { .sdv { display:none; } }
</style>

<script src="https://cdn.jsdelivr.net/npm/pdfjs-dist@3.11.174/build/pdf.min.js"></script>
<script>
(() => {
  const FILE = @json(route('secure-documents.file'));
  const BEAT = @json(route('secure-documents.heartbeat'));
  const CSRF = document.querySelector('meta[name="csrf-token"]').content;
  const WATERMARK = @json(array_values(array_filter($watermark)));
  const ALLOW_PRINT = @json((bool) $document->allow_print);

  const canvas = document.getElementById('sdv_canvas');
  const ctx = canvas.getContext('2d');
  let pdf = null, page = 1, rendering = false;

  pdfjsLib.GlobalWorkerOptions.workerSrc = 'https://cdn.jsdelivr.net/npm/pdfjs-dist@3.11.174/build/pdf.worker.min.js';

  async function load(){
    try {
      pdf = await pdfjsLib.getDocument({ url: FILE, withCredentials: true }).promise;
      document.getElementById('sdv_pages').textContent = pdf.numPages;
      document.getElementById('sdv_loading').hidden = true;
      document.getElementById('sdv_pagewrap').hidden = false;
      await render(1);
    } catch (e) {
      document.getElementById('sdv_loading').textContent =
        'This document could not be opened. Please contact GASQ.';
    }
  }

  async function render(n){
    if(!pdf || rendering || n < 1 || n > pdf.numPages) return;
    rendering = true;
    const p = await pdf.getPage(n);
    const width = Math.min(document.getElementById('sdv_stage').clientWidth - 28, 900);
    const base = p.getViewport({ scale: 1 });
    const viewport = p.getViewport({ scale: width / base.width });

    canvas.width = viewport.width;
    canvas.height = viewport.height;
    await p.render({ canvasContext: ctx, viewport }).promise;
    stamp();

    page = n;
    document.getElementById('sdv_page').textContent = n;
    rendering = false;
    seen.add(n);
  }

  // Drawn onto the same canvas as the page, so it is part of any screenshot.
  function stamp(){
    ctx.save();
    ctx.globalAlpha = 0.14;
    ctx.fillStyle = '#0b2447';
    ctx.font = `${Math.round(canvas.width / 26)}px -apple-system, Arial, sans-serif`;
    ctx.textAlign = 'center';
    ctx.translate(canvas.width / 2, canvas.height / 2);
    ctx.rotate(-Math.PI / 6);
    WATERMARK.forEach((line, i) => {
      const size = i === 0 ? canvas.width / 16 : canvas.width / 38;
      ctx.font = `${i === 0 ? 700 : 400} ${Math.round(size)}px -apple-system, Arial, sans-serif`;
      ctx.fillText(line, 0, (i - (WATERMARK.length - 1) / 2) * (canvas.width / 22));
    });
    ctx.restore();
  }

  document.getElementById('sdv_prev').addEventListener('click', () => render(page - 1));
  document.getElementById('sdv_next').addEventListener('click', () => render(page + 1));
  document.addEventListener('keydown', e => {
    if(e.key === 'ArrowLeft') render(page - 1);
    if(e.key === 'ArrowRight') render(page + 1);
  });
  window.addEventListener('resize', () => render(page));

  if(!ALLOW_PRINT){
    // Deterrence, not prevention: stated as such to GASQ and to the reader.
    window.addEventListener('beforeprint', () => { document.body.style.visibility = 'hidden'; });
    window.addEventListener('afterprint', () => { document.body.style.visibility = ''; });
  }

  // ── Reading time: only counts while the tab is visible and the reader is
  // active, so a tab left open overnight does not become "viewed all night".
  const seen = new Set([1]);
  let lastBeat = Date.now(), active = true, idleTimer = null;

  function markActive(){
    active = true;
    clearTimeout(idleTimer);
    idleTimer = setTimeout(() => { active = false; }, 90000);
  }
  ['mousemove','keydown','scroll','touchstart','click'].forEach(evt =>
    window.addEventListener(evt, markActive, { passive: true }));
  document.addEventListener('visibilitychange', () => {
    if(document.hidden){ active = false; } else { lastBeat = Date.now(); markActive(); }
  });
  markActive();

  async function beat(closing = false){
    const seconds = Math.round((Date.now() - lastBeat) / 1000);
    lastBeat = Date.now();
    if(!closing && (!active || document.hidden || seconds <= 0)) return;
    const body = JSON.stringify({ seconds: Math.min(seconds, 300), page, closing });
    if(closing && navigator.sendBeacon){
      navigator.sendBeacon(BEAT, new Blob([body], { type: 'application/json' }));
      return;
    }
    try {
      await fetch(BEAT, { method:'POST', credentials:'same-origin',
        headers: { 'Content-Type':'application/json', 'X-CSRF-TOKEN': CSRF, 'Accept':'application/json' },
        body });
    } catch (e) { /* a missed beat only loses a few seconds of timing */ }
  }

  setInterval(() => beat(false), 15000);
  window.addEventListener('pagehide', () => beat(true));

  load();
})();
</script>
@endsection

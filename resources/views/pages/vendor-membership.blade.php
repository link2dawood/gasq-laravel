@extends('layouts.app')

@section('title', 'Vendor Membership & Procurement Credit Guide')

@push('styles')
<style>
  /* ================= GASQ Vendor Membership — scoped under .vm-page ================= */
  .vm-page {
    --vm-navy:#0d233f; --vm-navy-800:#123055; --vm-navy-700:#1b3f68;
    --vm-blue:#2a6fc4; --vm-gold:#e3b444; --vm-gold-deep:#c2921d;
    --vm-ink:#17222f; --vm-muted:#566579;
    --vm-paper:#eef3fa; --vm-surface:#ffffff; --vm-surface-2:#f6f9fd;
    --vm-border:#d3e0ef; --vm-border-strong:#b9cbe1;
    --vm-good:#14855c; --vm-bad:#c23b2c;
    --vm-shadow-sm:0 1px 2px rgba(13,35,63,.06),0 2px 8px rgba(13,35,63,.05);
    --vm-shadow-md:0 10px 34px rgba(13,35,63,.10);
    --vm-shadow-lg:0 26px 60px rgba(13,35,63,.16);
    --vm-display:Georgia,"Iowan Old Style","Palatino Linotype","Book Antiqua","Times New Roman",serif;
    --vm-mono:ui-monospace,"SF Mono",SFMono-Regular,Menlo,Consolas,"Liberation Mono",monospace;
    background:var(--vm-paper); color:var(--vm-ink); line-height:1.6;
  }
  .vm-page *{ box-sizing:border-box; }
  .vm-wrap{ max-width:1160px; margin:0 auto; padding:0 24px; }
  .vm-page a{ text-decoration:none; }

  .vm-page h1,.vm-page h2,.vm-page h3{ font-family:var(--vm-display); font-weight:700; letter-spacing:-.01em; margin:0; }
  .vm-page h2{ text-wrap:balance; }
  .vm-num{ font-family:var(--vm-mono); font-variant-numeric:tabular-nums; letter-spacing:-.02em; }

  .vm-eyebrow{ text-transform:uppercase; letter-spacing:.18em; font-size:.74rem; font-weight:800; color:var(--vm-gold-deep); }
  .vm-hero .vm-eyebrow{ color:var(--vm-gold); }
  .vm-rule{ height:2px; width:54px; background:var(--vm-gold); border:0; margin:0 0 22px; }
  .vm-rule.vm-center{ margin-left:auto; margin-right:auto; }

  /* Buttons */
  .vm-btn{ display:inline-flex; align-items:center; gap:9px; padding:13px 22px; border-radius:9px;
    font-weight:700; font-size:.96rem; border:1px solid transparent; cursor:pointer;
    transition:transform .18s ease, box-shadow .18s ease, background .18s ease, color .18s ease, border-color .18s ease; }
  .vm-btn:focus-visible{ outline:3px solid var(--vm-gold); outline-offset:2px; }
  .vm-btn-gold{ background:var(--vm-gold); color:#2a1c00; box-shadow:0 8px 20px rgba(227,180,68,.30); }
  .vm-btn-gold:hover{ transform:translateY(-2px); box-shadow:0 14px 30px rgba(227,180,68,.42); color:#2a1c00; }
  .vm-btn-ghost{ border-color:rgba(255,255,255,.55); color:#fff; }
  .vm-btn-ghost:hover{ background:rgba(255,255,255,.10); transform:translateY(-2px); color:#fff; }
  .vm-btn-outline{ border-color:var(--vm-border-strong); color:var(--vm-ink); background:var(--vm-surface); }
  .vm-btn-outline:hover{ border-color:var(--vm-gold-deep); color:var(--vm-gold-deep); transform:translateY(-2px); }

  /* Hero */
  .vm-hero{ position:relative; color:#fff; overflow:hidden;
    background:radial-gradient(1100px 520px at 88% -10%, rgba(227,180,68,.22), transparent 60%),
      linear-gradient(160deg,var(--vm-navy) 0%,#0a1c33 62%,#0a1a30 100%); }
  .vm-hero::after{ content:""; position:absolute; inset:0; pointer-events:none;
    background-image:linear-gradient(rgba(255,255,255,.04) 1px,transparent 1px); background-size:100% 34px;
    -webkit-mask-image:linear-gradient(to bottom,transparent,#000 40%,transparent);
    mask-image:linear-gradient(to bottom,transparent,#000 40%,transparent); opacity:.5; }
  .vm-hero .vm-wrap{ position:relative; z-index:1; display:grid; grid-template-columns:1.32fr .68fr; gap:46px;
    align-items:center; padding-top:72px; padding-bottom:82px; }
  .vm-hero h1{ font-size:clamp(2.4rem,5.2vw,4.15rem); line-height:1.04; margin:16px 0 20px; color:#fff; }
  .vm-hero h1 .vm-accent{ color:var(--vm-gold); font-style:italic; }
  .vm-lead{ font-size:1.15rem; color:#d9e6f5; max-width:620px; margin:0 0 30px; }
  .vm-cta-row{ display:flex; flex-wrap:wrap; gap:14px; }
  .vm-trust{ margin-top:26px; font-size:.84rem; color:#9db6d3; display:flex; flex-wrap:wrap; gap:8px 18px; }
  .vm-trust span{ display:inline-flex; align-items:center; gap:7px; }
  .vm-trust .vm-dot{ color:var(--vm-gold); }

  .vm-prospectus{ background:linear-gradient(180deg,rgba(255,255,255,.09),rgba(255,255,255,.04));
    border:1px solid rgba(255,255,255,.20); border-radius:16px; padding:26px; box-shadow:var(--vm-shadow-lg); }
  .vm-prospectus .vm-cap{ font-size:.72rem; letter-spacing:.16em; text-transform:uppercase; color:var(--vm-gold); font-weight:800; }
  .vm-prospectus h3{ color:#fff; font-size:1.34rem; margin:8px 0 4px; }
  .vm-prospectus .vm-motto{ color:#dbe7f6; font-size:.98rem; margin:0 0 16px; }
  .vm-prospectus ul{ list-style:none; margin:0; padding:16px 0 0; border-top:1px solid rgba(255,255,255,.16); display:grid; gap:12px; }
  .vm-prospectus li{ display:grid; grid-template-columns:20px 1fr; gap:11px; font-size:.93rem; color:#e7f0fb; }
  .vm-prospectus li .vm-ck{ color:var(--vm-gold); font-weight:900; }
  .vm-prospectus .vm-rate{ font-family:var(--vm-mono); color:#fff; }

  /* Sections */
  .vm-section{ padding:76px 0; }
  .vm-sec-head{ max-width:720px; margin:0 0 40px; }
  .vm-sec-head.vm-center{ margin-left:auto; margin-right:auto; text-align:center; }
  .vm-sec-head h2{ color:var(--vm-ink); font-size:clamp(1.9rem,3.4vw,2.9rem); line-height:1.08; margin:0 0 12px; }
  .vm-sec-head p{ margin:0; color:var(--vm-muted); font-size:1.06rem; }
  .vm-band{ background:var(--vm-surface); border-top:1px solid var(--vm-border); border-bottom:1px solid var(--vm-border); }

  /* Contrast ledger */
  .vm-ledger{ display:grid; grid-template-columns:1fr 1fr; border:1px solid var(--vm-border); border-radius:14px; overflow:hidden; box-shadow:var(--vm-shadow-sm); }
  .vm-ledger>div{ padding:30px; }
  .vm-ledger .vm-old{ background:var(--vm-surface-2); }
  .vm-ledger .vm-new{ background:linear-gradient(180deg,#eef2f7,var(--vm-surface)); border-left:3px solid var(--vm-gold); }
  .vm-ledger h3{ margin:8px 0 16px; font-size:1.3rem; color:var(--vm-ink); }
  .vm-ledger .vm-tag{ font-size:.72rem; letter-spacing:.14em; text-transform:uppercase; font-weight:800; }
  .vm-ledger .vm-old .vm-tag{ color:var(--vm-muted); }
  .vm-ledger .vm-new .vm-tag{ color:var(--vm-gold-deep); }
  .vm-ledger ul{ list-style:none; margin:0; padding:0; display:grid; gap:13px; }
  .vm-ledger li{ display:grid; grid-template-columns:22px 1fr; gap:10px; font-size:.96rem; align-items:start; }
  .vm-ledger .vm-x{ color:var(--vm-bad); font-weight:900; }
  .vm-ledger .vm-c{ color:var(--vm-good); font-weight:900; }

  /* Cards */
  .vm-grid-3{ display:grid; grid-template-columns:repeat(3,1fr); gap:22px; }
  .vm-grid-2{ display:grid; grid-template-columns:repeat(2,1fr); gap:22px; }
  .vm-card{ background:var(--vm-surface); border:1px solid var(--vm-border); border-radius:14px; padding:26px;
    box-shadow:var(--vm-shadow-sm); transition:transform .2s ease, box-shadow .2s ease, border-color .2s ease; }
  .vm-card:hover{ transform:translateY(-4px); box-shadow:var(--vm-shadow-md); border-color:var(--vm-border-strong); }
  .vm-card h3{ color:var(--vm-ink); margin:12px 0 8px; font-size:1.24rem; }
  .vm-card p{ margin:0; color:var(--vm-muted); font-size:.97rem; }
  .vm-badge{ display:inline-block; font-size:.72rem; font-weight:800; letter-spacing:.04em; color:var(--vm-blue);
    background:rgba(42,111,196,.12); padding:5px 10px; border-radius:999px; }
  .vm-icon{ width:42px; height:42px; border-radius:10px; display:grid; place-items:center; background:rgba(227,180,68,.16); color:var(--vm-gold-deep); }
  .vm-icon svg{ width:22px; height:22px; }

  /* Plans */
  .vm-side-plans{ display:grid; grid-template-columns:1fr 1fr; gap:18px; margin-bottom:26px; }
  .vm-side-plan{ display:flex; align-items:center; justify-content:space-between; gap:18px; background:var(--vm-surface);
    border:1px dashed var(--vm-border-strong); border-radius:12px; padding:18px 22px; }
  .vm-side-plan h4{ margin:0 0 3px; font-family:var(--vm-display); font-size:1.1rem; color:var(--vm-ink); }
  .vm-side-plan p{ margin:0; font-size:.88rem; color:var(--vm-muted); }
  .vm-side-plan .vm-price{ font-family:var(--vm-mono); font-weight:700; color:var(--vm-ink); white-space:nowrap; }

  .vm-plans{ display:grid; grid-template-columns:repeat(3,1fr); gap:22px; align-items:stretch; }
  .vm-plan{ position:relative; background:var(--vm-surface); border:1px solid var(--vm-border); border-radius:16px;
    padding:30px 26px; display:flex; flex-direction:column; box-shadow:var(--vm-shadow-sm); transition:transform .2s ease, box-shadow .2s ease; }
  .vm-plan:hover{ transform:translateY(-4px); box-shadow:var(--vm-shadow-md); }
  .vm-plan.vm-featured{ border:1.5px solid var(--vm-gold); box-shadow:var(--vm-shadow-lg); background:linear-gradient(180deg,#fdf8ec,var(--vm-surface)); }
  .vm-plan .vm-flag{ position:absolute; top:-13px; left:50%; transform:translateX(-50%); background:var(--vm-gold); color:#2a1c00;
    font-size:.72rem; font-weight:800; letter-spacing:.06em; text-transform:uppercase; padding:6px 14px; border-radius:999px; white-space:nowrap; }
  .vm-plan h3{ margin:0 0 4px; font-size:1.4rem; color:var(--vm-ink); }
  .vm-plan .vm-tier-note{ color:var(--vm-muted); font-size:.88rem; min-height:38px; margin-bottom:12px; }
  .vm-plan .vm-price-row{ display:flex; align-items:baseline; gap:6px; margin-bottom:4px; }
  .vm-plan .vm-amt{ font-family:var(--vm-mono); font-size:2.5rem; font-weight:700; color:var(--vm-ink); letter-spacing:-.03em; }
  .vm-plan .vm-per{ color:var(--vm-muted); font-size:.95rem; }
  .vm-plan .vm-credits{ font-family:var(--vm-mono); font-size:.9rem; color:var(--vm-gold-deep); font-weight:700; margin-bottom:18px; }
  .vm-plan ul{ list-style:none; margin:0 0 24px; padding:18px 0 0; border-top:1px solid var(--vm-border); display:grid; gap:12px; flex:1; }
  .vm-plan li{ display:grid; grid-template-columns:20px 1fr; gap:10px; font-size:.93rem; color:var(--vm-ink); }
  .vm-plan li .vm-ck{ color:var(--vm-good); font-weight:900; }
  .vm-plan .vm-btn{ justify-content:center; width:100%; }

  /* Comparison table */
  .vm-table-note{ margin:30px 0 12px; font-weight:700; color:var(--vm-ink); font-family:var(--vm-display); font-size:1.2rem; }
  .vm-table-scroll{ overflow-x:auto; border:1px solid var(--vm-border); border-radius:14px; box-shadow:var(--vm-shadow-sm); }
  .vm-table-scroll table{ width:100%; border-collapse:collapse; min-width:760px; background:var(--vm-surface); }
  .vm-table-scroll thead th{ background:var(--vm-navy); color:#fff; font-weight:700; font-size:.84rem; letter-spacing:.02em; text-align:left; padding:14px 16px; }
  .vm-table-scroll tbody td,.vm-table-scroll tbody th{ padding:14px 16px; border-bottom:1px solid var(--vm-border); text-align:left; font-size:.92rem; vertical-align:middle; }
  .vm-table-scroll tbody th{ font-weight:700; color:var(--vm-ink); }
  .vm-table-scroll tbody tr:last-child td,.vm-table-scroll tbody tr:last-child th{ border-bottom:0; }
  .vm-table-scroll tbody tr.vm-hi th,.vm-table-scroll tbody tr.vm-hi td{ background:rgba(227,180,68,.09); }
  .vm-cell-num{ font-family:var(--vm-mono); font-variant-numeric:tabular-nums; }
  .vm-yes{ color:var(--vm-good); font-weight:800; }
  .vm-no{ color:var(--vm-bad); font-weight:800; }

  /* Guarantees */
  .vm-stamp-cards{ display:grid; grid-template-columns:1fr 1fr; gap:22px; }
  .vm-stamp{ background:var(--vm-surface); border:1px solid var(--vm-border); border-radius:14px; padding:28px; box-shadow:var(--vm-shadow-sm); }
  .vm-seal{ font-size:.72rem; font-weight:800; letter-spacing:.1em; text-transform:uppercase; color:var(--vm-gold-deep);
    border:1px solid var(--vm-gold); display:inline-block; padding:4px 10px; border-radius:6px; }
  .vm-stamp h3{ margin:14px 0 8px; color:var(--vm-ink); font-size:1.28rem; }
  .vm-stamp p{ color:var(--vm-muted); font-size:.96rem; margin:0 0 10px; }
  .vm-example{ margin-top:22px; color:#fff; border-radius:14px; padding:30px; background:linear-gradient(135deg,var(--vm-navy),var(--vm-navy-700));
    box-shadow:var(--vm-shadow-md); display:grid; grid-template-columns:1fr auto; gap:24px; align-items:center; }
  .vm-example h3{ margin:0 0 8px; color:#fff; font-size:1.35rem; }
  .vm-example p{ margin:0; color:#d7e4f3; font-size:.98rem; max-width:640px; }
  .vm-example .vm-figure{ text-align:right; }
  .vm-example .vm-figure .vm-big{ font-family:var(--vm-mono); font-size:2.3rem; font-weight:700; color:var(--vm-gold); display:block; letter-spacing:-.02em; }
  .vm-example .vm-figure small{ color:#b9cbe1; font-size:.8rem; letter-spacing:.04em; text-transform:uppercase; }

  /* Process */
  .vm-steps{ display:grid; grid-template-columns:repeat(5,1fr); gap:16px; }
  .vm-step{ background:var(--vm-surface); border:1px solid var(--vm-border); border-radius:12px; padding:22px 18px; box-shadow:var(--vm-shadow-sm); }
  .vm-step .vm-n{ font-family:var(--vm-mono); width:34px; height:34px; border-radius:50%; display:grid; place-items:center;
    background:var(--vm-navy); color:var(--vm-gold); font-weight:700; margin-bottom:14px; }
  .vm-step strong{ color:var(--vm-ink); display:block; margin-bottom:6px; font-family:var(--vm-display); }
  .vm-step span{ color:var(--vm-muted); font-size:.9rem; }

  /* FAQ */
  .vm-faq{ display:grid; gap:12px; max-width:860px; margin:0 auto; }
  details.vm-q{ background:var(--vm-surface); border:1px solid var(--vm-border); border-radius:12px; padding:4px 20px; box-shadow:var(--vm-shadow-sm); }
  details.vm-q summary{ cursor:pointer; list-style:none; padding:16px 0; font-weight:700; color:var(--vm-ink);
    display:flex; align-items:center; justify-content:space-between; gap:16px; font-size:1.03rem; }
  details.vm-q summary::-webkit-details-marker{ display:none; }
  details.vm-q summary .vm-plus{ color:var(--vm-gold-deep); font-family:var(--vm-mono); font-size:1.3rem; transition:transform .2s ease; }
  details.vm-q[open] summary .vm-plus{ transform:rotate(45deg); }
  details.vm-q p{ margin:0 0 18px; color:var(--vm-muted); font-size:.97rem; }

  /* Promise */
  .vm-promise{ color:#fff; text-align:center; position:relative; overflow:hidden;
    background:radial-gradient(800px 400px at 50% -20%, rgba(227,180,68,.20), transparent 60%), linear-gradient(180deg,var(--vm-navy),#0a1a30); }
  .vm-promise h2{ color:#fff; font-size:clamp(2rem,4vw,3.2rem); margin:0 0 18px; }
  .vm-promise p{ max-width:820px; margin:0 auto 16px; color:#d7e4f3; font-size:1.08rem; }
  .vm-tagline{ margin-top:30px; color:var(--vm-gold); font-weight:800; font-size:1.05rem; font-family:var(--vm-display); font-style:italic; }
  .vm-promise .vm-cta-row{ justify-content:center; margin-top:30px; }

  /* Report dashboard */
  .vm-dash-band{ background:var(--vm-navy); color:#fff; border-radius:10px; text-align:center; font-weight:800;
    letter-spacing:.12em; text-transform:uppercase; font-size:.76rem; padding:10px 14px; margin-bottom:22px; }
  .vm-tiles{ display:grid; grid-template-columns:repeat(4,1fr); gap:16px; margin-bottom:26px; }
  .vm-tile{ background:var(--vm-surface); border:1px solid var(--vm-border); border-radius:14px; padding:22px; box-shadow:var(--vm-shadow-sm); }
  .vm-tile .vm-tlabel{ font-size:.72rem; text-transform:uppercase; letter-spacing:.07em; color:var(--vm-muted); font-weight:700; }
  .vm-tile .vm-tval{ font-family:var(--vm-mono); font-size:1.85rem; font-weight:700; letter-spacing:-.02em; color:var(--vm-ink); margin-top:8px; line-height:1.05; }
  .vm-tile .vm-tval.vm-gold{ color:var(--vm-gold-deep); }
  .vm-tile .vm-tval.vm-green{ color:var(--vm-good); }
  .vm-tile .vm-tsub{ font-size:.8rem; color:var(--vm-muted); margin-top:6px; }
  .vm-compare{ background:var(--vm-surface); border:1px solid var(--vm-border); border-radius:14px; padding:26px 28px; box-shadow:var(--vm-shadow-sm); }
  .vm-compare .vm-ctitle{ font-family:var(--vm-display); font-size:1.2rem; color:var(--vm-ink); margin:0 0 18px; }
  .vm-cbar-row{ margin-bottom:16px; }
  .vm-cbar-label{ display:flex; justify-content:space-between; font-size:.9rem; color:var(--vm-ink); font-weight:600; margin-bottom:6px; }
  .vm-cbar-label .vm-cval{ font-family:var(--vm-mono); font-variant-numeric:tabular-nums; }
  .vm-cbar-track{ background:var(--vm-surface-2); border:1px solid var(--vm-border); border-radius:6px; height:34px; overflow:hidden; }
  .vm-cbar-fill{ height:100%; border-radius:0 6px 6px 0; display:flex; align-items:center; padding-left:14px; color:#fff; font-family:var(--vm-mono); font-size:.82rem; font-weight:700; }
  .vm-cbar-fill.vm-navy{ background:var(--vm-navy); }
  .vm-cbar-fill.vm-green{ background:var(--vm-good); }
  .vm-recovery{ margin-top:6px; padding-top:16px; border-top:1px dashed var(--vm-border-strong); font-size:.98rem; color:var(--vm-ink); display:flex; align-items:center; gap:10px; flex-wrap:wrap; }
  .vm-recovery .vm-pill{ font-family:var(--vm-mono); font-weight:700; color:#2a1c00; background:var(--vm-gold); padding:4px 12px; border-radius:999px; }
  .vm-dash-note{ text-align:center; color:var(--vm-muted); font-size:.82rem; margin-top:18px; max-width:640px; margin-inline:auto; }

  /* Scroll reveal */
  .vm-reveal{ opacity:0; transform:translateY(16px); transition:opacity .6s ease, transform .6s ease; }
  .vm-reveal.vm-in{ opacity:1; transform:none; }
  @media (prefers-reduced-motion: reduce){ .vm-reveal{ opacity:1; transform:none; transition:none; } }

  @media (max-width:900px){
    .vm-hero .vm-wrap,.vm-grid-3,.vm-grid-2,.vm-plans,.vm-steps{ grid-template-columns:1fr; }
    .vm-tiles{ grid-template-columns:repeat(2,1fr); }
  }
  @media (max-width:460px){ .vm-tiles{ grid-template-columns:1fr; } }
  @media (max-width:760px){
    .vm-ledger{ grid-template-columns:1fr; } .vm-ledger .vm-new{ border-left:0; border-top:3px solid var(--vm-gold); }
    .vm-side-plans,.vm-stamp-cards,.vm-example{ grid-template-columns:1fr; } .vm-example .vm-figure{ text-align:left; }
  }
</style>
@endpush

@section('content')
<div class="vm-page">

  <section class="vm-hero">
    <div class="vm-wrap">
      <div>
        <div class="vm-eyebrow">The Financial Operating System for Security Procurement&trade;</div>
        <h1>Win <span class="vm-accent">verified</span> security contracts. Never pay for another dead lead.</h1>
        <p class="vm-lead">
          GASQ Procurement Credits&trade; give qualified security providers verified opportunities, independent
          pricing intelligence, and certified appraisals &mdash; and put <strong>50% of your credits back</strong>
          when you compete and aren&rsquo;t selected.
        </p>
        <div class="vm-cta-row">
          <a class="vm-btn vm-btn-gold" href="#vm-plans">View membership plans</a>
          <a class="vm-btn vm-btn-ghost" href="{{ route('job-board') }}">Browse verified opportunities</a>
        </div>
        <div class="vm-trust">
          <span><span class="vm-dot">&#9670;</span> Credits never expire</span>
          <span><span class="vm-dot">&#9670;</span> Verified budget &amp; decision-maker on every job</span>
          <span><span class="vm-dot">&#9670;</span> No charge to message a buyer</span>
        </div>
      </div>

      <aside class="vm-prospectus vm-reveal">
        <div class="vm-cap">Membership at a glance</div>
        <h3>One Opportunity. One Investment.</h3>
        <p class="vm-motto">Unlimited collaboration once you&rsquo;re in.</p>
        <ul>
          <li><span class="vm-ck">&checkmark;</span><span>Credit rate: <span class="vm-rate">100 GPC = $1.00</span></span></li>
          <li><span class="vm-ck">&checkmark;</span><span>Credits roll over &mdash; no monthly or annual expiration</span></li>
          <li><span class="vm-ck">&checkmark;</span><span>Every opportunity is buyer-verified before you spend</span></li>
          <li><span class="vm-ck">&checkmark;</span><span>50% credit-back when you finish the process unselected</span></li>
        </ul>
      </aside>
    </div>
  </section>

  {{-- THE DIFFERENCE --}}
  <section class="vm-section" id="difference">
    <div class="vm-wrap">
      <div class="vm-sec-head vm-reveal">
        <hr class="vm-rule">
        <h2>You&rsquo;re not buying leads. You&rsquo;re buying an unfair information advantage.</h2>
        <p>Traditional platforms sell you a phone number and let ten vendors fight over it. GASQ verifies the opportunity, arms you with independent pricing, and protects the credits you spend.</p>
      </div>
      <div class="vm-ledger vm-reveal">
        <div class="vm-old">
          <span class="vm-tag">Traditional lead platforms</span>
          <h3>Contact-based selling</h3>
          <ul>
            <li><span class="vm-x">&times;</span><span>You pay for raw contact info &mdash; qualified or not</span></li>
            <li><span class="vm-x">&times;</span><span>The same lead is resold to a crowd of competitors</span></li>
            <li><span class="vm-x">&times;</span><span>No budget, scope, or decision-maker verified up front</span></li>
            <li><span class="vm-x">&times;</span><span>Zero pricing intelligence &mdash; you quote blind</span></li>
            <li><span class="vm-x">&times;</span><span>Spend is gone the moment you lose</span></li>
          </ul>
        </div>
        <div class="vm-new">
          <span class="vm-tag">GASQ Financial Operating System&trade;</span>
          <h3>Procurement-based participation</h3>
          <ul>
            <li><span class="vm-c">&checkmark;</span><span>Verified decision-maker, approved budget, scope &amp; start date</span></li>
            <li><span class="vm-c">&checkmark;</span><span>Independent Cost&nbsp;to&nbsp;Deliver&trade; &amp; Cost&nbsp;to&nbsp;Protect&trade; before you quote</span></li>
            <li><span class="vm-c">&checkmark;</span><span>Certified appraisals, ROI, capital recovery &amp; CFO-style reports</span></li>
            <li><span class="vm-c">&checkmark;</span><span>Unlimited collaboration with the buyer after you accept</span></li>
            <li><span class="vm-c">&checkmark;</span><span>50% of eligible credits returned if you&rsquo;re not selected</span></li>
          </ul>
        </div>
      </div>
    </div>
  </section>

  {{-- WHY JOIN --}}
  <section class="vm-section vm-band" id="why">
    <div class="vm-wrap">
      <div class="vm-sec-head vm-reveal">
        <hr class="vm-rule">
        <h2>Why qualified providers join GASQ</h2>
        <p>Better information, clearer pricing, verified buyers, and far lower opportunity risk.</p>
      </div>
      <div class="vm-grid-3">
        <article class="vm-card vm-reveal">
          <span class="vm-icon" aria-hidden="true"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 12l2 2 4-4"/><path d="M21 12c0 5-3.5 7.5-8.6 9a1 1 0 0 1-.8 0C6.5 19.5 3 17 3 12V6a1 1 0 0 1 .6-.9l8-3a1 1 0 0 1 .8 0l8 3A1 1 0 0 1 21 6z"/></svg></span>
          <h3>Qualified buyers</h3>
          <p>Every opportunity ships with a verified decision-maker, approved budget, scope of work, service location, start date, and procurement requirements.</p>
        </article>
        <article class="vm-card vm-reveal">
          <span class="vm-icon" aria-hidden="true"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 3v18h18"/><path d="M7 14l3-4 3 3 5-7"/></svg></span>
          <h3>Independent pricing</h3>
          <p>Run Cost to Protect&trade;, Cost to Deliver&trade;, ROI, capital recovery, payback, staffing, and budget validation before you commit a single resource.</p>
        </article>
        <article class="vm-card vm-reveal">
          <span class="vm-icon" aria-hidden="true"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 2v20"/><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/></svg></span>
          <h3>Protected spend</h3>
          <p>Pay-As-You-Go and monthly members get 50% of eligible credits replaced when they complete the process but aren&rsquo;t selected. Losing a bid doesn&rsquo;t burn your budget.</p>
        </article>
      </div>
    </div>
  </section>

  {{-- REPORT DASHBOARD --}}
  <section class="vm-section" id="report">
    <div class="vm-wrap">
      <div class="vm-sec-head vm-center vm-reveal">
        <hr class="vm-rule vm-center">
        <h2>What your GASQ Certified&trade; report delivers</h2>
        <p>Every opportunity comes with an independent appraisal. Here&rsquo;s a snapshot on a representative contract &mdash; the same figures your Cost to Protect&trade; report puts in front of the buyer.</p>
      </div>

      <div class="vm-dash-band vm-reveal">Financial Impact Summary</div>
      <div class="vm-tiles vm-reveal">
        <div class="vm-tile">
          <div class="vm-tlabel">In-House Cost to Protect&trade;</div>
          <div class="vm-tval">$91.84<span style="font-size:.9rem;color:var(--vm-muted)">/hr</span></div>
          <div class="vm-tsub">$802,286 per year (buyer TCO)</div>
        </div>
        <div class="vm-tile">
          <div class="vm-tlabel">Vendor Cost to Deliver&trade;</div>
          <div class="vm-tval vm-green">$64.29<span style="font-size:.9rem;color:var(--vm-muted)">/hr</span></div>
          <div class="vm-tsub">$561,600 per year (vendor TCO)</div>
        </div>
        <div class="vm-tile">
          <div class="vm-tlabel">Capital Recovery Opportunity</div>
          <div class="vm-tval vm-gold">$240,686</div>
          <div class="vm-tsub">$27.55/hr recovered vs in-house</div>
        </div>
        <div class="vm-tile">
          <div class="vm-tlabel">Savings vs In-House</div>
          <div class="vm-tval vm-gold">30%</div>
          <div class="vm-tsub">Payback period &asymp; 8.4 months</div>
        </div>
      </div>

      <div class="vm-dash-band vm-reveal">Staffing &amp; Coverage</div>
      <div class="vm-tiles vm-reveal">
        <div class="vm-tile">
          <div class="vm-tlabel">Annual Coverage Hours</div>
          <div class="vm-tval">8,736</div>
          <div class="vm-tsub">24&times;7 post coverage modeled</div>
        </div>
        <div class="vm-tile">
          <div class="vm-tlabel">Workforce Required</div>
          <div class="vm-tval">12<span style="font-size:.9rem;color:var(--vm-muted)"> FTE</span></div>
          <div class="vm-tsub">Includes relief &amp; supervision</div>
        </div>
        <div class="vm-tile">
          <div class="vm-tlabel">Shared Resources Safe-Zone Rate</div>
          <div class="vm-tval">$35.71<span style="font-size:.9rem;color:var(--vm-muted)">/hr</span></div>
          <div class="vm-tsub">Loaded wage floor for sustainability</div>
        </div>
        <div class="vm-tile">
          <div class="vm-tlabel">Price-Realism Check</div>
          <div class="vm-tval vm-green">Pass</div>
          <div class="vm-tsub">Bid is sustainable at this coverage</div>
        </div>
      </div>

      <div class="vm-compare vm-reveal">
        <h3 class="vm-ctitle">In-House vs. Outsourced &mdash; the recovery gap</h3>
        <div class="vm-cbar-row">
          <div class="vm-cbar-label"><span>In-House Cost to Protect&trade;</span><span class="vm-cval">$802,286 / yr</span></div>
          <div class="vm-cbar-track"><div class="vm-cbar-fill vm-navy" style="width:100%;">$91.84 / hr</div></div>
        </div>
        <div class="vm-cbar-row">
          <div class="vm-cbar-label"><span>Vendor Cost to Deliver&trade;</span><span class="vm-cval">$561,600 / yr</span></div>
          <div class="vm-cbar-track"><div class="vm-cbar-fill vm-green" style="width:70%;">$64.29 / hr</div></div>
        </div>
        <div class="vm-recovery">
          <span>Annual Capital Recovery Opportunity</span>
          <span class="vm-pill">$240,686 &middot; 30%</span>
        </div>
      </div>

      <p class="vm-dash-note vm-reveal">Representative example for illustration. Actual figures vary by scope, market, coverage, and vendor participation. Amounts in USD.</p>
    </div>
  </section>

  {{-- PLANS --}}
  <section class="vm-section vm-band" id="vm-plans">
    <div class="vm-wrap">
      <div class="vm-sec-head vm-center vm-reveal">
        <hr class="vm-rule vm-center">
        <h2>Choose how you participate</h2>
        <p>Flexible pay-as-you-go, a monthly growth plan, or unlimited annual access. Every plan includes credit rollover.</p>
      </div>

      <div class="vm-side-plans vm-reveal">
        <div class="vm-side-plan">
          <div>
            <h4>Pay-As-You-Go</h4>
            <p>No monthly fee &mdash; pay 1% of contract value only when you engage. Includes 50% credit-back.</p>
          </div>
          <div class="vm-price">1%<span style="font-size:.7rem;color:var(--vm-muted)"> / contract</span></div>
        </div>
        <div class="vm-side-plan">
          <div>
            <h4>Annual Unlimited</h4>
            <p>Unlimited participation and credits for teams running high opportunity volume.</p>
          </div>
          <div class="vm-price">$9,999<span style="font-size:.7rem;color:var(--vm-muted)"> / yr</span></div>
        </div>
      </div>

      <div class="vm-plans">
        <div class="vm-plan vm-reveal">
          <h3>Professional</h3>
          <div class="vm-tier-note">For providers actively bidding a steady flow of contracts.</div>
          <div class="vm-price-row"><span class="vm-amt">$299</span><span class="vm-per">/ month</span></div>
          <div class="vm-credits">25,000 GPC included &middot; add at 100 GPC = $1</div>
          <ul>
            <li><span class="vm-ck">&checkmark;</span>Verified opportunity access</li>
            <li><span class="vm-ck">&checkmark;</span>Full pricing &amp; appraisal toolset</li>
            <li><span class="vm-ck">&checkmark;</span>Credit rollover &mdash; never expires</li>
            <li><span class="vm-ck">&checkmark;</span>50% credit-back protection</li>
          </ul>
          <a class="vm-btn vm-btn-outline" href="{{ route('register.vendor.index') }}">Start Professional</a>
        </div>

        <div class="vm-plan vm-featured vm-reveal">
          <span class="vm-flag">Most popular</span>
          <h3>Business</h3>
          <div class="vm-tier-note">The best rate-to-volume balance for growing security firms.</div>
          <div class="vm-price-row"><span class="vm-amt">$599</span><span class="vm-per">/ month</span></div>
          <div class="vm-credits">75,000 GPC included &middot; add at 110 GPC = $1</div>
          <ul>
            <li><span class="vm-ck">&checkmark;</span>Everything in Professional</li>
            <li><span class="vm-ck">&checkmark;</span>3&times; the included credit allotment</li>
            <li><span class="vm-ck">&checkmark;</span>Better add-on credit rate (110/$1)</li>
            <li><span class="vm-ck">&checkmark;</span>Priority procurement support</li>
          </ul>
          <a class="vm-btn vm-btn-gold" href="{{ route('register.vendor.index') }}">Start Business</a>
        </div>

        <div class="vm-plan vm-reveal">
          <h3>Enterprise</h3>
          <div class="vm-tier-note">For high-volume providers and multi-market operators.</div>
          <div class="vm-price-row"><span class="vm-amt">$999</span><span class="vm-per">/ month</span></div>
          <div class="vm-credits">150,000 GPC included &middot; add at 120 GPC = $1</div>
          <ul>
            <li><span class="vm-ck">&checkmark;</span>Everything in Business</li>
            <li><span class="vm-ck">&checkmark;</span>Highest included allotment</li>
            <li><span class="vm-ck">&checkmark;</span>Best add-on credit rate (120/$1)</li>
            <li><span class="vm-ck">&checkmark;</span>Dedicated procurement support</li>
          </ul>
          <a class="vm-btn vm-btn-outline" href="{{ route('register.vendor.index') }}">Start Enterprise</a>
        </div>
      </div>

      <div class="vm-table-note vm-reveal">Full plan comparison</div>
      <div class="vm-table-scroll vm-reveal">
        <table>
          <thead>
            <tr>
              <th scope="col">Plan</th>
              <th scope="col">Price</th>
              <th scope="col">Included GPC</th>
              <th scope="col">Add-on rate</th>
              <th scope="col">Rollover</th>
              <th scope="col">50% credit-back</th>
            </tr>
          </thead>
          <tbody>
            <tr>
              <th scope="row">Pay-As-You-Go</th>
              <td>No monthly fee</td>
              <td class="vm-cell-num">&mdash;</td>
              <td class="vm-cell-num">1% of contract value</td>
              <td class="vm-yes">Yes</td>
              <td class="vm-yes">Yes</td>
            </tr>
            <tr>
              <th scope="row">Professional</th>
              <td class="vm-cell-num">$299 / mo</td>
              <td class="vm-cell-num">25,000</td>
              <td class="vm-cell-num">100 GPC = $1</td>
              <td class="vm-yes">Yes</td>
              <td class="vm-yes">Yes</td>
            </tr>
            <tr class="vm-hi">
              <th scope="row">Business</th>
              <td class="vm-cell-num">$599 / mo</td>
              <td class="vm-cell-num">75,000</td>
              <td class="vm-cell-num">110 GPC = $1</td>
              <td class="vm-yes">Yes</td>
              <td class="vm-yes">Yes</td>
            </tr>
            <tr>
              <th scope="row">Enterprise</th>
              <td class="vm-cell-num">$999 / mo</td>
              <td class="vm-cell-num">150,000</td>
              <td class="vm-cell-num">120 GPC = $1</td>
              <td class="vm-yes">Yes</td>
              <td class="vm-yes">Yes</td>
            </tr>
            <tr>
              <th scope="row">Annual Unlimited</th>
              <td class="vm-cell-num">$9,999 / yr</td>
              <td>Unlimited</td>
              <td>Included</td>
              <td>N/A</td>
              <td class="vm-no">Not applicable</td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>
  </section>

  {{-- PROTECTIONS --}}
  <section class="vm-section" id="protections">
    <div class="vm-wrap">
      <div class="vm-sec-head vm-reveal">
        <hr class="vm-rule">
        <h2>Your credits are built to keep their value</h2>
        <p>Two guarantees make GASQ credits behave like an asset, not a sunk cost.</p>
      </div>
      <div class="vm-stamp-cards">
        <article class="vm-stamp vm-reveal">
          <span class="vm-seal">Credit Rollover Guarantee&trade;</span>
          <h3>Credits roll over and never expire</h3>
          <p>Subscription credits, purchased credits, and replacement credits automatically carry from month to month.</p>
          <p>No monthly expiration, no annual expiration, and no maximum rollover balance while your account stays active and in good standing.</p>
        </article>
        <article class="vm-stamp vm-reveal">
          <span class="vm-seal">Vendor Credit Replacement Guarantee&trade;</span>
          <h3>50% replaced when you&rsquo;re not selected</h3>
          <p>Eligible Pay-As-You-Go and monthly members receive 50% of the total eligible GPC used when they complete the procurement process but aren&rsquo;t awarded the contract.</p>
          <p>Annual Unlimited is excluded &mdash; it already includes unlimited credits.</p>
        </article>
      </div>

      <div class="vm-example vm-reveal">
        <div>
          <h3>Pay-As-You-Go, worked out</h3>
          <p>A $1,000,000 contract carries a 1% participation fee of $10,000. Complete the process but don&rsquo;t win? GASQ returns 50% of the eligible value as credits toward your next opportunity and services.</p>
        </div>
        <div class="vm-figure">
          <span class="vm-big">$5,000</span>
          <small>credited back</small>
        </div>
      </div>
    </div>
  </section>

  {{-- SERVICES --}}
  <section class="vm-section vm-band" id="services">
    <div class="vm-wrap">
      <div class="vm-sec-head vm-reveal">
        <hr class="vm-rule">
        <h2>What your credits actually buy</h2>
        <p>Professional procurement services, financial intelligence, and platform tools &mdash; never unqualified leads.</p>
      </div>
      <div class="vm-grid-3">
        <article class="vm-card vm-reveal">
          <span class="vm-badge">Appraisal</span>
          <h3>Independent procurement appraisal</h3>
          <p>Cost to Protect&trade;, Cost to Deliver&trade;, ROI analysis, capital recovery, payback analysis, budget validation, and certified procurement appraisals.</p>
        </article>
        <article class="vm-card vm-reveal">
          <span class="vm-badge">Risk</span>
          <h3>Risk &amp; staffing tools</h3>
          <p>Risk scoring, staffing recommendations, cost analysis, site recommendations, mobile patrol pricing, route optimization, and officer productivity.</p>
        </article>
        <article class="vm-card vm-reveal">
          <span class="vm-badge">AI &amp; Reports</span>
          <h3>AI &amp; certified reports</h3>
          <p>Proposal generation, executive summaries, pricing analysis, scope review, CFO-style reports, and procurement recommendations.</p>
        </article>
      </div>
    </div>
  </section>

  {{-- PROCESS --}}
  <section class="vm-section" id="process">
    <div class="vm-wrap">
      <div class="vm-sec-head vm-reveal">
        <hr class="vm-rule">
        <h2>How a GASQ opportunity works</h2>
        <p>A transparent sequence that verifies both the opportunity and the vendor before any contract is awarded.</p>
      </div>
      <div class="vm-steps">
        <div class="vm-step vm-reveal"><div class="vm-n">01</div><strong>Buyer posts</strong><span>The buyer submits the opportunity, budget, scope, location, and timeline.</span></div>
        <div class="vm-step vm-reveal"><div class="vm-n">02</div><strong>GASQ verifies</strong><span>We confirm the buyer, decision-maker, budget, and procurement requirements.</span></div>
        <div class="vm-step vm-reveal"><div class="vm-n">03</div><strong>Vendor accepts</strong><span>Qualified vendors accept or decline before any full participation.</span></div>
        <div class="vm-step vm-reveal"><div class="vm-n">04</div><strong>Assess &amp; submit</strong><span>Risk assessment, proposal, interview, and document exchange are completed.</span></div>
        <div class="vm-step vm-reveal"><div class="vm-n">05</div><strong>Award &amp; replace</strong><span>The buyer selects. Eligible replacement credits are applied automatically.</span></div>
      </div>
    </div>
  </section>

  {{-- FAQ --}}
  <section class="vm-section vm-band" id="faq">
    <div class="vm-wrap">
      <div class="vm-sec-head vm-center vm-reveal">
        <hr class="vm-rule vm-center">
        <h2>Questions vendors ask first</h2>
      </div>
      <div class="vm-faq">
        <details class="vm-q vm-reveal"><summary>What exactly is a GASQ Procurement Credit (GPC)? <span class="vm-plus">+</span></summary>
          <p>A GPC is the platform&rsquo;s unit of value &mdash; <span class="vm-num">100 GPC = $1.00</span>. You spend GPC on procurement services (appraisals, risk and staffing tools, AI reports) and to participate in verified opportunities. You are never charged GPC to send an individual message to a buyer.</p></details>
        <details class="vm-q vm-reveal"><summary>So I&rsquo;m not paying for leads? <span class="vm-plus">+</span></summary>
          <p>Correct. GASQ doesn&rsquo;t sell contact lists. Your credits buy verified opportunities and the financial intelligence to price them &mdash; every job carries a confirmed decision-maker, approved budget, and scope before it reaches you.</p></details>
        <details class="vm-q vm-reveal"><summary>How does the 50% credit replacement work? <span class="vm-plus">+</span></summary>
          <p>If you&rsquo;re on Pay-As-You-Go or a monthly plan and you complete the full procurement process but the buyer selects someone else, GASQ returns 50% of the eligible GPC you spent as credits toward future opportunities and services. Annual Unlimited is excluded because it already includes unlimited credits.</p></details>
        <details class="vm-q vm-reveal"><summary>Do my credits expire? <span class="vm-plus">+</span></summary>
          <p>No. Subscription, purchased, and replacement credits roll over month to month with no expiration and no maximum balance, as long as your account stays active and in good standing.</p></details>
        <details class="vm-q vm-reveal"><summary>Pay-As-You-Go or a monthly plan &mdash; which is right for me? <span class="vm-plus">+</span></summary>
          <p>Pay-As-You-Go has no monthly fee and charges 1% of contract value only when you engage an opportunity &mdash; ideal for occasional bidders. Monthly plans include a large starting credit allotment and better add-on credit rates, which pay off once you&rsquo;re bidding regularly.</p></details>
        <details class="vm-q vm-reveal"><summary>Who are the buyers? <span class="vm-plus">+</span></summary>
          <p>Verified organizations that have posted a real security requirement &mdash; with a confirmed decision-maker, an approved budget, and a defined scope, location, and start date. GASQ verifies each one before vendors are invited to participate.</p></details>
      </div>
    </div>
  </section>

  {{-- PROMISE --}}
  <section class="vm-section vm-promise">
    <div class="vm-wrap">
      <hr class="vm-rule vm-center">
      <h2>The official GASQ promise</h2>
      <p>We don&rsquo;t sell leads. We deliver verified procurement opportunities supported by independent pricing, financial intelligence, certified analytics, and objective procurement tools.</p>
      <p>Every credit you invest helps buyers and vendors make informed business decisions &mdash; not speculative purchases.</p>
      <div class="vm-tagline">Know Before You Buy&reg;. &nbsp;Know Before You Bid&reg;. &nbsp;Know Before You Invest&reg;.</div>
      <div class="vm-cta-row">
        <a class="vm-btn vm-btn-gold" href="{{ route('register.vendor.index') }}">Become a GASQ Vendor</a>
        <a class="vm-btn vm-btn-ghost" href="{{ route('job-board') }}">Browse opportunities</a>
      </div>
    </div>
  </section>

</div>
@endsection

@push('scripts')
<script>
  (function () {
    var reduce = window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches;
    var els = document.querySelectorAll('.vm-page .vm-reveal');
    if (reduce || !('IntersectionObserver' in window)) {
      els.forEach(function (e) { e.classList.add('vm-in'); });
      return;
    }
    var io = new IntersectionObserver(function (entries) {
      entries.forEach(function (en) {
        if (en.isIntersecting) { en.target.classList.add('vm-in'); io.unobserve(en.target); }
      });
    }, { rootMargin: '0px 0px -8% 0px', threshold: 0.08 });
    els.forEach(function (e) { io.observe(e); });
  })();
</script>
@endpush

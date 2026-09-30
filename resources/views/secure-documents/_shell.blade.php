{{-- Account-free shell for the secure document viewer. --}}
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex,nofollow">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title ?? 'Secure Document' }} · GASQ</title>
    <style>
        :root { --ink:#10243f; --muted:#6b7c93; --line:rgba(15,23,42,.1); --navy:#0b3c78; --ground:#f6f8fb; }
        * { box-sizing: border-box; }
        body { margin:0; background:var(--ground); color:var(--ink);
               font-family:-apple-system,BlinkMacSystemFont,"Segoe UI",Arial,sans-serif; line-height:1.55; }
        .sd-bar { background:#fff; border-bottom:1px solid var(--line); padding:.7rem 1rem;
                  display:flex; align-items:center; justify-content:space-between; gap:1rem; flex-wrap:wrap; }
        .sd-brand { font-weight:800; color:var(--navy); letter-spacing:.3px; }
        .sd-meta { font-size:.78rem; color:var(--muted); }
        .sd-wrap { max-width:46rem; margin:0 auto; padding:32px 16px 64px; }
        .sd-card { background:#fff; border:1px solid var(--line); border-radius:14px; padding:26px; }
        h1 { font-size:1.4rem; margin:.2rem 0 1rem; color:var(--navy); }
        .sd-kicker { font-size:.72rem; text-transform:uppercase; letter-spacing:.12em; color:var(--muted); }
        .sd-field { width:100%; padding:12px 14px; border:1px solid rgba(15,23,42,.18); border-radius:8px; font-size:1rem; }
        .sd-code { letter-spacing:.5em; font-size:1.4rem; text-align:center; font-variant-numeric:tabular-nums; }
        .sd-btn { display:inline-block; background:var(--navy); color:#fff; border:0; border-radius:8px;
                  padding:12px 22px; font-weight:600; cursor:pointer; text-decoration:none; font-size:1rem; }
        .sd-btn.secondary { background:#eef2f8; color:var(--navy); }
        .sd-alert { padding:.7rem .9rem; border-radius:8px; font-size:.9rem; margin-bottom:16px; }
        .sd-alert.ok { background:rgba(22,163,74,.1); color:#15803d; }
        .sd-alert.bad { background:rgba(220,38,38,.08); color:#991b1b; }
        .sd-note { font-size:.82rem; color:var(--muted); }
        .sd-foot { margin-top:22px; font-size:.75rem; color:#9aa3b2; text-align:center; }
    </style>
</head>
<body>
<div class="sd-bar">
    <span class="sd-brand">GASQ</span>
    <span class="sd-meta">{{ $barMeta ?? 'Confidential document' }}</span>
</div>

@hasSection('full')
    @yield('full')
@else
<div class="sd-wrap">
    @if(session('status'))<div class="sd-alert ok">{{ session('status') }}</div>@endif
    @if($errors->any())<div class="sd-alert bad">{{ $errors->first() }}</div>@endif
    @yield('content')
    <p class="sd-foot">Confidential · prepared for the authorised recipient only</p>
</div>
@endif
</body>
</html>

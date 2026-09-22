{{-- Account-free shell for the buyer portal: no app nav, no login prompt. --}}
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex,nofollow">
    <title>{{ $title ?? 'Private Estimate' }} · GASQ</title>
    <link rel="stylesheet" href="{{ asset('css/gasq-theme.css') }}">
    <style>
        body { margin:0; background:#f6f8fb; font-family:-apple-system,BlinkMacSystemFont,"Segoe UI",Arial,sans-serif; color:#1f2937; line-height:1.55; }
        .pe-wrap { max-width:640px; margin:0 auto; padding:32px 16px 64px; }
        .pe-brand { display:flex; align-items:center; gap:10px; margin-bottom:24px; }
        .pe-brand strong { color:#0b2447; letter-spacing:.3px; }
        .pe-card { background:#fff; border:1px solid rgba(15,23,42,.08); border-radius:14px; padding:28px; box-shadow:0 1px 3px rgba(15,23,42,.06); }
        .pe-kicker { font-size:.72rem; text-transform:uppercase; letter-spacing:.12em; color:#6b7280; }
        h1 { font-size:1.5rem; color:#0b2447; margin:.4rem 0 1rem; }
        .pe-meta { font-size:.85rem; color:#6b7280; }
        .pe-steps { display:flex; flex-wrap:wrap; gap:6px; font-size:.72rem; color:#6b7280; margin-bottom:24px; }
        .pe-steps span { padding:.25rem .6rem; border-radius:999px; background:#eef2f8; }
        .pe-steps span.on { background:#0d6efd; color:#fff; font-weight:600; }
        .pe-btn { display:inline-block; background:#0d6efd; color:#fff; border:0; border-radius:8px; padding:12px 22px; font-weight:600; cursor:pointer; text-decoration:none; }
        .pe-btn.secondary { background:#eef2f8; color:#0b2447; }
        .pe-field { width:100%; padding:12px 14px; border:1px solid rgba(15,23,42,.18); border-radius:8px; font-size:1rem; }
        .pe-code { letter-spacing:.5em; font-size:1.4rem; text-align:center; font-variant-numeric:tabular-nums; }
        .pe-alert { padding:.7rem .9rem; border-radius:8px; font-size:.9rem; margin-bottom:16px; }
        .pe-alert.ok { background:rgba(22,163,74,.1); color:#15803d; }
        .pe-alert.bad { background:rgba(220,38,38,.08); color:#991b1b; }
        .pe-foot { margin-top:22px; font-size:.78rem; color:#9aa3b2; text-align:center; }
    </style>
</head>
<body>
<div class="pe-wrap">
    <div class="pe-brand">
        <strong>GASQ</strong><span class="pe-meta">Get A Security Quote</span>
    </div>

    @isset($steps)
        {{-- Verify → Requirements → Scope → Vendor Review → Estimate → Conversation → Decision (spec 69) --}}
        <div class="pe-steps">
            @foreach(['Verify','Requirements','Scope','Vendor Review','Estimate','Conversation','Decision'] as $i => $label)
                <span class="{{ $i === $steps ? 'on' : '' }}">{{ $label }}</span>
            @endforeach
        </div>
    @endisset

    @if(session('status'))<div class="pe-alert ok">{{ session('status') }}</div>@endif
    @if($errors->any())<div class="pe-alert bad">{{ $errors->first() }}</div>@endif

    {{ $slot ?? '' }}
    @yield('content')

    <p class="pe-foot">Confidential · prepared for the invited recipient only</p>
</div>
</body>
</html>

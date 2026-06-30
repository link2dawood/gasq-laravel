<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Session Expired — GASQ</title>
    {{-- Bounce the user to the login page automatically after a few seconds. --}}
    <meta http-equiv="refresh" content="5;url={{ route('login') }}">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    <style>
        body { background:#f4f6fb; color:#333444; min-height:100vh; display:flex; align-items:center; justify-content:center; }
        .expired-card { max-width: 30rem; width: 100%; }
        .btn-gasq { background:#062e7a; border-color:#062e7a; color:#fff; }
        .btn-gasq:hover { background:#04205a; border-color:#04205a; color:#fff; }
    </style>
</head>
<body>
    <div class="expired-card text-center p-4">
        <img src="{{ asset('img/gasq-logo.png') }}" alt="GASQ" style="height:64px;width:auto;" class="mb-4"
             onerror="this.style.display='none'">
        <h1 class="h3 fw-bold mb-2">Your session has expired</h1>
        <p class="mb-4">For your security you were automatically signed out after a period of inactivity.
            You&rsquo;ll be redirected to the login page in a moment.</p>
        <a href="{{ route('login') }}" class="btn btn-lg btn-gasq px-4">Return to Login</a>
        <p class="small text-muted mt-4 mb-0">
            <a href="{{ url('/') }}" class="text-muted">Back to homepage</a>
        </p>
    </div>
</body>
</html>

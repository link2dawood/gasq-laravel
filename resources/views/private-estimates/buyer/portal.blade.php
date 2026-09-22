{{-- Verified buyer portal. Intake, scope and the estimate itself land here next. --}}
@extends('private-estimates.buyer._shell', ['title' => 'Your Private Estimate', 'steps' => 1])

@section('content')
    <div class="pe-card">
        <div class="pe-kicker">Private Estimate {{ $estimate->public_id }}</div>
        <h1>You're verified</h1>

        <p>Thanks{{ $estimate->buyer_name ? ', ' . $estimate->buyer_name : '' }} — we've confirmed
            {{ $estimate->buyer_email }}.</p>

        <p class="pe-meta">
            Next you'll confirm your details, tell us what coverage you need, and review the scope before
            it goes to the vendor. Current status: <strong>{{ $estimate->statusLabel() }}</strong>.
        </p>
    </div>
@endsection

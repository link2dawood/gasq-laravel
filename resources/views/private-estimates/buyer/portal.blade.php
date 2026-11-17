{{-- Verified buyer portal. Shows the comparison report once the estimate carries
     a calculated rate; before that, where the estimate stands. --}}
@extends('private-estimates.buyer._shell', [
    'title' => 'Your Private Estimate',
    'steps' => $comparison ? 4 : 1,
    'wide' => (bool) $comparison,
])

@section('content')
    @if($comparison)
        <div class="pe-card">
            @include('private-estimates._comparison', ['r' => $comparison])
        </div>

        <div class="pe-card" style="margin-top:16px;">
            <div class="pe-kicker">Next</div>
            <p style="margin:.5rem 0 0;">
                Take this to your approvers, or come back to
                {{ $estimate->vendor?->company ?: $estimate->vendor?->name ?: 'your vendor' }}
                with questions on the scope or the rate.
            </p>
        </div>
    @else
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
    @endif
@endsection

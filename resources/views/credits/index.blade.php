@extends('layouts.app')

@section('title', 'Buy Credits')

@section('content')
<div class="container py-5">
    <h1 class="h2 mb-4">Credits</h1>
    @if ($errors->any())
        <div class="alert alert-danger alert-dismissible fade show mb-4" role="alert">
            <ul class="mb-0">@foreach ($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif
    <x-card title="Current balance" class="mb-4">
        <p class="fs-2 mb-0">{{ number_format($balance) }} credits</p>
        <p class="text-muted small mb-0 mt-2">Each calculator run uses {{ config('credits.calculator_per_run') }} credits (deducted when results are calculated).</p>
        <a href="{{ route('account-balance') }}" class="btn btn-sm btn-outline-primary mt-2">View history</a>
    </x-card>
    @if($hasRedeemableCoupons ?? false)
    <x-card title="Redeem coupon" class="mb-4">
        <p class="text-muted small">Enter a valid coupon code to add credits to your account.</p>
        <form method="POST" action="{{ route('credits.redeem') }}" class="row g-3 align-items-end">
            @csrf
            <div class="col-md-8">
                <label class="form-label">Coupon code</label>
                <input type="text" name="code" class="form-control @error('code') is-invalid @enderror" value="{{ old('code') }}" required maxlength="64" placeholder="Enter code" style="text-transform: uppercase;">
                @error('code')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            <div class="col-md-4">
                <button type="submit" class="btn btn-outline-primary w-100">Redeem Coupon</button>
            </div>
        </form>
    </x-card>
    @endif
    @php
        $paygUnit = (float) config('credits.payg.unit_price');
        $paygMin = (int) config('credits.payg.min');
        $paygMax = (int) config('credits.payg.max');
        $paygDefault = min($paygMax, max($paygMin, 50));
    @endphp
    <x-card title="Pay As You Go" class="mb-4">
        <p class="text-muted small">
            Buy exactly the credits you need at ${{ number_format($paygUnit, 2) }} each &mdash; no pack, no subscription.
            The packages below work out cheaper per credit if you buy ahead.
        </p>
        <form method="POST" action="{{ route('credits.payg') }}" class="row g-3 align-items-end">
            @csrf
            <div class="col-md-5">
                <label class="form-label" for="paygCredits">Credits</label>
                <input type="number" id="paygCredits" name="credits"
                       class="form-control @error('credits') is-invalid @enderror"
                       value="{{ old('credits', $paygDefault) }}"
                       min="{{ $paygMin }}" max="{{ $paygMax }}" step="1" required>
                <div class="form-text">Between {{ number_format($paygMin) }} and {{ number_format($paygMax) }} credits.</div>
                @error('credits')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            <div class="col-md-3">
                <label class="form-label" for="paygTotal">Total</label>
                <input type="text" id="paygTotal" class="form-control-plaintext fs-4 fw-bold mb-0 pt-0" readonly tabindex="-1" value="">
            </div>
            <div class="col-md-4">
                <button type="submit" class="btn btn-primary w-100">Buy Credits</button>
            </div>
        </form>
    </x-card>

    @push('scripts')
    <script>
    (function () {
        const qty = document.getElementById('paygCredits');
        const total = document.getElementById('paygTotal');
        if (!qty || !total) return;

        // Display only — the charge is always recomputed server-side from the
        // configured unit price, so editing this in devtools changes nothing.
        const unit = {{ json_encode($paygUnit) }};
        const min = {{ (int) $paygMin }};
        const max = {{ (int) $paygMax }};

        const render = () => {
            const n = parseInt(qty.value, 10);
            if (!Number.isFinite(n) || n < min || n > max) {
                total.value = '—';
                return;
            }
            total.value = (n * unit).toLocaleString(undefined, {
                style: 'currency', currency: 'USD',
            });
        };

        qty.addEventListener('input', render);
        render();
    })();
    </script>
    @endpush

    <h2 class="h5 mb-3">Credit packages</h2>
    @if($plans->isEmpty())
        <p class="text-muted">No credit packages available at the moment &mdash; use Pay As You Go above to buy any amount you need.</p>
    @else
        <div class="row g-4">
            @foreach($plans as $plan)
                <div class="col-md-4">
                    <x-card :title="$plan->name">
                        <p class="fs-4 mb-2">${{ number_format($plan->price, 2) }}</p>
                        <p class="text-muted small">{{ $plan->tokens_included }} credits</p>
                        <form method="POST" action="{{ route('credits.checkout', $plan) }}">
                            @csrf
                            <button type="submit" class="btn btn-primary w-100">Buy Now</button>
                        </form>
                    </x-card>
                </div>
            @endforeach
        </div>
    @endif
</div>
@endsection

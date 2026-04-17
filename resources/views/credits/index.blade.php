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
    <h2 class="h5 mb-3">Purchase credits</h2>
    @if($plans->isEmpty())
        <p class="text-muted">No credit packages available at the moment. Contact support to add credits.</p>
    @else
        <div class="row g-4">
            @foreach($plans as $plan)
                <div class="col-md-4">
                    <x-card :title="$plan->name">
                        <p class="fs-4 mb-2">${{ number_format($plan->price, 2) }}</p>
                        <p class="text-muted small">{{ $plan->tokens_included }} credits</p>
                        <form method="POST" action="{{ route('credits.checkout', $plan) }}">
                            @csrf
                            <button type="submit" class="btn btn-primary w-100">Purchase with Stripe</button>
                        </form>
                    </x-card>
                </div>
            @endforeach
        </div>
    @endif
</div>
@endsection

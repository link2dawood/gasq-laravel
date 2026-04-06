@extends('layouts.app')

@section('title', 'Buy Credits')

@section('content')
<div class="container py-5">
    <h1 class="h2 mb-4">Credits</h1>
    <x-card title="Current balance" class="mb-4">
        <p class="fs-2 mb-0">{{ number_format($balance) }} credits</p>
        <p class="text-muted small mb-0 mt-2">Each calculator run uses {{ config('credits.calculator_per_run') }} credits (deducted when results are calculated).</p>
        <a href="{{ route('account-balance') }}" class="btn btn-sm btn-outline-primary mt-2">View history</a>
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

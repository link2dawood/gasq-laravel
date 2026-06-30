@extends('layouts.app')

@section('title', 'Pricing')

@section('content')
<div class="container py-5">
    <h1 class="h2 mb-4">Pricing</h1>
    @if($plans->isEmpty())
        <p class="text-muted">Pricing plans will be listed here. Contact us for details.</p>
    @else
        <div class="row g-4">
            @foreach($plans as $plan)
                <div class="col-md-4">
                    <x-card :title="$plan->name">
                        <p class="fs-4 mb-2">${{ number_format($plan->price, 2) }}</p>
                        <p class="text-muted small">{{ $plan->tokens_included }} credits included</p>
                        @if($plan->features)
                            <ul class="small mb-3">
                                @foreach($plan->features as $f)
                                    <li>{{ $f }}</li>
                                @endforeach
                            </ul>
                        @endif
                        @auth
                            {{-- Logged in: carry this plan straight to Stripe checkout --}}
                            <form method="POST" action="{{ route('credits.checkout', $plan) }}">
                                @csrf
                                <button type="submit" class="btn btn-primary w-100">Buy Now</button>
                            </form>
                        @else
                            {{-- Guest: send to register, carrying the chosen plan --}}
                            <a href="{{ route('register', ['plan' => $plan->id]) }}" class="btn btn-primary w-100">Get Started</a>
                        @endauth
                    </x-card>
                </div>
            @endforeach
        </div>
    @endif
</div>
@endsection

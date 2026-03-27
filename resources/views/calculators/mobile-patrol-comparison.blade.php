@extends('layouts.app')

@section('title', 'Mobile Patrol Comparison')
@section('header_variant', 'dashboard')

@section('content')
<div class="container py-4">
    <h1 class="h2 mb-4">Mobile Patrol Comparison</h1>
    <x-card title="Compare two scenarios">
        <form method="POST" action="{{ route('backend.mobile-patrol.comparison.post') }}">
            @csrf
            <div class="row">
                <div class="col-md-6">
                    <h6 class="mb-2">Scenario A</h6>
                    <div class="mb-2"><input type="number" name="a_cost_per_visit" class="form-control" placeholder="Cost per visit $" step="0.01" min="0"></div>
                    <div class="mb-2"><input type="number" name="a_visits_per_month" class="form-control" placeholder="Visits per month" step="0.5" min="0"></div>
                    <div class="mb-2"><input type="number" name="a_monthly_base" class="form-control" placeholder="Monthly base $" step="0.01" min="0" value="0"></div>
                </div>
                <div class="col-md-6">
                    <h6 class="mb-2">Scenario B</h6>
                    <div class="mb-2"><input type="number" name="b_cost_per_visit" class="form-control" placeholder="Cost per visit $" step="0.01" min="0"></div>
                    <div class="mb-2"><input type="number" name="b_visits_per_month" class="form-control" placeholder="Visits per month" step="0.5" min="0"></div>
                    <div class="mb-2"><input type="number" name="b_monthly_base" class="form-control" placeholder="Monthly base $" step="0.01" min="0" value="0"></div>
                </div>
            </div>
            <button type="submit" class="btn btn-primary">Compare</button>
        </form>
    </x-card>
    @if($result)
        <x-card title="Comparison result" class="mt-4">
            <p class="mb-1"><strong>Scenario A annual:</strong> ${{ number_format($result['scenario_a_annual'], 2) }}</p>
            <p class="mb-1"><strong>Scenario B annual:</strong> ${{ number_format($result['scenario_b_annual'], 2) }}</p>
            <p class="mb-1"><strong>Savings (B vs A):</strong> ${{ number_format($result['savings'], 2) }}</p>
            <p class="mb-0"><strong>Savings %:</strong> {{ number_format($result['savings_percent'], 1) }}%</p>
            <x-report-actions report-type="mobile-patrol-comparison" />
        </x-card>
    @endif
    <p class="mt-3"><a href="{{ url('/mobile-patrol-calculator') }}">Single scenario calculator</a></p>
</div>
@endsection

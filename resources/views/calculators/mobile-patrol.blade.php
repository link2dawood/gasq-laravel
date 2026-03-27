@extends('layouts.app')

@section('title', 'Mobile Patrol Calculator')

@section('content')
<div class="container py-4">
    <h1 class="h2 mb-4">Mobile Patrol Calculator</h1>
    <x-card title="Single scenario">
        <form method="POST" action="{{ route('backend.mobile-patrol.calculator.post') }}">
            @csrf
            <div class="row">
                <div class="col-md-4 mb-3">
                    <label class="form-label">Cost per visit ($)</label>
                    <input type="number" name="cost_per_visit" class="form-control" step="0.01" min="0" required>
                </div>
                <div class="col-md-4 mb-3">
                    <label class="form-label">Visits per month</label>
                    <input type="number" name="visits_per_month" class="form-control" step="0.5" min="0" required>
                </div>
                <div class="col-md-4 mb-3">
                    <label class="form-label">Monthly base ($)</label>
                    <input type="number" name="monthly_base" class="form-control" step="0.01" min="0" value="0">
                </div>
            </div>
            <button type="submit" class="btn btn-primary">Calculate</button>
        </form>
    </x-card>
    @if(!empty($result))
        <x-card title="Result" class="mt-4">
            <p class="mb-1"><strong>Monthly cost:</strong> ${{ number_format($result['monthly_cost'], 2) }}</p>
            <p class="mb-0"><strong>Annual cost:</strong> ${{ number_format($result['annual_cost'], 2) }}</p>
            <x-report-actions report-type="mobile-patrol" />
        </x-card>
    @endif
    <p class="mt-3"><a href="{{ url('/mobile-patrol-comparison') }}">Compare two scenarios</a></p>
</div>
@endsection

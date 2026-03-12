@extends('layouts.app')

@section('title', 'Security Billing')

@section('content')
<div class="container py-4">
    <h1 class="h2 mb-4">Security Billing Calculator</h1>
    <x-card title="Billing estimate">
        <form method="POST" action="{{ route('security-billing.index') }}">
            @csrf
            <div class="row">
                <div class="col-md-4 mb-3">
                    <label class="form-label">Hourly rate ($)</label>
                    <input type="number" name="hourly_rate" class="form-control" step="0.01" min="0" required>
                </div>
                <div class="col-md-4 mb-3">
                    <label class="form-label">Hours per week</label>
                    <input type="number" name="hours_per_week" class="form-control" step="0.5" min="0" value="40" required>
                </div>
                <div class="col-md-4 mb-3">
                    <label class="form-label">Weeks</label>
                    <input type="number" name="weeks" class="form-control" min="1" value="52">
                </div>
            </div>
            <button type="submit" class="btn btn-primary">Calculate</button>
        </form>
    </x-card>
    @if(!empty($result))
        <x-card title="Result" class="mt-4">
            <p class="mb-1"><strong>Weekly total:</strong> ${{ number_format($result['weekly_total'], 2) }}</p>
            <p class="mb-1"><strong>Monthly total:</strong> ${{ number_format($result['monthly_total'], 2) }}</p>
            <p class="mb-0"><strong>Annual total:</strong> ${{ number_format($result['annual_total'], 2) }}</p>
            <x-report-actions report-type="security-billing" />
        </x-card>
    @endif
</div>
@endsection

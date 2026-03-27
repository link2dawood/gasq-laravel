@extends('layouts.app')

@section('title', 'GASQ Instant Estimator')

@section('content')
<div class="container py-4">
    <h1 class="h2 mb-4">GASQ Instant Estimator</h1>
    <x-card title="Estimate security cost">
        <form method="POST" action="{{ route('backend.instant-estimator.post') }}">
            @csrf
            <div class="row">
                <div class="col-md-4 mb-3">
                    <label class="form-label">Location</label>
                    <select name="location" class="form-select" required>
                        @foreach($locations as $loc)
                            <option value="{{ $loc }}">{{ ucfirst(str_replace('-', ' ', $loc)) }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-4 mb-3">
                    <label class="form-label">Hours per week</label>
                    <input type="number" name="hours_per_week" class="form-control" step="0.5" min="0" value="40" required>
                </div>
                <div class="col-md-4 mb-3">
                    <label class="form-label">Number of guards</label>
                    <input type="number" name="number_of_guards" class="form-control" min="1" value="1" required>
                </div>
            </div>
            <button type="submit" class="btn btn-primary">Calculate</button>
        </form>
    </x-card>
    @if(!empty($result))
        <x-card title="Result" class="mt-4">
            <p class="mb-1"><strong>Hourly rate (est.):</strong> ${{ number_format($result['hourly_rate'], 2) }}</p>
            <p class="mb-1"><strong>Weekly total:</strong> ${{ number_format($result['weekly_total'], 2) }}</p>
            <p class="mb-1"><strong>Monthly total:</strong> ${{ number_format($result['monthly_total'], 2) }}</p>
            <p class="mb-0"><strong>Annual total:</strong> ${{ number_format($result['annual_total'], 2) }}</p>
            <x-report-actions report-type="instant-estimator" />
        </x-card>
    @endif
</div>
@endsection

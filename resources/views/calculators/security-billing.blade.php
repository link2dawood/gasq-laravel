@extends('layouts.app')

@section('title', 'Security Billing')

@section('content')
<div class="container py-4">
    <h1 class="h2 mb-4">Security Billing Calculator</h1>
    @php
        $tab = request()->input('tab', 'calculator');
    @endphp

    <ul class="nav nav-tabs mb-4" role="tablist">
        <li class="nav-item" role="presentation">
            <a
                class="nav-link {{ $tab === 'calculator' ? 'active' : '' }}"
                href="{{ route('security-billing.index', ['tab' => 'calculator']) }}"
            >
                Calculator
            </a>
        </li>
        <li class="nav-item" role="presentation">
            <a
                class="nav-link {{ $tab === 'comparison' ? 'active' : '' }}"
                href="{{ route('security-billing.index', ['tab' => 'comparison']) }}"
            >
                Side-by-Side Comparison
            </a>
        </li>
    </ul>

    @if($tab === 'comparison')
        <x-card title="Side-by-Side Comparison">
            <p class="mb-3 text-gasq-muted">
                UI placeholder. The React project provides a comparison view here.
            </p>
            <div class="table-responsive">
                <table class="table table-striped align-middle">
                    <thead>
                        <tr>
                            <th>Metric</th>
                            <th>Example Comparison A</th>
                            <th>Example Comparison B</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td>Weekly Total</td>
                            <td class="font-monospace">$1,200.00</td>
                            <td class="font-monospace">$1,050.00</td>
                        </tr>
                        <tr>
                            <td>Annual Total</td>
                            <td class="font-monospace">$62,400.00</td>
                            <td class="font-monospace">$54,600.00</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </x-card>
    @else
        <x-card title="Billing estimate">
            <form method="POST" action="{{ route('backend.security-billing.post') }}">
                @csrf
                <input type="hidden" name="tab" value="calculator">
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
    @endif
</div>
@endsection

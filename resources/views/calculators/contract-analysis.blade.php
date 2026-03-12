@extends('layouts.app')

@section('title', 'Contract Analysis')

@section('content')
<div class="container py-4">
    <h1 class="h2 mb-4">Contract Analysis</h1>
    <x-card title="Categories: weekly hours, pay rate, bill rate">
        <form method="POST" action="{{ route('contract-analysis.index') }}">
            @csrf
            @for($i = 0; $i < 5; $i++)
            <div class="row mb-2">
                <div class="col-md-4"><input type="number" name="categories[{{ $i }}][weekly_hours]" class="form-control" placeholder="Weekly hours" step="0.5" min="0"></div>
                <div class="col-md-4"><input type="number" name="categories[{{ $i }}][pay_rate]" class="form-control" placeholder="Pay rate $" step="0.01" min="0"></div>
                <div class="col-md-4"><input type="number" name="categories[{{ $i }}][bill_rate]" class="form-control" placeholder="Bill rate $" step="0.01" min="0"></div>
            </div>
            @endfor
            <button type="submit" class="btn btn-primary mt-2">Analyze</button>
        </form>
    </x-card>
    @if(!empty($result))
        <x-card title="Summary" class="mt-4">
            <p class="mb-1"><strong>Total annual hours:</strong> {{ number_format($result['total_annual_hours'], 0) }}</p>
            <p class="mb-1"><strong>Total annual pay cost:</strong> ${{ number_format($result['total_annual_pay_cost'], 2) }}</p>
            <p class="mb-1"><strong>Total annual bill revenue:</strong> ${{ number_format($result['total_annual_bill_revenue'], 2) }}</p>
            <p class="mb-1"><strong>Gross margin:</strong> ${{ number_format($result['gross_margin'], 2) }}</p>
            <p class="mb-0"><strong>Margin %:</strong> {{ number_format($result['margin_percent'], 1) }}%</p>
            <x-report-actions report-type="contract-analysis" />
        </x-card>
    @endif
</div>
@endsection

@extends('layouts.app')

@section('title', 'Contract Analysis')

@section('content')
<div class="container py-4">
    <h1 class="h2 mb-4">Contract Analysis</h1>

    @php
        $internalTab = request()->input('tab', 'per-hour-analysis');
        $internalTab = is_string($internalTab) ? $internalTab : 'per-hour-analysis';
    @endphp

    <ul class="nav nav-tabs mb-4" role="tablist">
        <li class="nav-item" role="presentation">
            <a class="nav-link {{ $internalTab === 'per-hour-analysis' ? 'active' : '' }}"
               href="{{ route('contract-analysis.index', ['tab' => 'per-hour-analysis']) }}">
                Per Hour Analysis
            </a>
        </li>
        <li class="nav-item" role="presentation">
            <a class="nav-link {{ $internalTab === 'category-inputs' ? 'active' : '' }}"
               href="{{ route('contract-analysis.index', ['tab' => 'category-inputs']) }}">
                Category Inputs
            </a>
        </li>
        <li class="nav-item" role="presentation">
            <a class="nav-link {{ $internalTab === 'summary' ? 'active' : '' }}"
               href="{{ route('contract-analysis.index', ['tab' => 'summary']) }}">
                Summary
            </a>
        </li>
    </ul>

    @if($internalTab === 'category-inputs')
        <x-card title="Categories: weekly hours, pay rate, bill rate">
            <form method="POST" action="{{ route('contract-analysis.index', ['tab' => $internalTab]) }}">
                @csrf
                @for($i = 0; $i < 5; $i++)
                    <div class="row mb-2">
                        <div class="col-md-4">
                            <input type="number"
                                   name="categories[{{ $i }}][weekly_hours]"
                                   class="form-control"
                                   placeholder="Weekly hours"
                                   step="0.5" min="0">
                        </div>
                        <div class="col-md-4">
                            <input type="number"
                                   name="categories[{{ $i }}][pay_rate]"
                                   class="form-control"
                                   placeholder="Pay rate $"
                                   step="0.01" min="0">
                        </div>
                        <div class="col-md-4">
                            <input type="number"
                                   name="categories[{{ $i }}][bill_rate]"
                                   class="form-control"
                                   placeholder="Bill rate $"
                                   step="0.01" min="0">
                        </div>
                    </div>
                @endfor
                <button type="submit" class="btn btn-primary mt-2">Analyze</button>
            </form>
        </x-card>
    @endif

    @if($internalTab === 'per-hour-analysis')
        <x-card title="Per Hour Analysis">
            @if(!empty($result))
                <div class="text-gasq-muted mb-3">
                    Laravel currently calculates totals for the contract. This preview tab uses the same analyzed
                    result set as the Summary tab.
                </div>
                <p class="mb-1"><strong>Total annual hours:</strong> {{ number_format($result['total_annual_hours'], 0) }}</p>
                <p class="mb-1"><strong>Total annual pay cost:</strong> ${{ number_format($result['total_annual_pay_cost'], 2) }}</p>
                <p class="mb-1"><strong>Total annual bill revenue:</strong> ${{ number_format($result['total_annual_bill_revenue'], 2) }}</p>
                <p class="mb-1"><strong>Gross margin:</strong> ${{ number_format($result['gross_margin'], 2) }}</p>
                <p class="mb-0"><strong>Margin %:</strong> {{ number_format($result['margin_percent'], 1) }}%</p>
                <div class="mt-3">
                    <a class="btn btn-outline-primary" href="{{ route('contract-analysis.index', ['tab' => 'summary']) }}">
                        View Full Summary
                    </a>
                </div>
            @else
                <p class="mb-0 text-gasq-muted">
                    Run the analysis from the “Category Inputs” tab to see per-hour results.
                </p>
            @endif
        </x-card>
    @endif

    @if($internalTab === 'summary')
        @if(!empty($result))
            <x-card title="Summary">
                <p class="mb-1"><strong>Total annual hours:</strong> {{ number_format($result['total_annual_hours'], 0) }}</p>
                <p class="mb-1"><strong>Total annual pay cost:</strong> ${{ number_format($result['total_annual_pay_cost'], 2) }}</p>
                <p class="mb-1"><strong>Total annual bill revenue:</strong> ${{ number_format($result['total_annual_bill_revenue'], 2) }}</p>
                <p class="mb-1"><strong>Gross margin:</strong> ${{ number_format($result['gross_margin'], 2) }}</p>
                <p class="mb-0"><strong>Margin %:</strong> {{ number_format($result['margin_percent'], 1) }}%</p>
                <x-report-actions report-type="contract-analysis" />
            </x-card>
        @else
            <x-card title="Summary">
                <p class="mb-0 text-gasq-muted">
                    No summary yet. Submit category inputs to generate results.
                </p>
            </x-card>
        @endif
    @endif
</div>
@endsection

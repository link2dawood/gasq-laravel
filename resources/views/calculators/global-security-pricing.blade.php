@extends('layouts.app')

@section('title', 'Global Security Pricing')

@section('content')
@php
    $tab = request()->input('tab', 'categories');
    $tabs = [
        'categories' => 'Posts/Categories',
        'contract-analysis' => 'Contract Analysis',
        'bill-rate' => 'Bill Rate Analysis',
        'contract-summary' => 'Contract Summary',
        'settings' => 'Settings',
        'benefits' => 'Benefits',
        'costs' => 'Additional Costs',
        'summary' => 'Summary',
        'comparison' => 'Scenario Comparison',
    ];
@endphp

<div class="container py-4">
    <div class="d-flex align-items-start justify-content-between gap-3 flex-wrap mb-3">
        <div>
            <h1 class="h2 mb-1">Global Security Pricing</h1>
            <div class="text-gasq-muted small">UI preview with React-matching tabs.</div>
        </div>
        <a class="btn btn-outline-primary" href="{{ url('/contract-analysis') }}">
            Open Contract Analysis
        </a>
    </div>

    <div style="overflow-x:auto; white-space:nowrap;">
        <ul class="nav nav-tabs mb-4" role="tablist">
            @foreach($tabs as $key => $label)
                <li class="nav-item" role="presentation">
                    <a
                        class="nav-link {{ $tab === $key ? 'active' : '' }}"
                        href="{{ url('/global-security-pricing?tab=' . $key) }}"
                    >
                        {{ $label }}
                    </a>
                </li>
            @endforeach
        </ul>
    </div>

    @if($tab === 'categories')
        <x-card title="Posts/Categories">
            <p class="mb-3 text-gasq-muted">
                Static UI preview for categories/posts management.
            </p>
            <div class="table-responsive">
                <table class="table table-striped align-middle">
                    <thead>
                        <tr>
                            <th>Armed</th>
                            <th>Post/Position</th>
                            <th class="text-center">Weekly Hours</th>
                            <th class="text-end">Total Training (example)</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td><span class="badge text-bg-primary">Yes</span></td>
                            <td>Access Control Officer</td>
                            <td class="text-center font-monospace">54</td>
                            <td class="text-end font-monospace">$1,200.00</td>
                        </tr>
                        <tr>
                            <td><span class="badge text-bg-secondary">No</span></td>
                            <td>Unarmed Security Officer</td>
                            <td class="text-center font-monospace">40</td>
                            <td class="text-end font-monospace">$900.00</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </x-card>
    @else
        <x-card title="{{ $tabs[$tab] ?? 'Tab' }}">
            <p class="mb-0 text-gasq-muted">
                UI placeholder for this tab. (React wires this to interactive data + calculations.)
            </p>
        </x-card>
    @endif
</div>
@endsection


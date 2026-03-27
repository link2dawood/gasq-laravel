@extends('layouts.app')

@section('title', 'Mobile Patrol Cost Analysis')

@section('content')
@php
    $tab = request()->input('tab', 'calculator');
    $tabs = [
        'calculator' => 'Calculator',
        'dashboard' => 'Dashboard',
        'reports' => 'Reports',
        'settings' => 'Settings',
        'tools' => 'Tools',
    ];
@endphp

<div class="container py-4">
    <div class="d-flex align-items-start justify-content-between gap-3 flex-wrap mb-3">
        <div>
            <h1 class="h2 mb-1">Mobile Patrol Cost Analysis</h1>
            <div class="text-gasq-muted small">
                UI preview with React-matching tabs (no functional calculations).
            </div>
        </div>
        <a class="btn btn-outline-primary" href="{{ url('/mobile-patrol-calculator') }}">
            Open Mobile Patrol Calculator
        </a>
    </div>

    <div style="overflow-x:auto; white-space:nowrap;">
        <ul class="nav nav-tabs mb-4" role="tablist">
            @foreach($tabs as $key => $label)
                <li class="nav-item" role="presentation">
                    <a
                        class="nav-link {{ $tab === $key ? 'active' : '' }}"
                        href="{{ url('/mobile-patrol-analysis?tab=' . $key) }}"
                    >
                        {{ $label }}
                    </a>
                </li>
            @endforeach
        </ul>
    </div>

    @if($tab === 'dashboard')
        <x-card title="Dashboard">
            <p class="mb-3 text-gasq-muted">
                Static preview of KPIs and summary metrics.
            </p>
            <div class="row g-3">
                <div class="col-md-4">
                    <div class="text-gasq-muted small mb-1">Monthly Cost (example)</div>
                    <div class="fw-bold fs-5">$14,250.00</div>
                </div>
                <div class="col-md-4">
                    <div class="text-gasq-muted small mb-1">Miles / Month (example)</div>
                    <div class="fw-bold fs-5">12,600</div>
                </div>
                <div class="col-md-4">
                    <div class="text-gasq-muted small mb-1">Hits / Month (example)</div>
                    <div class="fw-bold fs-5">380</div>
                </div>
            </div>
        </x-card>
    @elseif($tab === 'reports')
        <x-card title="Reports">
            <p class="mb-3 text-gasq-muted">
                Preview list of downloadable report sections.
            </p>
            <ul class="mb-0 ps-3 text-gasq-muted">
                <li>Cost by Category</li>
                <li>Vehicle Utilization</li>
                <li>Scenario Comparison Snapshot</li>
            </ul>
        </x-card>
    @elseif($tab === 'settings')
        <x-card title="Settings">
            <p class="mb-0 text-gasq-muted">
                UI-only placeholder for settings/preferences.
            </p>
        </x-card>
    @elseif($tab === 'tools')
        <x-card title="Tools">
            <p class="mb-0 text-gasq-muted">
                UI-only placeholder for analysis tools.
            </p>
        </x-card>
    @else
        <x-card title="Calculator">
            <p class="mb-3 text-gasq-muted">
                Static preview of the mobile patrol calculator workspace.
            </p>
            <div class="row g-3">
                <div class="col-md-6">
                    <label class="form-label text-gasq-muted">Vehicle Fleet</label>
                    <div class="border rounded p-3 bg-white">
                        <div class="fw-semibold">Example Vehicle A</div>
                        <div class="text-gasq-muted small">Color: Blue</div>
                    </div>
                </div>
                <div class="col-md-6">
                    <label class="form-label text-gasq-muted">Fiscal Year</label>
                    <div class="border rounded p-3 bg-white">
                        <div class="fw-semibold">{{ now()->year }}</div>
                        <div class="text-gasq-muted small">Preview only</div>
                    </div>
                </div>
            </div>
        </x-card>
    @endif
</div>
@endsection


@extends('layouts.app')

@section('header_variant', 'dashboard')

@section('title', 'Government Contract Calculator')

@section('content')
<div class="container py-5">
    <div class="text-center mb-5">
        <div class="d-inline-block px-4 py-2 rounded-pill bg-secondary text-white mb-4">
            UI Preview Only
        </div>
        <h1 class="display-4 fw-bold mb-3">Government Contract</h1>
        <p class="lead text-gasq-muted mx-auto" style="max-width: 900px;">
            Static UI preview of a government contract cost structure. No calculator logic is enabled.
        </p>

        <div class="d-flex justify-content-center gap-3 flex-wrap mt-4">
            <a class="btn btn-primary btn-lg" href="{{ url('/gasq-instant-estimator') }}">Try Instant Estimator</a>
            <a class="btn btn-outline-primary btn-lg" href="{{ url('/open-bid-offer') }}">Open Bid Offer</a>
        </div>
    </div>

    <div class="row g-4">
        <div class="col-lg-8">
            <x-card title="Sample Annual Estimate KPIs" subtitle="Static example numbers">
                <div class="table-responsive">
                    <table class="table table-striped align-middle">
                        <thead>
                            <tr>
                                <th>Metric</th>
                                <th class="text-end">Value</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td>Labor Cost (example)</td>
                                <td class="text-end font-monospace fw-semibold">$84,500</td>
                            </tr>
                            <tr>
                                <td>Overhead (example)</td>
                                <td class="text-end font-monospace fw-semibold">$18,250</td>
                            </tr>
                            <tr>
                                <td>Fringe &amp; Benefits (example)</td>
                                <td class="text-end font-monospace fw-semibold">$22,100</td>
                            </tr>
                            <tr class="fw-bold">
                                <td>Total Annual Estimate (example)</td>
                                <td class="text-end font-monospace">$124,850</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </x-card>
        </div>
        <div class="col-lg-4">
            <x-card title="Compliance View" subtitle="UI-only preview">
                <ul class="mb-0 ps-3">
                    <li>Static layout for estimator structure</li>
                    <li>No interactive inputs</li>
                    <li>Designed to match calculator page styling</li>
                </ul>
            </x-card>
        </div>
    </div>
</div>
@endsection


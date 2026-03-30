@extends('layouts.app')

@section('header_variant', 'dashboard')

@section('title', 'Keeps Doors Open Calculator')

@section('content')
<div class="container py-5">
    <div class="text-center mb-5">
        <div class="d-inline-block px-4 py-2 rounded-pill bg-secondary text-white mb-4">
            UI Preview Only
        </div>
        <h1 class="display-4 fw-bold mb-3">Keeps Doors Open</h1>
        <p class="lead text-gasq-muted mx-auto" style="max-width: 900px;">
            Static visualization of how access-control staffing can translate into a budget-ready estimate.
        </p>

        <div class="d-flex justify-content-center gap-3 flex-wrap mt-4">
            <a class="btn btn-primary" href="{{ url('/post-coverage-schedule') }}">Post Coverage Schedule</a>
            <a class="btn btn-outline-primary" href="{{ url('/open-bid-offer') }}">Open Bid Offer</a>
        </div>
    </div>

    <x-card title="Example Access Control Staffing Costs" subtitle="Static table (no interactive inputs)">
        <div class="table-responsive">
            <table class="table table-striped align-middle">
                <thead>
                    <tr>
                        <th>Coverage Scenario</th>
                        <th class="text-center">Staffing</th>
                        <th class="text-end">Annual (example)</th>
                        <th class="text-end">Cost / Day (example)</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td class="fw-semibold">Single Entry Point</td>
                        <td class="text-center fw-semibold">1 guard</td>
                        <td class="text-end font-monospace">$46,200</td>
                        <td class="text-end font-monospace text-gasq-muted">$126.55</td>
                    </tr>
                    <tr>
                        <td class="fw-semibold">Front + Back Doors</td>
                        <td class="text-center fw-semibold">2 guards</td>
                        <td class="text-end font-monospace">$88,400</td>
                        <td class="text-end font-monospace text-gasq-muted">$242.19</td>
                    </tr>
                    <tr>
                        <td class="fw-semibold">Multi-Site Lobby Coverage</td>
                        <td class="text-center fw-semibold">3 guards</td>
                        <td class="text-end font-monospace">$132,600</td>
                        <td class="text-end font-monospace text-gasq-muted">$363.29</td>
                    </tr>
                    <tr>
                        <td class="fw-semibold">24/7 Access Control</td>
                        <td class="text-center fw-semibold">6 guards</td>
                        <td class="text-end font-monospace">$264,800</td>
                        <td class="text-end font-monospace text-gasq-muted">$725.75</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </x-card>
</div>
@endsection


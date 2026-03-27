@extends('layouts.app')

@section('title', 'GASQ TCO Calculator')

@section('content')
<div class="container py-5">
    <div class="text-center mb-5">
        <div class="d-inline-block px-4 py-2 rounded-pill bg-secondary text-white mb-4">
            UI Preview Only
        </div>
        <h1 class="display-4 fw-bold mb-3">Total Cost of Ownership, Made Clear</h1>
        <p class="lead text-gasq-muted mx-auto" style="max-width: 900px;">
            Review a sample cost breakdown layout designed to help buyers compare coverage approaches without
            enabling calculator logic.
        </p>

        <div class="d-flex justify-content-center gap-3 flex-wrap mt-4">
            <a class="btn btn-primary btn-lg" href="{{ url('/open-bid-offer') }}">Open Bid Offer</a>
            <a class="btn btn-outline-primary btn-lg" href="{{ route('jobs.create') }}">Post Your Job</a>
        </div>
    </div>

    <div class="row g-4">
        <div class="col-md-4">
            <x-card title="Example Buyer TCO" subtitle="Static UI example">
                <div class="display-5 fw-bold mb-1">$145,200</div>
                <div class="text-gasq-muted small">per year (example)</div>
            </x-card>
        </div>
        <div class="col-md-4">
            <x-card title="Example Vendor TCO" subtitle="Static UI example">
                <div class="display-5 fw-bold mb-1">$132,900</div>
                <div class="text-gasq-muted small">per year (example)</div>
            </x-card>
        </div>
        <div class="col-md-4">
            <x-card title="Decision Support" subtitle="Clarity over guesswork">
                <p class="mb-0 text-gasq-muted">
                    View cost components at-a-glance and understand what drives the estimate.
                </p>
            </x-card>
        </div>
    </div>

    <div class="mt-5">
        <x-card title="Sample Cost Breakdown" subtitle="Static preview table (no calculations)">
            <div class="table-responsive">
                <table class="table table-striped align-middle">
                    <thead>
                        <tr>
                            <th>Cost Component</th>
                            <th class="text-center">Hourly (example)</th>
                            <th class="text-end">Annual (example)</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td>Direct Labor</td>
                            <td class="text-center fw-semibold font-monospace">$18.40</td>
                            <td class="text-end font-monospace">$62,720</td>
                        </tr>
                        <tr>
                            <td>Fringe & Benefits</td>
                            <td class="text-center fw-semibold font-monospace">$6.15</td>
                            <td class="text-end font-monospace">$20,970</td>
                        </tr>
                        <tr>
                            <td>Operations</td>
                            <td class="text-center fw-semibold font-monospace">$4.30</td>
                            <td class="text-end font-monospace">$14,700</td>
                        </tr>
                        <tr>
                            <td>Overhead</td>
                            <td class="text-center fw-semibold font-monospace">$2.05</td>
                            <td class="text-end font-monospace">$7,010</td>
                        </tr>
                        <tr class="fw-bold">
                            <td>Total (Example)</td>
                            <td class="text-center font-monospace">$30.90</td>
                            <td class="text-end font-monospace">$105,400</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </x-card>
    </div>
</div>
@endsection


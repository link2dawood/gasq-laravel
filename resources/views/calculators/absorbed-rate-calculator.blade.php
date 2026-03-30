@extends('layouts.app')

@section('header_variant', 'dashboard')

@section('title', 'Absorbed Rate Calculator')

@section('content')
<div class="container py-5">
    <div class="text-center mb-5">
        <div class="d-inline-block px-4 py-2 rounded-pill bg-secondary text-white mb-4">
            UI Preview Only
        </div>
        <h1 class="display-4 fw-bold mb-3">Absorbed Rate</h1>
        <p class="lead text-gasq-muted mx-auto" style="max-width: 900px;">
            Example absorbed-rate scenarios showing how labor, fringe, and overhead can combine into a single
            operational number (static UI only).
        </p>
    </div>

    <div class="row g-4 mb-4">
        <div class="col-md-6">
            <x-card title="Quick Actions">
                <div class="d-flex gap-3 flex-wrap">
                    <a class="btn btn-primary" href="{{ url('/main-menu-calculator') }}">Main Menu Calculator</a>
                    <a class="btn btn-outline-primary" href="{{ url('/open-bid-offer') }}">Open Bid Offer</a>
                </div>
            </x-card>
        </div>
        <div class="col-md-6">
            <x-card title="What this page is">
                <p class="mb-0 text-gasq-muted">
                    Static preview layout only. No inputs, no calculations, no API calls.
                </p>
            </x-card>
        </div>
    </div>

    <x-card title="Example Absorbed Rate Table" subtitle="Static sample values (no calculations)">
        <div class="table-responsive">
            <table class="table table-striped align-middle">
                <thead>
                    <tr>
                        <th>Scenario</th>
                        <th class="text-center">Hours / Day (example)</th>
                        <th class="text-end">Absorbed Rate</th>
                        <th class="text-end">What’s included</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td class="fw-semibold">Basic Coverage</td>
                        <td class="text-center fw-semibold">8</td>
                        <td class="text-end font-monospace">$38.20/hr</td>
                        <td class="text-end text-gasq-muted">Labor + fringe (example)</td>
                    </tr>
                    <tr>
                        <td class="fw-semibold">Enhanced Coverage</td>
                        <td class="text-center fw-semibold">10</td>
                        <td class="text-end font-monospace">$45.60/hr</td>
                        <td class="text-end text-gasq-muted">Labor + fringe + ops (example)</td>
                    </tr>
                    <tr>
                        <td class="fw-semibold">24/7 Coverage</td>
                        <td class="text-center fw-semibold">24</td>
                        <td class="text-end font-monospace">$62.90/hr</td>
                        <td class="text-end text-gasq-muted">Labor + fringe + overhead (example)</td>
                    </tr>
                    <tr>
                        <td class="fw-semibold">Special Operations</td>
                        <td class="text-center fw-semibold">12</td>
                        <td class="text-end font-monospace">$54.10/hr</td>
                        <td class="text-end text-gasq-muted">Labor + ops + training (example)</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </x-card>
</div>
@endsection


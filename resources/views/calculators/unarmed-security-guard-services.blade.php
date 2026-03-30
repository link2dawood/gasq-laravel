@extends('layouts.app')

@section('header_variant', 'dashboard')

@section('title', 'Unarmed Security Guard Services')

@section('content')
<div class="container py-5">
    <div class="text-center mb-5">
        <h1 class="display-5 fw-bold mb-3">Unarmed Security Guard Services</h1>
        <p class="lead text-gasq-muted mx-auto" style="max-width: 900px;">
            UI preview of the React route <code>/unarmed-security-guard-services</code>.
        </p>
        <div class="d-flex justify-content-center gap-3 flex-wrap mt-4">
            <a class="btn btn-primary btn-lg" href="{{ url('/main-menu-calculator?tab=security') }}">Open Security Cost Calculator</a>
            <a class="btn btn-outline-primary btn-lg" href="{{ url('/open-bid-offer') }}">Open Bid Offer</a>
        </div>
    </div>

    <div class="row g-4">
        <div class="col-lg-7">
            <x-card title="Example Coverage Packages" subtitle="Static UI only">
                <div class="table-responsive">
                    <table class="table table-striped align-middle">
                        <thead>
                            <tr>
                                <th>Package</th>
                                <th class="text-center">Guards</th>
                                <th class="text-end">Hours / Week</th>
                                <th class="text-end">Example Monthly Total</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td class="fw-semibold">Lobby Coverage</td>
                                <td class="text-center fw-semibold">1</td>
                                <td class="text-end font-monospace">40</td>
                                <td class="text-end font-monospace">$2,450.00</td>
                            </tr>
                            <tr>
                                <td class="fw-semibold">Warehouse Perimeter</td>
                                <td class="text-center fw-semibold">2</td>
                                <td class="text-end font-monospace">80</td>
                                <td class="text-end font-monospace">$4,900.00</td>
                            </tr>
                            <tr>
                                <td class="fw-semibold">Events &amp; Access Control</td>
                                <td class="text-center fw-semibold">3</td>
                                <td class="text-end font-monospace">120</td>
                                <td class="text-end font-monospace">$7,350.00</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </x-card>
        </div>

        <div class="col-lg-5">
            <x-card title="What’s included" subtitle="Preview bullets">
                <ul class="mb-0 ps-3 text-gasq-muted">
                    <li>Unarmed presence and access control</li>
                    <li>Incident observation and site reporting</li>
                    <li>Front desk &amp; visitor management (as applicable)</li>
                    <li>Patrol routing guidance based on schedule</li>
                </ul>
            </x-card>
            <div class="mt-4">
                <x-card title="Next step" subtitle="Use functional calculators">
                    <p class="mb-0 text-gasq-muted">
                        Switch to `Main Menu` → `Security Cost` to run the functional estimate with your real inputs.
                    </p>
                </x-card>
            </div>
        </div>
    </div>
</div>
@endsection


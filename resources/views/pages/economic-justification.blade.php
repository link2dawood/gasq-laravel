@extends('layouts.app')

@section('title', 'Economic Justification')

@section('content')
<div class="container py-4">
    <h1 class="h2 mb-3">Economic Justification</h1>

    <x-calculator-tabs active="justification" />

    <div class="row g-4">
        <div class="col-lg-8">
            <div class="card gasq-card">
                <div class="card-header">
                    <h5 class="card-title mb-0">UI Preview Only</h5>
                </div>
                <div class="card-body">
                    <p class="text-gasq-muted mb-3">
                        This page is a UI-only preview to match the React route <code>/economic-justification</code>.
                        Tabs match the calculator navigation highlight.
                    </p>

                    <div class="card mb-3 border-primary">
                        <div class="card-body">
                            <div class="fw-semibold mb-2">Example ROI metrics</div>
                            <ul class="mb-0 ps-3 text-gasq-muted">
                                <li>In-house annual cost (example): $930,651.42</li>
                                <li>Outsourced annual cost (example): $651,443.52</li>
                                <li>Estimated ROI savings (example): $279,207.90</li>
                                <li>ROI percentage (example): 30%</li>
                            </ul>
                        </div>
                    </div>

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
                                    <td>Estimated Breakeven / Payback Period</td>
                                    <td class="text-end font-monospace">$8.40 Months</td>
                                </tr>
                                <tr>
                                    <td>ROI Dollar-for-Dollar Saved</td>
                                    <td class="text-end font-monospace">$1.42 Saved</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <div class="mt-3">
                        <a class="btn btn-outline-primary" href="{{ url('/main-menu-calculator?tab=economic') }}">
                            Open Functional Economic ROI Tab
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="card gasq-card">
                <div class="card-header">
                    <h5 class="card-title mb-0">What’s next</h5>
                </div>
                <div class="card-body">
                    <p class="text-gasq-muted mb-0">
                        When you want this route functional, we can wire it to the existing Laravel
                        `economicJustification` service (or expand it for additional justification fields).
                    </p>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection


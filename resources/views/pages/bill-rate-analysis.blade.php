@extends('layouts.app')

@section('title', 'Bill Rate Analysis')

@section('content')
<div class="container py-4">
    <h1 class="h2 mb-3">Bill Rate Analysis</h1>

    <x-calculator-tabs active="billrate" />

    <div class="row g-4">
        <div class="col-lg-8">
            <div class="card gasq-card">
                <div class="card-header">
                    <h5 class="card-title mb-0">UI Preview Only</h5>
                </div>
                <div class="card-body">
                    <p class="text-gasq-muted mb-3">
                        This Laravel page is a UI preview to match the React route <code>/bill-rate-analysis</code>.
                        No calculator logic is wired here.
                    </p>

                    <div class="table-responsive">
                        <table class="table table-striped align-middle">
                            <thead>
                                <tr>
                                    <th>Cost Component</th>
                                    <th class="text-end">Example Value</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td>Wages and Benefits</td>
                                    <td class="text-end font-monospace">$41.05</td>
                                </tr>
                                <tr>
                                    <td>Taxes and Insurance</td>
                                    <td class="text-end font-monospace">$10.96</td>
                                </tr>
                                <tr>
                                    <td>Training Costs</td>
                                    <td class="text-end font-monospace">$2.02</td>
                                </tr>
                                <tr>
                                    <td>Uniforms &amp; Equipment</td>
                                    <td class="text-end font-monospace">$1.47</td>
                                </tr>
                                <tr>
                                    <td class="fw-bold">Total (example)</td>
                                    <td class="text-end font-monospace fw-bold">$57.00</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <div class="mt-3">
                        <a class="btn btn-outline-primary" href="{{ url('/main-menu-calculator?tab=billrate') }}">
                            Open Functional Bill Rate Tab
                        </a>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-lg-4">
            <div class="card gasq-card">
                <div class="card-header">
                    <h5 class="card-title mb-0">What this shows</h5>
                </div>
                <div class="card-body">
                    <ul class="mb-0 ps-3">
                        <li>How labor + burden components feed into a bill rate</li>
                        <li>UI matches the tab highlight from React</li>
                        <li>Functional calculator lives in the main menu tab</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection


@extends('layouts.app')

@section('title', 'Manpower Hours')

@section('content')
<div class="container py-4">
    <h1 class="h2 mb-3">Manpower Hours</h1>

    <x-calculator-tabs active="manpower" />

    <div class="row g-4">
        <div class="col-lg-8">
            <div class="card gasq-card">
                <div class="card-header">
                    <h5 class="card-title mb-0">UI Preview Only</h5>
                </div>
                <div class="card-body">
                    <p class="text-gasq-muted mb-3">
                        This Laravel page matches the React route <code>/manpower-hours</code> visually (tab highlight),
                        but does not compute values.
                    </p>

                    <div class="table-responsive">
                        <table class="table table-striped align-middle">
                            <thead>
                                <tr>
                                    <th>Example Scenario</th>
                                    <th class="text-center">Hours / Week</th>
                                    <th class="text-end">Annual Hours</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td>8 hrs/day, Mon-Fri</td>
                                    <td class="text-center font-monospace">40</td>
                                    <td class="text-end font-monospace">20,800</td>
                                </tr>
                                <tr>
                                    <td>12 hrs/day, Mon-Fri</td>
                                    <td class="text-center font-monospace">60</td>
                                    <td class="text-end font-monospace">31,200</td>
                                </tr>
                                <tr>
                                    <td>24/7 Coverage</td>
                                    <td class="text-center font-monospace">168</td>
                                    <td class="text-end font-monospace">87,360</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <div class="mt-3">
                        <a class="btn btn-outline-primary" href="{{ url('/main-menu-calculator?tab=manpower') }}">
                            Open Functional Manpower Tab
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="card gasq-card">
                <div class="card-header">
                    <h5 class="card-title mb-0">Key inputs (preview)</h5>
                </div>
                <div class="card-body">
                    <ul class="mb-0 ps-3">
                        <li>Site coverage / required hours</li>
                        <li>Shift pattern (8/10/12/24)</li>
                        <li>Scheduling factor</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection


@extends('layouts.app')

@section('header_variant', 'dashboard')

@section('title', 'Calculator (UI)')

@section('content')
<div class="container py-5">
    <div class="text-center mb-5">
        <div class="d-inline-block px-4 py-2 rounded-pill bg-secondary text-white mb-4">
            UI Preview Only
        </div>
        <h1 class="display-4 fw-bold mb-3">Security Calculator</h1>
        <p class="lead text-gasq-muted mx-auto" style="max-width: 900px;">
            Visual preview of the calculator layout. All inputs are disabled (no cost calculations).
        </p>
        <div class="d-flex justify-content-center gap-3 flex-wrap mt-4">
            <a class="btn btn-primary btn-lg" href="{{ url('/main-menu-calculator') }}">Open Main Menu Calculator</a>
            <a class="btn btn-outline-primary btn-lg" href="{{ url('/calculator') }}">Open Calculator Hub</a>
        </div>
    </div>

    <div class="row g-4">
        <div class="col-lg-7">
            <x-card title="Inputs (Disabled)" subtitle="Preview fields">
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label">Service Type</label>
                        <select disabled class="form-select">
                            <option>Unarmed</option>
                            <option>Armed</option>
                            <option>Patrol</option>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Hourly Pay Rate ($)</label>
                        <input disabled class="form-control" value="25.00" />
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Staff per Shift</label>
                        <input disabled class="form-control" value="1" />
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Hours per Day</label>
                        <input disabled class="form-control" value="8" />
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Days per Week</label>
                        <input disabled class="form-control" value="5" />
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Contract Weeks</label>
                        <input disabled class="form-control" value="52" />
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Payment Plan</label>
                        <select disabled class="form-select">
                            <option value="">Select</option>
                            <option value="inhouse">In-house</option>
                            <option value="upfront">Upfront</option>
                            <option value="net30">Net 30</option>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Location Factor</label>
                        <select disabled class="form-select">
                            <option value="">Select</option>
                            <option value="critical">Critical</option>
                            <option value="high">High</option>
                            <option value="medium">Medium</option>
                            <option value="low">Low</option>
                        </select>
                    </div>
                </div>
                <div class="mt-4">
                    <button disabled class="btn btn-primary">Calculate (disabled)</button>
                </div>
            </x-card>
        </div>

        <div class="col-lg-5">
            <x-card title="Example Results" subtitle="Static sample values">
                <div class="row g-3">
                    <div class="col-12">
                        <div class="p-3 rounded border bg-light">
                            <div class="text-muted small">Hourly Rate (example)</div>
                            <div class="fw-bold fs-5">$62.90</div>
                        </div>
                    </div>
                    <div class="col-12">
                        <div class="p-3 rounded border bg-light">
                            <div class="text-muted small">Annual Total (example)</div>
                            <div class="fw-bold fs-5">$145,200</div>
                        </div>
                    </div>
                </div>

                <hr class="my-4">
                <div class="text-gasq-muted small mb-0">
                    Full calculation is available in the functional calculators under the existing calculator dropdown.
                </div>
            </x-card>
        </div>
    </div>
</div>
@endsection


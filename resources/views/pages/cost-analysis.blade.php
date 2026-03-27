@extends('layouts.app')

@section('title', 'Cost Analysis (UI)')

@section('content')
<div class="container mx-auto px-4 py-8">
    <div class="text-center mb-8">
        <h1 class="display-5 fw-bold mb-3">Know Your Total Cost of Ownership</h1>
        <p class="lead text-gasq-muted mb-0">
            Section 4 – Total Cost of Ownership Return on Investment Analysis (UI Preview Only)
        </p>
    </div>

    <div class="card gasq-card mb-4">
        <div class="card-header">
            <h5 class="card-title mb-0">Return on Investment Analysis</h5>
            <div class="text-gasq-muted small mt-1">
                Comprehensive cost comparison between in-house and outsourced security services (static).
            </div>
        </div>
        <div class="card-body">
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
                            <td>My Total Cost of Ownership Workforce Staffing Cost if Performed Inhouse</td>
                            <td class="text-end fw-bold text-primary">$930,651.42</td>
                        </tr>
                        <tr>
                            <td>My Outsourced Total Cost on Preventive Investment</td>
                            <td class="text-end fw-bold text-primary">$651,443.52</td>
                        </tr>
                        <tr>
                            <td>My Total Cost of Ownership Return on Investment Dollar Savings (ROI)</td>
                            <td class="text-end fw-bold text-primary">$279,207.90</td>
                        </tr>
                        <tr>
                            <td>My Total Cost of Ownership Return on Investment (% of Profit/Revenue Saved)</td>
                            <td class="text-end fw-bold text-primary">30%</td>
                        </tr>
                        <tr>
                            <td>Estimated Breakeven / Payback &amp; Recovery Period (Months)</td>
                            <td class="text-end fw-bold text-primary">8.40 Months</td>
                        </tr>
                        <tr>
                            <td>Return On Investment Dollar-for-Dollar Saved for Every Dollar Spent</td>
                            <td class="text-end fw-bold text-primary">$1.42 Saved</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="card gasq-card mb-4 border-primary">
        <div class="card-body">
            <div class="p-4 rounded" style="background: rgba(13,110,253,0.05);">
                <p class="mb-0">
                    <strong>All price quotes include the full cost of workforce staffing and support services</strong>,
                    including but not limited to: base wages, employer-paid payroll taxes (FICA, FUTA, SUTA), workers'
                    compensation, general liability insurance, unemployment insurance, paid time off, healthcare and fringe
                    benefits, uniforms and equipment, onboarding and training, site supervision, quality assurance oversight,
                    management and administrative support, 24/7 dispatch capability, compliance with local/state/federal
                    labor laws, and all service-level guarantees (including open post protection, vendor replacement, and
                    price lock guarantees) unless otherwise specified.
                </p>
            </div>
        </div>
    </div>

    <h2 class="text-center fw-bold mb-4">Key Components of GetASecurityQuote In-House Security Services Appraisal Estimate</h2>
    <p class="text-center text-gasq-muted mb-4">Here's what it typically includes:</p>

    <div class="row g-4 mb-4">
        <div class="col-md-6 col-lg-4">
            <x-card title="Direct Labor Costs">
                <ul class="mb-0 ps-3">
                    <li>Wages: Hourly pay (based on market/union/livable wage)</li>
                    <li>Overtime: scheduling &amp; holiday coverage</li>
                    <li>Paid Non-Worked Hours: holidays, sick leave, vacation, training</li>
                </ul>
            </x-card>
        </div>
        <div class="col-md-6 col-lg-4">
            <x-card title="Payroll Burden">
                <ul class="mb-0 ps-3">
                    <li>Employer Taxes: FICA, FUTA, SUTA, Medicare</li>
                    <li>Workers' Compensation: risk-rated by job classification</li>
                    <li>Unemployment Insurance and General Liability Insurance</li>
                </ul>
            </x-card>
        </div>
        <div class="col-md-6 col-lg-4">
            <x-card title="Employee Benefits">
                <ul class="mb-0 ps-3">
                    <li>Health Insurance and Retirement Contributions</li>
                    <li>Life &amp; Disability Insurance</li>
                    <li>Uniforms &amp; Equipment + licensing fees (as applicable)</li>
                </ul>
            </x-card>
        </div>
    </div>

    <div class="row g-4 mb-4">
        <div class="col-md-6 col-lg-4">
            <x-card title="Supervision & Management">
                <ul class="mb-0 ps-3">
                    <li>Field Supervisors / Account Managers</li>
                    <li>Scheduling &amp; Admin Staff</li>
                    <li>On-call Management Support</li>
                </ul>
            </x-card>
        </div>
        <div class="col-md-6 col-lg-4">
            <x-card title="Overhead Allocation">
                <ul class="mb-0 ps-3">
                    <li>Recruiting, Hiring, and Turnover Costs</li>
                    <li>Training (initial + recurring)</li>
                    <li>Vehicles, office space, software, timekeeping systems</li>
                </ul>
            </x-card>
        </div>
        <div class="col-md-6 col-lg-4">
            <x-card title="Risk & Liability Exposure">
                <ul class="mb-0 ps-3">
                    <li>Negligent security claims (examples: SB 68)</li>
                    <li>Legal &amp; HR costs for discipline/claims</li>
                    <li>Coverage gaps / untrained guards on post</li>
                </ul>
            </x-card>
        </div>
    </div>

    <x-card title="Key Financial Benefits" subtitle="Static preview badges">
        <div class="row g-3">
            <div class="col-md-4 text-center">
                <div class="badge text-bg-primary fs-6 p-3">30% Savings</div>
                <div class="text-gasq-muted small mt-2">Return on Investment</div>
            </div>
            <div class="col-md-4 text-center">
                <div class="badge text-bg-primary fs-6 p-3">8.4 Months</div>
                <div class="text-gasq-muted small mt-2">Payback Period</div>
            </div>
            <div class="col-md-4 text-center">
                <div class="badge text-bg-primary fs-6 p-3">$1.42 Saved</div>
                <div class="text-gasq-muted small mt-2">Per Dollar Spent</div>
            </div>
        </div>
    </x-card>
</div>
@endsection


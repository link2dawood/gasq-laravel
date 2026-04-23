@extends('layouts.app')
@section('title', 'Security Calculators')
@section('header_variant', 'dashboard')

@section('content')
@php
    $user = auth()->user();
    $isBuyer = $user?->isBuyer() ?? false;
@endphp
<div class="py-4 px-3 px-md-4" style="background:var(--gasq-background);min-height:calc(100vh - 5rem)">
<div class="container-xl">

    <div class="d-flex align-items-center gap-3 mb-2">
        <div>
            <div class="text-uppercase small fw-semibold text-gasq-muted" style="letter-spacing:.08em">GASQ Tools</div>
            <h1 class="h3 fw-bold mb-0 d-flex align-items-center gap-2">
                <i class="fa fa-calculator text-primary"></i> Security Calculators
            </h1>
        </div>
    </div>
    <p class="text-gasq-muted mb-5">
        {{ $isBuyer
            ? 'Buyer access is centered on the Instant Estimator. Step 1 captures the questionnaire, Step 2 sets up the estimate, and Step 3 unlocks after you choose to post the job or pay the fee.'
            : 'All workforce planning, cost analysis, and pricing tools in one place.' }}
    </p>

    {{-- Featured: Instant Estimator --}}
    <div class="calc-featured-card mb-5">
        <div class="calc-featured-body">
            <div class="calc-featured-icon">
                <i class="fa fa-bolt fa-2x"></i>
            </div>
            <div class="flex-grow-1">
                <div class="text-uppercase small fw-bold mb-1" style="letter-spacing:.08em;opacity:.7">Featured Tool</div>
                <h2 class="fw-bold mb-1 h4">GASQ Instant Estimator</h2>
                <p class="mb-0 opacity-75">
                    {{ $isBuyer
                        ? 'Questionnaire first, estimate setup second, and locked results in Step 3 until you choose Post Job or Pay 1% Fee.'
                        : 'Baseline pay, coverage planning, internal TCO vs outsourced bill rate, and share-ready estimate summaries — all from one screen.' }}
                </p>
            </div>
            <a href="{{ route('instant-estimator.index') }}" class="btn btn-light btn-lg fw-semibold flex-shrink-0">
                <i class="fa fa-bolt me-2"></i>Open Estimator
            </a>
        </div>
    </div>

    @if($isBuyer)
        <div class="mb-5">
            <h5 class="calc-group-label">Buyer Actions</h5>
            <div class="row g-3">
                <div class="col-md-6 col-xl-4">
                    <a href="{{ route('jobs.create') }}" class="calc-card">
                        <div class="calc-card-icon"><i class="fa fa-briefcase"></i></div>
                        <div class="calc-card-body">
                            <div class="calc-card-title">Post a Job</div>
                            <div class="calc-card-desc">Open the buyer questionnaire directly and publish your service request to qualified vendors.</div>
                        </div>
                        <i class="fa fa-arrow-right calc-card-arrow"></i>
                    </a>
                </div>
                <div class="col-md-6 col-xl-4">
                    <a href="{{ route('credits') }}" class="calc-card">
                        <div class="calc-card-icon"><i class="fa fa-credit-card"></i></div>
                        <div class="calc-card-body">
                            <div class="calc-card-title">Buy Credits</div>
                            <div class="calc-card-desc">Top up your account when you want to move from a fast estimate into paid platform actions.</div>
                        </div>
                        <i class="fa fa-arrow-right calc-card-arrow"></i>
                    </a>
                </div>
            </div>
        </div>
    @else
        {{-- Core V24 Tools --}}
        <div class="mb-5">
            <h5 class="calc-group-label">Core V24 Tools</h5>
            <div class="row g-3">
                <div class="col-md-6 col-xl-4">
                    <a href="{{ route('main-menu-calculator.index') }}" class="calc-card">
                        <div class="calc-card-icon"><i class="fa fa-calculator"></i></div>
                        <div class="calc-card-body">
                            <div class="calc-card-title">Main Menu Calculator</div>
                            <div class="calc-card-desc">Security cost, manpower hours, bill rate, and contract summary in one dashboard.</div>
                        </div>
                        <i class="fa fa-arrow-right calc-card-arrow"></i>
                    </a>
                </div>
                <div class="col-md-6 col-xl-4">
                    <a href="{{ route('master-inputs.index') }}" class="calc-card">
                        <div class="calc-card-icon"><i class="fa fa-sliders"></i></div>
                        <div class="calc-card-body">
                            <div class="calc-card-title">Master Inputs</div>
                            <div class="calc-card-desc">Configure shared wage, burden, and overhead rates used across all V24 calculators.</div>
                        </div>
                        <i class="fa fa-arrow-right calc-card-arrow"></i>
                    </a>
                </div>
            </div>
        </div>

        {{-- Contract & Billing --}}
        <div class="mb-5">
            <h5 class="calc-group-label">Contract &amp; Billing</h5>
            <div class="row g-3">
                <div class="col-md-6 col-xl-4">
                    <a href="{{ route('security-billing.index') }}" class="calc-card">
                        <div class="calc-card-icon"><i class="fa fa-file-invoice-dollar"></i></div>
                        <div class="calc-card-body">
                            <div class="calc-card-title">Security Billing</div>
                            <div class="calc-card-desc">Full contract billing breakdown with direct labor, burden, overhead, and profit.</div>
                        </div>
                        <i class="fa fa-arrow-right calc-card-arrow"></i>
                    </a>
                </div>
            </div>
        </div>

        {{-- Rates & Labor --}}
        <div class="mb-5">
        <h5 class="calc-group-label">Rates &amp; Labor</h5>
        <div class="row g-3">
            <div class="col-md-6 col-xl-4">
                <a href="{{ route('economic-justification.index') }}" class="calc-card">
                    <div class="calc-card-icon"><i class="fa fa-chart-line"></i></div>
                    <div class="calc-card-body">
                        <div class="calc-card-title">Economic Justification</div>
                        <div class="calc-card-desc">Side-by-side in-house vs vendor cost analysis with ROI and payback period.</div>
                    </div>
                    <i class="fa fa-arrow-right calc-card-arrow"></i>
                </a>
            </div>
            <div class="col-md-6 col-xl-4">
                <a href="{{ route('budget-calculator.index') }}" class="calc-card">
                    <div class="calc-card-icon"><i class="fa fa-piggy-bank"></i></div>
                    <div class="calc-card-body">
                        <div class="calc-card-title">Budget Calculator</div>
                        <div class="calc-card-desc">Estimate annual security spend based on post coverage and wage assumptions.</div>
                    </div>
                    <i class="fa fa-arrow-right calc-card-arrow"></i>
                </a>
            </div>
        </div>
        </div>

        {{-- Patrol --}}
        <div class="mb-5">
        <h5 class="calc-group-label">Patrol</h5>
        <div class="row g-3">
            <div class="col-md-6 col-xl-4">
                <a href="{{ route('mobile-patrol-calculator') }}" class="calc-card">
                    <div class="calc-card-icon"><i class="fa fa-car"></i></div>
                    <div class="calc-card-body">
                        <div class="calc-card-title">Mobile Patrol Calculator</div>
                        <div class="calc-card-desc">Calculate cost of mobile patrol coverage across multiple sites.</div>
                    </div>
                    <i class="fa fa-arrow-right calc-card-arrow"></i>
                </a>
            </div>
            <div class="col-md-6 col-xl-4">
                <a href="{{ route('mobile-patrol-comparison') }}" class="calc-card">
                    <div class="calc-card-icon"><i class="fa fa-code-compare"></i></div>
                    <div class="calc-card-body">
                        <div class="calc-card-title">Mobile Patrol Comparison</div>
                        <div class="calc-card-desc">Compare dedicated guard vs patrol service cost models.</div>
                    </div>
                    <i class="fa fa-arrow-right calc-card-arrow"></i>
                </a>
            </div>
            <div class="col-md-6 col-xl-4">
                <a href="{{ route('mobile-patrol-hit-calculator.index') }}" class="calc-card">
                    <div class="calc-card-icon"><i class="fa fa-bullseye"></i></div>
                    <div class="calc-card-body">
                        <div class="calc-card-title">Mobile Patrol Hit Calculator</div>
                        <div class="calc-card-desc">Determine cost per check and optimal patrol frequency.</div>
                    </div>
                    <i class="fa fa-arrow-right calc-card-arrow"></i>
                </a>
            </div>
            <div class="col-md-6 col-xl-4">
                <a href="{{ route('mobile-patrol-analysis.index') }}" class="calc-card">
                    <div class="calc-card-icon"><i class="fa fa-route"></i></div>
                    <div class="calc-card-body">
                        <div class="calc-card-title">Mobile Patrol Analysis</div>
                        <div class="calc-card-desc">Full route-based coverage analysis with visit density metrics.</div>
                    </div>
                    <i class="fa fa-arrow-right calc-card-arrow"></i>
                </a>
            </div>
        </div>
        </div>

        {{-- Full TCO Suite --}}
        <div class="mb-5">
        <h5 class="calc-group-label">Full TCO Suite</h5>
        <div class="row g-3">
            <div class="col-md-6 col-xl-4">
                <a href="{{ route('gasq-tco-calculator.index') }}" class="calc-card">
                    <div class="calc-card-icon"><i class="fa fa-table"></i></div>
                    <div class="calc-card-body">
                        <div class="calc-card-title">GASQ TCO Calculator</div>
                        <div class="calc-card-desc">Full total cost of ownership analysis across all labor and overhead components.</div>
                    </div>
                    <i class="fa fa-arrow-right calc-card-arrow"></i>
                </a>
            </div>
            <div class="col-md-6 col-xl-4">
                <a href="{{ route('government-contract-calculator.index') }}" class="calc-card">
                    <div class="calc-card-icon"><i class="fa fa-landmark"></i></div>
                    <div class="calc-card-body">
                        <div class="calc-card-title">Government Contract Calculator</div>
                        <div class="calc-card-desc">SCA/DBA compliant wage and fringe benefit pricing for federal contracts.</div>
                    </div>
                    <i class="fa fa-arrow-right calc-card-arrow"></i>
                </a>
            </div>
        </div>
        </div>

        {{-- Capital Recovery Report --}}
        <div class="mb-5">
        <h5 class="calc-group-label">Capital Recovery Report</h5>
        <div class="row g-3">
            <div class="col-md-6 col-xl-4">
                <a href="{{ route('workforce-appraisal-report.index') }}" class="calc-card">
                    <div class="calc-card-icon"><i class="fa fa-file-invoice"></i></div>
                    <div class="calc-card-body">
                        <div class="calc-card-title">Workforce Appraisal Report</div>
                        <div class="calc-card-desc">Full appraisal across all workforce tabs including labor build-up and cost stack.</div>
                    </div>
                    <i class="fa fa-arrow-right calc-card-arrow"></i>
                </a>
            </div>
            <div class="col-md-6 col-xl-4">
                <a href="{{ route('cfo-bill-rate-breakdown.index') }}" class="calc-card">
                    <div class="calc-card-icon"><i class="fa fa-percent"></i></div>
                    <div class="calc-card-body">
                        <div class="calc-card-title">CFO Bill Rate Breakdown</div>
                        <div class="calc-card-desc">Executive-level bill rate decomposition for finance and procurement review.</div>
                    </div>
                    <i class="fa fa-arrow-right calc-card-arrow"></i>
                </a>
            </div>
            <div class="col-md-6 col-xl-4">
                <a href="{{ route('post-position-summary.index') }}" class="calc-card">
                    <div class="calc-card-icon"><i class="fa fa-user-shield"></i></div>
                    <div class="calc-card-body">
                        <div class="calc-card-title">Post Position Summary</div>
                        <div class="calc-card-desc">Summarize post-by-post coverage, staffing, and cost requirements.</div>
                    </div>
                    <i class="fa fa-arrow-right calc-card-arrow"></i>
                </a>
            </div>
            <div class="col-md-6 col-xl-4">
                <a href="{{ route('appraisal-comparison-summary.index') }}" class="calc-card">
                    <div class="calc-card-icon"><i class="fa fa-balance-scale"></i></div>
                    <div class="calc-card-body">
                        <div class="calc-card-title">Appraisal Comparison Summary</div>
                        <div class="calc-card-desc">Compare multiple workforce appraisal scenarios side-by-side.</div>
                    </div>
                    <i class="fa fa-arrow-right calc-card-arrow"></i>
                </a>
            </div>
            <div class="col-md-6 col-xl-4">
                <a href="{{ route('gasq-direct-labor-build-up.index') }}" class="calc-card">
                    <div class="calc-card-icon"><i class="fa fa-layer-group"></i></div>
                    <div class="calc-card-body">
                        <div class="calc-card-title">Direct Labor Build-Up</div>
                        <div class="calc-card-desc">Layer-by-layer direct labor cost construction from wage through fully loaded rate.</div>
                    </div>
                    <i class="fa fa-arrow-right calc-card-arrow"></i>
                </a>
            </div>
            <div class="col-md-6 col-xl-4">
                <a href="{{ route('gasq-additional-cost-stack.index') }}" class="calc-card">
                    <div class="calc-card-icon"><i class="fa fa-list-ul"></i></div>
                    <div class="calc-card-body">
                        <div class="calc-card-title">Additional Cost Stack</div>
                        <div class="calc-card-desc">Add vehicle, equipment, uniform, and other pass-through costs to the total.</div>
                    </div>
                    <i class="fa fa-arrow-right calc-card-arrow"></i>
                </a>
            </div>
        </div>
        </div>
    @endif

</div>
</div>
@endsection

@push('scripts')
<style>
.calc-featured-card {
    border-radius: 1.5rem;
    background: linear-gradient(135deg, #0f2c63 0%, #123a86 60%, #0e2450 100%);
    color: #fff;
    padding: 2rem;
    box-shadow: 0 24px 48px -20px rgba(6,45,121,.5);
}
.calc-featured-body {
    display: flex;
    align-items: center;
    gap: 1.5rem;
    flex-wrap: wrap;
}
.calc-featured-icon {
    width: 4rem;
    height: 4rem;
    border-radius: 1.25rem;
    background: rgba(255,255,255,.15);
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
}
.calc-group-label {
    text-transform: uppercase;
    letter-spacing: .08em;
    font-size: .75rem;
    font-weight: 800;
    color: var(--gasq-primary, #062d79);
    margin-bottom: 1rem;
    padding-bottom: .5rem;
    border-bottom: 2px solid rgba(6,45,121,.1);
}
.calc-card {
    display: flex;
    align-items: center;
    gap: 1rem;
    padding: 1.1rem 1.2rem;
    border-radius: 1.1rem;
    background: #fff;
    border: 1px solid rgba(6,45,121,.1);
    text-decoration: none;
    color: inherit;
    transition: box-shadow .15s, border-color .15s, transform .1s;
    box-shadow: 0 2px 8px -4px rgba(6,45,121,.12);
}
.calc-card:hover {
    border-color: rgba(6,45,121,.25);
    box-shadow: 0 8px 20px -8px rgba(6,45,121,.25);
    transform: translateY(-1px);
    color: inherit;
    text-decoration: none;
}
.calc-card-icon {
    width: 2.75rem;
    height: 2.75rem;
    border-radius: .85rem;
    background: rgba(6,45,121,.07);
    display: flex;
    align-items: center;
    justify-content: center;
    color: var(--gasq-primary, #062d79);
    flex-shrink: 0;
    font-size: 1rem;
}
.calc-card-body {
    flex: 1;
    min-width: 0;
}
.calc-card-title {
    font-weight: 700;
    font-size: .97rem;
    color: #1a1a2e;
    margin-bottom: .2rem;
}
.calc-card-desc {
    font-size: .82rem;
    color: var(--gasq-muted, #6b7280);
    line-height: 1.45;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}
.calc-card-arrow {
    color: rgba(6,45,121,.3);
    font-size: .85rem;
    flex-shrink: 0;
    transition: color .15s;
}
.calc-card:hover .calc-card-arrow {
    color: var(--gasq-primary, #062d79);
}
@media (max-width: 575.98px) {
    .calc-featured-body { flex-direction: column; align-items: flex-start; }
    .calc-card-desc { white-space: normal; }
}
</style>
@endpush

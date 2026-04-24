@php
    $toggleId = $toggleId ?? 'navbarCalculatorsDropdown';
    $navLinkClass = $navLinkClass ?? 'nav-link text-gasq-muted dropdown-toggle';
    $user = auth()->user();
    $isBuyer = $user?->isBuyer() ?? false;
@endphp
<li class="nav-item dropdown">
    <a class="{{ $navLinkClass }}" href="#" id="{{ $toggleId }}" role="button" data-bs-toggle="dropdown" aria-expanded="false">
        Calculators
    </a>
    <ul class="dropdown-menu dropdown-menu-end py-0 gasq-calculators-nav" aria-labelledby="{{ $toggleId }}" style="max-height: min(70vh, 28rem); overflow-y: auto; min-width: 16.5rem;">
        <li><a class="dropdown-item fw-semibold" href="{{ route('instant-estimator.index') }}"><i class="fa fa-bolt me-2"></i>Instant Estimator</a></li>
        @if($isBuyer)
            <li><a class="dropdown-item" href="{{ route('jobs.create') }}"><i class="fa fa-briefcase me-2"></i>Post a Job</a></li>
            <li><a class="dropdown-item" href="{{ route('calculator.index') }}"><i class="fa fa-window-maximize me-2"></i>Buyer Estimator Hub</a></li>
            <li><hr class="dropdown-divider my-0"></li>
            <li class="px-3 py-3 small text-gasq-muted">Buyers start in the Instant Estimator. The rest of the calculator suite is reserved for vendor access.</li>
        @else
            <li><hr class="dropdown-divider my-0"></li>
            <li><h6 class="dropdown-header small text-uppercase text-gasq-muted mb-0 py-2 px-3">Core (V24 tools)</h6></li>
            <li><a class="dropdown-item" href="{{ route('main-menu-calculator.index') }}"><i class="fa fa-calculator me-2"></i>Main Menu Calculator</a></li>
            <li><a class="dropdown-item" href="{{ route('master-inputs.index') }}"><i class="fa fa-sliders me-2"></i>Master Inputs</a></li>

            <li><hr class="dropdown-divider my-0"></li>
            <li><h6 class="dropdown-header small text-uppercase text-gasq-muted mb-0 py-2 px-3">Contract &amp; billing</h6></li>
            <li><a class="dropdown-item" href="{{ route('security-billing.index') }}"><i class="fa fa-file-invoice-dollar me-2"></i>Security Billing</a></li>

            <li><hr class="dropdown-divider my-0"></li>
            <li><h6 class="dropdown-header small text-uppercase text-gasq-muted mb-0 py-2 px-3">Rates &amp; labor</h6></li>
            <li><a class="dropdown-item" href="{{ route('economic-justification.index') }}"><i class="fa fa-chart-line me-2"></i>Economic Justification</a></li>
            <li><a class="dropdown-item" href="{{ route('budget-calculator.index') }}"><i class="fa fa-piggy-bank me-2"></i>Budget Calculator</a></li>

            <li><hr class="dropdown-divider my-0"></li>
            <li><h6 class="dropdown-header small text-uppercase text-gasq-muted mb-0 py-2 px-3">Patrol</h6></li>
            <li><a class="dropdown-item" href="{{ route('mobile-patrol-calculator') }}"><i class="fa fa-car me-2"></i>Mobile Patrol Calculator</a></li>
            <li><a class="dropdown-item" href="{{ route('mobile-patrol-hit-calculator.index') }}"><i class="fa fa-bullseye me-2"></i>Mobile Patrol Hit Calculator</a></li>

            <li><hr class="dropdown-divider my-0"></li>
            <li><h6 class="dropdown-header small text-uppercase text-gasq-muted mb-0 py-2 px-3">Full TCO suite (app)</h6></li>
            <li><a class="dropdown-item" href="{{ route('calculator.index') }}"><i class="fa fa-window-maximize me-2"></i>Calculator (full app)</a></li>
            <li><a class="dropdown-item" href="{{ route('government-contract-calculator.index') }}"><i class="fa fa-landmark me-2"></i>Government Contract Calculator</a></li>

            <li><hr class="dropdown-divider my-0"></li>
            <li><h6 class="dropdown-header small text-uppercase text-gasq-muted mb-0 py-2 px-3">Capital recovery report</h6></li>
            <li><a class="dropdown-item" href="{{ route('workforce-appraisal-report.index') }}"><i class="fa fa-file-invoice me-2"></i>Workforce Appraisal (all tabs)</a></li>
            <li><a class="dropdown-item" href="{{ route('post-position-summary.index') }}"><i class="fa fa-user-shield me-2"></i>Post Position Summary</a></li>
            <li><a class="dropdown-item" href="{{ route('appraisal-comparison-summary.index') }}"><i class="fa fa-balance-scale me-2"></i>Appraisal Comparison Summary</a></li>
            <li><a class="dropdown-item" href="{{ route('gasq-direct-labor-build-up.index') }}"><i class="fa fa-layer-group me-2"></i>Direct Labor Build-Up</a></li>
            <li><a class="dropdown-item" href="{{ route('gasq-additional-cost-stack.index') }}"><i class="fa fa-list-ul me-2"></i>Additional Cost Stack</a></li>

            <li><hr class="dropdown-divider my-0"></li>
            <li><h6 class="dropdown-header small text-uppercase text-gasq-muted mb-0 py-2 px-3">Services &amp; pricing</h6></li>
            <li class="px-3 py-3 small text-gasq-muted">Additional service/pricing tools are currently hidden.</li>
        @endif
    </ul>
</li>

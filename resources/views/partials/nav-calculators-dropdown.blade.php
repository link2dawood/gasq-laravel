@php
    $toggleId = $toggleId ?? 'navbarCalculatorsDropdown';
    $navLinkClass = $navLinkClass ?? 'nav-link text-gasq-muted dropdown-toggle';
@endphp
<li class="nav-item dropdown">
    <a class="{{ $navLinkClass }}" href="#" id="{{ $toggleId }}" role="button" data-bs-toggle="dropdown" aria-expanded="false">
        Calculators
    </a>
    <ul class="dropdown-menu dropdown-menu-end py-0 gasq-calculators-nav" aria-labelledby="{{ $toggleId }}" style="max-height: min(70vh, 28rem); overflow-y: auto; min-width: 16.5rem;">
        <li><h6 class="dropdown-header small text-uppercase text-gasq-muted mb-0 py-2 px-3">Core (V24 tools)</h6></li>
        <li><a class="dropdown-item" href="{{ route('main-menu-calculator.index') }}"><i class="fa fa-calculator me-2"></i>Main Menu Calculator</a></li>
        <li><a class="dropdown-item" href="{{ route('gasq-instant-estimator.index') }}"><i class="fa fa-bolt me-2"></i>Instant Estimator</a></li>

        <li><hr class="dropdown-divider my-0"></li>
        <li><h6 class="dropdown-header small text-uppercase text-gasq-muted mb-0 py-2 px-3">Contract &amp; billing</h6></li>
        <li><a class="dropdown-item" href="{{ route('contract-analysis.index') }}"><i class="fa fa-file-contract me-2"></i>Contract Analysis</a></li>
        <li><a class="dropdown-item" href="{{ route('security-billing.index') }}"><i class="fa fa-file-invoice-dollar me-2"></i>Security Billing</a></li>

        <li><hr class="dropdown-divider my-0"></li>
        <li><h6 class="dropdown-header small text-uppercase text-gasq-muted mb-0 py-2 px-3">Rates &amp; labor</h6></li>
        <li><a class="dropdown-item" href="{{ route('bill-rate-analysis.index') }}"><i class="fa fa-dollar-sign me-2"></i>Bill Rate Analysis</a></li>
        <li><a class="dropdown-item" href="{{ route('cost-analysis.index') }}"><i class="fa fa-chart-bar me-2"></i>Cost Analysis</a></li>
        <li><a class="dropdown-item" href="{{ route('manpower-hours.index') }}"><i class="fa fa-users me-2"></i>Manpower Hours</a></li>
        <li><a class="dropdown-item" href="{{ route('economic-justification.index') }}"><i class="fa fa-chart-line me-2"></i>Economic Justification</a></li>
        <li><a class="dropdown-item" href="{{ route('hourly-pay-calculator.index') }}"><i class="fa fa-clock me-2"></i>Hourly Pay Calculator</a></li>
        <li><a class="dropdown-item" href="{{ route('budget-calculator.index') }}"><i class="fa fa-piggy-bank me-2"></i>Budget Calculator</a></li>

        <li><hr class="dropdown-divider my-0"></li>
        <li><h6 class="dropdown-header small text-uppercase text-gasq-muted mb-0 py-2 px-3">Patrol</h6></li>
        <li><a class="dropdown-item" href="{{ route('mobile-patrol-calculator') }}"><i class="fa fa-car me-2"></i>Mobile Patrol Calculator</a></li>
        <li><a class="dropdown-item" href="{{ route('mobile-patrol-comparison') }}"><i class="fa fa-code-compare me-2"></i>Mobile Patrol Comparison</a></li>
        <li><a class="dropdown-item" href="{{ route('mobile-patrol-analysis.index') }}"><i class="fa fa-route me-2"></i>Mobile Patrol Analysis</a></li>

        <li><hr class="dropdown-divider my-0"></li>
        <li><h6 class="dropdown-header small text-uppercase text-gasq-muted mb-0 py-2 px-3">Full TCO suite (app)</h6></li>
        <li><a class="dropdown-item" href="{{ route('gasq-tco-calculator.index') }}"><i class="fa fa-table me-2"></i>GASQ TCO Calculator</a></li>
        <li><a class="dropdown-item" href="{{ route('calculator.index') }}"><i class="fa fa-window-maximize me-2"></i>Calculator (full app)</a></li>
        <li><a class="dropdown-item" href="{{ route('absorbed-rate-calculator.index') }}"><i class="fa fa-percent me-2"></i>Absorbed Rate Calculator</a></li>
        <li><a class="dropdown-item" href="{{ route('government-contract-calculator.index') }}"><i class="fa fa-landmark me-2"></i>Government Contract Calculator</a></li>
        <li><a class="dropdown-item" href="{{ route('keeps-doors-open-calculator.index') }}"><i class="fa fa-door-open me-2"></i>Keeps Doors Open Calculator</a></li>

        <li><hr class="dropdown-divider my-0"></li>
        <li><h6 class="dropdown-header small text-uppercase text-gasq-muted mb-0 py-2 px-3">Capital recovery report</h6></li>
        <li><a class="dropdown-item" href="{{ route('workforce-appraisal-report.index') }}"><i class="fa fa-file-invoice me-2"></i>Workforce Appraisal (all tabs)</a></li>
        <li><a class="dropdown-item" href="{{ route('cfo-bill-rate-breakdown.index') }}"><i class="fa fa-percent me-2"></i>CFO Bill Rate Breakdown</a></li>
        <li><a class="dropdown-item" href="{{ route('post-position-summary.index') }}"><i class="fa fa-user-shield me-2"></i>Post Position Summary</a></li>
        <li><a class="dropdown-item" href="{{ route('appraisal-comparison-summary.index') }}"><i class="fa fa-balance-scale me-2"></i>Appraisal Comparison Summary</a></li>

        <li><hr class="dropdown-divider my-0"></li>
        <li><h6 class="dropdown-header small text-uppercase text-gasq-muted mb-0 py-2 px-3">Services &amp; pricing</h6></li>
        <li><a class="dropdown-item" href="{{ route('unarmed-security-guard-services.index') }}"><i class="fa fa-shield-halved me-2"></i>Unarmed Guard Services</a></li>
        <li><a class="dropdown-item" href="{{ route('security-quote.index') }}"><i class="fa fa-file-signature me-2"></i>Security Quote</a></li>
        <li><a class="dropdown-item" href="{{ route('global-security-pricing.index') }}"><i class="fa fa-earth-americas me-2"></i>Global Security Pricing</a></li>
    </ul>
</li>

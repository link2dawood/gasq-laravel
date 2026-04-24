@extends('layouts.app')

@section('header_variant', 'dashboard')

@section('title', 'Calculator Directory')

@section('content')
@php
    $calculatorGroups = [
        [
            'title' => 'Core Workspace',
            'subtitle' => 'Shared setup and primary calculator entry points.',
            'items' => [
                [
                    'title' => 'Master Inputs',
                    'subtitle' => 'Shared settings',
                    'description' => 'Edit the master numeric drivers used across the calculator suite.',
                    'href' => route('master-inputs.index'),
                    'access' => 'Sign in required',
                ],
                [
                    'title' => 'Main Menu Calculator',
                    'subtitle' => 'Main entry',
                    'description' => 'Open the central calculator workspace and navigate to the primary flows.',
                    'href' => route('main-menu-calculator.index'),
                    'access' => 'Protected',
                ],
                [
                    'title' => 'Budget Calculator',
                    'subtitle' => 'Budget planning',
                    'description' => 'Estimate annual, monthly, and weekly budget coverage with live inputs.',
                    'href' => route('budget-calculator.index'),
                    'access' => 'Protected',
                ],
                [
                    'title' => 'Buyer Fit Index',
                    'subtitle' => 'Fit scoring',
                    'description' => 'Review buyer-fit scoring inputs and calculator outputs in one place.',
                    'href' => route('buyer-fit-index.index'),
                    'access' => 'Protected',
                ],
            ],
        ],
        [
            'title' => 'Contract And Billing',
            'subtitle' => 'Billing, pricing, and justification tools.',
            'items' => [
                [
                    'title' => 'Security Billing',
                    'subtitle' => 'Billing scenarios',
                    'description' => 'Build security billing scenarios and review live pricing outputs.',
                    'href' => route('security-billing.index'),
                    'access' => 'Protected',
                ],
                [
                    'title' => 'Bill Rate Analysis',
                    'subtitle' => 'Rate builder',
                    'description' => 'Analyze bill rates with quick and component-based calculation tabs.',
                    'href' => route('bill-rate-analysis.index'),
                    'access' => 'Protected',
                ],
                [
                    'title' => 'Economic Justification',
                    'subtitle' => 'ROI analysis',
                    'description' => 'Compare in-house and vendor models with savings and ROI summaries.',
                    'href' => route('economic-justification.index'),
                    'access' => 'Protected',
                ],
                [
                    'title' => 'Government Contract Calculator',
                    'subtitle' => 'Public page',
                    'description' => 'Estimate loaded government contract rates from labor, burden, and fee.',
                    'href' => route('government-contract-calculator.index'),
                    'access' => 'Public',
                ],
            ],
        ],
        [
            'title' => 'Mobile Patrol',
            'subtitle' => 'Patrol pricing, comparison, and fleet analysis.',
            'items' => [
                [
                    'title' => 'Mobile Patrol Calculator',
                    'subtitle' => 'Patrol pricing',
                    'description' => 'Calculate patrol pricing with shared operating and labor inputs.',
                    'href' => route('mobile-patrol-calculator'),
                    'access' => 'Protected',
                ],
                [
                    'title' => 'Mobile Patrol Hit Calculator',
                    'subtitle' => 'Per-hit model',
                    'description' => 'Estimate patrol hit pricing, unit economics, and billable impact.',
                    'href' => route('mobile-patrol-hit-calculator.index'),
                    'access' => 'Protected',
                ],
            ],
        ],
        [
            'title' => 'Workforce Appraisal Suite',
            'subtitle' => 'CFO, scope-of-work, appraisal, and labor stack views.',
            'items' => [
                [
                    'title' => 'Workforce Appraisal Report',
                    'subtitle' => 'All tabs',
                    'description' => 'Open the complete workforce appraisal workspace with every report tab.',
                    'href' => route('workforce-appraisal-report.index'),
                    'access' => 'Protected',
                ],
                [
                    'title' => 'Post Position Summary',
                    'subtitle' => 'Scope of Work',
                    'description' => 'Open the scope-of-work and post-position summary tab directly.',
                    'href' => route('post-position-summary.index'),
                    'access' => 'Protected',
                ],
                [
                    'title' => 'Appraisal Comparison Summary',
                    'subtitle' => 'Comparison tab',
                    'description' => 'Jump directly to the appraisal comparison workspace.',
                    'href' => route('appraisal-comparison-summary.index'),
                    'access' => 'Protected',
                ],
                [
                    'title' => 'GASQ Direct Labor Build-Up',
                    'subtitle' => 'Labor stack',
                    'description' => 'View and edit the direct labor build-up calculator workspace.',
                    'href' => route('gasq-direct-labor-build-up.index'),
                    'access' => 'Protected',
                ],
                [
                    'title' => 'GASQ Additional Cost Stack',
                    'subtitle' => 'Support stack',
                    'description' => 'Review additional cost modules and annualized totals.',
                    'href' => route('gasq-additional-cost-stack.index'),
                    'access' => 'Protected',
                ],
            ],
        ],
        [
            'title' => 'Services And Pricing',
            'subtitle' => 'Related service and marketplace pricing pages.',
            'items' => [
                [
                    'title' => 'Open Bid Offer',
                    'subtitle' => 'Offer page',
                    'description' => 'Open the bid-offer pricing page.',
                    'href' => route('open-bid-offer.index'),
                    'access' => 'Public',
                ],
                [
                    'title' => 'Post Job',
                    'subtitle' => 'Job entry',
                    'description' => 'Open the job posting page tied to the marketplace workflow.',
                    'href' => route('post-job.index'),
                    'access' => 'Public',
                ],
                [
                    'title' => 'Post Coverage Schedule',
                    'subtitle' => 'Coverage planning',
                    'description' => 'Open the schedule-planning page for coverage requirements.',
                    'href' => route('post-coverage-schedule'),
                    'access' => 'Public',
                ],
            ],
        ],
    ];
@endphp

<div class="container py-5">
    <div class="mx-auto mb-5 text-center" style="max-width: 920px;">
        <div class="d-inline-flex align-items-center gap-2 px-4 py-2 rounded-pill bg-secondary text-white mb-4">
            <i class="fa fa-calculator"></i>
            <span>Calculator directory</span>
        </div>
        <h1 class="display-4 fw-bold mb-3">All Calculators</h1>
        <p class="lead text-gasq-muted mb-3">
            Browse every calculator and calculator-related workspace from one page. Each card below opens the matching tool directly.
        </p>
        <p class="small text-gasq-muted mb-0">
            Some tools are public. Protected tools may require sign in, phone verification, credits, a posted buyer job, and completed master inputs.
        </p>
    </div>

    @foreach ($calculatorGroups as $group)
        <section class="mb-5">
            <div class="d-flex flex-column flex-lg-row align-items-lg-end justify-content-between gap-2 mb-3">
                <div>
                    <h2 class="h4 fw-bold mb-1">{{ $group['title'] }}</h2>
                    <p class="text-gasq-muted mb-0">{{ $group['subtitle'] }}</p>
                </div>
                <div class="small text-gasq-muted">{{ count($group['items']) }} links</div>
            </div>

            <div class="row g-4">
                @foreach ($group['items'] as $item)
                    <div class="col-md-6 col-xl-4">
                        <x-card class="h-100 shadow-sm border-0">
                            <div class="d-flex align-items-start justify-content-between gap-3 mb-3">
                                <div>
                                    <h3 class="h5 fw-semibold mb-1">{{ $item['title'] }}</h3>
                                    <p class="small text-gasq-muted mb-0">{{ $item['subtitle'] }}</p>
                                </div>
                                <span class="badge rounded-pill text-bg-light border">{{ $item['access'] }}</span>
                            </div>

                            <p class="text-gasq-muted small mb-4">{{ $item['description'] }}</p>

                            <a class="btn btn-primary" href="{{ $item['href'] }}">
                                Open calculator
                            </a>
                        </x-card>
                    </div>
                @endforeach
            </div>
        </section>
    @endforeach
</div>
@endsection

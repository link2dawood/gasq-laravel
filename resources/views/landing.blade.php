@extends('layouts.app')

@section('title', 'GASQ — The Smarter Way to Buy & Sell Security Services')

@section('content')
<div class="min-vh-100">
    {{-- Hero – py-20, gradient, title/lead scale --}}
    <section class="gasq-hero-bg">
        <div class="container text-center px-4">
            <h1 class="gasq-hero-title mb-4">The Smarter Way to Buy & Sell Security Services</h1>
            <p class="gasq-hero-lead lead mb-5 mx-auto">Transparent. Fair. ROI-Driven. We connect buyers and security vendors through a proven process that saves time, money, and frustration.</p>
            <div class="d-flex flex-column flex-md-row gap-3 justify-content-center align-items-center mb-5">
                @auth
                    <a href="{{ route('job-board') }}" class="btn btn-primary btn-lg px-4 py-3 shadow">
                        <i class="fa fa-briefcase me-2"></i>View Available Jobs
                    </a>
                    <a href="{{ url('/gasq-instant-estimator') }}" class="btn btn-outline-primary btn-lg px-4 py-3">
                        <i class="fa fa-calculator me-2"></i>Calculate Your Savings
                    </a>
                @else
                    <a href="{{ route('register') }}" class="btn btn-primary btn-lg px-4 py-3 shadow">
                        <i class="fa fa-lightbulb me-2"></i>Know Before You Buy
                    </a>
                    <a href="{{ route('register') }}" class="btn btn-outline-primary btn-lg px-4 py-3">
                        <i class="fa fa-shield-alt me-2"></i>Join the Vendor Network
                    </a>
                @endauth
            </div>
            <div class="row g-4 justify-content-center mt-5 mx-auto" style="max-width: 72rem;">
                <div class="col-md-6">
                    <div class="gasq-card card h-100 p-4 p-lg-5 text-center">
                        <i class="fa fa-handshake text-primary fa-3x mb-3"></i>
                        <h3 class="gasq-card-title-lg mb-2">Buyer Confidence</h3>
                        <p class="text-gasq-muted mb-0">Make informed security decisions</p>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="gasq-card card h-100 p-4 p-lg-5 text-center">
                        <i class="fa fa-shield-alt text-primary fa-3x mb-3"></i>
                        <h3 class="gasq-card-title-lg mb-2">Quality Protection</h3>
                        <p class="text-gasq-muted mb-0">Professional security services</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    {{-- For Buyers – py-20, gasq-section --}}
    <section id="buyers" class="gasq-section">
        <div class="container px-4">
            <div class="text-center mb-5">
                <h2 class="gasq-section-title mb-4">Security Services Made Simple & Cost-Effective</h2>
            </div>
            <div class="row g-4 mb-5">
                <div class="col-lg-6">
                    <div class="gasq-card card p-4 p-lg-5">
                        <h3 class="card-title gasq-card-title-lg mb-4">Top Concerns We Solve:</h3>
                        <ul class="gasq-list-check mb-0">
                            <li>Avoid adding employees, payroll & insurance burdens</li>
                            <li>Make confident outsourcing decisions with clear data</li>
                            <li>Pay fair service fees while ensuring quality protection</li>
                            <li>Address urgent safety/security issues</li>
                            <li>Switch from underperforming vendors</li>
                        </ul>
                    </div>
                </div>
                <div class="col-lg-6">
                    <div class="gasq-card card p-4 p-lg-5">
                        <h3 class="card-title gasq-card-title-lg mb-4">Our Process in 3 Steps:</h3>
                        <div class="d-flex align-items-start gap-3 mb-3">
                            <span class="gasq-step-num flex-shrink-0">1</span>
                            <div>
                                <h4 class="h6 fw-semibold mb-1">Tell Us Your Needs</h4>
                                <p class="text-gasq-muted small mb-0">Fill out our quick RFQ questionnaire.</p>
                            </div>
                        </div>
                        <div class="d-flex align-items-start gap-3 mb-3">
                            <span class="gasq-step-num flex-shrink-0">2</span>
                            <div>
                                <h4 class="h6 fw-semibold mb-1">We Qualify Vendors for You</h4>
                                <p class="text-gasq-muted small mb-0">Maximum of 5 pre-screened providers.</p>
                            </div>
                        </div>
                        <div class="d-flex align-items-start gap-3">
                            <span class="gasq-step-num flex-shrink-0">3</span>
                            <div>
                                <h4 class="h6 fw-semibold mb-1">Hire with Confidence</h4>
                                <p class="text-gasq-muted small mb-0">Interview, select, and sign—stress free.</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            @auth
                <div class="gasq-cta-box rounded-3 p-4 p-lg-5 mb-5">
                    <p class="small fw-medium text-primary text-center mb-4">Ready to find your next security opportunity?</p>
                    <div class="row g-4">
                        <div class="col-md-6">
                            <a href="{{ route('job-board') }}" class="btn btn-primary w-100 py-3 shadow">
                                <i class="fa fa-briefcase me-2"></i>View Available Jobs
                            </a>
                            <p class="small text-gasq-muted mt-2 mb-0">Browse active security job postings and submit competitive bids</p>
                        </div>
                        <div class="col-md-6">
                            <a href="{{ url('/gasq-instant-estimator') }}" class="btn btn-outline-primary w-100 py-3">
                                <i class="fa fa-calculator me-2"></i>Calculate Your Savings
                            </a>
                            <p class="small text-gasq-muted mt-2 mb-0">Compare costs between in-house vs outsourced security</p>
                        </div>
                    </div>
                </div>
            @else
                <div class="gasq-cta-box-guest rounded-3 p-4 p-lg-5 mb-5 text-center">
                    <p class="small fw-medium mb-3">Join our security marketplace</p>
                    <a href="{{ route('register') }}" class="btn btn-primary py-3 px-4 shadow">
                        <i class="fa fa-user-check me-2"></i>Sign Up to View Jobs
                    </a>
                    <p class="small text-gasq-muted mt-3 mb-0">Create your account to access exclusive job opportunities</p>
                </div>
            @endauth
            <div class="gasq-card card p-4 p-lg-5 mb-5">
                <h3 class="card-title gasq-card-title-lg mb-4"><i class="fa fa-chart-bar text-primary me-2"></i>Why Buyers Choose GASQ:</h3>
                <div class="row g-4 text-center">
                    <div class="col-6 col-md-3">
                        <i class="fa fa-chart-line text-primary fa-2x mb-2"></i>
                        <h4 class="h6 fw-semibold mb-1">Free ROI & cost-benefit analysis</h4>
                    </div>
                    <div class="col-6 col-md-3">
                        <i class="fa fa-bullseye text-primary fa-2x mb-2"></i>
                        <h4 class="h6 fw-semibold mb-1">Industry benchmarked pricing</h4>
                        <p class="small text-gasq-muted mb-0">(in-house vs. outsource)</p>
                    </div>
                    <div class="col-6 col-md-3">
                        <i class="fa fa-award text-primary fa-2x mb-2"></i>
                        <h4 class="h6 fw-semibold mb-1">Vendor replacement guarantee</h4>
                    </div>
                    <div class="col-6 col-md-3">
                        <i class="fa fa-user-check text-primary fa-2x mb-2"></i>
                        <h4 class="h6 fw-semibold mb-1">Open post assurance & follow-up</h4>
                    </div>
                </div>
            </div>
            <div class="text-center">
                <a href="{{ url('/contract-analysis') }}" class="btn btn-primary btn-lg">Start My Free Analysis →</a>
            </div>
        </div>
    </section>

    {{-- For Sellers – gasq-section-muted --}}
    <section id="sellers" class="gasq-section-muted">
        <div class="container px-4">
            <div class="text-center mb-5">
                <h2 class="gasq-section-title mb-4">Level the Playing Field. Stop Competing on Price Alone.</h2>
            </div>
            <div class="row g-4 mb-5">
                <div class="col-lg-6">
                    <div class="gasq-card card p-4 p-lg-5">
                        <h3 class="card-title gasq-card-title-lg mb-4">Top Vendor Frustrations We Fix:</h3>
                        <ul class="gasq-list-check mb-0">
                            <li>Predatory underbidding in the industry</li>
                            <li>Prospects shopping quotes to competitors</li>
                            <li>Low officer pay-to-bill ratios</li>
                            <li>"Sweatshop" labor mentality driving down quality</li>
                            <li>Being judged on lowest cost, not qualifications</li>
                        </ul>
                    </div>
                </div>
                <div class="col-lg-6">
                    <div class="gasq-card card p-4 p-lg-5">
                        <h3 class="card-title gasq-card-title-lg mb-4">Why Join the GASQ Network:</h3>
                        <ul class="gasq-list-check mb-0">
                            <li>Over 90% of total contract value goes to the vendor</li>
                            <li>Officers earn 55–65% of bill rate</li>
                            <li>Prequalified buyer inquiries only (no wasted leads)</li>
                            <li>Reduced sales, marketing, and operational costs</li>
                            <li>More face-to-face time with serious buyers</li>
                        </ul>
                    </div>
                </div>
            </div>
            <div class="text-center">
                <a href="{{ route('register') }}" class="btn btn-primary btn-lg">Join the Vendor Network →</a>
            </div>
        </div>
    </section>

    {{-- How It Works / Transparency – gasq-section --}}
    <section id="how-it-works" class="gasq-section">
        <div class="container px-4">
            <div class="text-center mb-5">
                <h2 class="gasq-section-title mb-4">Transparency is Our Guarantee</h2>
                <div class="mx-auto" style="max-width: 42rem;">
                    <p class="text-gasq-muted mb-2">We eliminate vendor underbidding & protect fair compensation.</p>
                    <p class="text-gasq-muted mb-2">Buyers see true costs vs. in-house benchmarks before committing.</p>
                    <p class="text-gasq-muted mb-2">Sellers gain contracts at rates that respect officer pay & company value.</p>
                    <p class="text-gasq-muted mb-0">Both sides save time, money, and resources.</p>
                </div>
            </div>
            <div class="gasq-card card p-4 p-lg-5 mx-auto" style="max-width: 60rem;">
                <h3 class="card-title gasq-card-title-lg text-center mb-4">Process Comparison</h3>
                <div class="row g-4">
                    <div class="col-md-6">
                        <h4 class="h5 fw-semibold text-center text-gasq-destructive mb-3">Traditional RFP Process</h4>
                        <ul class="gasq-list-dot mb-0 text-gasq-destructive">
                            <li>Lengthy vendor search and vetting process</li>
                            <li>Price-focused competition leading to poor quality</li>
                            <li>No transparency in pricing or wage standards</li>
                            <li>High vendor acquisition costs</li>
                            <li>Limited post-contract support</li>
                        </ul>
                    </div>
                    <div class="col-md-6">
                        <h4 class="h5 fw-semibold text-center text-gasq-success mb-3">GASQ Marketplace Process</h4>
                        <ul class="gasq-list-check mb-0">
                            <li>Pre-qualified vendor network (max 5 providers)</li>
                            <li>Quality and value-based selection criteria</li>
                            <li>Full cost transparency and ROI analysis</li>
                            <li>Reduced sales cycles and costs</li>
                            <li>Ongoing support and performance monitoring</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </section>

    {{-- ROI Guarantee section --}}
    <section class="gasq-roi-section">
        <div class="container px-4">
            <div class="gasq-card card gasq-roi-card p-4 p-lg-5 mx-auto" style="max-width: 56rem;">
                <div class="text-center">
                    <h2 class="gasq-section-title text-primary mb-3">Your 1% = 100% ROI Guarantee</h2>
                    <p class="text-gasq-muted mb-4" style="font-size: 1.125rem;">At GetASecurityQuoteNow, we've reimagined the appraisal fee.</p>
                </div>
                <div class="mb-4" style="max-width: 48rem; margin-left: auto; margin-right: auto;">
                    <p class="mb-2">Instead of being an expense, our <strong class="text-primary">1% appraisal fee works like a down payment:</strong></p>
                    <p class="mb-2">Just like putting money down on a home, it secures your place in the process.</p>
                    <p class="mb-2">Unlike a mortgage, you get an <strong class="text-primary">instant 100% return</strong> when you choose a vendor through our network.</p>
                    <p class="mb-0">Every dollar is credited back—proving our role as the pricing referee that protects your budget.</p>
                </div>
                <div class="mb-4">
                    <p class="fw-semibold mb-3">With us, you don't just "get a quote." You get:</p>
                    <ul class="gasq-list-check mb-0">
                        <li>Transparent pricing backed by risk assessments</li>
                        <li>Fair wages and vendor accountability</li>
                        <li>The option to reveal your budget or request sealed bids after vendor interviews</li>
                        <li>Service guarantees, including our Vendor Replacement Guarantee</li>
                    </ul>
                </div>
                <div class="text-center">
                    <p class="fw-semibold mb-3">Ready to turn 1% into 100%?</p>
                    <div class="d-flex flex-column flex-sm-row gap-3 justify-content-center">
                        <a href="{{ route('jobs.create') }}" class="btn btn-primary btn-lg">Post Your Job Offer Now</a>
                        <a href="{{ route('discovery-call.index') }}" class="btn btn-outline-primary btn-lg">Schedule a Discovery Call</a>
                    </div>
                </div>
            </div>
        </div>
    </section>

    {{-- Social proof – gasq-section-muted --}}
    <section class="gasq-section-muted">
        <div class="container px-4 text-center">
            <h2 class="gasq-section-title mb-5">Trusted by Security Professionals</h2>
            <div class="row g-4 mb-5">
                <div class="col-md-4">
                    <div class="gasq-card card p-4 text-center">
                        <div class="fs-2 fw-bold text-primary mb-2">350+</div>
                        <p class="h6 fw-semibold mb-1">Prequalified Vendors</p>
                        <p class="text-gasq-muted small mb-0">Nationwide Network</p>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="gasq-card card p-4 text-center">
                        <div class="mb-2">
                            @for($i = 1; $i <= 5; $i++) <i class="fa fa-star gasq-star"></i> @endfor
                        </div>
                        <p class="h6 fw-semibold mb-1">5-Star Platform</p>
                        <p class="text-gasq-muted small mb-0">Rated by Users</p>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="gasq-card card p-4 text-center">
                        <div class="fs-2 fw-bold text-primary mb-2">90%</div>
                        <p class="h6 fw-semibold mb-1">Value to Vendors</p>
                        <p class="text-gasq-muted small mb-0">Contract Value Retained</p>
                    </div>
                </div>
            </div>
            <div class="gasq-card card p-5 mx-auto mb-4" style="max-width: 42rem;">
                <div class="mb-3">
                    @for($i = 1; $i <= 5; $i++) <i class="fa fa-star gasq-star fa-lg"></i> @endfor
                </div>
                <blockquote class="fst-italic mb-3">"GASQ transformed how we approach security procurement. The transparency and quality of vendors has exceeded our expectations, and the cost savings were significant."</blockquote>
                <p class="fw-semibold mb-0">– Property Manager, Fortune 500 Company</p>
            </div>
            <p class="text-gasq-muted">Trusted by property managers, schools, utilities, and government agencies</p>
        </div>
    </section>

    {{-- Final CTA – gasq-cta-bg --}}
    <section class="gasq-cta-bg">
        <div class="container px-4 text-center">
            <h2 class="gasq-section-title mb-5">Ready to Get Started?</h2>
            <div class="row g-4 justify-content-center">
                <div class="col-md-6">
                    <div class="gasq-card card p-4 p-lg-5 h-100">
                        <h3 class="gasq-card-title-lg mb-2">For Buyers</h3>
                        <p class="text-gasq-muted mb-4">Get a comprehensive cost-benefit analysis and connect with qualified vendors</p>
                        <a href="{{ url('/contract-analysis') }}" class="btn btn-primary w-100 py-3">Get Your Free Cost-Benefit Analysis →</a>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="gasq-card card p-4 p-lg-5 h-100">
                        <h3 class="gasq-card-title-lg mb-2">For Vendors</h3>
                        <p class="text-gasq-muted mb-4">Join our network and access qualified leads with fair compensation</p>
                        <a href="{{ route('register') }}" class="btn btn-outline-primary w-100 py-3">Join the Vendor Network →</a>
                    </div>
                </div>
            </div>
        </div>
    </section>
</div>

@section('footer')
<footer class="gasq-footer py-5">
    <div class="container px-4">
        <div class="row g-4 mb-4">
            <div class="col-md-3">
                <a href="{{ url('/') }}" class="d-inline-block mb-2">
                    <x-logo height="32" />
                </a>
                <p class="small text-gasq-muted mb-3">GetASecurityQuoteNow – Security Services. Simplified.</p>
                <p class="small mb-1">📞 1-800-GASQ-NOW</p>
                <p class="small mb-0">✉️ info@gasq.com</p>
            </div>
            <div class="col-md-3">
                <h4 class="h6 fw-semibold mb-3">For Buyers</h4>
                <ul class="list-unstyled small">
                    <li><a href="{{ url('/contract-analysis') }}" class="text-gasq-muted text-decoration-none">Cost-Benefit Analysis</a></li>
                    <li><a href="{{ route('jobs.create') }}" class="text-gasq-muted text-decoration-none">Post RFQ</a></li>
                    <li><a href="{{ route('register') }}" class="text-gasq-muted text-decoration-none">Register as Buyer</a></li>
                </ul>
            </div>
            <div class="col-md-3">
                <h4 class="h6 fw-semibold mb-3">For Vendors</h4>
                <ul class="list-unstyled small">
                    <li><a href="{{ route('register') }}" class="text-gasq-muted text-decoration-none">Join Network</a></li>
                    <li><a href="{{ route('job-board') }}" class="text-gasq-muted text-decoration-none">Browse Jobs</a></li>
                    <li><a href="{{ route('login') }}" class="text-gasq-muted text-decoration-none">Vendor Login</a></li>
                </ul>
            </div>
            <div class="col-md-3">
                <h4 class="h6 fw-semibold mb-3">Company</h4>
                <ul class="list-unstyled small">
                    <li><a href="{{ url('/#how-it-works') }}" class="text-gasq-muted text-decoration-none">How It Works</a></li>
                    <li><a href="{{ route('login') }}" class="text-gasq-muted text-decoration-none">Buyer Login</a></li>
                </ul>
            </div>
        </div>
        <div class="border-top border-gasq pt-4 text-center small text-gasq-muted">
            <p class="mb-0">&copy; {{ date('Y') }} GetASecurityQuoteNow (GASQ). All rights reserved. | Security Services. Simplified.</p>
        </div>
    </div>
</footer>
@endsection
@endsection

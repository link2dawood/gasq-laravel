@extends('layouts.app')

@section('title', 'Why GASQ Works')

@section('content')
<div class="container py-5" style="max-width: 860px;">
    <h1 class="fw-bold mb-3">Why GASQ Works</h1>

    <p>The problem isn't that buyers don't receive security quotes. The problem is that buyers have no independent way to determine whether those quotes represent the true cost of protection.</p>
    <p>That's where GetASecurityQuote (GASQ) changes the process.</p>
    <p>Instead of asking, <em>"Who gave me the lowest price?"</em> GASQ helps buyers ask, <em>"What should this service actually cost to deliver?"</em></p>

    <h2 class="h4 fw-semibold mt-5 mb-2">GASQ Starts With the Buyer's Cost—Not the Vendor's Price</h2>
    <p>Traditional procurement asks vendors to define the price. GASQ defines the <strong>Cost to Protect™</strong> first.</p>
    <p>Once the buyer understands the true cost of staffing, workforce maintenance, relief coverage, and non-productive hours, every proposal can be measured against the same independent benchmark.</p>
    <p>This changes the conversation from <strong>price negotiation</strong> to <strong>price validation</strong>.</p>

    <h2 class="h4 fw-semibold mt-5 mb-2">GASQ Measures the Total Cost of Ownership</h2>
    <p>Most buyers compare hourly rates. GASQ compares:</p>
    <ul>
        <li>Cost to Protect™</li>
        <li>Cost to Deliver Protection™</li>
        <li>Total Cost of Ownership (TCO)</li>
        <li>Workforce Maintenance Cost</li>
        <li>Hours Paid But Not Worked (HPNW)</li>
        <li>Capital Recovery Opportunity</li>
    </ul>
    <p>Instead of seeing only today's invoice, buyers see the complete financial picture over the life of the contract.</p>

    <h2 class="h4 fw-semibold mt-5 mb-2">GASQ Makes Pricing Transparent</h2>
    <p>A vendor can submit any hourly rate. GASQ shows whether that rate is:</p>
    <ul>
        <li>Sustainable</li>
        <li>Underpriced</li>
        <li>Overpriced</li>
        <li>Realistic for long-term service delivery</li>
    </ul>
    <p>This protects both buyers and vendors from unrealistic pricing that often results in poor service, turnover, or contract failure.</p>

    <h2 class="h4 fw-semibold mt-5 mb-2">GASQ Protects Both Sides</h2>
    <div class="row g-4">
        <div class="col-md-6">
            <h3 class="h6 fw-bold text-uppercase text-gasq-muted mb-2">For Buyers</h3>
            <ul>
                <li>Know your true budget before going to market.</li>
                <li>Compare vendors against one standard.</li>
                <li>Recover hidden costs.</li>
                <li>Reduce procurement risk.</li>
                <li>Improve negotiation leverage.</li>
            </ul>
        </div>
        <div class="col-md-6">
            <h3 class="h6 fw-bold text-uppercase text-gasq-muted mb-2">For Vendors</h3>
            <ul>
                <li>Demonstrate realistic pricing.</li>
                <li>Educate customers on the true cost of staffing.</li>
                <li>Protect profit margins.</li>
                <li>Avoid underbidding.</li>
                <li>Build long-term partnerships instead of winning unsustainable contracts.</li>
            </ul>
        </div>
    </div>

    <h2 class="h4 fw-semibold mt-5 mb-2">GASQ Is Based on Workforce Economics</h2>
    <p>Security is a manpower-driven service. One 24/7 security post requires approximately:</p>
    <ul>
        <li><strong>6</strong> full-time employees</li>
        <li><strong>12,480</strong> paid hours annually</li>
        <li><strong>8,736</strong> productive coverage hours</li>
    </ul>
    <p>Ignoring those workforce realities creates inaccurate pricing. GASQ incorporates them into every appraisal.</p>

    <h2 class="h4 fw-semibold mt-5 mb-2">GASQ Turns Procurement Into Financial Management</h2>
    <p>A bank statement tells you how much money you have. A GASQ appraisal tells you how much money you may be able to recover through smarter procurement.</p>
    <p>That's why we call it your <strong>Capital Recovery Opportunity</strong>.</p>

    <h2 class="h4 fw-semibold mt-5 mb-2">The GASQ Difference</h2>
    <p>Most companies sell security services. <strong>GASQ helps organizations buy security services intelligently.</strong></p>
    <p>Most companies compete on price. <strong>GASQ helps buyers understand value.</strong></p>
    <p>Most estimates answer: <em>"What will this cost?"</em> <strong>GASQ answers: "What should this cost?"</strong></p>

    <h2 class="h4 fw-semibold mt-5 mb-2">Our Promise</h2>
    <p class="fs-5 fw-semibold text-gasq-foreground">Know Your Cost to Protect™ Before You Buy.</p>
    <p>When buyers know their true cost of protection, they make better decisions, vendors compete on a level playing field, and security becomes a measurable business investment rather than just another expense.</p>

    <div class="text-center border rounded-3 p-4 p-md-5 mt-5" style="background:#f4f6fb;">
        <h3 class="h4 fw-bold mb-2">See What Your Security Should Actually Cost</h3>
        <p class="text-gasq-muted mb-4">Run an independent Cost to Protect™ appraisal in minutes — before you go to market.</p>
        <a href="{{ route('instant-estimator.index') }}" class="btn btn-primary btn-lg">Get My Instant Cost to Protect™ Estimate</a>
    </div>
</div>
@endsection

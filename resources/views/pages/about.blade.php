@extends('layouts.app')

@section('title', 'About GetASecurityQuote')

@section('content')
<div class="container py-5" style="max-width: 860px;">
    <h1 class="fw-bold mb-1">About GetASecurityQuote</h1>
    <p class="fs-5 text-gasq-muted mb-4">Know Your Cost to Protect™ Before You Buy.</p>

    <p>At GetASecurityQuote (GASQ), we believe buyers deserve more than competing price quotes—they deserve an independent understanding of what security services should actually cost before selecting a provider.</p>

    <p>For decades, the security industry has relied on vendors to define pricing. That means the same company selling the service is often establishing the pricing methodology used to justify its proposal. GASQ was created to change that.</p>

    <p>We developed the <strong>Cost to Protect™</strong> methodology to provide buyers with an independent appraisal of the true cost of delivering security services based on staffing requirements, workforce availability, coverage schedules, wage assumptions, and total cost of ownership—not simply an hourly bill rate.</p>

    <h2 class="h4 fw-semibold mt-5 mb-2">Our Mission</h2>
    <p>Our mission is to bring transparency, consistency, and confidence to the procurement of security services by giving buyers the information they need to make informed decisions before awarding a contract.</p>

    <h2 class="h4 fw-semibold mt-5 mb-2">What Makes GASQ Different?</h2>
    <p>Unlike traditional quote services, GASQ works on behalf of the buyer—not the vendor. Our independent appraisal helps organizations:</p>
    <ul>
        <li>Determine their estimated Cost to Protect™ before requesting proposals.</li>
        <li>Understand the true cost of delivering security services.</li>
        <li>Identify potential capital recovery opportunities.</li>
        <li>Evaluate vendor pricing against an independent benchmark rather than allowing vendors to define the standard.</li>
        <li>Reduce procurement risk through objective analysis and structured vendor evaluation.</li>
    </ul>

    <h2 class="h4 fw-semibold mt-5 mb-2">Our Methodology</h2>
    <p>The GASQ methodology is built around workforce economics and operational realities, including:</p>
    <ul>
        <li>Required staffing levels for continuous coverage.</li>
        <li>Hours Paid But Not Worked (HPNW™).</li>
        <li>Workforce maintenance costs.</li>
        <li>Total Cost to Deliver Protection.</li>
        <li>Total Cost of Ownership.</li>
        <li>Vendor sustainability and pricing realism.</li>
    </ul>
    <p>Instead of focusing solely on hourly rates, GASQ evaluates the complete financial picture required to consistently deliver reliable security services.</p>

    <h2 class="h4 fw-semibold mt-5 mb-2">GASQ Certified™</h2>
    <p>Our goal is to establish an industry standard for independently verifying and validating security service estimates.</p>
    <p>The <strong>GASQ Certified™</strong> designation recognizes pricing analyses that have been evaluated using the GASQ methodology, providing buyers with greater confidence in procurement decisions.</p>

    <h2 class="h4 fw-semibold mt-5 mb-2">Who We Serve</h2>
    <p>GASQ supports organizations that purchase security services, including:</p>
    <ul>
        <li>Commercial real estate owners and managers</li>
        <li>Healthcare systems</li>
        <li>Educational institutions</li>
        <li>Government agencies</li>
        <li>Manufacturing and distribution facilities</li>
        <li>Critical infrastructure</li>
        <li>Hospitality and entertainment venues</li>
        <li>Data centers</li>
        <li>Multi-site enterprises</li>
    </ul>

    <h2 class="h4 fw-semibold mt-5 mb-2">Our Commitment</h2>
    <p>We believe every buyer should know their Cost to Protect™ before they buy.</p>
    <p>By replacing guesswork with independent analysis, GASQ helps organizations make smarter purchasing decisions, improve vendor selection, and better manage long-term security investments.</p>
    <p>GetASecurityQuote isn't simply another quoting platform. We're building a new standard for how security services are evaluated, priced, and purchased.</p>

    <p class="fs-5 fw-semibold text-gasq-foreground mt-4">Know Your Cost to Protect™. Buy with Confidence.</p>

    <div class="text-center border rounded-3 p-4 p-md-5 mt-5" style="background:#f4f6fb;">
        <h3 class="h4 fw-bold mb-2">Know Your Cost to Protect™ Before You Buy</h3>
        <p class="text-gasq-muted mb-4">Get an independent appraisal of what your security should actually cost — before you go to market.</p>
        <a href="{{ route('instant-estimator.index') }}" class="btn btn-primary btn-lg">Get My Instant Cost to Protect™ Estimate</a>
    </div>
</div>
@endsection

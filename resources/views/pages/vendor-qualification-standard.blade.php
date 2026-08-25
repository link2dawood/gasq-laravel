@extends('layouts.app')

@section('title', 'Vendor Qualification Standard')

@section('content')
<div class="container py-5" style="max-width: 860px;">
    <h1 class="fw-bold mb-3">Vendor Qualification Standard</h1>

    <p class="lead">When GASQ says a vendor is <strong>prequalified</strong>, this is the standard being applied.</p>

    <p>Every vendor that responds to a buyer opportunity on GASQ must clear two separate tests before their
        response reaches the buyer: a <strong>responsive</strong> test and a <strong>responsible</strong> test. These are the
        same two tests used in formal public procurement. Failing either one <em>blocks</em> the submission &mdash; it is not
        recorded as a warning for the buyer to weigh later.</p>

    <h2 class="h4 fw-semibold mt-5 mb-2">1. Responsive &mdash; can the vendor actually perform this scope?</h2>
    <p>The vendor must affirmatively confirm every one of the following for the specific opportunity. A blank, a
        &ldquo;no,&rdquo; or an unanswered item is a failure:</p>
    <ul>
        <li>Licensed to provide security services in the jurisdiction of the work</li>
        <li>Able to meet the requested start date</li>
        <li>Able to meet the requested coverage hours</li>
        <li>Able to staff the requested personnel</li>
        <li>Able to meet uniform requirements</li>
        <li>Able to meet reporting requirements</li>
        <li>Able to meet technology and compliance requirements</li>
        <li>Able to meet the stated insurance minimums</li>
        <li>Able to meet the stated wage requirement</li>
        <li>Able to meet training requirements</li>
        <li>Able to meet the required response time</li>
        <li>Has reviewed the full scope of work</li>
        <li>Accepts the terms, the pricing basis, and the schedule</li>
        <li>Confirms the pricing is <em>sustainable</em> &mdash; not a rate the vendor cannot hold</li>
        <li>Has declared at least one technology capability</li>
    </ul>

    <h2 class="h4 fw-semibold mt-5 mb-2">2. Documentation &mdash; all seven, every time</h2>
    <p>A response cannot be submitted until all seven documents are on file for that opportunity:</p>
    <ul>
        <li>State Security License</li>
        <li>Certificate of Insurance</li>
        <li>W-9</li>
        <li>Capability Statement</li>
        <li>Workers&rsquo; Compensation Certificate</li>
        <li>General Liability Certificate</li>
        <li>Business License</li>
    </ul>
    <p>Documents already held on the vendor&rsquo;s GASQ profile are carried forward automatically, but the set must be
        complete for the opportunity in question.</p>

    <h2 class="h4 fw-semibold mt-5 mb-2">3. Responsible &mdash; is the vendor financially and operationally sound?</h2>
    <p>The following are <strong>disqualifying</strong>. A vendor that answers yes to any of them is blocked from
        submitting:</p>
    <ul>
        <li>Has failed to make payroll</li>
        <li>Has lost a contract over staffing performance</li>
        <li>Has negligent-security litigation history</li>
        <li>Has had a security license suspended</li>
    </ul>
    <p>The vendor must also affirmatively demonstrate:</p>
    <ul>
        <li>Three verifiable references</li>
        <li>Documented past performance</li>
        <li>Workers&rsquo; Compensation <em>and</em> General Liability insurance in force</li>
        <li><strong>30&ndash;45 days of payroll sustainment</strong> &mdash; the ability to pay officers before the first
            invoice is settled</li>
    </ul>
    <p>That last requirement is the one most often missing elsewhere. An underfunded vendor is the most common root
        cause of the understaffing, turnover, and service failures that a low bid appears to avoid.</p>

    <h2 class="h4 fw-semibold mt-5 mb-2">Jurisdiction matters</h2>
    <p>Security licensing and insurance requirements are set state by state. GASQ verifies a vendor&rsquo;s standing
        for the jurisdiction where the work will actually be performed. Qualification is <strong>not</strong> a universal or
        nationwide badge, and a vendor qualified for one state&rsquo;s opportunity is not automatically qualified for
        another&rsquo;s.</p>

    <h2 class="h4 fw-semibold mt-5 mb-2">What qualification does not mean</h2>
    <p>Being prequalified on GASQ is a screening standard, not a warranty. Specifically, it is not:</p>
    <ul>
        <li>A guarantee of service quality or contract performance</li>
        <li>An endorsement or recommendation of one vendor over another</li>
        <li>A substitute for the buyer&rsquo;s own due diligence, site visit, or reference checks</li>
        <li>A credit rating or a guarantee of ongoing financial condition</li>
    </ul>
    <p>Qualification reflects what the vendor attested to and documented at the time of submission. Buyers receive the
        completed questionnaire and supporting documents so they can review the evidence directly rather than rely on a
        badge.</p>

    <div class="mt-5 p-4 rounded" style="background:#f5f7fa;">
        <p class="mb-2 fw-semibold">Buying security services?</p>
        <p class="text-gasq-muted mb-3">Establish your Cost to Protect&trade; benchmark first, then measure every
            qualified response against it.</p>
        <a href="{{ route('instant-estimator.index') }}" class="btn btn-primary">Start My Free Estimate</a>
    </div>
</div>
@endsection

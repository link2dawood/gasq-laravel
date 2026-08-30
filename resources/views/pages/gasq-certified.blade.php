@extends('layouts.app')

@section('title', 'GASQ Certified')

{{--
    Standards page for the GASQ Certified(tm) mark.

    Everything asserted below is grounded in what the platform actually does
    (Cost to Protect / Workforce-to-Post methodology, and the fact that the
    appraisal is computed from buyer scope inputs, never from a vendor quote).

    STILL NEEDS BUSINESS SIGN-OFF before these can be stated publicly:
      - who performs the review (named analyst role / credential)
      - whether certification carries a fixed validity window
      - the re-certification / appeal process
    Do not invent answers to these; add them once the business confirms.
--}}

@section('content')
<div class="container py-5" style="max-width: 860px;">
    <h1 class="fw-bold mb-3">GASQ Certified&trade;</h1>

    <p class="lead">What the mark means, what it covers, and just as importantly &mdash; what it does not.</p>

    <h2 class="h4 fw-semibold mt-5 mb-2">The estimate is certified, not the vendor</h2>
    <p>This is the distinction that matters most. GASQ Certified applies to a <strong>pricing analysis</strong> &mdash;
        the appraisal document produced for a specific scope of work. It does not rate, rank, endorse, or accredit any
        security company.</p>
    <p>A vendor cannot be &ldquo;GASQ Certified.&rdquo; A vendor can be <em>qualified</em> to respond to an opportunity, which is a
        separate and independently documented standard. See the
        <a href="{{ route('vendor-qualification-standard') }}">Vendor Qualification Standard</a> for that.</p>

    <h2 class="h4 fw-semibold mt-5 mb-2">What is being certified</h2>
    <p>That the analysis was produced using the GASQ methodology and that its inputs are internally consistent and
        complete. In practice that means the appraisal accounts for:</p>
    <ul>
        <li>The <strong>Cost to Protect&trade;</strong> &mdash; the full cost of performing the function correctly</li>
        <li><strong>Workforce-to-Post&trade;</strong> staffing: the officers genuinely required to hold the posts requested</li>
        <li>Relief coverage, turnover, and hours paid but not worked</li>
        <li>Wage realism for the labor market where the work is performed</li>
        <li>Total Cost of Ownership, not just an hourly rate</li>
        <li>Capital recovery opportunity measured against in-house delivery</li>
    </ul>

    <h2 class="h4 fw-semibold mt-5 mb-2">How independence is maintained</h2>
    <p>The Cost to Protect is calculated from the buyer&rsquo;s scope, coverage requirements, and prevailing labor
        costs. <strong>No vendor&rsquo;s proposed bill rate is an input to it.</strong> GASQ does not need to see a quote to
        produce the benchmark, and the benchmark does not move because a vendor priced high or low.</p>
    <p>The order of operations is deliberate: the buyer-side analysis is completed <em>first</em>. Vendor participation,
        qualification, and selection happen only afterward, and are measured against a number that was already fixed.
        That is what <strong>Validate Before You Estimate&trade;</strong> means in practice.</p>
    <p>GASQ earns revenue from vendor participation on the platform. That relationship is disclosed deliberately, because
        independence is a claim about <em>method</em>, not a claim about having no commercial interests: no vendor can pay to
        change a Cost to Protect figure, to alter an appraisal, or to be presented to a buyer as better priced than the
        analysis shows.</p>

    <h2 class="h4 fw-semibold mt-5 mb-2">What is not being certified</h2>
    <p>A GASQ Certified appraisal is a financial analysis. It is explicitly <strong>not</strong>:</p>
    <ul>
        <li>A guarantee that any vendor will perform to the standard modeled</li>
        <li>A guarantee that a contract can be awarded at the figure shown</li>
        <li>A verification of any particular vendor&rsquo;s licensing, insurance, or financial condition</li>
        <li>A legal, tax, insurance, or accounting opinion</li>
        <li>A prediction of what vendors in a given market will actually quote</li>
    </ul>

    <h2 class="h4 fw-semibold mt-5 mb-2">Assumptions can change &mdash; and are shown</h2>
    <p>An appraisal is a point-in-time calculation. It is only as current as the inputs behind it: wage rates, coverage
        hours, post count, scope, and location. Change any of those and the analysis must be re-run. For that reason every
        appraisal shows its inputs alongside its outputs, so a buyer, a CFO, or a vendor can see exactly what was assumed
        and challenge any assumption directly rather than argue with a total.</p>

    <div class="mt-5 p-4 rounded" style="background:#f5f7fa;">
        <p class="mb-2 fw-semibold">See it on your own scope.</p>
        <p class="text-gasq-muted mb-3">Run a Cost to Protect analysis before you go to market.</p>
        <a href="{{ route('instant-estimator.index') }}" class="btn btn-primary">Start My Free Estimate</a>
    </div>
</div>
@endsection

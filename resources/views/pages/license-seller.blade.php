{{-- DRAFT — review with legal counsel before relying on this wording. --}}
@extends('layouts.app')

@section('title', 'Seller License & Usage Agreement')

@section('content')
<div class="container py-5" style="max-width: 820px;">
    <h1 class="fw-bold mb-1">GASQ Seller License &amp; Usage Agreement</h1>
    <p class="text-gasq-muted mb-4">
        Version {{ config('license.seller_version') }} · Applies to vendors and sellers
    </p>

    <p>This Seller License &amp; Usage Agreement (the “Agreement”) governs your access to and use of the
        Get A Security Quote (“GASQ”) calculators, the GASQ Cost to Protect™ methodology, and any reports,
        estimates, or outputs they generate (collectively, the “Service”). By checking “I agree,” creating a
        vendor account, or using any calculator, you accept this Agreement.</p>

    <h5 class="fw-semibold mt-4">1. Permitted Use</h5>
    <p>GASQ grants you a limited, non-exclusive, non-transferable, revocable license to use the Service and its
        outputs to prepare pricing, bids, and proposals <strong>for your own prospective clients</strong>, and to
        share generated reports with the specific client for whom they were prepared.</p>

    <h5 class="fw-semibold mt-4">2. Restrictions</h5>
    <p>You agree that you will <strong>not</strong>, and will not permit any third party to:</p>
    <ul>
        <li>Reverse engineer, decompile, derive, or attempt to reconstruct the methodologies, formulas,
            benchmarks, staffing algorithms, or analytical frameworks underlying the Service;</li>
        <li>Reproduce, redistribute, publish, resell, sublicense, or commercially exploit the methodology or any
            report as a standalone product, in whole or in part;</li>
        <li>Build, market, or assist others in building any competing or derivative calculator, pricing model, or
            tool based on the Service or its outputs;</li>
        <li>Represent the GASQ methodology or its outputs as your own, or remove, obscure, or alter any report
            number, watermark, confidentiality notice, or attribution.</li>
    </ul>

    <h5 class="fw-semibold mt-4">3. Confidentiality &amp; Intellectual Property</h5>
    <p>The Service, the GASQ Cost to Protect™ methodology, and all related concepts, calculations, presentation
        formats, and analytical frameworks are the proprietary and confidential intellectual property of GASQ.
        You receive no ownership rights and will hold the methodology in confidence.</p>

    <h5 class="fw-semibold mt-4">4. No Guarantee; Estimates Only</h5>
    <p>Outputs are estimates for planning, pricing, and comparison only. You remain solely responsible for the
        quotes, bids, and commitments you make to your clients.</p>

    <h5 class="fw-semibold mt-4">5. Traceability</h5>
    <p>Each report carries a unique report number and may be watermarked for authentication and traceability.
        You acknowledge that misuse may be traced to your account of record.</p>

    <h5 class="fw-semibold mt-4">6. Termination</h5>
    <p>GASQ may suspend or revoke this license and your account access at any time for breach. Sections 2–4
        survive termination.</p>

    <p class="text-gasq-muted mt-4 small">
        Questions: {{ config('license.contact_email') }}. This Agreement does not create an attorney–client
        relationship and is not legal advice to you.
    </p>
</div>
@endsection

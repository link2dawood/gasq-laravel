@extends('layouts.app')

@section('title', 'Terms & Conditions')

@section('content')
<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-lg-10">
            <div class="mb-4">
                <span class="badge bg-light text-dark border">Legal</span>
                <h1 class="h2 mt-3 mb-2">Terms &amp; Conditions</h1>
                <p class="text-gasq-muted mb-0">These terms explain how buyers, vendors, and visitors may use the GASQ platform and related services.</p>
            </div>

            <div class="card gasq-card mb-4">
                <div class="card-body">
                    <h2 class="h5 mb-3">1. Platform Use</h2>
                    <p class="mb-0">GASQ helps buyers request security service quotes, compare options, and interact with vendors. Vendors may review opportunities and respond through the platform. You agree to use the platform only for lawful business purposes and to provide accurate information when creating an account, posting a request, or responding to an opportunity.</p>
                </div>
            </div>

            <div class="card gasq-card mb-4">
                <div class="card-body">
                    <h2 class="h5 mb-3">2. Account Responsibility</h2>
                    <p class="mb-0">Users are responsible for maintaining the confidentiality of their login credentials and for all activity that occurs under their account. You should promptly update your account details if your contact information changes and notify the GASQ team if you believe your account has been used without authorization.</p>
                </div>
            </div>

            <div class="card gasq-card mb-4">
                <div class="card-body">
                    <h2 class="h5 mb-3">3. Buyer Submissions</h2>
                    <p class="mb-0">Buyers are responsible for the accuracy of job details, schedules, budgets, site information, and compliance requirements entered into the questionnaire or any generated job announcement. GASQ may use the submitted information to generate, format, and distribute a job posting to qualified vendors.</p>
                </div>
            </div>

            <div class="card gasq-card mb-4">
                <div class="card-body">
                    <h2 class="h5 mb-3">4. Vendor Responses</h2>
                    <p class="mb-0">Vendors are responsible for the accuracy of their qualifications, pricing, availability, and other response details. GASQ does not guarantee that a buyer will award a project or that a vendor will be selected. Any agreement for services is made directly between the buyer and vendor unless otherwise stated in writing.</p>
                </div>
            </div>

            <div class="card gasq-card mb-4">
                <div class="card-body">
                    <h2 class="h5 mb-3">5. Credits, Pricing, and Payments</h2>
                    <p class="mb-0">Certain platform features may require credits or payment. Credit purchases, redemptions, and usage are subject to the pricing and payment terms shown on the platform at the time of purchase. GASQ may update pricing, credit packages, and platform offerings from time to time.</p>
                </div>
            </div>

            <div class="card gasq-card mb-4">
                <div class="card-body">
                    <h2 class="h5 mb-3">6. Communications</h2>
                    <p class="mb-0">By creating an account or submitting a request, you agree that GASQ may contact you about verification codes, account activity, quote requests, responses, support matters, and relevant service updates by email, phone, or text message where permitted.</p>
                </div>
            </div>

            <div class="card gasq-card mb-4">
                <div class="card-body">
                    <h2 class="h5 mb-3">7. Limitation of Platform Role</h2>
                    <p class="mb-0">GASQ provides a technology platform to help connect buyers and vendors and to support quoting and evaluation workflows. GASQ is not a party to the final service contract between buyers and vendors unless explicitly stated otherwise. Users are responsible for their own due diligence, contract review, and business decisions.</p>
                </div>
            </div>

            <div class="card gasq-card">
                <div class="card-body">
                    <h2 class="h5 mb-3">8. Updates to These Terms</h2>
                    <p class="mb-0">GASQ may revise these terms from time to time. Continued use of the platform after updates become effective means you accept the revised terms. For questions about these terms, contact the GASQ team before continuing to use the service.</p>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@section('footer')
    @include('partials.site-footer')
@endsection

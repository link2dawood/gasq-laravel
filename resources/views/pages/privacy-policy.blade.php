@extends('layouts.app')

@section('title', 'Privacy Policy')

@section('content')
<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-lg-10">
            <div class="mb-4">
                <span class="badge bg-light text-dark border">Legal</span>
                <h1 class="h2 mt-3 mb-2">Privacy Policy</h1>
                <p class="text-gasq-muted mb-0">This policy explains what information GASQ collects, 
                how it is used, and how it supports the buyer and vendor experience on the platform.</p>
            </div>

            <div class="card gasq-card mb-4">
                <div class="card-body">
                    <h2 class="h5 mb-3">1. Information We Collect</h2>
                    <p class="mb-0">GASQ may collect account information, contact details, company 
                    details, project information, quote request data, job posting details, vendor 
                    response information, payment-related records, and technical usage data needed 
                    to operate the platform.</p>
                </div>
            </div>

            <div class="card gasq-card mb-4">
                <div class="card-body">
                    <h2 class="h5 mb-3">2. How We Use Information</h2>
                    <p class="mb-0">We use this information to create and manage user accounts, 
                    verify phone numbers, generate and publish buyer job announcements, connect 
                    buyers with vendors, process credits and payments, provide support, improve 
                    platform features, and maintain security and audit records.</p>
                </div>
            </div>

            <div class="card gasq-card mb-4">
                <div class="card-body">
                    <h2 class="h5 mb-3">3. Sharing Information</h2>
                    <p class="mb-0">Buyer information may be shared with relevant vendors as part 
                    of the quoting and job announcement process. Vendor response information may be 
                    shared with buyers. GASQ may use service providers that support hosting, 
                    analytics, payment processing, or customer communications as needed to operate 
                    the platform.</p>
                    
                    <p class="mb-0 mt-3"><strong>SMS &amp; Mobile Data:</strong> No mobile 
                    information will be shared with third parties or affiliates for marketing or 
                    promotional purposes. All SMS opt-in data and consent information will not be 
                    shared with any third parties, affiliates, or service providers for any purpose 
                    other than delivering the messages you have consented to receive.</p>
                </div>
            </div>

            <div class="card gasq-card mb-4">
                <div class="card-body">
                    <h2 class="h5 mb-3">4. SMS and Phone Messaging</h2>
                    <p class="mb-0">GASQNOW sends SMS messages only to users who have explicitly 
                    opted in to receive text communications. SMS messages are used for:</p>
                    <ul class="mt-2 mb-0">
                        <li>One-time passcodes (OTP) for account verification and login (2FA)</li>
                        <li>Security alerts related to account or profile changes</li>
                        <li>Transactional notifications related to your account activity</li>
                    </ul>
                    <p class="mt-3 mb-0">Message frequency varies based on account activity. 
                    Message and data rates may apply depending on your carrier and plan.</p>
                    <p class="mt-3 mb-0"><strong>To opt out:</strong> Reply <strong>STOP</strong> 
                    to any SMS message at any time. You will receive a confirmation and no further 
                    messages will be sent.</p>
                    <p class="mt-2 mb-0"><strong>For help:</strong> Reply <strong>HELP</strong> 
                    to any SMS message or contact us directly.</p>
                    <p class="mt-3 mb-0"><strong>No mobile information will be shared with third 
                    parties or affiliates for marketing or promotional purposes. All the above 
                    categories exclude text messaging originator opt-in data and consent; this 
                    information will not be shared with any third parties.</strong></p>
                </div>
            </div>

            <div class="card gasq-card mb-4">
                <div class="card-body">
                    <h2 class="h5 mb-3">5. Data Retention and Security</h2>
                    <p class="mb-0">GASQ retains business and account records for operational, 
                    support, compliance, and audit purposes. We use reasonable administrative and 
                    technical safeguards to protect information, but no internet-based service 
                    can guarantee absolute security.</p>
                </div>
            </div>

            <div class="card gasq-card mb-4">
                <div class="card-body">
                    <h2 class="h5 mb-3">6. User Choices</h2>
                    <p class="mb-0">Users may update certain profile details from within their 
                    account. Some communications are required to operate the platform, including 
                    verification and transactional notices. To stop receiving SMS messages, reply 
                    STOP at any time.</p>
                </div>
            </div>

            <div class="card gasq-card">
                <div class="card-body">
                    <h2 class="h5 mb-3">7. Policy Updates</h2>
                    <p class="mb-0">GASQ may update this privacy policy from time to time. When 
                    updates are made, the current version posted on the platform will govern future 
                    use. If you have questions about privacy practices, please contact the GASQ team.
                    </p>
                </div>
            </div>

        </div>
    </div>
</div>
@endsection

@section('footer')
    @include('partials.site-footer')
@endsection

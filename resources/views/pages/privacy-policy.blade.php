@extends('layouts.app')

@section('title', 'Privacy Policy')

@section('content')
<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-lg-10">
            <div class="mb-4">
                <span class="badge bg-light text-dark border">Legal</span>
                <h1 class="h2 mt-3 mb-2">Privacy Policy</h1>
                <p class="text-gasq-muted mb-0">This policy explains what information GASQ collects, how it is used, and how it supports the buyer and vendor experience on the platform.</p>
            </div>

            <div class="card gasq-card mb-4">
                <div class="card-body">
                    <h2 class="h5 mb-3">1. Information We Collect</h2>
                    <p class="mb-0">GASQ may collect account information, contact details, company details, project information, quote request data, job posting details, vendor response information, payment-related records, and technical usage data needed to operate the platform.</p>
                </div>
            </div>

            <div class="card gasq-card mb-4">
                <div class="card-body">
                    <h2 class="h5 mb-3">2. How We Use Information</h2>
                    <p class="mb-0">We use this information to create and manage user accounts, verify phone numbers, generate and publish buyer job announcements, connect buyers with vendors, process credits and payments, provide support, improve platform features, and maintain security and audit records.</p>
                </div>
            </div>

            <div class="card gasq-card mb-4">
                <div class="card-body">
                    <h2 class="h5 mb-3">3. Sharing Information</h2>
                    <p class="mb-0">Buyer information may be shared with relevant vendors as part of the quoting and job announcement process. Vendor response information may be shared with buyers. GASQ may also use service providers that support hosting, messaging, analytics, payment processing, or customer communications as needed to operate the platform.</p>
                </div>
            </div>

            <div class="card gasq-card mb-4">
                <div class="card-body">
                    <h2 class="h5 mb-3">4. Phone and Messaging Data</h2>
                    <p class="mb-0">Phone numbers may be used for account verification, security checks, service notifications, and communication related to account activity or active opportunities. Message and carrier fees may apply depending on the user’s provider and location.</p>
                </div>
            </div>

            <div class="card gasq-card mb-4">
                <div class="card-body">
                    <h2 class="h5 mb-3">5. Data Retention and Security</h2>
                    <p class="mb-0">GASQ retains business and account records for operational, support, compliance, and audit purposes. We use reasonable administrative and technical safeguards to protect information, but no internet-based service can guarantee absolute security.</p>
                </div>
            </div>

            <div class="card gasq-card mb-4">
                <div class="card-body">
                    <h2 class="h5 mb-3">6. User Choices</h2>
                    <p class="mb-0">Users may update certain profile details from within their account. Some communications are required to operate the platform, including verification and transactional notices. Marketing or optional communications may be managed separately where applicable.</p>
                </div>
            </div>

            <div class="card gasq-card">
                <div class="card-body">
                    <h2 class="h5 mb-3">7. Policy Updates</h2>
                    <p class="mb-0">GASQ may update this privacy policy from time to time. When updates are made, the current version posted on the platform will govern future use. If you have questions about privacy practices, please contact the GASQ team.</p>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@section('footer')
    @include('partials.site-footer')
@endsection

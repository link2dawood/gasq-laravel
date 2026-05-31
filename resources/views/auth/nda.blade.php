@extends('layouts.auth')

@section('title', 'Beta Test NDA & Confidentiality Acknowledgment')

@section('content')
<div class="text-center mb-4">
    <h1 class="h4 fw-bold text-gasq-foreground mb-2">Beta Test NDA &amp; Confidentiality Acknowledgment</h1>
    <p class="text-gasq-muted small mb-0">Private Beta Access Agreement &mdash; GetASecurityQuoteNow.com</p>
</div>

<div class="card gasq-card shadow-sm mx-auto" style="max-width: 56rem;">
    <div class="card-body p-4 p-lg-5">
        @if (session('error'))
            <div class="alert alert-warning small mb-4">{{ session('error') }}</div>
        @endif

        <p class="small text-gasq-muted">
            Before accessing this private beta website, calculator, pricing model, forms, reports, software tools, or
            related materials, you must review and acknowledge the following confidentiality terms.
        </p>
        <p class="small text-gasq-muted mb-4">
            By selecting <strong>&ldquo;I Agree and Acknowledge&rdquo;</strong>, you confirm that you understand and
            agree to keep all beta test information confidential.
        </p>

        <div class="border rounded p-3 p-md-4 mb-4" style="max-height: 26rem; overflow-y: auto;">
            <h2 class="h6 fw-bold mt-0">1. Purpose of Beta Access</h2>
            <p class="small mb-2">
                You are being granted limited access to review, test, evaluate, and provide feedback on
                GetASecurityQuoteNow&rsquo;s private beta platform, including but not limited to:
            </p>
            <ul class="small mb-2">
                <li>Website pages and user experience</li>
                <li>Security pricing calculators</li>
                <li>Budget and estimate tools</li>
                <li>Rescope and job offer forms</li>
                <li>Vendor network concepts</li>
                <li>Pricing models and formulas</li>
                <li>Reports, documents, workflows, and platform language</li>
                <li>Any screenshots, demonstrations, emails, or related beta materials</li>
            </ul>
            <p class="small">This access is provided only for beta testing and feedback purposes.</p>

            <h2 class="h6 fw-bold">2. Confidential Information</h2>
            <p class="small mb-2">
                You agree that all information viewed, tested, received, or discussed during this beta test is
                confidential and proprietary to GetASecurityQuoteNow.
            </p>
            <p class="small mb-2">Confidential information includes, but is not limited to:</p>
            <ul class="small">
                <li>Pricing formulas</li>
                <li>Calculator logic</li>
                <li>Business methods</li>
                <li>Platform design</li>
                <li>Buyer and vendor workflows</li>
                <li>Proprietary terminology</li>
                <li>Reports and templates</li>
                <li>Marketing language</li>
                <li>Screenshots</li>
                <li>Beta test links</li>
                <li>Login credentials</li>
                <li>Any non-public business information</li>
            </ul>

            <h2 class="h6 fw-bold">3. Non-Disclosure Agreement</h2>
            <p class="small">
                You agree not to copy, share, disclose, publish, forward, sell, reproduce, reverse engineer, or
                distribute any confidential information from this beta test without written permission from
                GetASecurityQuoteNow.
            </p>
            <p class="small">
                You also agree not to share your beta access link, password, screenshots, screen recordings, or
                platform content with any third party.
            </p>

            <h2 class="h6 fw-bold">4. Limited Use</h2>
            <p class="small mb-2">You may use the beta platform only to:</p>
            <ul class="small">
                <li>Review the beta site</li>
                <li>Test the available tools</li>
                <li>Submit feedback</li>
                <li>Identify errors, issues, or improvements</li>
                <li>Evaluate the usefulness of the platform</li>
            </ul>
            <p class="small">
                You may not use the beta platform to create a competing product, copy the pricing model, or
                commercially exploit any part of the beta materials.
            </p>

            <h2 class="h6 fw-bold">5. No Public Disclosure</h2>
            <p class="small">
                You agree not to post, publish, or discuss the beta platform on social media, websites, forums,
                videos, podcasts, blogs, or public platforms without prior written approval.
            </p>

            <h2 class="h6 fw-bold">6. Feedback Ownership</h2>
            <p class="small">
                Any comments, suggestions, recommendations, corrections, or feedback you provide may be used by
                GetASecurityQuoteNow to improve the platform. You agree that GetASecurityQuoteNow may use your
                feedback without compensation, unless otherwise agreed in writing.
            </p>

            <h2 class="h6 fw-bold">7. No Guarantee of Final Product</h2>
            <p class="small">
                You understand that this is a beta test. The platform may contain errors, incomplete features,
                draft language, pricing assumptions, or unfinished tools. Beta access does not guarantee future
                access, partnership, vendor approval, buyer approval, or any business relationship.
            </p>

            <h2 class="h6 fw-bold">8. Access May Be Revoked</h2>
            <p class="small">
                GetASecurityQuoteNow may suspend or revoke beta access at any time, with or without notice,
                especially if confidentiality terms are violated.
            </p>

            <h2 class="h6 fw-bold">9. Acknowledgment</h2>
            <p class="small mb-2">By clicking the acknowledgment button below, you confirm that:</p>
            <ul class="small mb-0">
                <li>You have read this Beta Test NDA and Confidentiality Acknowledgment.</li>
                <li>You agree to keep all beta test information confidential.</li>
                <li>You will not share, copy, publish, or distribute beta materials.</li>
                <li>You understand that access is limited to testing and feedback only.</li>
                <li>You agree not to use the information to create or support a competing product.</li>
            </ul>
        </div>

        <form action="{{ route('nda.accept') }}" method="POST" novalidate>
            @csrf
            <div class="form-check mb-4">
                <input
                    type="checkbox"
                    class="form-check-input @error('acknowledge') is-invalid @enderror"
                    id="nda_acknowledge"
                    name="acknowledge"
                    value="1"
                    {{ old('acknowledge') ? 'checked' : '' }}
                    required
                >
                <label class="form-check-label small" for="nda_acknowledge">
                    I have read, understand, and agree to the Beta Test NDA and Confidentiality Acknowledgment.
                </label>
                @error('acknowledge')
                    <div class="invalid-feedback d-block">{{ $message }}</div>
                @enderror
            </div>

            <div class="d-flex flex-column flex-md-row gap-2 justify-content-between align-items-md-center">
                <a href="{{ route('logout') }}"
                   class="text-gasq-muted small"
                   onclick="event.preventDefault(); document.getElementById('nda-logout-form').submit();">
                    Sign out
                </a>
                <button type="submit" class="btn btn-primary btn-lg">
                    I Agree and Acknowledge &mdash; Continue to Beta Site
                </button>
            </div>
        </form>

        <form id="nda-logout-form" action="{{ route('logout') }}" method="POST" class="d-none">
            @csrf
        </form>
    </div>
</div>
@endsection

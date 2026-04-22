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
                    <li><a href="{{ route('pricing') }}" class="text-gasq-muted text-decoration-none">Know Before You Buy</a></li>
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
                    <li><a href="{{ route('terms') }}" class="text-gasq-muted text-decoration-none">Terms & Conditions</a></li>
                    <li><a href="{{ route('privacy-policy') }}" class="text-gasq-muted text-decoration-none">Privacy Policy</a></li>
                    <li><a href="{{ route('login') }}" class="text-gasq-muted text-decoration-none">Buyer Login</a></li>
                </ul>
            </div>
        </div>
        <div class="border-top border-gasq pt-4 text-center small text-gasq-muted">
            <p class="mb-0">&copy; {{ date('Y') }} GetASecurityQuoteNow (GASQ). All rights reserved. | Security Services. Simplified.</p>
        </div>
    </div>
</footer>

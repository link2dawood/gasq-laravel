<footer class="gasq-footer py-5">
    <div class="container px-4">
        <div class="row g-4 mb-4">
            {{-- Brand + real contact info --}}
            <div class="col-md-4">
                <a href="{{ url('/') }}" class="d-inline-block mb-2">
                    <x-logo height="32" />
                </a>
                <p class="small text-gasq-muted mb-3">GetASecurityQuoteNow – Security Services. Simplified.</p>
                <p class="small mb-1"><i class="fa fa-envelope me-2 text-gasq-muted"></i><a href="mailto:info@getasecurityquotenow.com" class="text-gasq-muted text-decoration-none">info@getasecurityquotenow.com</a></p>
                <p class="small mb-1"><i class="fa fa-phone me-2 text-gasq-muted"></i>P: (470) 633-2816</p>
                <p class="small mb-1"><i class="fa fa-phone me-2 text-gasq-muted"></i>A: (404) 922-2872</p>
                <p class="small text-gasq-muted mb-3">(open 24 hours a day, 7 days a week)</p>

                {{-- Social + Book a Call --}}
                <div class="d-flex align-items-center gap-3 mb-3">
                    <a href="https://www.facebook.com/getasecurityquote" target="_blank" rel="noopener" aria-label="Facebook" class="text-decoration-none" style="font-size:1.25rem;color:#1877f2;"><i class="fab fa-facebook"></i></a>
                    <a href="https://www.linkedin.com/company/getasecurityquote" target="_blank" rel="noopener" aria-label="LinkedIn" class="text-decoration-none" style="font-size:1.25rem;color:#0a66c2;"><i class="fab fa-linkedin"></i></a>
                </div>
                <a href="https://getasecurityquote.bookafy.com/" target="_blank" rel="noopener" class="btn btn-dark btn-sm rounded-pill px-3">
                    <i class="fa fa-phone-alt me-2"></i>Book a Call
                </a>
            </div>

            <div class="col-md-2">
                <h4 class="h6 fw-semibold mb-3">For Buyers</h4>
                <ul class="list-unstyled small">
                    <li><a href="{{ route('pricing') }}" class="text-gasq-muted text-decoration-none">Know Before You Buy</a></li>
                    <li><a href="{{ route('security-services') }}" class="text-gasq-muted text-decoration-none">Security Services</a></li>
                    <li><a href="{{ route('jobs.create') }}" class="text-gasq-muted text-decoration-none">Post Your Job</a></li>
                    <li><a href="{{ route('register.buyer.index') }}" class="text-gasq-muted text-decoration-none">Register as Buyer</a></li>
                    <li><a href="{{ route('login') }}" class="text-gasq-muted text-decoration-none">Buyer Login</a></li>
                    <li><a href="{{ route('pricing.buyers') }}" class="text-gasq-muted text-decoration-none">Buyer Pricing</a></li>
                    <li><a href="{{ route('buyer-faq') }}" class="text-gasq-muted text-decoration-none">Buyer FAQ</a></li>
                </ul>
            </div>

            <div class="col-md-3">
                <h4 class="h6 fw-semibold mb-3">For Vendors</h4>
                <ul class="list-unstyled small">
                    <li><a href="{{ route('job-board') }}" class="text-gasq-muted text-decoration-none">Browse Jobs</a></li>
                    <li><a href="{{ route('industries-served') }}" class="text-gasq-muted text-decoration-none">Industries We Serve</a></li>
                    <li><a href="{{ route('register.vendor.index') }}" class="text-gasq-muted text-decoration-none">Register as Vendor</a></li>
                    <li><a href="{{ route('login') }}" class="text-gasq-muted text-decoration-none">Vendor Login</a></li>
                    <li><a href="{{ route('credits') }}" class="text-gasq-muted text-decoration-none">Buy Credits</a></li>
                    <li><a href="{{ route('pricing.vendors') }}" class="text-gasq-muted text-decoration-none">Vendor Pricing</a></li>
                    <li><a href="{{ route('vendor-faq') }}" class="text-gasq-muted text-decoration-none">Vendor FAQ</a></li>
                </ul>
            </div>

            <div class="col-md-3">
                <h4 class="h6 fw-semibold mb-3">Company</h4>
                <ul class="list-unstyled small">
                    <li><a href="{{ route('about') }}" class="text-gasq-muted text-decoration-none">About Us</a></li>
                    <li><a href="{{ route('why-gasq-works') }}" class="text-gasq-muted text-decoration-none">Why GASQ Works</a></li>
                    <li><a href="{{ url('/#how-it-works') }}" class="text-gasq-muted text-decoration-none">How It Works</a></li>
                    <li><a href="{{ route('terms') }}" class="text-gasq-muted text-decoration-none">Terms &amp; Conditions</a></li>
                    <li><a href="{{ route('privacy-policy') }}" class="text-gasq-muted text-decoration-none">Privacy Policy</a></li>
                    <li><a href="{{ route('contact') }}" class="text-gasq-muted text-decoration-none">Contact Us</a></li>
                </ul>
            </div>
        </div>

        <div class="border-top border-gasq pt-4 d-flex flex-wrap justify-content-between align-items-center gap-3">
            <div class="d-flex align-items-center gap-2">
                {{-- Seal: transparent blend + click to explain its significance --}}
                <a href="#" data-bs-toggle="modal" data-bs-target="#gasqSealModal" aria-label="What is GASQ Certified?">
                    <img src="{{ asset('images/gasq-certified-seal.png') }}" alt="GASQ Certified" width="56" height="56" style="height:auto;mix-blend-mode:multiply;">
                </a>
                <a href="#" data-bs-toggle="modal" data-bs-target="#gasqSealModal" class="small text-gasq-muted text-decoration-none">GASQ Certified&trade; <i class="fa fa-circle-info ms-1"></i></a>
            </div>
            <p class="small text-gasq-muted mb-0 text-center">&copy; {{ date('Y') }} GetASecurityQuoteNow (GASQ). All rights reserved. | Security Services. Simplified.</p>
        </div>
    </div>

    {{-- Seal significance popup --}}
    <div class="modal fade" id="gasqSealModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">What does GASQ Certified&trade; mean?</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="text-center mb-3">
                        <img src="{{ asset('images/gasq-certified-seal.png') }}" alt="GASQ Certified" width="96" height="96" style="height:auto;mix-blend-mode:multiply;">
                    </div>
                    <p class="mb-2">The <strong>GASQ Certified&trade;</strong> seal marks a pricing analysis that was evaluated using the GASQ <strong>Cost to Protect&trade;</strong> methodology — an independent benchmark of what security services should actually cost to deliver.</p>
                    <p class="mb-0">It tells buyers the numbers were measured against staffing requirements, workforce availability, and total cost of ownership, not just a vendor&rsquo;s hourly bill rate — so they can buy with confidence.</p>
                </div>
                <div class="modal-footer">
                    <a href="{{ route('why-gasq-works') }}" class="btn btn-outline-primary btn-sm">Learn how it works</a>
                    <button type="button" class="btn btn-primary btn-sm" data-bs-dismiss="modal">Got it</button>
                </div>
            </div>
        </div>
    </div>
</footer>

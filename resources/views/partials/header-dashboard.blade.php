<header class="gasq-navbar sticky top-0 z-50">
    <div class="container px-4">
        <nav class="navbar navbar-expand-md navbar-light py-3 align-items-center">
            <a class="navbar-brand d-flex align-items-center" href="{{ url('/home') }}">
                <x-logo height="40" class="d-inline-block" />
            </a>

            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#dashboardNavbar"
                    aria-controls="dashboardNavbar" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>

            <div class="collapse navbar-collapse gasq-navbar-nav-right" id="dashboardNavbar">
                @auth
                    @if(auth()->user()->isVendor())
                        <ul class="navbar-nav ms-auto align-items-center gap-1">
                            <li class="nav-item"><a class="nav-link text-gasq-muted {{ request()->routeIs('home') ? 'active fw-semibold text-dark' : '' }}" href="{{ route('home') }}">Dashboard</a></li>
                            <li class="nav-item"><a class="nav-link text-gasq-muted {{ request()->routeIs('vendor-leads.index') && request('view') !== 'responses' ? 'active fw-semibold text-dark' : '' }}" href="{{ route('vendor-leads.index') }}">Leads</a></li>
                            <li class="nav-item"><a class="nav-link text-gasq-muted {{ request()->routeIs('vendor-leads.index') && request('view') === 'responses' ? 'active fw-semibold text-dark' : '' }}" href="{{ route('vendor-leads.index', ['view' => 'responses']) }}">Responses</a></li>
                            <li class="nav-item"><a class="nav-link text-gasq-muted {{ request()->routeIs('profile.*') ? 'active fw-semibold text-dark' : '' }}" href="{{ route('profile.show') }}">Settings</a></li>
                            <li class="nav-item"><a class="nav-link text-gasq-muted" href="{{ route('landing') }}">Home</a></li>
                            <li class="nav-item ms-md-2">
                                <span class="btn btn-outline-primary btn-sm d-flex align-items-center gap-2">
                                    <i class="fa fa-wallet"></i>
                                    <span class="fw-semibold">{{ isset($walletBalance) ? $walletBalance : 0 }} Credits</span>
                                </span>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link text-gasq-muted" href="{{ route('logout') }}"
                                   onclick="event.preventDefault(); document.getElementById('logout-form-dash').submit();">Logout</a>
                                <form id="logout-form-dash" action="{{ route('logout') }}" method="POST" class="d-none">@csrf</form>
                            </li>
                        </ul>
                    @else
                        <ul class="navbar-nav ms-auto align-items-center gap-1">
                            <li class="nav-item">
                                <a class="nav-link text-gasq-muted" href="{{ route('job-board') }}">Job Board</a>
                            </li>
                            @include('partials.nav-calculators-dropdown', ['toggleId' => 'navbarCalculatorsDash'])
                            <li class="nav-item">
                                <a class="nav-link text-gasq-muted" href="{{ route('credits') }}">Credits</a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link text-gasq-muted" href="{{ route('discovery-call.index') }}">Discovery Call</a>
                            </li>

                            <li class="nav-item dropdown ms-2">
                                <a class="btn btn-outline-primary btn-sm d-flex align-items-center gap-2" href="#" data-bs-toggle="dropdown" id="walletDropdownDash">
                                    <i class="fa fa-wallet"></i>
                                    <span class="fw-semibold">{{ isset($walletBalance) ? $walletBalance : 0 }} Credits</span>
                                </a>
                                <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="walletDropdownDash" style="min-width: 14rem;">
                                    <li class="px-3 py-2">
                                        <p class="small mb-0 text-gasq-muted">Your Balance</p>
                                        <p class="mb-0 fs-5 fw-bold text-primary">{{ isset($walletBalance) ? $walletBalance : 0 }} Credits</p>
                                    </li>
                                    <li><hr class="dropdown-divider"></li>
                                    <li><a class="dropdown-item" href="{{ route('credits') }}"><i class="fa fa-plus me-2"></i>Purchase Credits</a></li>
                                    <li><a class="dropdown-item" href="{{ route('account-balance') }}"><i class="fa fa-history me-2"></i>Transaction History</a></li>
                                </ul>
                            </li>

                            <li class="nav-item dropdown">
                                <a class="nav-link dropdown-toggle d-flex align-items-center" href="#" id="dashUserMenu" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                                    <img src="{{ Auth::user()->avatar_url }}" alt="" class="rounded-circle me-2" width="32" height="32">
                                    {{ Auth::user()->name }}
                                </a>
                                <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="dashUserMenu">
                                    <li class="px-3 py-2">
                                        <p class="small fw-medium mb-0">Account</p>
                                        <p class="small text-gasq-muted mb-0">{{ Auth::user()->email }}</p>
                                    </li>
                                    <li><hr class="dropdown-divider"></li>
                                    <li><a class="dropdown-item" href="{{ route('profile.show') }}"><i class="fa fa-user me-2"></i>Profile</a></li>
                                    <li><a class="dropdown-item" href="{{ url('/home') }}"><i class="fa fa-gauge me-2"></i>Dashboard</a></li>
                                    @if(auth()->user()->isBuyer() || auth()->user()->isVendor())
                                        <li><a class="dropdown-item" href="{{ route('jobs.index') }}">My Jobs</a></li>
                                    @endif
                                    @if(auth()->user()->isAdmin())
                                        <li><a class="dropdown-item" href="{{ route('admin.settings') }}"><i class="fa fa-cog me-2"></i>Settings</a></li>
                                        <li><a class="dropdown-item" href="{{ route('admin.tokens') }}"><i class="fa fa-shield me-2"></i>Token Admin</a></li>
                                        <li><a class="dropdown-item" href="{{ route('admin.faqs.index') }}"><i class="fa fa-question-circle me-2"></i>FAQs</a></li>
                                        <li><a class="dropdown-item" href="{{ route('admin.content-sections.index') }}"><i class="fa fa-file-alt me-2"></i>Page Content</a></li>
                                        <li><a class="dropdown-item" href="{{ route('admin.analytics') }}">Analytics</a></li>
                                    @endif
                                    <li><hr class="dropdown-divider"></li>
                                    <li>
                                        <a class="dropdown-item" href="{{ route('logout') }}"
                                           onclick="event.preventDefault(); document.getElementById('logout-form-dash').submit();">
                                            <i class="fa fa-sign-out-alt me-2"></i>Log out
                                        </a>
                                        <form id="logout-form-dash" action="{{ route('logout') }}" method="POST" class="d-none">@csrf</form>
                                    </li>
                                </ul>
                            </li>
                        </ul>
                    @endif
                @endauth
            </div>
        </nav>
    </div>
</header>

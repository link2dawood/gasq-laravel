<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', config('app.name', 'GASQ'))</title>
    <link rel="dns-prefetch" href="//fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=Nunito" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="{{ mix('css/app.css') }}" rel="stylesheet">
    <link href="{{ asset('css/gasq-theme.css') }}?v={{ @filemtime(public_path('css/gasq-theme.css')) ?: '1' }}" rel="stylesheet">
    @stack('styles')
</head>
<body class="bg-gasq-background">
    <div id="app">
        @php
            $headerVariant = trim($__env->yieldContent('header_variant'));
            if ($headerVariant === '') {
                $headerVariant = (auth()->check() && request()->is(
                    'home',
                    'profile*',
                    'credits*',
                    'account-balance*',
                    'discovery-call*',
                    'jobs*',
                    'admin*',
                    '_backend*',
                    'gasq-tco-calculator',
                    'absorbed-rate-calculator',
                    'government-contract-calculator',
                    'keeps-doors-open-calculator',
                    'calculator',
                    'instant-estimator',
                    'open-bid-offer',
                    'post-job',
                    'post-coverage-schedule',
                    'vendor-form',
                    'register/*',
                    'security-quote',
                ))
                    ? 'dashboard'
                    : 'site';
            }
        @endphp

        @php
            $dashboardCalculatorRoute = auth()->check() && auth()->user()->isBuyer()
                ? route('calculator.index')
                : route('main-menu-calculator.index');
        @endphp

        @if($headerVariant === 'dashboard')
            @include('partials.header-dashboard')
        @else
        <header class="gasq-navbar sticky top-0 z-50">
            <div class="container px-4">
                <nav class="navbar navbar-expand-md navbar-light py-4 align-items-center">
                    <a class="navbar-brand d-flex align-items-center" href="{{ url('/') }}">
                        <x-logo height="48" class="d-inline-block" />
                    </a>
                    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarSupportedContent" aria-controls="navbarSupportedContent" aria-expanded="false" aria-label="Toggle navigation">
                        <span class="navbar-toggler-icon"></span>
                    </button>
                    <div class="collapse navbar-collapse gasq-navbar-nav-right" id="navbarSupportedContent">
                        <ul class="navbar-nav ms-auto align-items-center">
                            <li class="nav-item"><a class="nav-link text-gasq-muted" href="{{ url('/#buyers') }}">For Buyers</a></li>
                            <li class="nav-item"><a class="nav-link text-gasq-muted" href="{{ url('/#sellers') }}">For Sellers</a></li>
                            <li class="nav-item"><a class="nav-link text-gasq-muted" href="{{ url('/#how-it-works') }}">How It Works</a></li>
                            <li class="nav-item"><a class="nav-link text-gasq-muted" href="{{ route('job-board') }}">Job Board</a></li>
                            @include('partials.nav-calculators-dropdown', ['toggleId' => 'navbarCalculatorsSite'])
                            @guest
                                <li class="nav-item"><a class="nav-link text-gasq-muted" href="{{ route('login') }}">Login</a></li>
                            @else
                                <li class="nav-item dropdown">
                                    <a class="btn btn-outline-primary btn-sm d-flex align-items-center gap-2" href="#" data-bs-toggle="dropdown" id="walletDropdown">
                                        <i class="fa fa-wallet"></i>
                                        <span class="fw-semibold">{{ isset($walletBalance) ? $walletBalance : 0 }} Credits</span>
                                    </a>
                                    <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="walletDropdown" style="min-width: 14rem;">
                                        <li class="px-3 py-2">
                                            <p class="small mb-0 text-gasq-muted">Your Balance</p>
                                            <p class="mb-0 fs-5 fw-bold text-primary">{{ isset($walletBalance) ? $walletBalance : 0 }} Credits</p>
                                        </li>
                                        <li><hr class="dropdown-divider"></li>
                                        <li><a class="dropdown-item" href="{{ route('credits') }}"><i class="fa fa-plus me-2"></i>Purchase Credits</a></li>
                                        <li><a class="dropdown-item" href="{{ route('account-balance') }}"><i class="fa fa-history me-2"></i>Transaction History</a></li>
                                    </ul>
                                </li>
                                <li class="nav-item"><a class="nav-link text-gasq-muted" href="{{ route('discovery-call.index') }}">Discovery Call</a></li>
                                <li class="nav-item dropdown">
                                    <a class="nav-link dropdown-toggle d-flex align-items-center" href="#" id="navbarDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                                        <img src="{{ Auth::user()->avatar_url }}" alt="" class="rounded-circle me-2" width="32" height="32"> {{ Auth::user()->name }}
                                    </a>
                                    <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="navbarDropdown">
                                        <li class="px-3 py-2">
                                            <p class="small fw-medium mb-0">Account</p>
                                            <p class="small text-gasq-muted mb-0">{{ Auth::user()->email }}</p>
                                        </li>
                                        <li><hr class="dropdown-divider"></li>
                                        <li><a class="dropdown-item" href="{{ route('credits') }}"><i class="fa fa-coins me-2"></i>Buy Credits</a></li>
                                        <li><a class="dropdown-item" href="{{ route('profile.show') }}"><i class="fa fa-user me-2"></i>Profile</a></li>
                                        <li><a class="dropdown-item" href="{{ $dashboardCalculatorRoute }}"><i class="fa fa-calculator me-2"></i>{{ auth()->user()->isBuyer() ? 'Estimator Hub' : 'Dashboard' }}</a></li>
                                        @if(auth()->user()->isBuyer() || auth()->user()->isVendor())
                                            <li><a class="dropdown-item" href="{{ route('jobs.index') }}">My Jobs</a></li>
                                        @endif
                                        @if(auth()->user()->isAdmin())
                                            <li><a class="dropdown-item" href="{{ route('admin.settings') }}"><i class="fa fa-cog me-2"></i>Settings</a></li>
                                            <li><a class="dropdown-item" href="{{ route('admin.tokens') }}"><i class="fa fa-shield me-2"></i>Token Admin</a></li>
                                            <li><a class="dropdown-item" href="{{ route('admin.twilio.show') }}"><i class="fa fa-comment-sms me-2"></i>Twilio Health</a></li>
                                            <li><a class="dropdown-item" href="{{ route('admin.coupons.index') }}"><i class="fa fa-ticket-alt me-2"></i>Coupons</a></li>
                                            <li><a class="dropdown-item" href="{{ route('admin.faqs.index') }}"><i class="fa fa-question-circle me-2"></i>FAQs</a></li>
                                            <li><a class="dropdown-item" href="{{ route('admin.content-sections.index') }}"><i class="fa fa-file-alt me-2"></i>Page Content</a></li>
                                            <li><a class="dropdown-item" href="{{ route('admin.analytics') }}">Analytics</a></li>
                                        @endif
                                        <li><hr class="dropdown-divider"></li>
                                        <li>
                                            <a class="dropdown-item" href="{{ route('logout') }}" onclick="event.preventDefault(); document.getElementById('logout-form').submit();"><i class="fa fa-sign-out-alt me-2"></i>Log out</a>
                                            <form id="logout-form" action="{{ route('logout') }}" method="POST" class="d-none">@csrf</form>
                                        </li>
                                    </ul>
                                </li>
                            @endguest
                        </ul>
                    </div>
                </nav>
            </div>
        </header>
        @endif

        <main class="@yield('main_class', 'py-4')">
            @if(session('success'))
                <div class="container"><div class="alert alert-success alert-dismissible fade show" role="alert">{{ session('success') }}<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div></div>
            @endif
            @if(session('error'))
                <div class="container"><div class="alert alert-danger alert-dismissible fade show" role="alert">{{ session('error') }}<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div></div>
            @endif
            @if(session('needs_credits'))
                <button id="needsCreditsTrigger" type="button" class="d-none" data-bs-toggle="modal" data-bs-target="#needsCreditsModal"></button>
                <div class="modal fade" id="needsCreditsModal" tabindex="-1" aria-hidden="true">
                    <div class="modal-dialog modal-dialog-centered">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h5 class="modal-title">More credits needed</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                            </div>
                            <div class="modal-body">
                                <p class="mb-1">Generating this report costs <strong>{{ session('needs_credits') }} credits</strong>, and your balance is too low.</p>
                                <p class="text-muted small mb-0">Add credits to download or email your report.</p>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Not now</button>
                                <a href="{{ route('credits') }}" class="btn btn-primary">Add Credits</a>
                            </div>
                        </div>
                    </div>
                </div>
                <script>document.addEventListener('DOMContentLoaded',function(){var t=document.getElementById('needsCreditsTrigger');if(t)t.click();});</script>
            @endif
            @yield('content')
        </main>
        @hasSection('footer')
            @yield('footer')
        @endif
    </div>
    @php
        $gasqMasterInputs = $masterInputs ?? null;
        $gasqCalculatorState = [
            'type' => $savedCalculatorType ?? null,
            'scenario' => $savedCalculatorScenario ?? null,
            'result' => $savedCalculatorResult ?? null,
        ];

        if (is_array($gasqMasterInputs) && is_array($gasqCalculatorState['scenario'] ?? null)) {
            $scenario = $gasqCalculatorState['scenario'];
            $meta = (array) ($scenario['meta'] ?? []);
            $meta['inputs'] = array_replace($gasqMasterInputs, (array) ($meta['inputs'] ?? []));
            $scenario['meta'] = $meta;
            $gasqCalculatorState['scenario'] = $scenario;
        }
    @endphp
    <script src="{{ mix('js/app.js') }}"></script>
    <script>
        window.__gasqCalculatorState = {{ \Illuminate\Support\Js::from($gasqCalculatorState) }};
        window.__gasqMasterInputs = {{ \Illuminate\Support\Js::from($gasqMasterInputs) }};
        window.GASQ = Object.assign(window.GASQ || {}, {
            getMasterInput(key, fallback = null) {
                const inputs = window.__gasqMasterInputs || {};
                return Object.prototype.hasOwnProperty.call(inputs, key) ? inputs[key] : fallback;
            },
            getMasterPercentInput(key, fallback = null) {
                const value = this.getMasterInput(key, fallback);
                if (value === null || value === undefined || value === '') {
                    return fallback;
                }

                const numeric = Number(value);
                return Number.isFinite(numeric) ? numeric * 100 : fallback;
            }
        });
    </script>
    @auth
    <form id="auto-logout-form" action="{{ route('logout') }}" method="POST" class="d-none">
        @csrf
    </form>
    <script>
        (function () {
            const lifetimeMs = {{ (int) config('session.lifetime') * 60 * 1000 }};
            if (lifetimeMs <= 0) return;

            let timeoutId = null;
            const storageKey = 'gasq_last_activity';

            function submitLogout() {
                try { localStorage.removeItem(storageKey); } catch (e) {}
                const form = document.getElementById('auto-logout-form');
                if (form) {
                    form.submit();
                } else {
                    window.location.href = "{{ route('login') }}";
                }
            }

            function readLastActivity() {
                try {
                    const raw = localStorage.getItem(storageKey);
                    const parsed = raw ? parseInt(raw, 10) : NaN;
                    return Number.isFinite(parsed) ? parsed : Date.now();
                } catch (e) {
                    return Date.now();
                }
            }

            function writeLastActivity(ts) {
                try { localStorage.setItem(storageKey, String(ts)); } catch (e) {}
            }

            function scheduleCheck() {
                if (timeoutId !== null) clearTimeout(timeoutId);
                const elapsed = Date.now() - readLastActivity();
                const remaining = lifetimeMs - elapsed;
                if (remaining <= 0) {
                    submitLogout();
                    return;
                }
                timeoutId = setTimeout(scheduleCheck, Math.min(remaining, 30000));
            }

            function bumpActivity() {
                writeLastActivity(Date.now());
                scheduleCheck();
            }

            ['click', 'keydown', 'mousemove', 'scroll', 'touchstart'].forEach(function (evt) {
                document.addEventListener(evt, function () {
                    const now = Date.now();
                    if (now - readLastActivity() > 30000) {
                        writeLastActivity(now);
                    }
                }, { passive: true });
            });

            document.addEventListener('visibilitychange', function () {
                if (!document.hidden) scheduleCheck();
            });

            window.addEventListener('storage', function (e) {
                if (e.key === storageKey) scheduleCheck();
            });

            bumpActivity();
        })();
    </script>
    @endauth

    <script>
        // Mobile nav: start collapsed, let the user open it, and auto-close it
        // when they tap a link or scroll the page so it doesn't linger on screen.
        (function () {
            var nav = document.getElementById('navbarSupportedContent');
            var toggler = document.querySelector('.gasq-navbar .navbar-toggler');
            if (!nav || !toggler) return;

            function closeNav() {
                if (nav.classList.contains('show')) toggler.click(); // let Bootstrap hide + keep state
            }

            // Tapping a real navigation link or dropdown item closes the menu
            // (dropdown toggles are left alone so submenus still open).
            nav.querySelectorAll('a.nav-link:not(.dropdown-toggle), a.dropdown-item').forEach(function (a) {
                a.addEventListener('click', closeNav);
            });

            window.addEventListener('scroll', closeNav, { passive: true });
        })();
    </script>

    @stack('scripts')
</body>
</html>

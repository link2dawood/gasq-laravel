@extends('layouts.app')

@section('title', $job->title)

@section('content')
@php
    $isLoggedIn        = auth()->check();
    $isOwner           = $isLoggedIn && $job->user_id === auth()->id();
    $isVendorViewer    = $isLoggedIn && auth()->user()->isVendor() && ! $isOwner;
    $userBid           = $isVendorViewer ? $job->bids->firstWhere('user_id', auth()->id()) : null;
    $offerOpen         = $job->isOfferOpen();
    $mapsKey           = config('services.google.maps_api_key');
    $showMap           = $job->hasGeoPoint() && $mapsKey;
    $responseTarget    = 5;
    $respondedCount    = $job->bids->filter(fn($b) => $b->hasVendorResponded())->count();
    $acceptedCount     = $job->bids->filter(fn($b) => $b->vendorAccepted())->count();
    $declinedCount     = $job->bids->filter(fn($b) => $b->vendorDeclined())->count();
    $vrStatus          = $userBid?->vendor_response_status ?? 'pending';
    $vrAccepted        = $vrStatus === 'accepted';
    $vrDeclined        = $vrStatus === 'declined';
    $progressPct       = min(100, ($respondedCount / $responseTarget) * 100);
    $offerStatusLabel  = $offerOpen ? 'Open' : 'Closed';
    $offerStatusBadge  = $offerOpen ? 'success' : 'secondary';
    $isHired           = $job->isHired();
    $isClosed          = $job->isClosed();
    $hiredBid          = $job->hired_bid_id ? $job->bids->firstWhere('id', $job->hired_bid_id) : null;
    $hiredVendorName   = $hiredBid?->user?->name ?? $job->hired_external_name;
@endphp

<div class="container py-4 px-4">

    {{-- Breadcrumb --}}
    <nav aria-label="breadcrumb" class="mb-3">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="{{ route('job-board') }}">Job Board</a></li>
            <li class="breadcrumb-item active">{{ Str::limit($job->title, 40) }}</li>
        </ol>
    </nav>

    {{-- Flash messages --}}
    @if(session('success'))
        <div class="alert alert-success" role="alert">
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            {{ session('success') }}
        </div>
    @endif
    @if(session('error'))
        <div class="alert alert-danger" role="alert">
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            {{ session('error') }}
        </div>
    @endif

    {{-- Title row --}}
    <div class="d-flex flex-wrap justify-content-between align-items-start gap-2 mb-4">
        <h1 class="gasq-page-title mb-0">{{ $job->title }}</h1>
        @if($isOwner)
            <div class="d-flex gap-2">
                @if(! $isHired && ! $isClosed)
                    <button type="button" class="btn btn-outline-secondary btn-sm" data-bs-toggle="modal" data-bs-target="#closeJobModal">Close job</button>
                @endif
                <a href="{{ route('jobs.edit', $job) }}" class="btn btn-outline-primary btn-sm">Edit</a>
                <form action="{{ route('jobs.destroy', $job) }}" method="POST" class="d-inline" onsubmit="return confirm('Remove this job?');">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn btn-outline-danger btn-sm">Remove</button>
                </form>
            </div>
        @endif
    </div>

    {{-- Hired / closed banner --}}
    @if($isHired)
        <div class="alert alert-success d-flex flex-wrap align-items-center justify-content-between gap-2 mb-4" id="hired-banner">
            <div>
                <i class="fa fa-circle-check me-1"></i>
                <strong>Hired:</strong>
                <span id="hired-vendor-name">{{ $hiredVendorName ?? 'A professional was hired' }}</span>
                @if($hiredBid?->user?->company)
                    <span class="text-muted">({{ $hiredBid->user->company }})</span>
                @endif
                @if($job->hired_at)
                    <span class="text-muted small ms-2">on {{ $job->hired_at->format('M j, Y') }}</span>
                @endif
            </div>
            @if($hiredBid && $hiredBid->user)
                <a href="{{ route('vendor-profile.show', $hiredBid->user) }}" class="btn btn-sm btn-outline-success">View vendor</a>
            @endif
        </div>
    @elseif($isClosed)
        <div class="alert alert-secondary mb-4">
            <i class="fa fa-circle-xmark me-1"></i> This job is closed.
            @if($job->close_reason)
                <span class="text-muted small">(Reason: {{ str_replace('_', ' ', $job->close_reason) }})</span>
            @endif
        </div>
    @endif

    {{-- ═══ TOP VENDOR RESPONSE PANEL ═══ --}}
    @if($isVendorViewer)
        <div class="card gasq-card mb-4 border-2 @if($vrAccepted) border-success @endif @if($vrDeclined) border-secondary @endif">
            <div class="card-body">
                <div class="d-flex flex-column flex-lg-row justify-content-between align-items-lg-start gap-3">
                    <div class="flex-grow-1">
                        <div class="d-flex flex-wrap align-items-center gap-2 mb-2">
                            <span class="small text-uppercase fw-semibold text-gasq-muted">Your Response</span>
                            <span class="badge bg-{{ $offerStatusBadge }}">Offer {{ $offerStatusLabel }}</span>
                            <span class="badge bg-light text-dark border">{{ $respondedCount }}/{{ $responseTarget }} responded</span>
                            <span class="badge bg-success-subtle text-success-emphasis">{{ $acceptedCount }} accepted</span>
                            <span class="badge bg-secondary-subtle text-secondary-emphasis">{{ $declinedCount }} declined</span>
                        </div>
                        {{-- Progress bar --}}
                        <div class="progress mb-3" style="height: 6px;" title="{{ $respondedCount }}/{{ $responseTarget }} vendors responded">
                            <div class="progress-bar bg-success" role="progressbar" style="width: {{ $progressPct }}%"></div>
                        </div>
                        @if($vrAccepted)
                            <p class="mb-0 text-success fw-semibold">
                                <i class="fa fa-circle-check me-1"></i>You accepted this job offer.
                                @if($offerOpen) You can change your response while the offer remains open.@endif
                            </p>
                        @endif
                        @if($vrDeclined)
                            <p class="mb-0 text-gasq-muted">
                                <i class="fa fa-circle-xmark me-1"></i>You declined this job offer.
                                @if($offerOpen) You can change your response while the offer remains open.@endif
                            </p>
                        @endif
                        @if(! $vrAccepted && ! $vrDeclined)
                            <p class="mb-0 text-gasq-muted">Review this job offer and record your response. We are tracking toward a target of {{ $responseTarget }} vendor responses.</p>
                        @endif
                    </div>
                    <div class="d-flex flex-wrap gap-2 flex-shrink-0">
                        <form action="{{ route('bids.offer-response', $job) }}" method="POST">
                            @csrf
                            <input type="hidden" name="status" value="accepted">
                            <button type="submit" class="btn btn-success @if($vrAccepted) active @endif" @disabled(! $offerOpen)>
                                <i class="fa fa-check me-1"></i>Accept
                            </button>
                        </form>
                        <form action="{{ route('bids.offer-response', $job) }}" method="POST">
                            @csrf
                            <input type="hidden" name="status" value="declined">
                            <button type="submit" class="btn btn-outline-secondary @if($vrDeclined) active @endif" @disabled(! $offerOpen)>
                                <i class="fa fa-xmark me-1"></i>Decline
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    @endif

    @if(! $isLoggedIn)
        <div class="alert alert-info mb-4">
            <a href="{{ route('login') }}" class="text-primary text-decoration-none">Sign in</a> as a vendor to accept or decline this job offer.
        </div>
    @endif

    {{-- Job details --}}
    <div class="card gasq-card mb-4">
        <div class="card-body">
            <p class="text-gasq-muted small mb-2">
                Posted by {{ $job->user->name }}
                @if($job->user->company)({{ $job->user->company }})@endif
                · {{ $job->created_at->format('M j, Y') }}
            </p>
            @if($job->location)
                <p class="mb-1"><strong>Location:</strong> {{ $job->location }}</p>
            @endif
            @if($job->hasGeoPoint())
                <p class="mb-1 small text-gasq-muted">
                    <i class="fa fa-map-pin me-1"></i>{{ number_format((float) $job->latitude, 5) }}, {{ number_format((float) $job->longitude, 5) }}
                </p>
            @endif
            @if($job->category)
                <p class="mb-1"><strong>Category:</strong> {{ $job->category }}</p>
            @endif
            @if($job->service_start_date || $job->service_end_date)
                <p class="mb-1"><strong>Period:</strong> {{ $job->service_start_date?->format('M j, Y') }} – {{ $job->service_end_date?->format('M j, Y') }}</p>
            @endif
            @if($job->budget_min || $job->budget_max)
                <p class="mb-1"><strong>Budget:</strong> ${{ number_format($job->budget_min ?? 0) }} – ${{ number_format($job->budget_max ?? 0) }}</p>
            @endif
            @if($job->guards_per_shift)
                <p class="mb-1"><strong>Guards per shift:</strong> {{ $job->guards_per_shift }}</p>
            @endif
            @if($job->description)
                <hr>
                <div>{!! nl2br(e($job->description)) !!}</div>
            @endif
            @if($job->property_type)
                <p class="mb-0 mt-2"><strong>Property type:</strong> {{ $job->property_type }}</p>
            @endif
            @if($job->special_requirements && count($job->special_requirements) > 0)
                <p class="mb-0 mt-2"><strong>Special requirements:</strong></p>
                <ul class="mb-0">
                    @foreach($job->special_requirements as $req)
                        <li>{{ $req }}</li>
                    @endforeach
                </ul>
            @endif
        </div>
    </div>

    {{-- Map --}}
    @if($showMap)
        <div class="card gasq-card mb-4">
            <div class="card-header">
                <h5 class="card-title mb-0">Job site map</h5>
            </div>
            <div class="card-body">
                <div id="job-show-map" class="rounded border" style="height: 280px; min-height: 200px; border-color: var(--gasq-border);"></div>
            </div>
        </div>
        @push('scripts')
            <script>
                window.initJobShowMap = function () {
                    var el = document.getElementById('job-show-map');
                    if (!el || !window.google || !google.maps) { return; }
                    var center = { lat: {{ (float) $job->latitude }}, lng: {{ (float) $job->longitude }} };
                    var map = new google.maps.Map(el, { zoom: 14, center: center, mapTypeControl: true });
                    new google.maps.Marker({ position: center, map: map, title: @json(Str::limit($job->title, 80)) });
                };
            </script>
            <script src="https://maps.googleapis.com/maps/api/js?key={{ $mapsKey }}&callback=initJobShowMap" async defer></script>
        @endpush
    @endif

    {{-- Vendor responses list --}}
    <h2 class="gasq-card-title-lg mb-3">Vendor Responses (<span data-live="responded">{{ $respondedCount }}</span>/{{ $responseTarget }})</h2>

    @if($job->bids->isEmpty())
        <p class="text-gasq-muted" id="bids-empty">No vendor responses yet.</p>
    @else
        <div class="row g-3 mb-4" id="bids-list" data-live-root>
            @foreach($job->bids as $bid)
                @php
                    $bidVrStatus  = $bid->vendor_response_status ?? 'pending';
                    $bidBadgeCls  = $bid->vendorAccepted() ? 'success' : ($bid->vendorDeclined() ? 'secondary' : 'warning');
                    $bidIsHired   = $job->hired_bid_id === $bid->id;
                @endphp
                <div class="col-12" data-bid-row="{{ $bid->id }}">
                    <div class="card gasq-card @if($bidIsHired) border-success border-2 @endif">
                        <div class="card-body">
                            @if($bidIsHired)
                                <div class="mb-2"><span class="badge bg-success"><i class="fa fa-circle-check me-1"></i>Hired</span></div>
                            @endif
                            <div class="d-flex flex-wrap justify-content-between align-items-start gap-2 mb-2">
                                <h6 class="card-title mb-0">
                                    <a href="{{ route('vendor-profile.show', $bid->user) }}" class="text-decoration-none">{{ $bid->user->name }}</a>
                                    @if($bid->user->company)
                                        <span class="text-gasq-muted fw-normal">({{ $bid->user->company }})</span>
                                    @endif
                                </h6>
                                <span class="badge bg-{{ $bidBadgeCls }}">{{ $bidVrStatus }}</span>
                            </div>
                            @if($bid->message)
                                <p class="mb-1 small">{{ $bid->message }}</p>
                            @endif
                            @if($bid->proposal)
                                <p class="mb-2 small text-gasq-muted">{{ Str::limit($bid->proposal, 300) }}</p>
                            @endif
                            @if($bid->amount && (float) $bid->amount > 0)
                                <p class="fw-bold mb-1">${{ number_format((float) $bid->amount, 2) }}</p>
                            @endif
                            @if($bid->hasCounterOffer())
                                <div class="border-start border-3 border-primary ps-2 py-1 mb-2 small">
                                    <strong>Counter offer:</strong> ${{ number_format($bid->counter_offer_amount, 2) }}
                                    @if($bid->counter_offer_message)
                                        <br>{{ $bid->counter_offer_message }}
                                    @endif
                                    <br><span class="text-gasq-muted">{{ $bid->counter_offer_at?->format('M j, Y H:i') }}</span>
                                </div>
                            @endif
                            @if($isOwner && ! $isHired && ! $isClosed)
                                <div class="d-flex flex-wrap gap-2 align-items-center mt-2">
                                    <form action="{{ route('jobs.hire', $job) }}" method="POST" class="d-inline" onsubmit="return confirm('Hire {{ addslashes($bid->user->name) }}? This will reject all other bids.');">
                                        @csrf
                                        <input type="hidden" name="bid_id" value="{{ $bid->id }}">
                                        <input type="hidden" name="source" value="platform">
                                        <button type="submit" class="btn btn-sm btn-primary"><i class="fa fa-handshake me-1"></i>Hire</button>
                                    </form>
                                    @if($bid->isPending())
                                    <form action="{{ route('bids.respond', $bid) }}" method="POST" class="d-inline">
                                        @csrf
                                        <input type="hidden" name="status" value="accepted">
                                        <button type="submit" class="btn btn-sm btn-success">Accept Bid</button>
                                    </form>
                                    <form action="{{ route('bids.respond', $bid) }}" method="POST" class="d-inline">
                                        @csrf
                                        <input type="hidden" name="status" value="rejected">
                                        <button type="submit" class="btn btn-sm btn-outline-secondary">Reject Bid</button>
                                    </form>
                                    <button type="button" class="btn btn-sm btn-outline-primary" data-bs-toggle="collapse" data-bs-target="#counter-{{ $bid->id }}" aria-expanded="false">Counter offer</button>
                                    <div class="collapse w-100 mt-2" id="counter-{{ $bid->id }}">
                                        <div class="card gasq-card card-body">
                                            <form action="{{ route('bids.counter-offer', $bid) }}" method="POST">
                                                @csrf
                                                <div class="mb-2">
                                                    <label class="form-label small">Amount ($)</label>
                                                    <input type="number" name="counter_offer_amount" class="form-control form-control-sm" step="0.01" min="0" value="{{ old('counter_offer_amount', $bid->counter_offer_amount) }}" required>
                                                </div>
                                                <div class="mb-2">
                                                    <label class="form-label small">Message (optional)</label>
                                                    <textarea name="counter_offer_message" class="form-control form-control-sm" rows="2">{{ old('counter_offer_message', $bid->counter_offer_message) }}</textarea>
                                                </div>
                                                <button type="submit" class="btn btn-sm btn-primary">Send counter offer</button>
                                            </form>
                                        </div>
                                    </div>
                                    @endif
                                </div>
                            @endif
                            @if($bid->vendor_responded_at)
                                <small class="text-gasq-muted d-block mt-2">Responded {{ $bid->vendor_responded_at->format('M j, Y H:i') }}</small>
                            @endif
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    @endif

    {{-- ═══ BOTTOM VENDOR RESPONSE PANEL ═══ --}}
    <div class="card gasq-card mt-2">
        <div class="card-body">
            <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3">
                <div>
                    <div class="fw-semibold mb-1">{{ $respondedCount }}/{{ $responseTarget }} vendors responded</div>
                    <div class="progress mb-2" style="height: 6px; min-width: 180px;">
                        <div class="progress-bar bg-success" role="progressbar" style="width: {{ $progressPct }}%"></div>
                    </div>
                    <small class="text-gasq-muted">{{ $acceptedCount }} accepted · {{ $declinedCount }} declined · Offer: {{ $offerStatusLabel }}</small>
                </div>
                @if($isVendorViewer)
                    <div class="d-flex flex-wrap gap-2 flex-shrink-0">
                        <form action="{{ route('bids.offer-response', $job) }}" method="POST">
                            @csrf
                            <input type="hidden" name="status" value="accepted">
                            <button type="submit" class="btn btn-success @if($vrAccepted) active @endif" @disabled(! $offerOpen)>
                                <i class="fa fa-check me-1"></i>Accept
                            </button>
                        </form>
                        <form action="{{ route('bids.offer-response', $job) }}" method="POST">
                            @csrf
                            <input type="hidden" name="status" value="declined">
                            <button type="submit" class="btn btn-outline-secondary @if($vrDeclined) active @endif" @disabled(! $offerOpen)>
                                <i class="fa fa-xmark me-1"></i>Decline
                            </button>
                        </form>
                    </div>
                @endif
                @if(! $isLoggedIn)
                    <a href="{{ route('login') }}" class="btn btn-outline-primary">Sign in to respond</a>
                @endif
            </div>
        </div>
    </div>

    @push('scripts')
    <script>
    (function () {
        const FRAGMENT_URL = @json(route('jobs.bids-fragment', $job));
        const RESPONSE_TARGET = {{ $responseTarget }};
        let lastSig = '';

        function fmtMoney(v) {
            if (v === null || v === undefined || isNaN(v)) return '';
            return '$' + Number(v).toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 });
        }

        function statusBadge(b) {
            if (b.is_hired) return '<span class="badge bg-success">Hired</span>';
            const vr = b.vendor_response_status || 'pending';
            const cls = vr === 'accepted' ? 'success' : (vr === 'declined' || vr === 'rejected' ? 'secondary' : 'warning');
            return `<span class="badge bg-${cls}">${vr}</span>`;
        }

        function updateLiveCounts(counts) {
            const el = document.querySelector('[data-live="responded"]');
            if (el) el.textContent = counts.responded;
        }

        function updateHiredBanner(jobInfo, bids) {
            const banner = document.getElementById('hired-banner');
            const nameEl = document.getElementById('hired-vendor-name');
            if (!jobInfo.is_hired) return;
            let name = jobInfo.hired_external_name;
            if (!name && jobInfo.hired_bid_id) {
                const b = bids.find(x => x.id === jobInfo.hired_bid_id);
                name = b ? b.vendor_name : null;
            }
            if (banner && nameEl && name) nameEl.textContent = name;
            if (!banner && name) {
                // Reload to render full banner — first-time hire
                location.reload();
            }
        }

        async function poll() {
            try {
                const r = await fetch(FRAGMENT_URL, { credentials: 'same-origin', headers: { 'Accept': 'application/json' }});
                if (!r.ok) return;
                const data = await r.json();
                const sig = JSON.stringify({ s: data.job.status, h: data.job.hired_bid_id, c: data.counts, b: data.bids.map(x => [x.id, x.status, x.vendor_response_status, x.is_hired, x.counter_offer_amount]) });
                if (sig === lastSig) return;
                lastSig = sig;
                updateLiveCounts(data.counts);
                updateHiredBanner(data.job, data.bids);

                // Update each bid row badge & status pill if present in DOM
                data.bids.forEach(b => {
                    const row = document.querySelector(`[data-bid-row="${b.id}"]`);
                    if (!row) { return; }
                    const badge = row.querySelector('.card-body > .d-flex .badge');
                    if (badge) badge.outerHTML = statusBadge(b);
                });

                // If status changed to closed/awarded and we are not yet showing it, reload
                if ((data.job.is_closed || data.job.is_hired) && !document.getElementById('hired-banner') && !document.querySelector('.alert.alert-secondary')) {
                    location.reload();
                }
            } catch (e) { /* noop */ }
        }

        // Initial poll baseline + interval
        poll();
        setInterval(poll, 10000);
    })();
    </script>
    @endpush

    @if($isOwner && ! $isHired && ! $isClosed)
        {{-- Close Job modal: two-step survey --}}
        <div class="modal fade" id="closeJobModal" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content">
                    <form id="closeJobForm" method="POST" action="">
                        @csrf
                        <div class="modal-header">
                            <h5 class="modal-title" id="closeJobTitle">Which professional did you hire?</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body">
                            {{-- Step 1: which professional did you hire --}}
                            <div data-step="who">
                                <div class="vstack gap-2">
                                    @foreach($job->bids as $bid)
                                        <label class="border rounded p-2 d-flex gap-2 align-items-center mb-0">
                                            <input class="form-check-input mt-0" type="radio" name="who_choice" value="bid:{{ $bid->id }}">
                                            <span>{{ $bid->user->name }}@if($bid->user->company) ({{ $bid->user->company }})@endif</span>
                                        </label>
                                    @endforeach
                                    <label class="border rounded p-2 d-flex gap-2 align-items-center mb-0">
                                        <input class="form-check-input mt-0" type="radio" name="who_choice" value="external">
                                        <span>Someone not on the platform</span>
                                    </label>
                                    <label class="border rounded p-2 d-flex gap-2 align-items-center mb-0">
                                        <input class="form-check-input mt-0" type="radio" name="who_choice" value="none">
                                        <span>I didn't hire a professional</span>
                                    </label>
                                </div>
                                <div class="mt-3 d-none" data-external-name>
                                    <label class="form-label small">Who did you hire?</label>
                                    <input type="text" name="external_name" class="form-control form-control-sm" placeholder="Vendor or person's name">
                                </div>
                            </div>

                            {{-- Step 2: why didn't you hire --}}
                            <div data-step="why" class="d-none">
                                <p class="text-gasq-muted small">Help us improve — why didn't you hire a professional?</p>
                                <div class="vstack gap-2">
                                    @php
                                    $reasons = [
                                        'still_deciding' => "I'm still deciding on who to hire",
                                        'diy_or_friend' => 'I decided to do it myself or a friend helped',
                                        'change_of_plan' => 'There was a change of plan',
                                        'on_hold' => "I'm putting the project on hold",
                                        'quotes_not_right' => 'The quotes were not right for me',
                                        'other' => 'Other',
                                    ];
                                    @endphp
                                    @foreach($reasons as $key => $label)
                                        <label class="border rounded p-2 d-flex gap-2 align-items-center mb-0">
                                            <input class="form-check-input mt-0" type="radio" name="close_reason" value="{{ $key }}">
                                            <span>{{ $label }}</span>
                                        </label>
                                    @endforeach
                                </div>
                                <div class="mt-3 d-none" data-reason-other>
                                    <input type="text" name="close_reason_other" class="form-control form-control-sm" placeholder="Tell us more (optional)">
                                </div>
                            </div>

                            <input type="hidden" name="bid_id" value="">
                            <input type="hidden" name="source" value="">
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-link text-secondary" data-bs-dismiss="modal" data-back-btn>Cancel</button>
                            <button type="button" class="btn btn-primary" data-next-btn>Next</button>
                            <button type="submit" class="btn btn-primary d-none" data-submit-btn>Submit</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        @push('scripts')
        <script>
        (function () {
            const modalEl = document.getElementById('closeJobModal');
            if (!modalEl) return;
            const form     = modalEl.querySelector('#closeJobForm');
            const stepWho  = modalEl.querySelector('[data-step="who"]');
            const stepWhy  = modalEl.querySelector('[data-step="why"]');
            const title    = modalEl.querySelector('#closeJobTitle');
            const nextBtn  = modalEl.querySelector('[data-next-btn]');
            const subBtn   = modalEl.querySelector('[data-submit-btn]');
            const extWrap  = modalEl.querySelector('[data-external-name]');
            const otherWrap= modalEl.querySelector('[data-reason-other]');

            const hireUrl  = @json(route('jobs.hire', $job));
            const closeUrl = @json(route('jobs.close', $job));

            function selectedWho() {
                const r = modalEl.querySelector('input[name="who_choice"]:checked');
                return r ? r.value : null;
            }

            modalEl.addEventListener('change', (e) => {
                if (e.target.name === 'who_choice') {
                    extWrap.classList.toggle('d-none', e.target.value !== 'external');
                }
                if (e.target.name === 'close_reason') {
                    otherWrap.classList.toggle('d-none', e.target.value !== 'other');
                }
            });

            nextBtn.addEventListener('click', () => {
                const who = selectedWho();
                if (!who) { alert('Please pick one.'); return; }

                if (who.startsWith('bid:')) {
                    // Hire flow — submit immediately
                    form.action = hireUrl;
                    form.querySelector('[name=bid_id]').value = who.replace('bid:', '');
                    form.querySelector('[name=source]').value = 'platform';
                    form.submit();
                    return;
                }

                if (who === 'external') {
                    const name = (form.querySelector('[name=external_name]')?.value || '').trim();
                    if (!name) { alert('Please tell us who you hired.'); return; }
                    form.action = hireUrl;
                    form.querySelector('[name=source]').value = 'external';
                    form.submit();
                    return;
                }

                // who === 'none' → ask why
                title.textContent = "Why didn't you hire a professional?";
                stepWho.classList.add('d-none');
                stepWhy.classList.remove('d-none');
                nextBtn.classList.add('d-none');
                subBtn.classList.remove('d-none');
                form.action = closeUrl;
                form.querySelector('[name=source]').value = 'none';
            });
        })();
        </script>
        @endpush
    @endif

</div>
@endsection

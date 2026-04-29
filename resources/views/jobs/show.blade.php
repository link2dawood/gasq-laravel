@extends('layouts.app')

@section('title', $job->title)

@section('content')
@php
    $isLoggedIn       = auth()->check();
    $isOwner          = $isLoggedIn && $job->user_id === auth()->id();
    $isVendorBidder   = $isLoggedIn && auth()->user()->isVendor() && ! $isOwner;
    $userBid          = $isVendorBidder ? $job->bids->firstWhere('user_id', auth()->id()) : null;
    $canSubmit        = $isVendorBidder && ! $userBid;
    $hasPending       = $isVendorBidder && $userBid && $userBid->isPending();
    $hasFinalStatus   = $isVendorBidder && $userBid && ! $userBid->isPending();
    $hasCounter       = $hasPending && $userBid->hasCounterOffer();
    $mapsKey          = config('services.google.maps_api_key');
    $showMap          = $job->hasGeoPoint() && $mapsKey;
@endphp

<div class="container py-4 px-4">
    <nav aria-label="breadcrumb" class="mb-3">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="{{ route('job-board') }}">Job Board</a></li>
            <li class="breadcrumb-item active">{{ Str::limit($job->title, 40) }}</li>
        </ol>
    </nav>

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

    <div class="d-flex flex-wrap justify-content-between align-items-start gap-2 mb-4">
        <h1 class="gasq-page-title mb-0">{{ $job->title }}</h1>
        @if($isOwner)
            <div class="d-flex gap-2">
                <a href="{{ route('jobs.edit', $job) }}" class="btn btn-outline-primary btn-sm">Edit</a>
                <form action="{{ route('jobs.destroy', $job) }}" method="POST" class="d-inline" onsubmit="return confirm('Remove this job?');">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn btn-outline-danger btn-sm">Remove</button>
                </form>
            </div>
        @endif
    </div>

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

    <h2 class="gasq-card-title-lg mb-3">Bids ({{ $job->bids->count() }})</h2>

    {{-- Vendor: submit new bid --}}
    @if($canSubmit)
        <div class="card gasq-card mb-4">
            <div class="card-header"><h5 class="card-title mb-0">Submit a bid</h5></div>
            <div class="card-body">
                <form action="{{ route('bids.store', $job) }}" method="POST">
                    @csrf
                    <div class="mb-3">
                        <label class="form-label">Amount ($) <span class="text-danger">*</span></label>
                        <input type="number" name="amount" class="form-control @error('amount') is-invalid @enderror" value="{{ old('amount') }}" step="0.01" min="0" required>
                        @error('amount')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Message</label>
                        <textarea name="message" class="form-control @error('message') is-invalid @enderror" rows="2">{{ old('message') }}</textarea>
                        @error('message')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Proposal</label>
                        <textarea name="proposal" class="form-control @error('proposal') is-invalid @enderror" rows="4">{{ old('proposal') }}</textarea>
                        @error('proposal')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <button type="submit" class="btn btn-primary">Submit Bid</button>
                </form>
            </div>
        </div>
    @endif

    {{-- Vendor: counter offer notice --}}
    @if($hasCounter)
        <div class="alert alert-info mb-4">
            <strong>Counter offer from buyer:</strong> ${{ number_format($userBid->counter_offer_amount, 2) }}
            @if($userBid->counter_offer_message)
                <p class="mb-0 mt-1">{{ $userBid->counter_offer_message }}</p>
            @endif
            <p class="small mb-0 mt-1 text-gasq-muted">Update your bid below to respond.</p>
        </div>
    @endif

    {{-- Vendor: edit pending bid --}}
    @if($hasPending)
        <div class="card gasq-card mb-4">
            <div class="card-header"><h5 class="card-title mb-0">Your bid (pending)</h5></div>
            <div class="card-body">
                <form action="{{ route('bids.update', $userBid) }}" method="POST">
                    @csrf
                    @method('PUT')
                    <div class="mb-3">
                        <label class="form-label">Amount ($) <span class="text-danger">*</span></label>
                        <input type="number" name="amount" class="form-control @error('amount') is-invalid @enderror" value="{{ old('amount', $userBid->amount) }}" step="0.01" min="0" required>
                        @error('amount')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Message</label>
                        <textarea name="message" class="form-control @error('message') is-invalid @enderror" rows="2">{{ old('message', $userBid->message) }}</textarea>
                        @error('message')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Proposal</label>
                        <textarea name="proposal" class="form-control @error('proposal') is-invalid @enderror" rows="4">{{ old('proposal', $userBid->proposal) }}</textarea>
                        @error('proposal')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <button type="submit" class="btn btn-primary">Update Bid</button>
                </form>
            </div>
        </div>
    @endif

    {{-- Vendor: bid accepted/rejected --}}
    @if($hasFinalStatus)
        <div class="alert alert-info mb-4">
            Your bid of ${{ number_format($userBid->amount, 2) }} was <strong>{{ $userBid->status }}</strong>.
        </div>
    @endif

    {{-- Guest prompt --}}
    @if(! $isLoggedIn)
        <p class="text-gasq-muted">
            <a href="{{ route('login') }}" class="text-primary text-decoration-none">Sign in</a> as a vendor to submit a bid.
        </p>
    @endif

    {{-- Bids list --}}
    @if($job->bids->isEmpty())
        <p class="text-gasq-muted">No bids yet.</p>
    @else
        <div class="row g-3">
            @foreach($job->bids as $bid)
                <div class="col-12">
                    <div class="card gasq-card">
                        <div class="card-body">
                            <div class="d-flex flex-wrap justify-content-between align-items-start gap-2 mb-2">
                                <h6 class="card-title mb-0">
                                    <a href="{{ route('vendor-profile.show', $bid->user) }}" class="text-decoration-none">{{ $bid->user->name }}</a>
                                    @if($bid->user->company)<span class="text-gasq-muted fw-normal">({{ $bid->user->company }})</span>@endif
                                </h6>
                                <span class="badge bg-{{ $bid->status === 'accepted' ? 'success' : ($bid->status === 'rejected' ? 'secondary' : 'warning') }}">{{ $bid->status }}</span>
                            </div>
                            <p class="fw-bold mb-1">${{ number_format($bid->amount, 2) }}</p>
                            @if($bid->message)<p class="mb-1 small">{{ $bid->message }}</p>@endif
                            @if($bid->proposal)<p class="mb-2 small text-gasq-muted">{{ Str::limit($bid->proposal, 300) }}</p>@endif
                            @if($bid->hasCounterOffer())
                                <div class="border-start border-3 border-primary ps-2 py-1 mb-2 small">
                                    <strong>Counter offer:</strong> ${{ number_format($bid->counter_offer_amount, 2) }}
                                    @if($bid->counter_offer_message)<br>{{ $bid->counter_offer_message }}@endif
                                    <br><span class="text-gasq-muted">{{ $bid->counter_offer_at?->format('M j, Y H:i') }}</span>
                                </div>
                            @endif
                            <div class="d-flex flex-wrap gap-2 align-items-center">
                                @if($isOwner && $bid->isPending())
                                    <form action="{{ route('bids.respond', $bid) }}" method="POST" class="d-inline">
                                        @csrf
                                        <input type="hidden" name="status" value="accepted">
                                        <button type="submit" class="btn btn-sm btn-success">Accept</button>
                                    </form>
                                    <form action="{{ route('bids.respond', $bid) }}" method="POST" class="d-inline">
                                        @csrf
                                        <input type="hidden" name="status" value="rejected">
                                        <button type="submit" class="btn btn-sm btn-outline-secondary">Reject</button>
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
                            <small class="text-gasq-muted d-block mt-2">Bid submitted {{ $bid->created_at->format('M j, Y H:i') }}</small>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    @endif
</div>
@endsection

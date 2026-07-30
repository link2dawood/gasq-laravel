@extends('layouts.app')

@section('title', 'Interviews — ' . $job->title)
@section('header_variant', 'dashboard')

@section('content')
@php
    $CFG = \App\Models\InterviewConfig::class;
    $statusLabels = [
        $CFG::STATUS_SETUP => ['Setup', 'secondary'],
        $CFG::STATUS_OPEN => ['Scheduling open', 'primary'],
        $CFG::STATUS_INTERVIEWS_COMPLETE => ['Interviews complete', 'info'],
        $CFG::STATUS_CERTIFIED => ['Certified', 'success'],
        $CFG::STATUS_PRICE_REVEALED => ['Price revealed', 'success'],
        $CFG::STATUS_CLOSED => ['Closed', 'dark'],
    ];
    $currentStatus = $config->status ?: $CFG::STATUS_SETUP;
    [$statusText, $statusColor] = $statusLabels[$currentStatus] ?? ['Setup', 'secondary'];
    $deadlineValue = $config->scheduling_deadline ? $config->scheduling_deadline->format('Y-m-d\TH:i') : '';
    $pricingUnlocked = $config->exists ? $config->pricingUnlocked() : false;
    $isSelf = ($config->scheduling_method ?: $CFG::METHOD_SELF) === $CFG::METHOD_SELF;
    $money = fn ($v) => $v === null ? '—' : \App\Support\Currency::format((float) $v);
@endphp

<div class="container py-4 px-4" style="max-width: 1100px;">

    <nav aria-label="breadcrumb" class="mb-3">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="{{ route('jobs.index') }}">My Jobs</a></li>
            <li class="breadcrumb-item"><a href="{{ route('jobs.show', $job) }}">{{ Str::limit($job->title, 40) }}</a></li>
            <li class="breadcrumb-item active">Interviews</li>
        </ol>
    </nav>

    <div class="d-flex flex-wrap justify-content-between align-items-start gap-2 mb-1">
        <h1 class="h3 fw-bold mb-0"><i class="fa fa-calendar-check text-primary me-2"></i>Interview Control Room</h1>
        <span class="badge bg-{{ $statusColor }} align-self-center">{{ $statusText }}</span>
    </div>
    <p class="text-gasq-muted mb-4">
        Interview first, price last. Sealed pricing for <strong>{{ $job->title }}</strong> stays hidden until you certify the interviews are complete.
    </p>

    <div class="accordion mb-4" id="interviewAccordion">

        {{-- ─────────── 1. SETTINGS ─────────── --}}
        <div class="accordion-item">
            <h2 class="accordion-header">
                <button class="accordion-button {{ $config->exists ? 'collapsed' : '' }}" type="button" data-bs-toggle="collapse" data-bs-target="#secSettings">
                    <i class="fa fa-sliders me-2 text-primary"></i>1. Interview Settings
                </button>
            </h2>
            <div id="secSettings" class="accordion-collapse collapse {{ $config->exists ? '' : 'show' }}" data-bs-parent="#interviewAccordion">
                <div class="accordion-body">
                    <form action="{{ route('interviews.config', $job) }}" method="POST">
                        @csrf
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Scheduling method</label>
                            <select name="scheduling_method" class="form-select" required>
                                <option value="{{ $CFG::METHOD_SELF }}" @selected(old('scheduling_method', $config->scheduling_method) === $CFG::METHOD_SELF)>Vendor self-scheduling (recommended)</option>
                                <option value="{{ $CFG::METHOD_ASSIGNED }}" @selected(old('scheduling_method', $config->scheduling_method) === $CFG::METHOD_ASSIGNED)>Buyer-assigned</option>
                                <option value="{{ $CFG::METHOD_GASQ }}" @selected(old('scheduling_method', $config->scheduling_method) === $CFG::METHOD_GASQ)>GASQ-managed</option>
                            </select>
                        </div>
                        <div class="row g-3">
                            <div class="col-sm-6">
                                <label class="form-label fw-semibold">Default format</label>
                                <select name="default_format" class="form-select">
                                    <option value="">Choose…</option>
                                    <option value="virtual" @selected(old('default_format', $config->default_format) === 'virtual')>Virtual (video)</option>
                                    <option value="phone" @selected(old('default_format', $config->default_format) === 'phone')>Phone</option>
                                    <option value="in_person" @selected(old('default_format', $config->default_format) === 'in_person')>In person</option>
                                </select>
                            </div>
                            <div class="col-sm-6">
                                <label class="form-label fw-semibold">Time zone</label>
                                <select name="timezone" class="form-select">
                                    <option value="">Choose…</option>
                                    @foreach($timezones as $tzValue => $tzLabel)
                                        <option value="{{ $tzValue }}" @selected(old('timezone', $config->timezone) === $tzValue)>{{ $tzLabel }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        <div class="row g-3 mt-0">
                            <div class="col-sm-4">
                                <label class="form-label fw-semibold">Interview length</label>
                                <div class="input-group"><input type="number" name="interview_minutes" min="5" max="240" class="form-control" value="{{ old('interview_minutes', $config->interview_minutes ?? 30) }}" required><span class="input-group-text">min</span></div>
                            </div>
                            <div class="col-sm-4">
                                <label class="form-label fw-semibold">Evaluation buffer</label>
                                <div class="input-group"><input type="number" name="evaluation_minutes" min="0" max="240" class="form-control" value="{{ old('evaluation_minutes', $config->evaluation_minutes ?? 15) }}" required><span class="input-group-text">min</span></div>
                            </div>
                            <div class="col-sm-4">
                                <label class="form-label fw-semibold">Gap between</label>
                                <div class="input-group"><input type="number" name="min_gap_minutes" min="0" max="240" class="form-control" value="{{ old('min_gap_minutes', $config->min_gap_minutes ?? 0) }}" required><span class="input-group-text">min</span></div>
                            </div>
                        </div>
                        <div class="mb-3 mt-3">
                            <label class="form-label fw-semibold">Meeting location / link</label>
                            <input type="text" name="location" class="form-control" value="{{ old('location', $config->location) }}" placeholder="Zoom/Meet link, phone bridge, or physical address">
                        </div>
                        <div class="row g-3">
                            <div class="col-sm-6">
                                <label class="form-label fw-semibold">Scheduling deadline</label>
                                <input type="datetime-local" name="scheduling_deadline" class="form-control" value="{{ old('scheduling_deadline', $deadlineValue) }}">
                            </div>
                            <div class="col-sm-6">
                                <label class="form-label fw-semibold"># of vendors to interview</label>
                                <input type="number" name="num_vendors" min="1" max="50" class="form-control" value="{{ old('num_vendors', $config->num_vendors) }}" placeholder="e.g. 3">
                            </div>
                        </div>
                        <div class="mb-3 mt-3">
                            <label class="form-label fw-semibold">Required attendees on your side</label>
                            <textarea name="required_attendees" rows="2" class="form-control" placeholder="Names/roles who must attend">{{ old('required_attendees', $config->required_attendees) }}</textarea>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Price reveal</label>
                            <select name="reveal_method" class="form-select">
                                <option value="">Decide later</option>
                                <option value="all" @selected(old('reveal_method', $config->reveal_method) === 'all')>Reveal all vendors' prices after interviews</option>
                                <option value="finalists" @selected(old('reveal_method', $config->reveal_method) === 'finalists')>Reveal finalists' prices only</option>
                                <option value="selected" @selected(old('reveal_method', $config->reveal_method) === 'selected')>Reveal the selected vendor's price only</option>
                            </select>
                        </div>
                        <div class="form-check mb-4">
                            <input type="hidden" name="disclose_competitor_count" value="0">
                            <input class="form-check-input" type="checkbox" name="disclose_competitor_count" value="1" id="discloseCount" @checked(old('disclose_competitor_count', $config->disclose_competitor_count))>
                            <label class="form-check-label" for="discloseCount">Tell vendors how many competitors they're up against</label>
                        </div>
                        <button type="submit" class="btn btn-primary"><i class="fa fa-floppy-disk me-2"></i>Save Interview Settings</button>
                    </form>
                </div>
            </div>
        </div>

        {{-- ─────────── 2. OPEN SLOTS (self-scheduling) ─────────── --}}
        @if($isSelf)
        <div class="accordion-item">
            <h2 class="accordion-header">
                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#secSlots">
                    <i class="fa fa-clock me-2 text-primary"></i>2. Open Time Slots <span class="badge bg-light text-dark border ms-2">{{ $slots->count() }}</span>
                </button>
            </h2>
            <div id="secSlots" class="accordion-collapse collapse" data-bs-parent="#interviewAccordion">
                <div class="accordion-body">
                    <p class="text-gasq-muted small">Publish the times you're available. Invited vendors each book one open slot.</p>
                    <form action="{{ route('interviews.slots.add', $job) }}" method="POST" class="row g-2 align-items-end mb-3">
                        @csrf
                        <div class="col-sm-8">
                            <label class="form-label fw-semibold small">Add a slot (start time)</label>
                            <input type="datetime-local" name="starts_at" class="form-control @error('starts_at') is-invalid @enderror" required>
                            @error('starts_at')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-sm-4"><button class="btn btn-outline-primary w-100"><i class="fa fa-plus me-1"></i>Add slot</button></div>
                    </form>
                    @forelse($slots as $slot)
                        <div class="d-flex align-items-center justify-content-between border-bottom py-2">
                            <div>
                                <i class="fa fa-calendar-day text-gasq-muted me-2"></i>{{ $slot->starts_at->format('D M j, Y · g:i A') }}
                                @if($slot->isBooked())<span class="badge bg-success-subtle text-success-emphasis ms-2">Booked</span>@else<span class="badge bg-light text-dark border ms-2">Open</span>@endif
                            </div>
                            @unless($slot->isBooked())
                                <form action="{{ route('interviews.slots.delete', [$job, $slot]) }}" method="POST" onsubmit="return confirm('Remove this slot?');">
                                    @csrf @method('DELETE')
                                    <button class="btn btn-sm btn-outline-danger"><i class="fa fa-trash"></i></button>
                                </form>
                            @endunless
                        </div>
                    @empty
                        <p class="text-gasq-muted small mb-0">No slots yet.</p>
                    @endforelse
                </div>
            </div>
        </div>
        @endif

        {{-- ─────────── 3. INVITE VENDORS ─────────── --}}
        <div class="accordion-item">
            <h2 class="accordion-header">
                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#secInvite">
                    <i class="fa fa-user-plus me-2 text-primary"></i>3. Invite Vendors <span class="badge bg-light text-dark border ms-2">{{ $bidVendors->count() }} available</span>
                </button>
            </h2>
            <div id="secInvite" class="accordion-collapse collapse" data-bs-parent="#interviewAccordion">
                <div class="accordion-body">
                    <p class="text-gasq-muted small">Vendors who engaged this job. Invite the ones you want to interview.</p>
                    @forelse($bidVendors as $bid)
                        <div class="d-flex align-items-center justify-content-between border-bottom py-2">
                            <div>
                                <div class="fw-semibold">{{ $bid->user?->name ?? 'Vendor' }}</div>
                                @if($bid->user?->company)<div class="small text-gasq-muted">{{ $bid->user->company }}</div>@endif
                            </div>
                            <form action="{{ route('interviews.invite', $job) }}" method="POST">
                                @csrf
                                <input type="hidden" name="vendor_id" value="{{ $bid->user_id }}">
                                <button class="btn btn-sm btn-primary"><i class="fa fa-paper-plane me-1"></i>Invite</button>
                            </form>
                        </div>
                    @empty
                        <p class="text-gasq-muted small mb-0">All engaged vendors are already invited, or none have bid yet.</p>
                    @endforelse
                </div>
            </div>
        </div>
    </div>

    {{-- ─────────── 4. INTERVIEW STATUS + SCORECARDS ─────────── --}}
    <div class="card gasq-card mb-4">
        <div class="card-header py-3 px-4 d-flex justify-content-between align-items-center">
            <h2 class="h6 fw-bold mb-0"><i class="fa fa-list-check me-2 text-primary"></i>Interviews ({{ $interviews->count() }})</h2>
        </div>
        <div class="card-body p-0">
            @forelse($interviews as $interview)
                <div class="border-bottom p-4">
                    <div class="d-flex flex-wrap justify-content-between align-items-start gap-2">
                        <div>
                            <div class="fw-semibold">{{ $interview->vendor?->name ?? 'Vendor #'.$interview->vendor_id }}</div>
                            @if($interview->vendor?->company)<div class="small text-gasq-muted">{{ $interview->vendor->company }}</div>@endif
                            <div class="small text-gasq-muted mt-1">
                                @if($interview->scheduled_at)<i class="fa fa-clock me-1"></i>{{ $interview->scheduled_at->format('D M j, Y · g:i A') }}@else Not scheduled yet @endif
                            </div>
                        </div>
                        <div class="text-end">
                            <span class="badge bg-light text-dark border text-capitalize">{{ str_replace('_',' ',$interview->status) }}</span>
                            @if($interview->capability_score !== null)
                                <div class="mt-1"><span class="badge bg-info-subtle text-info-emphasis">Capability {{ rtrim(rtrim(number_format((float)$interview->capability_score,1),'0'),'.') }}/100</span></div>
                            @endif
                            <div class="mt-1 small">
                                Price:
                                @if($pricingUnlocked && $interview->price_status === 'revealed' && $interview->bid)
                                    <span class="fw-bold text-success">{{ $money($interview->bid->amount) }}</span>
                                @else
                                    <span class="text-gasq-muted"><i class="fa fa-lock me-1"></i>Sealed</span>
                                @endif
                            </div>
                        </div>
                    </div>

                    {{-- Scorecard --}}
                    <div class="mt-2">
                        <button class="btn btn-sm btn-outline-secondary" type="button" data-bs-toggle="collapse" data-bs-target="#score{{ $interview->id }}">
                            <i class="fa fa-star-half-stroke me-1"></i>{{ $interview->capability_score !== null ? 'Edit scorecard' : 'Score this interview' }}
                        </button>
                        <div class="collapse mt-3" id="score{{ $interview->id }}">
                            <form action="{{ route('interviews.score', [$job, $interview]) }}" method="POST">
                                @csrf
                                @foreach($criteria as $c)
                                    @php $prev = data_get($interview->score_breakdown, $c['key']); @endphp
                                    <div class="row align-items-center g-2 mb-2">
                                        <div class="col-8"><label class="form-label mb-0 small">{{ $c['label'] }} <span class="text-gasq-muted">({{ (int) round($c['weight']*100) }}%)</span></label></div>
                                        <div class="col-4">
                                            <div class="input-group input-group-sm">
                                                <input type="number" step="0.5" min="0" max="10" name="scores[{{ $c['key'] }}]" class="form-control" value="{{ old('scores.'.$c['key'], $prev) }}" required>
                                                <span class="input-group-text">/10</span>
                                            </div>
                                        </div>
                                    </div>
                                @endforeach
                                <div class="mb-2">
                                    <label class="form-label mb-0 small">Notes (private)</label>
                                    <textarea name="buyer_notes" rows="2" class="form-control form-control-sm">{{ old('buyer_notes', $interview->buyer_notes) }}</textarea>
                                </div>
                                <button class="btn btn-sm btn-primary"><i class="fa fa-floppy-disk me-1"></i>Save score</button>
                            </form>
                        </div>
                    </div>
                </div>
            @empty
                <p class="text-gasq-muted small mb-0 p-4">No vendors invited yet. Use “Invite Vendors” above.</p>
            @endforelse
        </div>
    </div>

    {{-- ─────────── 5. CERTIFY + REVEAL ─────────── --}}
    <div class="card gasq-card">
        <div class="card-header py-3 px-4"><h2 class="h6 fw-bold mb-0"><i class="fa fa-unlock-keyhole me-2 text-primary"></i>Certify &amp; Reveal Pricing</h2></div>
        <div class="card-body p-4">
            @if(! $pricingUnlocked)
                <p class="text-gasq-muted small">Sealed pricing is hidden until you certify the interviews are complete. This keeps the decision capability-first.</p>
                <form action="{{ route('interviews.certify', $job) }}" method="POST" onsubmit="return confirm('Certify interviews complete? This unlocks the pricing step.');">
                    @csrf
                    <button class="btn btn-success"><i class="fa fa-circle-check me-2"></i>Certify interviews complete</button>
                </form>
            @else
                <p class="text-success small mb-3"><i class="fa fa-circle-check me-1"></i>Certified{{ $config->certified_at ? ' on '.$config->certified_at->format('M j, Y') : '' }}. You can now reveal sealed pricing.</p>
                @if($config->status !== $CFG::STATUS_PRICE_REVEALED)
                    <form action="{{ route('interviews.reveal', $job) }}" method="POST" onsubmit="return confirm('Reveal sealed pricing now?');">
                        @csrf
                        <button class="btn btn-primary"><i class="fa fa-unlock me-2"></i>Reveal sealed pricing</button>
                    </form>
                @else
                    <span class="badge bg-success">Pricing revealed</span>
                @endif
            @endif
        </div>
    </div>
</div>
@endsection

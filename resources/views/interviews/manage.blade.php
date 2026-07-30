@extends('layouts.app')

@section('title', 'Interviews — ' . $job->title)
@section('header_variant', 'dashboard')

@section('content')
@php

    $statusLabels = [
        \App\Models\InterviewConfig::STATUS_SETUP => ['Setup', 'secondary'],
        \App\Models\InterviewConfig::STATUS_OPEN => ['Scheduling open', 'primary'],
        \App\Models\InterviewConfig::STATUS_INTERVIEWS_COMPLETE => ['Interviews complete', 'info'],
        \App\Models\InterviewConfig::STATUS_CERTIFIED => ['Certified', 'success'],
        \App\Models\InterviewConfig::STATUS_PRICE_REVEALED => ['Price revealed', 'success'],
        \App\Models\InterviewConfig::STATUS_CLOSED => ['Closed', 'dark'],
    ];
    $currentStatus = $config->status ?: \App\Models\InterviewConfig::STATUS_SETUP;
    [$statusText, $statusColor] = $statusLabels[$currentStatus] ?? ['Setup', 'secondary'];
    $deadlineValue = $config->scheduling_deadline
        ? $config->scheduling_deadline->format('Y-m-d\TH:i')
        : '';
@endphp

<div class="container py-4 px-4" style="max-width: 1100px;">

    {{-- Breadcrumb --}}
    <nav aria-label="breadcrumb" class="mb-3">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="{{ route('jobs.index') }}">My Jobs</a></li>
            <li class="breadcrumb-item"><a href="{{ route('jobs.show', $job) }}">{{ Str::limit($job->title, 40) }}</a></li>
            <li class="breadcrumb-item active">Interviews</li>
        </ol>
    </nav>

    <div class="d-flex flex-wrap justify-content-between align-items-start gap-2 mb-1">
        <h1 class="h3 fw-bold mb-0"><i class="fa fa-calendar-check text-primary me-2"></i>Interview Setup</h1>
        <span class="badge bg-{{ $statusColor }} align-self-center">{{ $statusText }}</span>
    </div>
    <p class="text-gasq-muted mb-4">
        Interview first, price last. Configure how vendors interview for
        <strong>{{ $job->title }}</strong> — the sealed pricing stays hidden until you certify the interviews are complete.
    </p>

    <div class="row g-4">
        {{-- ─────────── CONFIG FORM ─────────── --}}
        <div class="col-lg-7">
            <div class="card gasq-card">
                <div class="card-header py-3 px-4">
                    <h2 class="h6 fw-bold mb-0"><i class="fa fa-sliders me-2 text-primary"></i>Interview Settings</h2>
                </div>
                <div class="card-body p-4">
                    <form action="{{ route('interviews.config', $job) }}" method="POST">
                        @csrf

                        <div class="mb-3">
                            <label class="form-label fw-semibold">Scheduling method</label>
                            <select name="scheduling_method" class="form-select @error('scheduling_method') is-invalid @enderror" required>
                                <option value="{{ \App\Models\InterviewConfig::METHOD_SELF }}" @selected(old('scheduling_method', $config->scheduling_method) === \App\Models\InterviewConfig::METHOD_SELF)>Vendor self-scheduling (recommended) — vendors pick from your open slots</option>
                                <option value="{{ \App\Models\InterviewConfig::METHOD_ASSIGNED }}" @selected(old('scheduling_method', $config->scheduling_method) === \App\Models\InterviewConfig::METHOD_ASSIGNED)>Buyer-assigned — you assign each vendor a time</option>
                                <option value="{{ \App\Models\InterviewConfig::METHOD_GASQ }}" @selected(old('scheduling_method', $config->scheduling_method) === \App\Models\InterviewConfig::METHOD_GASQ)>GASQ-managed — GASQ coordinates scheduling</option>
                            </select>
                            @error('scheduling_method')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>

                        <div class="row g-3">
                            <div class="col-sm-6">
                                <label class="form-label fw-semibold">Default format</label>
                                <select name="default_format" class="form-select @error('default_format') is-invalid @enderror">
                                    <option value="">Choose…</option>
                                    <option value="virtual" @selected(old('default_format', $config->default_format) === 'virtual')>Virtual (video)</option>
                                    <option value="phone" @selected(old('default_format', $config->default_format) === 'phone')>Phone</option>
                                    <option value="in_person" @selected(old('default_format', $config->default_format) === 'in_person')>In person</option>
                                </select>
                                @error('default_format')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-sm-6">
                                <label class="form-label fw-semibold">Time zone</label>
                                <select name="timezone" class="form-select @error('timezone') is-invalid @enderror">
                                    <option value="">Choose…</option>
                                    @foreach($timezones as $tzValue => $tzLabel)
                                        <option value="{{ $tzValue }}" @selected(old('timezone', $config->timezone) === $tzValue)>{{ $tzLabel }}</option>
                                    @endforeach
                                </select>
                                @error('timezone')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                        </div>

                        <div class="row g-3 mt-0">
                            <div class="col-sm-4">
                                <label class="form-label fw-semibold">Interview length</label>
                                <div class="input-group">
                                    <input type="number" name="interview_minutes" min="5" max="240" class="form-control @error('interview_minutes') is-invalid @enderror" value="{{ old('interview_minutes', $config->interview_minutes ?? 30) }}" required>
                                    <span class="input-group-text">min</span>
                                </div>
                                @error('interview_minutes')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-sm-4">
                                <label class="form-label fw-semibold">Evaluation buffer</label>
                                <div class="input-group">
                                    <input type="number" name="evaluation_minutes" min="0" max="240" class="form-control @error('evaluation_minutes') is-invalid @enderror" value="{{ old('evaluation_minutes', $config->evaluation_minutes ?? 15) }}" required>
                                    <span class="input-group-text">min</span>
                                </div>
                                @error('evaluation_minutes')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-sm-4">
                                <label class="form-label fw-semibold">Gap between</label>
                                <div class="input-group">
                                    <input type="number" name="min_gap_minutes" min="0" max="240" class="form-control @error('min_gap_minutes') is-invalid @enderror" value="{{ old('min_gap_minutes', $config->min_gap_minutes ?? 0) }}" required>
                                    <span class="input-group-text">min</span>
                                </div>
                                @error('min_gap_minutes')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                            </div>
                        </div>

                        <div class="mb-3 mt-3">
                            <label class="form-label fw-semibold">Meeting location / link</label>
                            <input type="text" name="location" class="form-control @error('location') is-invalid @enderror" value="{{ old('location', $config->location) }}" placeholder="Zoom/Meet link, phone bridge, or physical address">
                            @error('location')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>

                        <div class="row g-3">
                            <div class="col-sm-6">
                                <label class="form-label fw-semibold">Scheduling deadline</label>
                                <input type="datetime-local" name="scheduling_deadline" class="form-control @error('scheduling_deadline') is-invalid @enderror" value="{{ old('scheduling_deadline', $deadlineValue) }}">
                                @error('scheduling_deadline')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-sm-6">
                                <label class="form-label fw-semibold"># of vendors to interview</label>
                                <input type="number" name="num_vendors" min="1" max="50" class="form-control @error('num_vendors') is-invalid @enderror" value="{{ old('num_vendors', $config->num_vendors) }}" placeholder="e.g. 3">
                                @error('num_vendors')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                        </div>

                        <div class="mb-3 mt-3">
                            <label class="form-label fw-semibold">Required attendees on your side</label>
                            <textarea name="required_attendees" rows="2" class="form-control @error('required_attendees') is-invalid @enderror" placeholder="Names/roles who must attend (e.g. Facilities Director, Security Manager)">{{ old('required_attendees', $config->required_attendees) }}</textarea>
                            @error('required_attendees')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-semibold">Price reveal</label>
                            <select name="reveal_method" class="form-select @error('reveal_method') is-invalid @enderror">
                                <option value="">Decide later</option>
                                <option value="all" @selected(old('reveal_method', $config->reveal_method) === 'all')>Reveal all vendors' prices after interviews</option>
                                <option value="finalists" @selected(old('reveal_method', $config->reveal_method) === 'finalists')>Reveal finalists' prices only</option>
                                <option value="selected" @selected(old('reveal_method', $config->reveal_method) === 'selected')>Reveal the selected vendor's price only</option>
                            </select>
                            <div class="form-text">Prices stay sealed until you certify interviews are complete.</div>
                            @error('reveal_method')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>

                        <div class="form-check mb-4">
                            <input type="hidden" name="disclose_competitor_count" value="0">
                            <input class="form-check-input" type="checkbox" name="disclose_competitor_count" value="1" id="discloseCount" @checked(old('disclose_competitor_count', $config->disclose_competitor_count))>
                            <label class="form-check-label" for="discloseCount">
                                Tell vendors how many competitors they're up against
                            </label>
                        </div>

                        <div class="d-flex gap-2">
                            <button type="submit" class="btn btn-primary"><i class="fa fa-floppy-disk me-2"></i>Save Interview Settings</button>
                            <a href="{{ route('jobs.show', $job) }}" class="btn btn-outline-secondary">Back to job</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        {{-- ─────────── STATUS PANEL ─────────── --}}
        <div class="col-lg-5">
            <div class="card gasq-card mb-4">
                <div class="card-header py-3 px-4">
                    <h2 class="h6 fw-bold mb-0"><i class="fa fa-list-check me-2 text-primary"></i>Interview Status</h2>
                </div>
                <div class="card-body p-4">
                    @forelse($interviews as $interview)
                        <div class="d-flex align-items-center justify-content-between border-bottom py-2">
                            <div>
                                <div class="fw-semibold">{{ $interview->vendor?->name ?? 'Vendor #' . $interview->vendor_id }}</div>
                                @if($interview->vendor?->company)
                                    <div class="small text-gasq-muted">{{ $interview->vendor->company }}</div>
                                @endif
                                @if($interview->scheduled_at)
                                    <div class="small text-gasq-muted"><i class="fa fa-clock me-1"></i>{{ $interview->scheduled_at->format('M j, Y g:i A') }}</div>
                                @endif
                            </div>
                            <span class="badge bg-light text-dark border text-capitalize">{{ str_replace('_', ' ', $interview->status) }}</span>
                        </div>
                    @empty
                        <p class="text-gasq-muted small mb-0">
                            No interviews scheduled yet. Save your settings, then invite vendors from the pool below
                            (vendor invitations &amp; self-scheduling are the next step).
                        </p>
                    @endforelse
                </div>
            </div>

            <div class="card gasq-card">
                <div class="card-header py-3 px-4">
                    <h2 class="h6 fw-bold mb-0"><i class="fa fa-users me-2 text-primary"></i>Vendors Who Engaged ({{ $bidVendors->count() }})</h2>
                </div>
                <div class="card-body p-4">
                    @forelse($bidVendors as $bid)
                        <div class="d-flex align-items-center justify-content-between border-bottom py-2">
                            <div>
                                <div class="fw-semibold">{{ $bid->user?->name ?? 'Vendor' }}</div>
                                @if($bid->user?->company)
                                    <div class="small text-gasq-muted">{{ $bid->user->company }}</div>
                                @endif
                            </div>
                            <span class="badge bg-secondary-subtle text-secondary-emphasis">Bid received</span>
                        </div>
                    @empty
                        <p class="text-gasq-muted small mb-0">No vendors have engaged this job yet.</p>
                    @endforelse
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

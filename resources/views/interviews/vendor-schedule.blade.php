@extends('layouts.app')

@section('title', 'Schedule Interview')
@section('header_variant', 'dashboard')

@section('content')
@php
    $cal = \App\Support\InterviewCalendar::class;
    $competitorCount = null;
    if ($config->disclose_competitor_count) {
        $competitorCount = \App\Models\Interview::where('job_posting_id', $job->id)->count();
    }
@endphp
<div class="container py-4 px-4" style="max-width: 820px;">

    <nav aria-label="breadcrumb" class="mb-3">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="{{ route('interviews.vendor.index') }}">My Interviews</a></li>
            <li class="breadcrumb-item active">{{ Str::limit($job->title, 40) }}</li>
        </ol>
    </nav>

    <h1 class="h3 fw-bold mb-1"><i class="fa fa-calendar-check text-primary me-2"></i>Interview — {{ $job->title }}</h1>
    <p class="text-gasq-muted mb-4">
        {{ $interview->duration_minutes ?? 30 }}-minute
        {{ $interview->format ? str_replace('_',' ',$interview->format).' ' : '' }}interview.
        @if($competitorCount !== null)You're one of <strong>{{ $competitorCount }}</strong> vendors being interviewed.@endif
    </p>

    {{-- Scheduled state --}}
    @if($interview->scheduled_at)
        <div class="card gasq-card mb-4 border-success">
            <div class="card-body p-4">
                <div class="d-flex align-items-center gap-2 mb-2">
                    <i class="fa fa-circle-check text-success fa-lg"></i>
                    <h2 class="h5 mb-0">Scheduled</h2>
                </div>
                <p class="mb-1 fw-semibold">{{ $interview->scheduled_at->format('l, F j, Y') }} at {{ $interview->scheduled_at->format('g:i A') }}
                    @if($interview->timezone)<span class="text-gasq-muted small">({{ $interview->timezone }})</span>@endif
                </p>
                @if($interview->location)<p class="mb-3 small"><i class="fa fa-location-dot me-1 text-gasq-muted"></i>{{ $interview->location }}</p>@endif

                <div class="d-flex flex-wrap gap-2">
                    <a href="{{ $cal::googleUrl($interview) }}" target="_blank" rel="noopener" class="btn btn-sm btn-outline-primary"><i class="fa fa-calendar-plus me-1"></i>Google Calendar</a>
                    <a href="{{ $cal::outlookUrl($interview) }}" target="_blank" rel="noopener" class="btn btn-sm btn-outline-primary"><i class="fa fa-calendar-plus me-1"></i>Outlook</a>
                    <a href="{{ route('interviews.ics', $interview) }}" class="btn btn-sm btn-outline-secondary"><i class="fa fa-download me-1"></i>Download .ics</a>
                </div>
            </div>
        </div>
    @endif

    {{-- Prep acknowledgment --}}
    <div class="card gasq-card mb-4">
        <div class="card-body p-4">
            <h2 class="h6 fw-bold mb-2"><i class="fa fa-clipboard-check me-2 text-primary"></i>Interview prep</h2>
            <ul class="small text-gasq-muted mb-3">
                <li>Be ready to speak to your staffing &amp; coverage plan, licensing/insurance, and relevant experience.</li>
                <li>Pricing is <strong>sealed</strong> — do not lead with price; the buyer reveals pricing only after interviews.</li>
                @if($config->required_attendees)<li>Buyer attendees: {{ $config->required_attendees }}</li>@endif
            </ul>
            @if($interview->vendor_prep_acknowledged_at)
                <span class="badge bg-success-subtle text-success-emphasis"><i class="fa fa-check me-1"></i>Acknowledged {{ $interview->vendor_prep_acknowledged_at->format('M j, Y') }}</span>
            @else
                <form action="{{ route('interviews.vendor.prep-ack', $interview) }}" method="POST">
                    @csrf
                    <button class="btn btn-sm btn-outline-primary"><i class="fa fa-check me-1"></i>I've reviewed the prep</button>
                </form>
            @endif
        </div>
    </div>

    {{-- Slot picker --}}
    <div class="card gasq-card">
        <div class="card-header py-3 px-4"><h2 class="h6 fw-bold mb-0"><i class="fa fa-clock me-2 text-primary"></i>{{ $interview->scheduled_at ? 'Reschedule' : 'Pick a time' }}</h2></div>
        <div class="card-body p-4">
            @if($config->scheduling_method !== \App\Models\InterviewConfig::METHOD_SELF)
                <p class="text-gasq-muted small mb-0">The buyer is coordinating scheduling for this interview{{ $config->scheduling_method === \App\Models\InterviewConfig::METHOD_GASQ ? ' through GASQ' : '' }}. You'll be notified of your time.</p>
            @elseif($openSlots->isEmpty())
                <p class="text-gasq-muted small mb-0">No open times right now. Check back shortly — the buyer is adding availability.</p>
            @else
                <form action="{{ route('interviews.vendor.book', $interview) }}" method="POST">
                    @csrf
                    @if($config->timezone)
                        <p class="small text-gasq-muted mb-2"><i class="fa fa-globe me-1"></i>Times shown in {{ $config->timezone }}.</p>
                    @endif
                    <div class="list-group mb-3">
                        @foreach($openSlots as $slot)
                            <label class="list-group-item d-flex align-items-center gap-2">
                                <input class="form-check-input me-1" type="radio" name="slot_id" value="{{ $slot->id }}" required>
                                <span>{{ $slot->starts_at->format('l, F j · g:i A') }}</span>
                            </label>
                        @endforeach
                    </div>
                    <button class="btn btn-primary"><i class="fa fa-calendar-check me-2"></i>{{ $interview->scheduled_at ? 'Move to this time' : 'Book this time' }}</button>
                </form>
            @endif
        </div>
    </div>
</div>
@endsection

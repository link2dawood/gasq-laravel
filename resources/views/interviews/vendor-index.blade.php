@extends('layouts.app')

@section('title', 'My Interviews')
@section('header_variant', 'dashboard')

@section('content')
<div class="container py-4 px-4" style="max-width: 900px;">
    <h1 class="h3 fw-bold mb-1"><i class="fa fa-calendar-check text-primary me-2"></i>My Interviews</h1>
    <p class="text-gasq-muted mb-4">Buyer interview invitations. Pick a time for each — pricing stays sealed until the buyer completes interviews.</p>

    <div class="card gasq-card">
        <div class="card-body p-0">
            @forelse($interviews as $interview)
                <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 border-bottom p-3 p-md-4">
                    <div>
                        <div class="fw-semibold">{{ $interview->jobPosting?->title ?? 'Security job' }}</div>
                        @if($interview->jobPosting?->location)<div class="small text-gasq-muted"><i class="fa fa-location-dot me-1"></i>{{ $interview->jobPosting->location }}</div>@endif
                        <div class="small text-gasq-muted mt-1">
                            @if($interview->scheduled_at)
                                <i class="fa fa-clock me-1"></i>{{ $interview->scheduled_at->format('D M j, Y · g:i A') }}
                            @else
                                <span class="text-warning-emphasis"><i class="fa fa-triangle-exclamation me-1"></i>Not scheduled — action needed</span>
                            @endif
                        </div>
                    </div>
                    <div class="d-flex align-items-center gap-2">
                        <span class="badge bg-light text-dark border text-capitalize">{{ str_replace('_',' ',$interview->status) }}</span>
                        <a href="{{ route('interviews.vendor.schedule', $interview) }}" class="btn btn-sm btn-primary">
                            {{ $interview->scheduled_at ? 'View / reschedule' : 'Schedule' }}
                        </a>
                    </div>
                </div>
            @empty
                <p class="text-gasq-muted small mb-0 p-4">You have no interview invitations yet. When a buyer invites you, it will appear here.</p>
            @endforelse
        </div>
    </div>
</div>
@endsection

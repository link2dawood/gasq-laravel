@extends('layouts.app')

@section('title', 'Analytics Dashboard')

@section('content')
<div class="container py-4">
    <div class="mb-4">
        <h1 class="h2 fw-bold mb-1">Analytics Dashboard</h1>
        <p class="text-gasq-muted mb-0">Overview of usage and key metrics (last 7 days)</p>
    </div>

    <div class="row g-3 mb-4">
        <div class="col-sm-6 col-lg-3">
            <div class="card gasq-card">
                <div class="card-body">
                    <div class="small text-gasq-muted mb-1">Users</div>
                    <div class="h3 fw-bold mb-0">{{ $metrics['total_users'] }}</div>
                    <div class="small text-gasq-muted">Buyers: {{ $metrics['total_buyers'] }}, Vendors: {{ $metrics['total_vendors'] }}</div>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-lg-3">
            <div class="card gasq-card">
                <div class="card-body">
                    <div class="small text-gasq-muted mb-1">Jobs</div>
                    <div class="h3 fw-bold mb-0">{{ $metrics['total_jobs'] }}</div>
                    <div class="small text-gasq-muted">Bids: {{ $metrics['total_bids'] }}</div>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-lg-3">
            <div class="card gasq-card">
                <div class="card-body">
                    <div class="small text-gasq-muted mb-1">Discovery Calls</div>
                    <div class="h3 fw-bold mb-0">{{ $metrics['total_discovery_calls'] }}</div>
                </div>
            </div>
        </div>
    </div>

    {{-- BUYER FUNNEL --}}
    <div class="card gasq-card mb-4">
        <div class="card-header d-flex flex-wrap justify-content-between align-items-center gap-2">
            <h3 class="card-title mb-0">Buyer funnel (last 7 days)</h3>
            <span class="small text-gasq-muted">Distinct sessions per stage</span>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-sm align-middle mb-0">
                    <thead>
                        <tr>
                            <th>Stage</th>
                            <th class="text-end">Sessions</th>
                            <th class="text-end">From previous</th>
                            <th class="text-end">Dropped</th>
                            <th class="text-end">From top</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($journey as $stage)
                            <tr>
                                <td>{{ $stage['label'] }}</td>
                                <td class="text-end fw-semibold">{{ number_format($stage['count']) }}</td>
                                <td class="text-end">
                                    @if($stage['from_previous'] === null)
                                        <span class="text-gasq-muted">&mdash;</span>
                                    @else
                                        <span class="{{ $stage['from_previous'] < 50 ? 'text-danger fw-semibold' : '' }}">{{ $stage['from_previous'] }}%</span>
                                    @endif
                                </td>
                                <td class="text-end text-gasq-muted">
                                    {{ $stage['dropped'] === null ? '—' : number_format($stage['dropped']) }}
                                </td>
                                <td class="text-end text-gasq-muted">
                                    {{ $stage['from_top'] === null ? '—' : $stage['from_top'] . '%' }}
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="alert alert-warning mt-3 mb-0 small">
                <strong>Registration abandoned:</strong>
                {{ number_format($registrationAbandoned) }}
                @if($registrationAbandonRate !== null)
                    ({{ $registrationAbandonRate }}% of registrations started)
                @endif
                <div class="mt-1">
                    Derived as <em>registration started &minus; registration completed</em>, not a stored event.
                </div>
            </div>

            <p class="small text-gasq-muted mt-3 mb-0">
                These are stage volume ratios, not a cohort funnel: the session id is regenerated at login,
                so a single visitor cannot be followed across the registration boundary. Read a percentage as
                &ldquo;how many sessions reach this stage&rdquo;, not &ldquo;how many of these exact people continued&rdquo;.
            </p>
        </div>
    </div>

    {{-- SIDE ENTRIES --}}
    <div class="row g-3 mb-4">
        @foreach($sideEntries as $entry)
            <div class="col-sm-6 col-lg-3">
                <div class="card gasq-card h-100">
                    <div class="card-body">
                        <div class="small text-gasq-muted mb-1">{{ $entry['label'] }}</div>
                        <div class="h3 fw-bold mb-0">{{ number_format($entry['count']) }}</div>
                    </div>
                </div>
            </div>
        @endforeach
    </div>

    <div class="row g-4">
        <div class="col-md-6">
            <div class="card gasq-card">
                <div class="card-header">
                    <h3 class="card-title mb-0">Events by type (last 7 days)</h3>
                </div>
                <div class="card-body">
                    @if($eventsByType->isEmpty())
                        <p class="text-gasq-muted mb-0">No events recorded yet.</p>
                    @else
                        <div class="table-responsive">
                            <table class="table table-hover mb-0">
                                <thead>
                                    <tr>
                                        <th>Event type</th>
                                        <th class="text-end">Count</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($eventsByType as $row)
                                        <tr>
                                            <td>{{ $row->event_type }}</td>
                                            <td class="text-end">{{ $row->count }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card gasq-card">
                <div class="card-header">
                    <h3 class="card-title mb-0">Events per day (last 7 days)</h3>
                </div>
                <div class="card-body">
                    @if($dailyEvents->isEmpty())
                        <p class="text-gasq-muted mb-0">No events recorded yet.</p>
                    @else
                        <div class="table-responsive">
                            <table class="table table-hover mb-0">
                                <thead>
                                    <tr>
                                        <th>Date</th>
                                        <th class="text-end">Events</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($dailyEvents as $row)
                                        <tr>
                                            <td>{{ \Carbon\Carbon::parse($row->day)->format('M j, Y') }}</td>
                                            <td class="text-end">{{ $row->count }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
@endsection


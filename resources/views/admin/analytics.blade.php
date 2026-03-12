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


@extends('layouts.app')

@section('title', 'Activity Log')

@section('content')
<div class="container py-4 px-4">
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-4">
        <h1 class="gasq-page-title mb-0">Activity Log</h1>
        <a href="{{ route('admin.analytics') }}" class="btn btn-outline-primary btn-sm">Analytics dashboard</a>
    </div>
    <p class="text-gasq-muted">Every page view and action (create / update / delete) performed on the platform. Request bodies are never stored — only who did what, when, and from where.</p>

    <div class="card gasq-card mb-4">
        <div class="card-body p-3">
            <form method="get" class="row g-2 align-items-end">
                <div class="col-md-3">
                    <label class="form-label small text-gasq-muted mb-1">User (name or email)</label>
                    <input type="text" name="user" value="{{ request('user') }}" class="form-control form-control-sm" placeholder="Search user">
                </div>
                <div class="col-md-3">
                    <label class="form-label small text-gasq-muted mb-1">Action</label>
                    <select name="event_type" class="form-select form-select-sm">
                        <option value="">All actions</option>
                        @foreach($eventTypes as $type)
                            <option value="{{ $type }}" @selected(request('event_type') === $type)>{{ $type }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label small text-gasq-muted mb-1">Type</label>
                    <select name="type" class="form-select form-select-sm">
                        <option value="">All</option>
                        <option value="writes" @selected(request('type') === 'writes')>Changes only</option>
                        <option value="views" @selected(request('type') === 'views')>Page views</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label small text-gasq-muted mb-1">From</label>
                    <input type="date" name="from" value="{{ request('from') }}" class="form-control form-control-sm">
                </div>
                <div class="col-md-2">
                    <label class="form-label small text-gasq-muted mb-1">To</label>
                    <input type="date" name="to" value="{{ request('to') }}" class="form-control form-control-sm">
                </div>
                <div class="col-12 d-flex gap-2">
                    <button type="submit" class="btn btn-primary btn-sm">Filter</button>
                    <a href="{{ route('admin.activity') }}" class="btn btn-outline-secondary btn-sm">Reset</a>
                </div>
            </form>
        </div>
    </div>

    <div class="card gasq-card">
        <div class="table-responsive">
            <table class="table table-sm table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>When</th>
                        <th>User</th>
                        <th>Action</th>
                        <th>Method</th>
                        <th>Path</th>
                        <th>IP</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($events as $event)
                        @php
                            $method = $event->event_data['method'] ?? 'GET';
                            $isWrite = in_array($method, ['POST', 'PUT', 'PATCH', 'DELETE'], true);
                            $badge = match($method) {
                                'POST' => 'bg-success',
                                'PUT', 'PATCH' => 'bg-warning text-dark',
                                'DELETE' => 'bg-danger',
                                default => 'bg-secondary',
                            };
                        @endphp
                        <tr>
                            <td class="small text-nowrap">{{ $event->created_at?->format('M j, Y g:i A') }}</td>
                            <td class="small">
                                @if($event->user)
                                    <div class="fw-semibold">{{ $event->user->name }}</div>
                                    <div class="text-gasq-muted">{{ $event->user->email }} · {{ ucfirst($event->user->user_type) }}</div>
                                @else
                                    <span class="text-gasq-muted">Guest</span>
                                @endif
                            </td>
                            <td class="small"><code>{{ $event->event_type }}</code></td>
                            <td><span class="badge {{ $badge }}">{{ $method }}</span></td>
                            <td class="small text-break">/{{ $event->event_data['path'] ?? '' }}
                                @if($isWrite && !empty($event->event_data['params']))
                                    <span class="text-gasq-muted">{{ json_encode($event->event_data['params']) }}</span>
                                @endif
                            </td>
                            <td class="small text-nowrap text-gasq-muted">{{ $event->ip_address }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="text-center text-gasq-muted py-4">No activity found for these filters.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="mt-3">
        {{ $events->links() }}
    </div>
</div>
@endsection

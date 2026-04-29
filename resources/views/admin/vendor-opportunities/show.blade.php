@extends('layouts.app')

@section('title', 'Vendor Opportunity Review')

@section('content')
<div class="container py-4">
    <div class="d-flex flex-wrap justify-content-between align-items-start gap-3 mb-4">
        <div>
            <p class="text-uppercase text-gasq-muted small fw-semibold mb-1">Admin Review</p>
            <h1 class="gasq-page-title mb-2">{{ $opportunity->jobPosting->title }}</h1>
            <p class="text-gasq-muted mb-0">{{ $opportunity->jobPosting->location ?: 'Location not provided' }}</p>
        </div>
        <div class="d-flex flex-wrap gap-2">
            @if($opportunity->status === \App\Models\VendorOpportunity::STATUS_PENDING_REVIEW)
                <form action="{{ route('admin.vendor-opportunities.approve', $opportunity) }}" method="POST">
                    @csrf
                    <button type="submit" class="btn btn-primary">Approve and Send</button>
                </form>
            @endif
            @if(! $opportunity->isClosed())
                <form action="{{ route('admin.vendor-opportunities.close', $opportunity) }}" method="POST">
                    @csrf
                    <button type="submit" class="btn btn-outline-secondary">Close Opportunity</button>
                </form>
            @endif
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    <div class="row g-4 mb-4">
        <div class="col-md-4">
            <div class="card gasq-card h-100">
                <div class="card-body">
                    <p class="small text-uppercase text-gasq-muted fw-semibold mb-2">Buyer</p>
                    <div class="fw-semibold">{{ $opportunity->jobPosting->user->name }}</div>
                    <div class="text-gasq-muted">{{ $opportunity->jobPosting->user->company ?: 'No company' }}</div>
                    <div class="text-gasq-muted mt-2">{{ $opportunity->jobPosting->user->email }}</div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card gasq-card h-100">
                <div class="card-body">
                    <p class="small text-uppercase text-gasq-muted fw-semibold mb-2">Lead Summary</p>
                    <div>Lead tier: <strong>{{ strtoupper($opportunity->lead_tier) }}</strong></div>
                    <div>Status: <strong>{{ ucfirst(str_replace('_', ' ', $opportunity->status)) }}</strong></div>
                    <div>Estimated annual value: <strong>${{ number_format((float) $opportunity->estimated_annual_contract_value, 2) }}</strong></div>
                    <div>Target vendors: <strong>{{ $opportunity->vendor_target_count }}</strong></div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card gasq-card h-100">
                <div class="card-body">
                    <p class="small text-uppercase text-gasq-muted fw-semibold mb-2">Qualification</p>
                    <div>Decision maker verified: <strong>{{ $opportunity->decision_maker_verified ? 'Yes' : 'No' }}</strong></div>
                    <div>Budget confirmed: <strong>{{ $opportunity->budget_confirmed ? 'Yes' : 'No' }}</strong></div>
                    <div>Scope completed: <strong>{{ $opportunity->scope_completed ? 'Yes' : 'No' }}</strong></div>
                    <div>Timeline ready: <strong>{{ $opportunity->timeline_ready ? 'Yes' : 'No' }}</strong></div>
                    <div>Move forward confirmed: <strong>{{ $opportunity->move_forward_confirmed ? 'Yes' : 'No' }}</strong></div>
                </div>
            </div>
        </div>
    </div>

    <div class="card gasq-card">
        <div class="card-header bg-white">
            <h2 class="h4 fw-bold mb-0">Invited Vendors</h2>
        </div>
        <div class="table-responsive">
            <table class="table align-middle mb-0">
                <thead>
                    <tr>
                        <th>Vendor</th>
                        <th>Status</th>
                        <th>Match</th>
                        <th>Credits</th>
                        <th>Bid</th>
                        <th>Realism</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($opportunity->invitations as $invitation)
                        <tr>
                            <td>
                                <div class="fw-semibold">{{ $invitation->vendor->name }}</div>
                                <div class="small text-gasq-muted">{{ $invitation->vendor->company ?: 'No company' }}</div>
                            </td>
                            <td>
                                <div>{{ ucfirst(str_replace('_', ' ', $invitation->status)) }}</div>
                                @if($invitation->decline_reason)
                                    <div class="small text-gasq-muted">Reason: {{ str_replace('_', ' ', $invitation->decline_reason) }}</div>
                                @endif
                            </td>
                            <td>
                                <div>{{ number_format((float) $invitation->match_score, 2) }}</div>
                                <div class="small text-gasq-muted">{{ implode(', ', $invitation->match_reasons ?? []) }}</div>
                            </td>
                            <td>{{ number_format($invitation->credits_to_unlock) }}</td>
                            <td>
                                @if($invitation->bid)
                                    <div>${{ number_format((float) $invitation->bid->annual_price, 2) }}</div>
                                    <div class="small text-gasq-muted">{{ $invitation->bid->start_availability }}</div>
                                @else
                                    <span class="text-gasq-muted">Not submitted</span>
                                @endif
                            </td>
                            <td>
                                @if($invitation->bid)
                                    <div>{{ $invitation->bid->realism_score }}</div>
                                    <div class="small text-gasq-muted">{{ ucfirst((string) $invitation->bid->realism_label) }}</div>
                                @else
                                    <span class="text-gasq-muted">Pending</span>
                                @endif
                            </td>
                            <td class="text-end">
                                @if($invitation->bid && $invitation->status !== 'awarded' && ! $opportunity->isClosed())
                                    <form action="{{ route('admin.vendor-opportunities.award', $invitation) }}" method="POST">
                                        @csrf
                                        <button type="submit" class="btn btn-sm btn-success">Award</button>
                                    </form>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="text-center text-gasq-muted py-4">No vendors invited yet.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection

@extends('layouts.app')

@section('title', 'Vendor Opportunities')
@section('header_variant', 'dashboard')

@section('content')
<div class="container py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <p class="text-uppercase text-gasq-muted small fw-semibold mb-1">Admin</p>
            <h1 class="gasq-page-title mb-0">Vendor Opportunities</h1>
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    <div class="card gasq-card">
        <div class="table-responsive">
            <table class="table align-middle mb-0">
                <thead>
                    <tr>
                        <th>Buyer</th>
                        <th>Lead Tier</th>
                        <th>Service</th>
                        <th>Annual Value</th>
                        <th>Invited</th>
                        <th>Opened</th>
                        <th>Accepted</th>
                        <th>Declined</th>
                        <th>Bids</th>
                        <th>Credits</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($opportunities as $opportunity)
                        @php
                            $openedCount = $opportunity->invitations->whereNotNull('opened_at')->count();
                            $acceptedCount = $opportunity->invitations->whereNotNull('accepted_at')->count();
                            $declinedCount = $opportunity->invitations->where('status', 'declined')->count();
                            $bidCount = $opportunity->invitations->whereNotNull('bid_submitted_at')->count();
                            $creditsEarned = $opportunity->invitations->sum('credits_to_unlock');
                            $winningInvitation = $opportunity->invitations->firstWhere('status', 'awarded');
                        @endphp
                        <tr>
                            <td>
                                <div class="fw-semibold">{{ $opportunity->jobPosting->user->name }}</div>
                                <div class="small text-gasq-muted">{{ $opportunity->jobPosting->user->company ?: 'No company' }}</div>
                            </td>
                            <td><span class="badge bg-primary-subtle text-primary-emphasis border">{{ strtoupper($opportunity->lead_tier) }}</span></td>
                            <td>{{ $opportunity->jobPosting->category ?: 'Not provided' }}</td>
                            <td>${{ number_format((float) $opportunity->estimated_annual_contract_value, 2) }}</td>
                            <td>{{ $opportunity->invitations->count() }}</td>
                            <td>{{ $openedCount }}</td>
                            <td>{{ $acceptedCount }}</td>
                            <td>{{ $declinedCount }}</td>
                            <td>{{ $bidCount }}</td>
                            <td>{{ number_format($creditsEarned) }}</td>
                            <td class="text-end">
                                <a href="{{ route('admin.vendor-opportunities.show', $opportunity) }}" class="btn btn-sm btn-outline-primary">Review</a>
                                @if($winningInvitation)
                                    <div class="small text-success mt-2">Winner: {{ $winningInvitation->vendor->name }}</div>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="11" class="text-center text-gasq-muted py-4">No vendor opportunities yet.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="mt-3">
        {{ $opportunities->links() }}
    </div>
</div>
@endsection

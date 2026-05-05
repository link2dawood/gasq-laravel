@extends('layouts.app')

@section('title', 'Vendor Estimate')

@section('content')
@php
    $snap = is_array($submission->snapshot) ? $submission->snapshot : [];
    $vendor = $submission->vendor;
    $job = $submission->jobPosting;
    $buyer = $submission->buyer;
    $rows = $snap['rows'] ?? [];
    $totals = $snap['totals'] ?? [];
    $serviceLabel = $snap['service_label'] ?? ($job?->category ?? 'Security services');
    $location = $snap['location'] ?? ($job?->location ?? '');
@endphp

<div class="container py-4">
    <div class="card shadow-sm border-0 mb-4">
        <div class="card-body p-4 p-lg-5" style="background: linear-gradient(135deg, rgba(13,110,253,0.10) 0%, rgba(255,255,255,1) 70%);">
            <div class="d-flex flex-wrap align-items-start justify-content-between gap-3">
                <div>
                    <p class="text-uppercase small fw-semibold text-primary mb-1">Vendor Estimate</p>
                    <h1 class="h3 fw-bold mb-1">{{ $serviceLabel }}</h1>
                    @if($location)<p class="text-gasq-muted mb-0"><i class="fa fa-location-dot me-1"></i>{{ $location }}</p>@endif
                </div>
                <div class="text-end">
                    <p class="text-gasq-muted small mb-1">Submitted</p>
                    <p class="mb-0">{{ $submission->created_at?->format('M j, Y') }}</p>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-3 mb-4">
        <div class="col-md-6">
            <div class="card gasq-card h-100">
                <div class="card-body">
                    <p class="small text-gasq-muted mb-1">From (Vendor)</p>
                    <h5 class="mb-1">{{ $vendor?->name ?? '—' }}</h5>
                    @if($vendor?->company)<p class="mb-0 text-gasq-muted">{{ $vendor->company }}</p>@endif
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card gasq-card h-100">
                <div class="card-body">
                    <p class="small text-gasq-muted mb-1">To (Buyer)</p>
                    <h5 class="mb-1">{{ $buyer?->name ?? '—' }}</h5>
                    @if($job)<p class="mb-0 text-gasq-muted">Job: {{ $job->title }}</p>@endif
                </div>
            </div>
        </div>
    </div>

    <div class="card gasq-card mb-4">
        <div class="card-body">
            <h2 class="h5 fw-bold mb-3">Cost breakdown</h2>
            @if(!empty($rows))
                <div class="table-responsive">
                    <table class="table mb-0">
                        <thead><tr><th>Line item</th><th class="text-end">Amount</th></tr></thead>
                        <tbody>
                            @foreach($rows as $row)
                                <tr>
                                    <td>{{ $row['label'] ?? '' }}</td>
                                    <td class="text-end">{{ $row['value'] ?? '' }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @else
                <p class="text-gasq-muted mb-0">No line items captured.</p>
            @endif
        </div>
    </div>

    @if(!empty($totals))
        <div class="card gasq-card mb-4">
            <div class="card-body">
                <h2 class="h5 fw-bold mb-3">Totals</h2>
                <table class="table mb-0">
                    @foreach($totals as $row)
                        <tr>
                            <td><strong>{{ $row['label'] ?? '' }}</strong></td>
                            <td class="text-end"><strong>{{ $row['value'] ?? '' }}</strong></td>
                        </tr>
                    @endforeach
                </table>
            </div>
        </div>
    @endif

    @if(!empty($snap['notes']))
        <div class="card gasq-card mb-4">
            <div class="card-body">
                <h2 class="h5 fw-bold mb-2">Notes from the vendor</h2>
                <div>{!! nl2br(e($snap['notes'])) !!}</div>
            </div>
        </div>
    @endif

    @if($job)
        <div class="text-center">
            <a class="btn btn-primary btn-lg px-5" href="{{ route('jobs.show', $job) }}">Open job page</a>
        </div>
    @endif
</div>
@endsection

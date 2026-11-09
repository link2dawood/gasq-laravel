@extends('layouts.app')
@section('title', 'Vendor Estimate Dashboard')
@section('header_variant', 'dashboard')
@section('content')
<div class="container py-4">
    <h1>Estimate dashboard</h1>
    <p><a href="{{ route('vendor-calendar.index') }}">Manage availability &amp; appointments</a></p>
    <p class="lead text-muted">Review buyer requirements, prepare estimates, and manage your pipeline.</p>
    <div class="row g-3 my-3">
        @foreach(['New enquiries' => $leadCounts['new'] ?? 0, 'Qualified leads' => $leadCounts['qualified'] ?? 0, 'Draft estimates' => $estimateCounts['draft'] ?? 0, 'Ready estimates' => $estimateCounts['ready'] ?? 0] as $label => $count)
        <div class="col-6 col-lg-3"><div class="border rounded p-3"><div class="text-muted">{{ $label }}</div><strong class="fs-3">{{ $count }}</strong></div></div>
        @endforeach
    </div>
    <p><a href="{{ route('vendor-buyer-leads.index') }}" class="btn btn-primary">Review leads &amp; prepare an estimate</a></p>
    <form method="GET" class="d-flex flex-wrap align-items-end gap-2 my-4">@if(request('lead'))<input type="hidden" name="lead" value="{{ request('lead') }}">@endif<div><label class="form-label" for="estimate-status">Estimate status</label><select class="form-select" id="estimate-status" name="status"><option value="">All estimates</option>@foreach(\App\Models\VendorLeadEstimate::STATUSES as $value => $label)<option value="{{ $value }}" @selected(request('status') === $value)>{{ $label }}</option>@endforeach</select></div><button type="submit" class="btn btn-outline-primary">Filter</button><a href="{{ route('vendor-estimates.index') }}">Show all estimates</a></form>
    @forelse($estimates as $estimate)
    <article class="border rounded p-3 mb-3">
        <div class="d-flex flex-wrap justify-content-between gap-2"><h2 class="h5"><a href="{{ route('vendor-estimates.edit', $estimate) }}">{{ $estimate->title }}</a></h2><span>{{ \App\Models\VendorLeadEstimate::STATUSES[$estimate->status] }}</span></div>
        <p class="small text-muted">{{ $estimate->number }} · Updated {{ $estimate->updated_at->format('M j, Y g:i A') }}</p>
        <p>{{ $estimate->buyer['contact_name'] }}{{ !empty($estimate->buyer['company']) ? ' · '.$estimate->buyer['company'] : '' }}<br>{{ $estimate->buyer['site_address'] }}</p>
        <p class="mb-2"><strong>{{ $estimate->money($estimate->totals()['first_year']) }}</strong> first-year total · Valid through {{ $estimate->valid_until->format('M j, Y') }} @if($estimate->valid_until->isBefore(today()))<span class="text-danger">(Expired)</span>@endif</p>
        <a href="{{ route('vendor-estimates.edit', $estimate) }}">Review / edit estimate</a>
        <p class="small text-muted mt-2 mb-0">Email: {{ $estimate->latestDelivery ? \App\Models\VendorEstimateDelivery::STATUSES[$estimate->latestDelivery->status] : 'Not sent' }}</p>
    </article>
    @empty
    <div class="border rounded p-4"><h2 class="h5">No estimates to show</h2><p class="mb-0">Open a buyer enquiry and choose “Prepare estimate” to get started.</p></div>
    @endforelse
    {{ $estimates->links() }}
</div>
@endsection

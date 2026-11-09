@extends('layouts.app')
@section('title', 'Buyer Enquiries')
@section('header_variant', 'dashboard')
@section('content')
<div class="container py-4">
    <h1>Buyer enquiries</h1>
    <p><a href="{{ route('vendor-estimates.index') }}">Estimate dashboard</a></p>
    <p class="text-muted">Share your private invitation link to collect buyer contact details and service requirements.</p>
    @if(session('status'))<div class="alert alert-success" role="status">{{ session('status') }}</div>@endif
    @if($errors->any())<div class="alert alert-danger" role="alert"><ul class="mb-0">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif
    <section class="border rounded p-3 mb-4" aria-labelledby="invitation-heading">
        <h2 class="h5" id="invitation-heading">Your private invitation link</h2>
        <label class="form-label small" for="invitation-url">Copy and share this link with prospective buyers.</label>
        <div class="d-flex flex-wrap gap-2"><input class="form-control" style="flex:1;min-width:220px;" id="invitation-url" readonly value="{{ $link->invitationUrl() }}" onclick="this.select()"><button class="btn btn-outline-primary" type="button" id="copy-invitation">Copy link</button><a class="btn btn-outline-secondary" href="{{ $link->invitationUrl() }}" target="_blank" rel="noopener noreferrer">Preview form</a></div>
        <p id="copy-status" class="small mt-2" role="status"></p>
        @php
            $invitationSubject = 'Your private security services request form';
            $invitationBody = "Hello,\n\nPlease use my private invitation link below to share your contact details and scope of security services:\n\n".$link->invitationUrl()."\n\nOnce you submit the form, I will review your requirements and prepare an estimate. If the PDF estimate is password protected, its email will include a link to schedule a review before password access is released.\n\nThank you,\n".(auth()->user()->company ?: auth()->user()->name);
        @endphp
        <a class="btn btn-outline-primary btn-sm" href="mailto:?subject={{ rawurlencode($invitationSubject) }}&amp;body={{ rawurlencode($invitationBody) }}">Draft invitation email</a>
        <p class="small text-muted mt-2 mb-0">Opens a draft in your email app. Add the buyer's email address, review the message, and send it directly.</p>
        <form method="POST" action="{{ route('vendor-buyer-leads.rotate') }}" class="mt-3">@csrf
            <details><summary>Replace this invitation link</summary><p class="small text-muted mt-2">Replacing the link disables the previous one. Existing leads are kept.</p><button class="btn btn-sm btn-outline-danger" type="submit">Generate replacement link</button></details>
        </form>
    </section>
    <form method="GET" class="row g-2 align-items-end mb-4">
        <div class="col-md-6"><label class="form-label" for="q">Search name, company, or email</label><input class="form-control" id="q" name="q" maxlength="100" value="{{ request('q') }}"></div>
        <div class="col-md-3"><label class="form-label" for="status">Status</label><select class="form-select" id="status" name="status"><option value="">All statuses</option>@foreach(\App\Models\VendorBuyerLead::STATUSES as $value => $label)<option value="{{ $value }}" @selected(request('status') === $value)>{{ $label }}</option>@endforeach</select></div>
        <div class="col-md-3"><button class="btn btn-primary" type="submit">Filter leads</button> <a href="{{ route('vendor-buyer-leads.index') }}">Clear</a></div>
    </form>
    <p class="text-muted">{{ $leads->total() }} {{ \Illuminate\Support\Str::plural('enquiry', $leads->total()) }}</p>
    @forelse($leads as $lead)
    <article class="border rounded p-3 mb-3">
        <div class="d-flex flex-wrap justify-content-between gap-2"><h2 class="h5 mb-1">{{ $lead->contact_name }}{{ $lead->company ? ' · '.$lead->company : '' }}</h2><span class="badge bg-secondary align-self-start">{{ \App\Models\VendorBuyerLead::STATUSES[$lead->status] }}</span></div>
        <p class="small text-muted">Received {{ $lead->created_at->format('M j, Y g:i A') }}</p>
        <p><strong>Email:</strong> <a href="mailto:{{ $lead->email }}">{{ $lead->email }}</a><br><strong>Phone:</strong> {{ $lead->phone }}</p>
        <p><strong>Site:</strong> {{ $lead->site_address }}<br><strong>Service:</strong> {{ $lead->service_type }}<br><strong>Preferred start:</strong> {{ $lead->start_date?->format('M j, Y') ?? 'Not supplied' }}<br><strong>Weekly service hours:</strong> {{ $lead->weekly_hours ?? 'Not supplied' }}</p>
        <p class="mb-1"><strong>Requirements</strong></p><div style="white-space:pre-wrap;overflow-wrap:anywhere;">{{ $lead->scope }}</div>
        <p class="mt-3"><a href="{{ route('vendor-estimates.create', $lead) }}" class="btn btn-outline-primary btn-sm">Prepare estimate</a></p>
        @if($lead->estimates_count)<p><a href="{{ route('vendor-estimates.index', ['lead' => $lead->id]) }}">View estimates ({{ $lead->estimates_count }})</a></p>@endif
        <details class="mt-3" @if($lead->notes) open @endif><summary>Manage lead</summary>
            <form method="POST" action="{{ route('vendor-buyer-leads.update', $lead) }}" class="mt-3">@csrf @method('PATCH')
                <label class="form-label" for="status-{{ $lead->id }}">Lead status</label><select class="form-select mb-2" id="status-{{ $lead->id }}" name="status">@foreach(\App\Models\VendorBuyerLead::STATUSES as $value => $label)<option value="{{ $value }}" @selected($lead->status === $value)>{{ $label }}</option>@endforeach</select>
                <label class="form-label" for="notes-{{ $lead->id }}">Internal notes</label><textarea class="form-control mb-2" id="notes-{{ $lead->id }}" name="notes" rows="3" maxlength="10000">{{ $lead->notes }}</textarea>
                <button class="btn btn-sm btn-primary" type="submit">Save lead</button>
            </form>
        </details>
    </article>
    @empty
    <div class="border rounded p-4"><h2 class="h5">No enquiries to show</h2><p class="mb-0">Share your invitation link to collect new leads, or clear the filters to see all enquiries.</p></div>
    @endforelse
    {{ $leads->links() }}
</div>
@endsection
@push('scripts')
<script>
document.getElementById('copy-invitation').addEventListener('click', async function () {
    const input = document.getElementById('invitation-url');
    const status = document.getElementById('copy-status');
    try { await navigator.clipboard.writeText(input.value); status.textContent = 'Invitation link copied.'; }
    catch (error) { input.select(); status.textContent = 'Select and copy the invitation link above.'; }
});
</script>
@endpush

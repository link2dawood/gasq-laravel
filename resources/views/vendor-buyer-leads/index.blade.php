@extends('layouts.app')
@section('title', 'Buyer Enquiries')
@section('header_variant', 'dashboard')
@section('content')
<div class="container py-4">
    <h1>Buyer enquiries</h1>
    <p><a href="{{ route('vendor-estimates.index') }}">Estimate dashboard</a></p>
    <p class="text-muted">Share your private invitation link to collect completed GASQ Security Services Opportunity Qualification Forms. Each submission is tracked here and emailed to you with GASQ copied.</p>
    @if(session('status'))<div class="alert alert-success" role="status">{{ session('status') }}</div>@endif
    @if($errors->any())<div class="alert alert-danger" role="alert"><ul class="mb-0">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif
    <section class="border rounded p-3 mb-4" aria-labelledby="invitation-heading">
        <h2 class="h5" id="invitation-heading">Your private invitation link</h2>
        <label class="form-label small" for="invitation-url">Copy and share this link with prospective buyers.</label>
        <div class="d-flex flex-wrap gap-2"><input class="form-control" style="flex:1;min-width:220px;" id="invitation-url" readonly value="{{ $link->invitationUrl() }}" onclick="this.select()"><button class="btn btn-outline-primary" type="button" id="copy-invitation">Copy link</button><a class="btn btn-outline-secondary" href="{{ $link->invitationUrl() }}" target="_blank" rel="noopener noreferrer">Preview form</a></div>
        <p id="copy-status" class="small mt-2" role="status"></p>
        @php
            $invitationSubject = 'Your Private Invitation to Begin Your Security Services Estimate';
            $invitationBody = "Hi,\n\nThank you for considering ".(auth()->user()->company ?: auth()->user()->name)." for your security needs.\n\nPlease use your private link below to access and complete the GASQ Buyer Intake Form:\n\n".$link->invitationUrl()."\n\nPlease provide:\n\n- Decision-maker's name and contact information.\n- Projected or approved security budget.\n- Anticipated start date and end date, if applicable.\n- Requested scope of services, including location, coverage schedule, and staffing needs.\n\nAfter completing the form, please return it directly to ".(auth()->user()->name ?: auth()->user()->company)." at ".auth()->user()->email.".\n\nIf your budget or service requirements are still being developed, please indicate that on the form.\n\nOnce we receive your completed form, we will review your requirements and contact you to arrange a brief scope review and any necessary site assessment before preparing your estimate.\n\nThis invitation is intended for your organization. Please keep the link private.\n\nThank you,\n\n".(auth()->user()->name ?: auth()->user()->company)."\n".(auth()->user()->company ?: auth()->user()->name).(auth()->user()->phone ? "\n".auth()->user()->phone : "")."\n".auth()->user()->email."\nGASQ Network Vendor\n\nKnow Before You Buy.";
        @endphp
        <div class="border-top mt-3 pt-3">
            <h3 class="h6">Send a private invitation</h3>
            <p class="small text-muted">GASQ sends the approved invitation for you. Your company name, contact details and private link are filled in automatically, replies come back to you, and a copy lands in your inbox.</p>
            <form method="POST" action="{{ route('vendor-buyer-leads.send-invitation') }}" class="row g-2 align-items-end">@csrf
                <div class="col-md-5">
                    <label class="form-label" for="buyer_name">Buyer's name</label>
                    <input class="form-control @error('buyer_name') is-invalid @enderror" id="buyer_name" name="buyer_name" maxlength="255" value="{{ old('buyer_name') }}" required>
                    @error('buyer_name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-5">
                    <label class="form-label" for="buyer_email">Buyer's email address</label>
                    <input class="form-control @error('buyer_email') is-invalid @enderror" id="buyer_email" name="buyer_email" type="email" maxlength="255" value="{{ old('buyer_email') }}" required>
                    @error('buyer_email')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-2"><button class="btn btn-primary w-100" type="submit">Send invitation</button></div>
            </form>
        </div>
        <details class="mt-3"><summary class="small">Prefer to send it from your own email app?</summary>
            <a class="btn btn-outline-primary btn-sm mt-2" href="mailto:?subject={{ rawurlencode($invitationSubject) }}&amp;body={{ rawurlencode($invitationBody) }}">Draft invitation email</a>
            <p class="small text-muted mt-2 mb-0">Opens a draft in your email app. Add the buyer's email address, review the message, and send it directly.</p>
        </details>
        <form method="POST" action="{{ route('vendor-buyer-leads.rotate') }}" class="mt-3">@csrf
            <details><summary>Replace this invitation link</summary><p class="small text-muted mt-2">Replacing the link disables the previous one. Existing leads are kept.</p><button class="btn btn-sm btn-outline-danger" type="submit">Generate replacement link</button></details>
        </form>
    </section>
    <form method="GET" class="row g-2 align-items-end mb-4">
        <div class="col-md-4"><label class="form-label" for="q">Search name, company, or email</label><input class="form-control" id="q" name="q" maxlength="100" value="{{ request('q') }}"></div>
        <div class="col-md-3"><label class="form-label" for="status">Lead status</label><select class="form-select" id="status" name="status"><option value="">All lead statuses</option>@foreach(\App\Models\VendorBuyerLead::STATUSES as $value => $label)<option value="{{ $value }}" @selected(request('status') === $value)>{{ $label }}</option>@endforeach</select></div>
        <div class="col-md-3"><label class="form-label" for="qualification_status">Qualification review</label><select class="form-select" id="qualification_status" name="qualification_status"><option value="">All review outcomes</option>@foreach(\App\Support\BuyerQualificationQuestionnaire::STATUSES as $value => $meta)<option value="{{ $value }}" @selected(request('qualification_status') === $value)>{{ $meta['label'] }}</option>@endforeach</select></div>
        <div class="col-md-2"><button class="btn btn-primary" type="submit">Filter leads</button> <a href="{{ route('vendor-buyer-leads.index') }}">Clear</a></div>
    </form>
    <p class="text-muted">{{ $leads->total() }} {{ \Illuminate\Support\Str::plural('enquiry', $leads->total()) }}</p>
    @forelse($leads as $lead)
    <article class="border rounded p-3 mb-3">
        <div class="d-flex flex-wrap justify-content-between gap-2"><h2 class="h5 mb-1">{{ $lead->contact_name }}{{ $lead->contact_title ? ', '.$lead->contact_title : '' }}{{ $lead->company ? ' · '.$lead->company : '' }}</h2>
            <div class="d-flex flex-wrap gap-2 align-self-start">
                @if($lead->qualification_status)<span class="badge bg-{{ $lead->qualificationStatusBadge() }}">{{ $lead->qualificationStatusLabel() }}</span>@endif
                <span class="badge bg-secondary">{{ \App\Models\VendorBuyerLead::STATUSES[$lead->status] }}</span>
            </div>
        </div>
        <p class="small text-muted">Received {{ $lead->created_at->format('M j, Y g:i A') }}</p>
        @if($lead->qualification_status)<p class="small text-muted mb-3">{{ $lead->qualificationStatusGuidance() }}</p>@endif
        <p><strong>Email:</strong> <a href="mailto:{{ $lead->email }}">{{ $lead->email }}</a><br><strong>Phone:</strong> {{ $lead->phone }}@if($lead->preferred_contact_method)<br><strong>Prefers:</strong> {{ \App\Support\BuyerQualificationQuestionnaire::CONTACT_METHODS[$lead->preferred_contact_method] ?? 'Not supplied' }}@endif</p>
        @if($lead->decision_maker_name)<p><strong>Authorized decision-maker:</strong> {{ $lead->decision_maker_name }}<br><strong>Decision-maker phone:</strong> {{ $lead->decision_maker_phone ?: 'Not supplied' }}<br><strong>Decision-maker email:</strong> {{ $lead->decision_maker_email ?: 'Not supplied' }}</p>@endif
        <p><strong>Site:</strong> {{ $lead->site_address }}@if($lead->industry)<br><strong>Industry:</strong> {{ $lead->industry }}@endif<br><strong>Service:</strong> {{ $lead->serviceTypeLabel() }}<br><strong>Approved budget:</strong> {{ $lead->approvedBudgetLabel() }}<br><strong>Requested start:</strong> {{ $lead->start_date?->format('M j, Y') ?? 'Not supplied' }}@if($lead->service_end_date)<br><strong>Duration/end date:</strong> {{ $lead->service_end_date->format('M j, Y') }}@endif<br><strong>Coverage:</strong> {{ $lead->coverageLabel() }}<br><strong>Weekly service hours:</strong> {{ $lead->weekly_hours ?? 'Not supplied' }}</p>
        <p class="mb-1"><strong>Duties and requirements</strong></p><div style="white-space:pre-wrap;overflow-wrap:anywhere;">{{ $lead->scope }}</div>
        @if($lead->qualification_answers)
        @php $sections = \App\Support\BuyerQualificationQuestionnaire::answerSummary($lead); $clarify = \App\Support\BuyerQualificationQuestionnaire::clarificationItems($lead); @endphp
        @if($clarify)
        <div class="alert alert-warning mt-3 mb-0 py-2 px-3">
            <strong class="d-block">Clarify before the qualification meeting</strong>
            <span class="small">A "No" should prompt clarification rather than automatic rejection.</span>
            <ul class="small mb-0 mt-1">@foreach($clarify as $item)<li>{{ $item }}</li>@endforeach</ul>
        </div>
        @endif
        <details class="mt-3"><summary>Qualification form answers</summary>
            @foreach($sections as $section)
            <p class="fw-semibold mb-1 mt-3">{{ $section['title'] }}</p>
            <table class="table table-sm mb-0"><tbody>
                @foreach($section['rows'] as $row)
                <tr><td style="width:3rem;" class="text-muted">{{ $row['number'] }}</td><td>{{ $row['text'] }}</td><td class="text-end @if($row['is_no']) text-danger fw-bold @endif" style="width:5rem;">{{ $row['answer'] }}</td></tr>
                @endforeach
            </tbody></table>
            @endforeach
        </details>
        @endif
        <p class="mt-3"><a href="{{ route('vendor-estimates.create', $lead) }}" class="btn btn-outline-primary btn-sm">Prepare estimate</a></p>
        @if($lead->estimates_count)<p><a href="{{ route('vendor-estimates.index', ['lead' => $lead->id]) }}">View estimates ({{ $lead->estimates_count }})</a></p>@endif
        <details class="mt-3" @if($lead->notes) open @endif><summary>Manage lead</summary>
            <form method="POST" action="{{ route('vendor-buyer-leads.update', $lead) }}" class="mt-3">@csrf @method('PATCH')
                <label class="form-label" for="status-{{ $lead->id }}">Lead status</label><select class="form-select mb-2" id="status-{{ $lead->id }}" name="status">@foreach(\App\Models\VendorBuyerLead::STATUSES as $value => $label)<option value="{{ $value }}" @selected($lead->status === $value)>{{ $label }}</option>@endforeach</select>
                <label class="form-label" for="qualification-status-{{ $lead->id }}">Internal review outcome</label><select class="form-select mb-2" id="qualification-status-{{ $lead->id }}" name="qualification_status"><option value="">Not assessed</option>@foreach(\App\Support\BuyerQualificationQuestionnaire::STATUSES as $value => $meta)<option value="{{ $value }}" @selected($lead->qualification_status === $value)>{{ $meta['label'] }}</option>@endforeach</select>
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

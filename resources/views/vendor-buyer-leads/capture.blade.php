@extends('layouts.app')
@section('title', 'Request Security Services')
@section('content')
<div class="container py-5" style="max-width:820px;">
    <p class="text-uppercase small text-muted mb-2">Private buyer invitation</p>
    <h1 class="mb-3">Tell us what you need protected.</h1>
    <p class="lead">Send your requirements to {{ $link->vendor->company ?: $link->vendor->name }}.</p>
    @if(session('enquiry_received'))
        <div class="alert alert-success" role="status"><h2 class="h5">Your enquiry has been received.</h2>Your details have been sent to {{ $link->vendor->company ?: $link->vendor->name }} for review. They can contact you to discuss your requirements.</div>
        <a href="{{ route('buyer-enquiries.show', $link->token) }}" class="btn btn-outline-primary">Submit another enquiry</a>
    @else
        <p class="text-muted">Share your contact details and scope of services. No account is required. Fields marked * are required.</p>
        @if($errors->any())<div class="alert alert-danger" role="alert">Please correct the highlighted fields.</div>@endif
        <form method="POST" action="{{ route('buyer-enquiries.store', $link->token) }}">
            @csrf
            <h2 class="h4 mt-4">Your contact details</h2>
            <div class="row g-3">
                @foreach(['contact_name' => ['Full name', 'text', 255, true], 'company' => ['Company', 'text', 255, false], 'email' => ['Email address', 'email', 255, true], 'phone' => ['Phone number', 'tel', 50, true]] as $field => [$label, $type, $max, $required])
                <div class="col-md-6"><label class="form-label" for="{{ $field }}">{{ $label }}{{ $required ? ' *' : '' }}</label>
                    <input class="form-control @error($field) is-invalid @enderror" id="{{ $field }}" name="{{ $field }}" type="{{ $type }}" maxlength="{{ $max }}" value="{{ old($field) }}" @required($required)>
                    @error($field)<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                @endforeach
            </div>
            <h2 class="h4 mt-4">Scope of services</h2>
            <div class="mb-3"><label class="form-label" for="site_address">Service location / site address *</label>
                <input class="form-control @error('site_address') is-invalid @enderror" id="site_address" name="site_address" maxlength="500" value="{{ old('site_address') }}" required>
                @error('site_address')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            <div class="mb-3"><label class="form-label" for="service_type">Type of service *</label>
                <input class="form-control @error('service_type') is-invalid @enderror" id="service_type" name="service_type" maxlength="100" placeholder="For example: unarmed guarding, patrol, or event security" value="{{ old('service_type') }}" required>
                @error('service_type')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            <div class="mb-3"><label class="form-label" for="scope">Describe your requirements *</label>
                <textarea class="form-control @error('scope') is-invalid @enderror" id="scope" name="scope" rows="5" maxlength="10000" placeholder="Include site details, duties, coverage days and times, staffing needs, and any special requirements." required>{{ old('scope') }}</textarea>
                @error('scope')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            <div class="row g-3 mb-4">
                <div class="col-md-6"><label class="form-label" for="start_date">Preferred start date</label><input class="form-control @error('start_date') is-invalid @enderror" id="start_date" name="start_date" type="date" min="{{ now()->toDateString() }}" value="{{ old('start_date') }}">@error('start_date')<div class="invalid-feedback">{{ $message }}</div>@enderror</div>
                <div class="col-md-6"><label class="form-label" for="weekly_hours">Estimated total weekly service hours</label><input class="form-control @error('weekly_hours') is-invalid @enderror" id="weekly_hours" name="weekly_hours" type="number" min="0.01" max="100000" step="0.01" value="{{ old('weekly_hours') }}">@error('weekly_hours')<div class="invalid-feedback">{{ $message }}</div>@enderror</div>
            </div>
            <p class="small text-muted">Your submission will be shared with {{ $link->vendor->company ?: $link->vendor->name }} so they can respond to your enquiry.</p>
            <button type="submit" class="btn btn-primary">Send my enquiry</button>
        </form>
    @endif
</div>
@endsection

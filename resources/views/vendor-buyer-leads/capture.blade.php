@extends('layouts.app')
@section('title', 'Security Services Opportunity Qualification Form')
@section('content')
@php use App\Support\BuyerQualificationQuestionnaire as Form; @endphp
<div class="container py-5" style="max-width:860px;">
    <p class="text-uppercase small text-muted mb-2">Private buyer invitation</p>
    <h1 class="mb-1">GASQ Security Services Opportunity Qualification Form</h1>
    <p class="lead fst-italic mb-3">Know Before You Buy.</p>
    @if(session('enquiry_received'))
        <div class="alert alert-success" role="status">
            <h2 class="h5">Your completed form has been returned.</h2>
            Your answers have gone to {{ $returnToName }} at {{ $returnToCompany }}, a copy has been emailed to you for your records, and a copy has been filed with GASQ. You will be contacted to arrange a brief scope review and any necessary site assessment before an estimate is prepared.
        </div>
        <a href="{{ route('buyer-enquiries.show', $link->token) }}" class="btn btn-outline-primary">Submit another form</a>
    @else
        <p>Complete this form for {{ $returnToCompany }}. No account is required.</p>
        <div class="alert alert-info" role="note">
            <strong class="d-block">Where this form is returned</strong>
            When you submit, your completed form is returned directly to <strong>{{ $returnToName }}</strong> at <a href="mailto:{{ $returnToEmail }}">{{ $returnToEmail }}</a>, and a copy is emailed to you for your records. There is nothing to print, attach or send yourself.
        </div>
        <p class="text-muted">All information below is required. A "No" answer does not disqualify your project: it tells us what to clarify with you before an estimate is prepared. If your budget or service requirements are still being developed, say so in the scope of services box below.</p>
        @if($errors->any())<div class="alert alert-danger" role="alert">Please complete the highlighted fields. Every question must be answered.</div>@endif
        <form method="POST" action="{{ route('buyer-enquiries.store', $link->token) }}">
            @csrf

            <h2 class="h4 mt-4">Contact and project information</h2>
            <div class="row g-3">
                <div class="col-md-6">
                    <label class="form-label" for="company">Company/property name *</label>
                    <input class="form-control @error('company') is-invalid @enderror" id="company" name="company" maxlength="255" value="{{ old('company') }}" required>
                    @error('company')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-6">
                    <label class="form-label" for="industry">Business industry *</label>
                    <input class="form-control @error('industry') is-invalid @enderror" id="industry" name="industry" maxlength="255" value="{{ old('industry') }}" required>
                    @error('industry')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-6">
                    <label class="form-label" for="contact_name">Contact name *</label>
                    <input class="form-control @error('contact_name') is-invalid @enderror" id="contact_name" name="contact_name" maxlength="255" value="{{ old('contact_name') }}" required>
                    @error('contact_name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-6">
                    <label class="form-label" for="contact_title">Contact title *</label>
                    <input class="form-control @error('contact_title') is-invalid @enderror" id="contact_title" name="contact_title" maxlength="255" value="{{ old('contact_title') }}" required>
                    @error('contact_title')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-6">
                    <label class="form-label" for="phone">Phone *</label>
                    <input class="form-control @error('phone') is-invalid @enderror" id="phone" name="phone" type="tel" maxlength="50" value="{{ old('phone') }}" required>
                    @error('phone')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-6">
                    <label class="form-label" for="email">Your email address *</label>
                    <input class="form-control @error('email') is-invalid @enderror" id="email" name="email" type="email" maxlength="255" value="{{ old('email') }}" required aria-describedby="email-help">
                    <div class="form-text" id="email-help">Your copy of the completed form is sent here.</div>
                    @error('email')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-6">
                    <label class="form-label" for="return_to_email">Return to (vendor) *</label>
                    <input class="form-control" id="return_to_email" name="return_to_email" type="email" value="{{ $returnToEmail }}" readonly aria-describedby="return-to-help">
                    <div class="form-text" id="return-to-help">{{ $returnToName }} at {{ $returnToCompany }} receives your completed form at this address.</div>
                </div>
                <div class="col-12">
                    <label class="form-label" for="site_address">Service location/address *</label>
                    <input class="form-control @error('site_address') is-invalid @enderror" id="site_address" name="site_address" maxlength="500" value="{{ old('site_address') }}" required>
                    @error('site_address')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-4">
                    <label class="form-label" for="decision_maker_name">Authorized decision-maker's name *</label>
                    <input class="form-control @error('decision_maker_name') is-invalid @enderror" id="decision_maker_name" name="decision_maker_name" maxlength="255" value="{{ old('decision_maker_name') }}" required>
                    @error('decision_maker_name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-4">
                    <label class="form-label" for="decision_maker_phone">Decision-maker's phone *</label>
                    <input class="form-control @error('decision_maker_phone') is-invalid @enderror" id="decision_maker_phone" name="decision_maker_phone" type="tel" maxlength="50" value="{{ old('decision_maker_phone') }}" required>
                    @error('decision_maker_phone')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-4">
                    <label class="form-label" for="decision_maker_email">Decision-maker's email *</label>
                    <input class="form-control @error('decision_maker_email') is-invalid @enderror" id="decision_maker_email" name="decision_maker_email" type="email" maxlength="255" value="{{ old('decision_maker_email') }}" required>
                    @error('decision_maker_email')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-6">
                    <label class="form-label" for="approved_budget">Approved budget ($) *</label>
                    <input class="form-control @error('approved_budget') is-invalid @enderror" id="approved_budget" name="approved_budget" type="number" min="0.01" step="0.01" max="99999999" value="{{ old('approved_budget') }}" required>
                    @error('approved_budget')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-6">
                    <label class="form-label" for="approved_budget_basis">The approved budget is *</label>
                    <select class="form-select @error('approved_budget_basis') is-invalid @enderror" id="approved_budget_basis" name="approved_budget_basis" required>
                        <option value="">Select one</option>
                        @foreach(Form::BUDGET_BASIS as $value => $label)
                        <option value="{{ $value }}" @selected(old('approved_budget_basis') === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                    @error('approved_budget_basis')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-6">
                    <label class="form-label" for="start_date">Requested start date *</label>
                    <input class="form-control @error('start_date') is-invalid @enderror" id="start_date" name="start_date" type="date" min="{{ now()->toDateString() }}" value="{{ old('start_date') }}" required>
                    @error('start_date')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-6">
                    <label class="form-label" for="service_end_date">Anticipated service duration/end date *</label>
                    <input class="form-control @error('service_end_date') is-invalid @enderror" id="service_end_date" name="service_end_date" type="date" min="{{ now()->toDateString() }}" value="{{ old('service_end_date') }}" required>
                    @error('service_end_date')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-3">
                    <label class="form-label" for="hours_per_day">Coverage hours per day *</label>
                    <input class="form-control @error('hours_per_day') is-invalid @enderror" id="hours_per_day" name="hours_per_day" type="number" min="0.5" max="24" step="0.5" value="{{ old('hours_per_day') }}" required>
                    @error('hours_per_day')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-3">
                    <label class="form-label" for="days_per_week">Days per week *</label>
                    <input class="form-control @error('days_per_week') is-invalid @enderror" id="days_per_week" name="days_per_week" type="number" min="1" max="7" step="1" value="{{ old('days_per_week') }}" required>
                    @error('days_per_week')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-3">
                    <label class="form-label" for="officers_per_shift">Officers required per shift *</label>
                    <input class="form-control @error('officers_per_shift') is-invalid @enderror" id="officers_per_shift" name="officers_per_shift" type="number" min="1" max="500" step="1" value="{{ old('officers_per_shift') }}" required>
                    @error('officers_per_shift')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-3">
                    <label class="form-label" for="number_of_weeks">Number of weeks *</label>
                    <input class="form-control @error('number_of_weeks') is-invalid @enderror" id="number_of_weeks" name="number_of_weeks" type="number" min="1" max="520" step="1" value="{{ old('number_of_weeks') }}" required>
                    @error('number_of_weeks')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
            </div>

            <fieldset class="mt-4">
                <legend class="form-label">Service type * <span class="text-muted fw-normal">(select all that apply)</span></legend>
                @error('service_types')<div class="text-danger small mb-2">{{ $message }}</div>@enderror
                <div class="d-flex flex-wrap gap-3">
                    @foreach(Form::SERVICE_TYPES as $value => $label)
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" id="service_type_{{ $value }}" name="service_types[]" value="{{ $value }}" @checked(in_array($value, (array) old('service_types', []), true))>
                        <label class="form-check-label" for="service_type_{{ $value }}">{{ $label }}</label>
                    </div>
                    @endforeach
                </div>
                <div class="mt-2" style="max-width:360px;">
                    <label class="form-label small" for="service_type_other">If other, please specify</label>
                    <input class="form-control @error('service_type_other') is-invalid @enderror" id="service_type_other" name="service_type_other" maxlength="100" value="{{ old('service_type_other') }}">
                    @error('service_type_other')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
            </fieldset>

            <fieldset class="mt-4">
                <legend class="form-label">Which method of communication do you prefer? *</legend>
                @error('preferred_contact_method')<div class="text-danger small mb-2">{{ $message }}</div>@enderror
                <div class="d-flex flex-wrap gap-3">
                    @foreach(Form::CONTACT_METHODS as $value => $label)
                    <div class="form-check">
                        <input class="form-check-input" type="radio" id="contact_method_{{ $value }}" name="preferred_contact_method" value="{{ $value }}" @checked(old('preferred_contact_method') === $value) required>
                        <label class="form-check-label" for="contact_method_{{ $value }}">{{ $label }}</label>
                    </div>
                    @endforeach
                </div>
            </fieldset>

            <h2 class="h4 mt-4">Scope of services</h2>
            <div class="mb-3">
                <label class="form-label" for="scope">Duties the security personnel will perform *</label>
                <textarea class="form-control @error('scope') is-invalid @enderror" id="scope" name="scope" rows="5" maxlength="10000" placeholder="Include site details, post orders and duties, access control, patrol expectations, reporting, and any special requirements." required>{{ old('scope') }}</textarea>
                @error('scope')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>

            @foreach($sections as $section)
            <fieldset class="border rounded p-3 mt-4">
                <legend class="h5 float-none w-auto px-2">{{ $section['title'] }}</legend>
                @foreach($section['questions'] as $question)
                @php $key = $question['key']; $answered = old("answers.$key"); @endphp
                <div class="row g-2 align-items-start py-2 @if(! $loop->last) border-bottom @endif">
                    <div class="col-md-8">
                        <span class="text-muted me-2">{{ $question['number'] }}.</span>{{ $question['text'] }}
                        @error("answers.$key")<div class="text-danger small mt-1">Please answer question {{ $question['number'] }}.</div>@enderror
                    </div>
                    <div class="col-md-4">
                        <div class="d-flex flex-wrap gap-3 justify-content-md-end">
                            @foreach($question['options'] as $value => $label)
                            <div class="form-check">
                                <input class="form-check-input @error("answers.$key") is-invalid @enderror" type="radio" id="answers_{{ $key }}_{{ $value }}" name="answers[{{ $key }}]" value="{{ $value }}" @checked($answered === $value) required>
                                <label class="form-check-label" for="answers_{{ $key }}_{{ $value }}">{{ $label }}</label>
                            </div>
                            @endforeach
                        </div>
                    </div>
                </div>
                @endforeach
            </fieldset>
            @endforeach

            <p class="small text-muted mt-4">Submitting returns your completed form to {{ $returnToName }} at {{ $returnToEmail }}, sends a copy to your own email address, and files a copy with GASQ. It is used to qualify your project and prepare an estimate.</p>
            <button type="submit" class="btn btn-primary btn-lg">Submit and return this form</button>
        </form>
    @endif
</div>
@endsection

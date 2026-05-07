@extends('layouts.app')

@section('title', 'Questionnaire Submitted')

@section('content')
<div class="container py-4">
    <div class="row">
        <div class="col-lg-8 mx-auto">
            <div class="card border-0 shadow-sm">
                <div class="card-body p-4">
                    <div class="text-center mb-4">
                        <div class="display-1 text-success mb-2">&#10004;</div>
                        <h3>Questionnaire Submitted</h3>
                        <p class="text-muted">
                            Your response was sent to the buyer on
                            <strong>{{ $questionnaire->submitted_at?->format('M j, Y g:i A') }}</strong>.
                        </p>
                    </div>

                    <dl class="row">
                        <dt class="col-sm-5">Job</dt>
                        <dd class="col-sm-7">{{ $questionnaire->jobPosting?->title ?? '—' }}</dd>

                        <dt class="col-sm-5">Responsive Status</dt>
                        <dd class="col-sm-7">
                            @if($questionnaire->is_responsive)
                                <span class="badge bg-success">RESPONSIVE</span>
                            @else
                                <span class="badge bg-warning text-dark">Non-responsive</span>
                            @endif
                        </dd>

                        <dt class="col-sm-5">Responsible Status</dt>
                        <dd class="col-sm-7">
                            @if($questionnaire->is_responsible)
                                <span class="badge bg-success">RESPONSIBLE</span>
                            @else
                                <span class="badge bg-warning text-dark">Non-responsible</span>
                            @endif
                        </dd>

                        <dt class="col-sm-5">Documents Submitted</dt>
                        <dd class="col-sm-7">{{ $questionnaire->documents->count() }}</dd>

                        <dt class="col-sm-5">Buyer review link expires</dt>
                        <dd class="col-sm-7">{{ $questionnaire->buyer_review_expires_at?->format('M j, Y') }}</dd>
                    </dl>

                    <div class="text-center mt-4">
                        <a href="{{ route('home') }}" class="btn btn-outline-secondary">Return to dashboard</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

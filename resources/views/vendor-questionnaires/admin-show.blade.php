@extends('layouts.app')

@section('title', 'Admin — Vendor Questionnaire')

@section('content')
<div class="container py-4">
    <div class="row">
        <div class="col-lg-10 mx-auto">
            <h3 class="mb-1">Vendor Qualification Response (Admin View)</h3>
            <p class="text-muted small">
                Vendor: <strong>{{ $questionnaire->vendor?->name }}</strong> &middot;
                Buyer: <strong>{{ $questionnaire->jobPosting?->user?->name }}</strong> &middot;
                Job: <strong>{{ $questionnaire->jobPosting?->title }}</strong>
            </p>
            <p class="small">
                Status: <strong>{{ strtoupper($questionnaire->status) }}</strong>
                @if($questionnaire->submitted_at)
                    &middot; Submitted {{ $questionnaire->submitted_at->format('M j, Y g:i A') }}
                @endif
            </p>

            <div class="d-flex gap-2 mb-4">
                @if($questionnaire->is_responsive)
                    <span class="badge bg-success">RESPONSIVE</span>
                @elseif($questionnaire->is_responsive === false)
                    <span class="badge bg-warning text-dark">Non-responsive</span>
                @endif
                @if($questionnaire->is_responsible)
                    <span class="badge bg-success">RESPONSIBLE</span>
                @elseif($questionnaire->is_responsible === false)
                    <span class="badge bg-warning text-dark">Non-responsible</span>
                @endif
            </div>

            @include('vendor-questionnaires._partials.response-summary', [
                'questionnaire' => $questionnaire,
                'documentTypes' => $documentTypes,
                'showDocumentLinks' => true,
            ])
        </div>
    </div>
</div>
@endsection

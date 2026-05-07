@extends('layouts.app')

@section('title', 'Vendor Qualification Response')

@section('content')
<div class="container py-4">
    <div class="row">
        <div class="col-lg-10 mx-auto">
            <h3 class="mb-1">Vendor Qualification Response</h3>
            <p class="text-muted">
                Submitted by <strong>{{ $questionnaire->vendor?->name }}</strong>
                on {{ $questionnaire->submitted_at?->format('M j, Y g:i A') }}
            </p>

            <div class="d-flex gap-2 mb-4">
                @if($questionnaire->is_responsive)
                    <span class="badge bg-success fs-6">RESPONSIVE</span>
                @else
                    <span class="badge bg-warning text-dark fs-6">Non-responsive</span>
                @endif
                @if($questionnaire->is_responsible)
                    <span class="badge bg-success fs-6">RESPONSIBLE</span>
                @else
                    <span class="badge bg-warning text-dark fs-6">Non-responsible</span>
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

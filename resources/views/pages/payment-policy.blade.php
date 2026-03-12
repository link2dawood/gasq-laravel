@extends('layouts.app')

@section('title', 'Payment Model & Policy')

@section('content')
<div class="container py-5">
    <h1 class="h2 mb-4">Payment Model & Appraisal Policy</h1>
    @if(isset($sections) && $sections->isNotEmpty())
        <div class="accordion" id="policyAccordion">
            @foreach($sections as $i => $section)
                <div class="accordion-item">
                    <h2 class="accordion-header">
                        <button class="accordion-button {{ $i > 0 ? 'collapsed' : '' }}" type="button" data-bs-toggle="collapse" data-bs-target="#policy-{{ $section->id }}">
                            {{ $section->title }}
                        </button>
                    </h2>
                    <div id="policy-{{ $section->id }}" class="accordion-collapse collapse {{ $i === 0 ? 'show' : '' }}" data-bs-parent="#policyAccordion">
                        <div class="accordion-body">
                            {!! $section->body !!}
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    @else
        <div class="card gasq-card">
            <div class="card-body">
                <p class="text-gasq-muted mb-0">Payment terms and appraisal policy. Content is managed by the admin team.</p>
            </div>
        </div>
    @endif
</div>
@endsection

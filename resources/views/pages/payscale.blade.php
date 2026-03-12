@extends('layouts.app')

@section('title', 'Pay Scale')

@section('content')
<div class="container py-5">
    <h1 class="h2 mb-4">Pay Scale</h1>
    @if(isset($sections) && $sections->isNotEmpty())
        <div class="accordion" id="payscaleAccordion">
            @foreach($sections as $i => $section)
                <div class="accordion-item">
                    <h2 class="accordion-header">
                        <button class="accordion-button {{ $i > 0 ? 'collapsed' : '' }}" type="button" data-bs-toggle="collapse" data-bs-target="#payscale-{{ $section->id }}">
                            {{ $section->title }}
                        </button>
                    </h2>
                    <div id="payscale-{{ $section->id }}" class="accordion-collapse collapse {{ $i === 0 ? 'show' : '' }}" data-bs-parent="#payscaleAccordion">
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
                <p class="text-gasq-muted mb-0">Pay scale and compensation information for security services. Content is managed by the admin team.</p>
            </div>
        </div>
    @endif
</div>
@endsection

@extends('layouts.app')

@section('title', 'FAQ')

@section('content')
<div class="container py-5">
    <h1 class="h2 mb-4">Frequently Asked Questions</h1>
    @if($faqs->isEmpty())
        <p class="text-muted">No FAQs yet.</p>
    @else
        <div class="accordion" id="faqAccordion">
            @foreach($faqs as $i => $faq)
                <div class="accordion-item">
                    <h2 class="accordion-header">
                        <button class="accordion-button {{ $i > 0 ? 'collapsed' : '' }}" type="button" data-bs-toggle="collapse" data-bs-target="#faq-{{ $faq->id }}">
                            {{ $faq->question }}
                        </button>
                    </h2>
                    <div id="faq-{{ $faq->id }}" class="accordion-collapse collapse {{ $i === 0 ? 'show' : '' }}" data-bs-parent="#faqAccordion">
                        <div class="accordion-body">{{ $faq->answer }}</div>
                    </div>
                </div>
            @endforeach
        </div>
    @endif
</div>
@endsection

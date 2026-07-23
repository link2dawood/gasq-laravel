@extends('layouts.app')

@section('title', $faqTitle ?? 'FAQ')

@section('content')
<div class="container py-5">
    <h1 class="h2 mb-4">{{ $faqTitle ?? 'Frequently Asked Questions' }}</h1>
    @if(request()->routeIs('buyer-faq') || request()->routeIs('vendor-faq'))
        <div class="d-flex flex-wrap gap-2 mb-4">
            <a href="{{ route('buyer-faq') }}" class="btn btn-sm {{ request()->routeIs('buyer-faq') ? 'btn-primary' : 'btn-outline-primary' }}">Buyer FAQ</a>
            <a href="{{ route('vendor-faq') }}" class="btn btn-sm {{ request()->routeIs('vendor-faq') ? 'btn-primary' : 'btn-outline-primary' }}">Vendor FAQ</a>
        </div>
    @endif
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
                        <div class="accordion-body">
                            {!! nl2br(e($faq->answer)) !!}
                            @if(!empty($faq->cta_url))
                                <div class="mt-3">
                                    <a href="{{ $faq->cta_url }}" class="btn btn-sm btn-outline-primary">{{ $faq->cta_label ?: 'Learn more' }}</a>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    @endif
</div>
@endsection

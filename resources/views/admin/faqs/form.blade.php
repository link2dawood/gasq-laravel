@extends('layouts.app')

@section('title', $isEdit ? 'Edit FAQ' : 'Add FAQ')

@section('content')
<div class="container py-4">
    <div class="mb-4">
        <h1 class="h2 fw-bold mb-1">{{ $isEdit ? 'Edit FAQ' : 'Add FAQ' }}</h1>
        <p class="text-gasq-muted mb-0"><a href="{{ route('admin.faqs.index') }}">← Back to FAQs</a></p>
    </div>

    @if ($errors->any())
        <div class="alert alert-danger alert-dismissible fade show mb-4" role="alert">
            <ul class="mb-0">@foreach ($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    <div class="card gasq-card">
        <div class="card-body">
            <form method="POST" action="{{ $isEdit ? route('admin.faqs.update', $faq) : route('admin.faqs.store') }}">
                @csrf
                @if($isEdit) @method('PUT') @endif
                <div class="mb-3">
                    <label class="form-label">Question</label>
                    <input type="text" name="question" class="form-control" value="{{ old('question', $faq->question) }}" required maxlength="500">
                </div>
                <div class="mb-3">
                    <label class="form-label">Answer</label>
                    <textarea name="answer" class="form-control" rows="4" required>{{ old('answer', $faq->answer) }}</textarea>
                </div>
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Order</label>
                        <input type="number" name="order" class="form-control" value="{{ old('order', $faq->order) }}" min="0">
                    </div>
                    <div class="col-md-6 mb-3 d-flex align-items-end">
                        <div class="form-check">
                            <input type="checkbox" name="is_active" value="1" class="form-check-input" id="faq_active" {{ old('is_active', $faq->is_active) ? 'checked' : '' }}>
                            <label class="form-check-label" for="faq_active">Visible on FAQ page</label>
                        </div>
                    </div>
                </div>
                <button type="submit" class="btn btn-primary">{{ $isEdit ? 'Update' : 'Create' }}</button>
            </form>
        </div>
    </div>
</div>
@endsection

@extends('layouts.app')

@section('title', $isEdit ? 'Edit Section' : 'Add Section')

@section('content')
<div class="container py-4">
    <div class="mb-4">
        <h1 class="h2 fw-bold mb-1">{{ $isEdit ? 'Edit Section' : 'Add Section' }}</h1>
        <p class="text-gasq-muted mb-0"><a href="{{ route('admin.content-sections.index', ['page' => $section->page_slug]) }}">Back to Page Content</a></p>
    </div>

    @if ($errors->any())
        <div class="alert alert-danger alert-dismissible fade show mb-4" role="alert">
            <ul class="mb-0">@foreach ($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    <div class="card gasq-card">
        <div class="card-body">
            <form method="POST" action="{{ $isEdit ? route('admin.content-sections.update', $section) : route('admin.content-sections.store') }}">
                @csrf
                @if($isEdit) @method('PUT') @endif
                <div class="mb-3">
                    <label class="form-label">Page</label>
                    <select name="page_slug" class="form-select" required>
                        @foreach($pageOptions as $slug => $label)
                            <option value="{{ $slug }}" {{ old('page_slug', $section->page_slug) === $slug ? 'selected' : '' }}>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="mb-3">
                    <label class="form-label">Title</label>
                    <input type="text" name="title" class="form-control" value="{{ old('title', $section->title) }}" required maxlength="255">
                </div>
                <div class="mb-3">
                    <label class="form-label">Body (HTML allowed)</label>
                    <textarea name="body" class="form-control" rows="6">{{ old('body', $section->body) }}</textarea>
                </div>
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Order</label>
                        <input type="number" name="order" class="form-control" value="{{ old('order', $section->order) }}" min="0">
                    </div>
                    <div class="col-md-6 mb-3 d-flex align-items-end">
                        <div class="form-check">
                            <input type="checkbox" name="is_active" value="1" class="form-check-input" id="section_active" {{ old('is_active', $section->is_active) ? 'checked' : '' }}>
                            <label class="form-check-label" for="section_active">Visible on frontend</label>
                        </div>
                    </div>
                </div>
                <button type="submit" class="btn btn-primary">{{ $isEdit ? 'Update' : 'Create' }}</button>
            </form>
        </div>
    </div>
</div>
@endsection

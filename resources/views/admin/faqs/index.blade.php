@extends('layouts.app')

@section('title', 'Admin FAQs')

@section('content')
<div class="container py-4">
    <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-4">
        <div>
            <h1 class="h2 fw-bold mb-1">FAQs</h1>
            <p class="text-gasq-muted mb-0">Manage frequently asked questions (order and visibility).</p>
        </div>
        <a href="{{ route('admin.faqs.create') }}" class="btn btn-primary">Add FAQ</a>
    </div>

    @if (session('success'))
        <div class="alert alert-success alert-dismissible fade show mb-4" role="alert">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif
    @if ($errors->any())
        <div class="alert alert-danger alert-dismissible fade show mb-4" role="alert">
            <ul class="mb-0">@foreach ($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    <div class="card gasq-card">
        <div class="card-body">
            @if($faqs->isEmpty())
                <p class="text-gasq-muted mb-0">No FAQs yet. <a href="{{ route('admin.faqs.create') }}">Add one</a>.</p>
            @else
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead>
                            <tr>
                                <th style="width: 60px;">Order</th>
                                <th>Question</th>
                                <th class="text-center" style="width: 100px;">Active</th>
                                <th style="width: 140px;"></th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($faqs as $faq)
                                <tr>
                                    <td>{{ $faq->order }}</td>
                                    <td>{{ Str::limit($faq->question, 60) }}</td>
                                    <td class="text-center">{{ $faq->is_active ? 'Yes' : 'No' }}</td>
                                    <td>
                                        <a href="{{ route('admin.faqs.edit', $faq) }}" class="btn btn-sm btn-outline-primary me-1">Edit</a>
                                        <form action="{{ route('admin.faqs.destroy', $faq) }}" method="POST" class="d-inline" onsubmit="return confirm('Delete this FAQ?');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-sm btn-outline-danger">Delete</button>
                                        </form>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>
    </div>
    <p class="mt-3 mb-0"><a href="{{ route('admin.settings') }}" class="text-gasq-muted">Back to Settings</a></p>
</div>
@endsection

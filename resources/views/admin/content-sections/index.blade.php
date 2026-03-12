@extends('layouts.app')

@section('title', 'Admin Page Content')

@section('content')
<div class="container py-4">
    <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-4">
        <div>
            <h1 class="h2 fw-bold mb-1">Page Content</h1>
            <p class="text-gasq-muted mb-0">Sections for Pay Scale, Payment Policy, and Post Coverage Schedule.</p>
        </div>
        <a href="{{ route('admin.content-sections.create', ['page' => $currentPage]) }}" class="btn btn-primary">Add section</a>
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

    <ul class="nav nav-tabs mb-4">
        @foreach($pageOptions as $slug => $label)
            <li class="nav-item">
                <a class="nav-link {{ $currentPage === $slug ? 'active' : '' }}" href="{{ route('admin.content-sections.index', ['page' => $slug]) }}">{{ $label }}</a>
            </li>
        @endforeach
    </ul>

    <div class="card gasq-card">
        <div class="card-body">
            @if($sections->isEmpty())
                <p class="text-gasq-muted mb-0">No sections for this page. <a href="{{ route('admin.content-sections.create', ['page' => $currentPage]) }}">Add one</a>.</p>
            @else
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead>
                            <tr>
                                <th style="width: 60px;">Order</th>
                                <th>Title</th>
                                <th class="text-center" style="width: 100px;">Active</th>
                                <th style="width: 140px;"></th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($sections as $section)
                                <tr>
                                    <td>{{ $section->order }}</td>
                                    <td>{{ Str::limit($section->title, 50) }}</td>
                                    <td class="text-center">{{ $section->is_active ? 'Yes' : 'No' }}</td>
                                    <td>
                                        <a href="{{ route('admin.content-sections.edit', $section) }}" class="btn btn-sm btn-outline-primary me-1">Edit</a>
                                        <form action="{{ route('admin.content-sections.destroy', $section) }}" method="POST" class="d-inline" onsubmit="return confirm('Delete this section?');">
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

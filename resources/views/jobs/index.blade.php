@extends('layouts.app')

@section('title', 'Job Board')

@section('content')
<div class="container py-4 px-4">
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-4">
        <h1 class="gasq-page-title mb-0">Job Board</h1>
        @auth
            @if(auth()->user()->isBuyer())
                <a href="{{ route('jobs.create') }}" class="btn btn-primary">Post a Job</a>
            @endif
        @endauth
    </div>

    <div class="card gasq-card mb-4">
        <div class="card-body p-4">
            <form action="{{ route('job-board') }}" method="get" class="row g-3 mb-0">
                <div class="col-md-3">
                    <label class="form-label small text-gasq-muted">Search</label>
                    <input type="text" name="search" class="form-control" placeholder="Title or description" value="{{ request('search') }}">
                </div>
                <div class="col-md-2">
                    <label class="form-label small text-gasq-muted">Category</label>
                    <input type="text" name="category" class="form-control" placeholder="Category" value="{{ request('category') }}">
                </div>
                <div class="col-md-2">
                    <label class="form-label small text-gasq-muted">Location</label>
                    <input type="text" name="location" class="form-control" placeholder="Location" value="{{ request('location') }}">
                </div>
                <div class="col-md-2 d-flex align-items-end">
                    <button type="submit" class="btn btn-outline-primary w-100">Filter</button>
                </div>
            </form>
        </div>
    </div>

    @if(session('success'))
        <x-alert type="success" dismissible>{{ session('success') }}</x-alert>
    @endif
    @if(session('error'))
        <x-alert type="danger" dismissible>{{ session('error') }}</x-alert>
    @endif

    @if($jobs->isEmpty())
        <div class="card gasq-card">
            <div class="card-body p-4 p-lg-5">
                <p class="text-gasq-muted mb-0">No jobs match your criteria. Check back later or post a job if you're a buyer.</p>
            </div>
        </div>
    @else
        <div class="list-group">
            @foreach($jobs as $job)
                <a href="{{ route('jobs.show', $job) }}" class="list-group-item list-group-item-action">
                    <div class="d-flex w-100 justify-content-between align-items-start">
                        <h5 class="mb-1 fw-semibold">{{ $job->title }}</h5>
                        <small class="text-gasq-muted">{{ $job->created_at->diffForHumans() }}</small>
                    </div>
                    @if($job->location)
                        <small class="text-gasq-muted">Location: {{ $job->location }}</small>
                    @endif
                    @if($job->budget_min || $job->budget_max)
                        <span class="text-gasq-muted ms-2">Budget: ${{ number_format($job->budget_min ?? 0) }} – ${{ number_format($job->budget_max ?? 0) }}</span>
                    @endif
                    <p class="mb-0 mt-1 small text-gasq-muted">{{ Str::limit($job->description, 120) }}</p>
                    <small class="text-gasq-muted">{{ $job->bids->count() }} bid(s)</small>
                </a>
            @endforeach
        </div>
        <div class="mt-4">{{ $jobs->links() }}</div>
    @endif
</div>
@endsection

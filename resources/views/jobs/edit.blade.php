@extends('layouts.app')

@section('title', 'Edit Job')

@section('content')
<div class="container py-4">
    <h1 class="h2 mb-4">Edit Job</h1>
    <x-card title="Job details">
        <form action="{{ route('jobs.update', $job) }}" method="POST">
            @csrf
            @method('PUT')
            <div class="mb-3">
                <label class="form-label">Title <span class="text-danger">*</span></label>
                <input type="text" name="title" class="form-control @error('title') is-invalid @enderror" value="{{ old('title', $job->title) }}" required>
                @error('title')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            <div class="row">
                <div class="col-md-6 mb-3">
                    <label class="form-label">Category</label>
                    <input type="text" name="category" class="form-control" value="{{ old('category', $job->category) }}">
                </div>
                <div class="col-md-6 mb-3">
                    @include('jobs.partials.location-fields', [
                        'suffix' => 'edit',
                        'location' => old('location', $job->location),
                        'latitude' => old('latitude', $job->latitude),
                        'longitude' => old('longitude', $job->longitude),
                        'googlePlaceId' => old('google_place_id', $job->google_place_id),
                    ])
                </div>
            </div>
            <div class="row">
                <div class="col-md-6 mb-3">
                    <label class="form-label">Service start date</label>
                    <input type="date" name="service_start_date" class="form-control" value="{{ old('service_start_date', $job->service_start_date?->format('Y-m-d')) }}">
                </div>
                <div class="col-md-6 mb-3">
                    <label class="form-label">Service end date</label>
                    <input type="date" name="service_end_date" class="form-control" value="{{ old('service_end_date', $job->service_end_date?->format('Y-m-d')) }}">
                </div>
            </div>
            <div class="row">
                <div class="col-md-4 mb-3">
                    <label class="form-label">Guards per shift</label>
                    <input type="number" name="guards_per_shift" class="form-control" value="{{ old('guards_per_shift', $job->guards_per_shift) }}" min="1">
                </div>
                <div class="col-md-4 mb-3">
                    <label class="form-label">Budget min ($)</label>
                    <input type="number" name="budget_min" class="form-control" value="{{ old('budget_min', $job->budget_min) }}" step="0.01" min="0">
                </div>
                <div class="col-md-4 mb-3">
                    <label class="form-label">Budget max ($)</label>
                    <input type="number" name="budget_max" class="form-control" value="{{ old('budget_max', $job->budget_max) }}" step="0.01" min="0">
                </div>
            </div>
            <div class="mb-3">
                <label class="form-label">Description</label>
                <textarea name="description" class="form-control" rows="4">{{ old('description', $job->description) }}</textarea>
            </div>
            <div class="mb-3">
                <label class="form-label">Property type</label>
                <input type="text" name="property_type" class="form-control" value="{{ old('property_type', $job->property_type) }}">
            </div>
            <div class="mb-3">
                <label class="form-label">Special requirements (one per line)</label>
                <textarea name="special_requirements" class="form-control" rows="2">@if($job->special_requirements){{ implode("\n", $job->special_requirements) }}@endif</textarea>
            </div>
            <div class="mb-3">
                <label class="form-label">Expires at</label>
                <input type="datetime-local" name="expires_at" class="form-control" value="{{ $job->expires_at?->format('Y-m-d\TH:i') }}">
            </div>
            <div class="d-flex gap-2">
                <button type="submit" class="btn btn-primary">Update Job</button>
                <a href="{{ route('jobs.show', $job) }}" class="btn btn-outline-secondary">Cancel</a>
            </div>
        </form>
    </x-card>
</div>
@endsection

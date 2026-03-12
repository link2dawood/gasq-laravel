@extends('layouts.app')

@section('title', 'Post a Job')

@section('content')
<div class="container py-4">
    <h1 class="h2 mb-4">Post a Job</h1>
    <x-card title="Job details">
        <form action="{{ route('jobs.store') }}" method="POST">
            @csrf
            <div class="mb-3">
                <label class="form-label">Title <span class="text-danger">*</span></label>
                <input type="text" name="title" class="form-control @error('title') is-invalid @enderror" value="{{ old('title') }}" required>
                @error('title')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            <div class="row">
                <div class="col-md-6 mb-3">
                    <label class="form-label">Category</label>
                    <input type="text" name="category" class="form-control @error('category') is-invalid @enderror" value="{{ old('category') }}" placeholder="e.g. Unarmed Security">
                    @error('category')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-6 mb-3">
                    <label class="form-label">Location</label>
                    <input type="text" name="location" class="form-control @error('location') is-invalid @enderror" value="{{ old('location') }}">
                    @error('location')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
            </div>
            <div class="row">
                <div class="col-md-6 mb-3">
                    <label class="form-label">Service start date</label>
                    <input type="date" name="service_start_date" class="form-control @error('service_start_date') is-invalid @enderror" value="{{ old('service_start_date') }}">
                    @error('service_start_date')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-6 mb-3">
                    <label class="form-label">Service end date</label>
                    <input type="date" name="service_end_date" class="form-control @error('service_end_date') is-invalid @enderror" value="{{ old('service_end_date') }}">
                    @error('service_end_date')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
            </div>
            <div class="row">
                <div class="col-md-4 mb-3">
                    <label class="form-label">Guards per shift</label>
                    <input type="number" name="guards_per_shift" class="form-control @error('guards_per_shift') is-invalid @enderror" value="{{ old('guards_per_shift', 1) }}" min="1">
                    @error('guards_per_shift')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-4 mb-3">
                    <label class="form-label">Budget min ($)</label>
                    <input type="number" name="budget_min" class="form-control @error('budget_min') is-invalid @enderror" value="{{ old('budget_min') }}" step="0.01" min="0">
                    @error('budget_min')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-4 mb-3">
                    <label class="form-label">Budget max ($)</label>
                    <input type="number" name="budget_max" class="form-control @error('budget_max') is-invalid @enderror" value="{{ old('budget_max') }}" step="0.01" min="0">
                    @error('budget_max')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
            </div>
            <div class="mb-3">
                <label class="form-label">Description</label>
                <textarea name="description" class="form-control @error('description') is-invalid @enderror" rows="4">{{ old('description') }}</textarea>
                @error('description')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            <div class="mb-3">
                <label class="form-label">Property type</label>
                <input type="text" name="property_type" class="form-control @error('property_type') is-invalid @enderror" value="{{ old('property_type') }}">
                @error('property_type')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            <div class="mb-3">
                <label class="form-label">Special requirements (one per line)</label>
                <textarea name="special_requirements" class="form-control @error('special_requirements') is-invalid @enderror" rows="2" placeholder="Requirement 1&#10;Requirement 2">{{ old('special_requirements') }}</textarea>
                @error('special_requirements')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            <div class="mb-3">
                <label class="form-label">Expires at</label>
                <input type="datetime-local" name="expires_at" class="form-control @error('expires_at') is-invalid @enderror" value="{{ old('expires_at') }}">
                @error('expires_at')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            <div class="d-flex gap-2">
                <button type="submit" class="btn btn-primary">Post Job</button>
                <a href="{{ route('job-board') }}" class="btn btn-outline-secondary">Cancel</a>
            </div>
        </form>
    </x-card>
</div>
@endsection

@extends('layouts.app')

@section('title', 'Schedule Discovery Call')

@section('content')
<div class="container py-4">
    <h1 class="h2 mb-4">Schedule a Discovery Call</h1>

    @if(session('success'))
        <x-alert type="success" dismissible>{{ session('success') }}</x-alert>
    @endif

    <x-card title="Book your call">
        <form method="POST" action="{{ route('discovery-call.store') }}">
            @csrf
            <div class="mb-3">
                <label class="form-label">Preferred date & time</label>
                <input type="datetime-local" name="preferred_time" class="form-control @error('preferred_time') is-invalid @enderror" required>
                @error('preferred_time')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            <div class="mb-3">
                <label class="form-label">What would you like to discuss?</label>
                <textarea name="notes" class="form-control @error('notes') is-invalid @enderror" rows="4" placeholder="Tell us about your property, current security setup, and goals."></textarea>
                @error('notes')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            <button type="submit" class="btn btn-primary">Request Call</button>
        </form>
    </x-card>

    @if($existing)
        <x-card title="Your latest request" class="mt-4">
            <p class="mb-1"><strong>Status:</strong> {{ ucfirst($existing->status) }}</p>
            <p class="mb-1"><strong>Requested at:</strong> {{ $existing->requested_at?->format('M j, Y H:i') ?? 'N/A' }}</p>
            @if($existing->notes)
                <p class="mb-0"><strong>Notes:</strong> {{ $existing->notes }}</p>
            @endif
        </x-card>
    @endif
</div>
@endsection


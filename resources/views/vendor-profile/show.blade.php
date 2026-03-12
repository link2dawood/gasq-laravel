@extends('layouts.app')

@section('title', $vendor->name . ' — Vendor Profile')

@section('content')
<div class="container py-4">
    <nav aria-label="breadcrumb" class="mb-3">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="{{ route('job-board') }}">Job Board</a></li>
            <li class="breadcrumb-item active">Vendor Profile</li>
        </ol>
    </nav>

    <div class="row">
        <div class="col-md-4 text-center mb-4">
            <img src="{{ $vendor->avatar_url }}" alt="" class="rounded-circle mb-2" width="120" height="120">
            <h1 class="h4">{{ $vendor->name }}</h1>
            @if($vendor->company)<p class="text-muted mb-0">{{ $vendor->company }}</p>@endif
        </div>
        <div class="col-md-8">
            @if($vendor->vendorProfile)
                <x-card title="About">
                    @if($vendor->vendorProfile->company_name)
                        <p><strong>Company:</strong> {{ $vendor->vendorProfile->company_name }}</p>
                    @endif
                    @if($vendor->vendorProfile->description)
                        <div class="mb-2">{{ nl2br(e($vendor->vendorProfile->description)) }}</div>
                    @endif
                    @if($vendor->vendorProfile->phone)
                        <p class="mb-0"><strong>Phone:</strong> {{ $vendor->vendorProfile->phone }}</p>
                    @endif
                    @if($vendor->vendorProfile->address)
                        <p class="mb-0"><strong>Address:</strong> {{ $vendor->vendorProfile->address }}</p>
                    @endif
                    @if($vendor->vendorProfile->is_verified)
                        <span class="badge bg-success mt-2">Verified</span>
                    @endif
                </x-card>
                @if($vendor->vendorProfile->capabilities && count($vendor->vendorProfile->capabilities) > 0)
                    <x-card title="Capabilities" class="mt-3">
                        <ul class="mb-0">
                            @foreach($vendor->vendorProfile->capabilities as $cap)
                                <li>{{ is_string($cap) ? $cap : json_encode($cap) }}</li>
                            @endforeach
                        </ul>
                    </x-card>
                @endif
            @else
                <x-card>
                    <p class="text-muted mb-0">This vendor has not completed their profile yet.</p>
                    <p class="mb-0 mt-2">Contact: {{ $vendor->email }}@if($vendor->phone) · {{ $vendor->phone }}@endif</p>
                </x-card>
            @endif
        </div>
    </div>
</div>
@endsection

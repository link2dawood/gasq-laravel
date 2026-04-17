@extends('layouts.app')

@section('title', 'Admin Settings')

@section('content')
<div class="container py-4">
    <div class="mb-4">
        <h1 class="h2 fw-bold mb-1">Settings</h1>
        <p class="text-gasq-muted mb-0">Manage site-wide configuration and feature flags.</p>
    </div>

    @if (session('success'))
        <div class="alert alert-success alert-dismissible fade show mb-4" role="alert">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    <div class="card gasq-card mb-4">
        <div class="card-header">
            <h3 class="card-title mb-0">Content</h3>
        </div>
        <div class="card-body">
            <p class="text-gasq-muted small mb-2">Manage FAQs, coupons, and page content (Pay Scale, Payment Policy, Post Coverage Schedule).</p>
            <a href="{{ route('admin.coupons.index') }}" class="btn btn-sm btn-outline-primary me-2">Coupons</a>
            <a href="{{ route('admin.faqs.index') }}" class="btn btn-sm btn-outline-primary me-2">FAQs</a>
            <a href="{{ route('admin.content-sections.index') }}" class="btn btn-sm btn-outline-primary">Page Content</a>
        </div>
    </div>

    <div class="card gasq-card mb-4">
        <div class="card-header">
            <h3 class="card-title mb-0">Website logo</h3>
        </div>
        <div class="card-body">
            @if($siteLogoPath)
                <p class="small text-gasq-muted mb-2">Current logo (used in navbar, auth, footer):</p>
                <img src="{{ asset($siteLogoPath) }}" alt="Site logo" class="d-block mb-3" style="height: 56px; width: auto;">
            @else
                <p class="small text-gasq-muted mb-2">Default logo is shown. Upload a custom logo to replace it.</p>
            @endif
            <form action="{{ route('admin.settings.logo') }}" method="POST" enctype="multipart/form-data" class="d-flex flex-wrap align-items-end gap-2">
                @csrf
                <div class="flex-grow-1" style="min-width: 200px;">
                    <label class="form-label small mb-1">Upload new logo</label>
                    <input type="file" name="logo" class="form-control form-control-sm @error('logo') is-invalid @enderror" accept="image/jpeg,image/png,image/gif,image/webp,image/svg+xml">
                    @error('logo')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                    <p class="small text-gasq-muted mt-1 mb-0">JPEG, PNG, GIF, WebP or SVG. Max 2 MB.</p>
                </div>
                <button type="submit" class="btn btn-primary btn-sm">Upload logo</button>
            </form>
            @if($siteLogoPath)
                <form action="{{ route('admin.settings.logo.remove') }}" method="POST" class="mt-2 d-inline" onsubmit="return confirm('Remove custom logo and use default?');">
                    @csrf
                    <button type="submit" class="btn btn-sm btn-outline-danger">Remove custom logo</button>
                </form>
            @endif
        </div>
    </div>

    <form action="{{ route('admin.settings.update') }}" method="POST">
        @csrf
        <div class="row g-4">
            @foreach($settingsByGroup as $group => $settings)
                <div class="col-md-6">
                    <div class="card gasq-card">
                        <div class="card-header">
                            <h3 class="card-title mb-0 text-capitalize">{{ $group }} settings</h3>
                        </div>
                        <div class="card-body">
                            @foreach($settings as $setting)
                                <div class="mb-3">
                                    <label class="form-label">{{ $setting->key }}</label>
                                    <input type="text"
                                           name="settings[{{ $setting->key }}][value]"
                                           value="{{ old("settings.{$setting->key}.value", $setting->value) }}"
                                           class="form-control">
                                    <input type="hidden"
                                           name="settings[{{ $setting->key }}][group]"
                                           value="{{ $setting->group }}">
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
        <div class="mt-4">
            <button type="submit" class="btn btn-primary">Save settings</button>
        </div>
    </form>
</div>
@endsection

@extends('layouts.app')

@section('title', 'Admin Settings')
@section('header_variant', 'dashboard')

@php
    $settingGroupCount = $settingsByGroup->count();
    $settingCount = $settingsByGroup->flatten(1)->count();
@endphp

@push('styles')
<style>
    .admin-settings-shell {
        min-height: calc(100vh - 72px);
        background: #f5f7fb;
    }
    .admin-settings-wrap {
        width: min(100%, 1180px);
        margin: 0 auto;
    }
    .admin-settings-section {
        background: #fff;
        border: 1px solid #e2e8f2;
        border-radius: 1.1rem;
    }
    .admin-settings-hero {
        padding: 1.55rem 1.7rem;
    }
    .admin-settings-panel {
        padding: 1.35rem 1.45rem;
        height: 100%;
    }
    .admin-settings-chip {
        display: inline-flex;
        align-items: center;
        gap: .45rem;
        padding: .44rem .75rem;
        border-radius: 999px;
        background: #f7f9fc;
        border: 1px solid #e1e7f0;
        color: #2d3954;
        font-size: .9rem;
        font-weight: 600;
    }
    .admin-layout {
        display: grid;
        gap: 1.25rem;
        grid-template-columns: minmax(300px, .88fr) minmax(0, 1.6fr);
    }
    .admin-kpi-grid {
        display: grid;
        gap: .8rem;
        grid-template-columns: repeat(3, minmax(0, 1fr));
    }
    .admin-kpi {
        padding: .9rem 1rem;
        border-radius: .95rem;
        background: #f8fafc;
    }
    .admin-kpi-label {
        color: #6e7891;
        font-size: .82rem;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: .03em;
    }
    .admin-kpi-value {
        color: #253049;
        font-size: 1.6rem;
        font-weight: 800;
        line-height: 1.1;
        margin-top: .3rem;
    }
    .admin-kpi-note {
        color: #6d7690;
        font-size: .92rem;
        margin-top: .35rem;
    }
    .admin-tool {
        padding: 1.2rem 1.25rem;
    }
    .admin-tool p {
        color: #6d7690;
    }
    .admin-section-title {
        color: #1f2a44;
        font-size: 1.05rem;
        font-weight: 700;
        margin-bottom: .25rem;
    }
    .admin-section-copy {
        color: #687389;
        font-size: .95rem;
        margin-bottom: 1rem;
    }
    .admin-actions-list {
        display: grid;
        gap: .7rem;
    }
    .admin-link-row {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 1rem;
        padding: .85rem 0;
        border-top: 1px solid #edf1f6;
    }
    .admin-link-row:first-child {
        border-top: 0;
        padding-top: 0;
    }
    .admin-logo-preview {
        display: flex;
        align-items: center;
        gap: 1rem;
        padding: .9rem 1rem;
        border-radius: .95rem;
        background: #f8fafc;
        margin-bottom: 1rem;
    }
    .admin-group-grid {
        display: grid;
        gap: 1rem;
        grid-template-columns: repeat(2, minmax(0, 1fr));
    }
    .admin-group {
        padding: 1.15rem 1.2rem;
        border-radius: 1rem;
        background: #fbfcfe;
        border: 1px solid #e7edf5;
    }
    .admin-group-header {
        margin-bottom: 1rem;
        padding-bottom: .8rem;
        border-bottom: 1px solid #edf1f6;
    }
    .admin-group-note {
        color: #6d7690;
        font-size: .92rem;
        margin: 0;
    }
    .admin-group-field + .admin-group-field {
        margin-top: .9rem;
    }
    .admin-toolbar {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 1rem;
        margin-bottom: 1rem;
    }
    @media (max-width: 991.98px) {
        .admin-kpi-grid,
        .admin-group-grid,
        .admin-layout {
            grid-template-columns: 1fr;
        }
    }
</style>
@endpush

@section('content')
<div class="admin-settings-shell py-4 px-3 px-md-4">
    <div class="admin-settings-wrap">
        @if (session('success'))
            <div class="alert alert-success alert-dismissible fade show mb-4" role="alert">
                {{ session('success') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        @endif

        <div class="admin-settings-section admin-settings-hero mb-4">
            <div class="d-flex flex-wrap align-items-start justify-content-between gap-3">
                <div>
                    <div class="text-uppercase small fw-semibold text-gasq-muted mb-2">Admin Settings</div>
                    <h1 class="display-6 fw-bold mb-2">Platform Configuration</h1>
                    <p class="text-gasq-muted mb-3">Review the platform’s active tools, branding, delivery services, and grouped runtime values from one calmer control surface.</p>
                    <div class="d-flex flex-wrap gap-2">
                        <span class="admin-settings-chip"><i class="fa fa-sliders"></i>{{ $settingCount }} settings</span>
                        <span class="admin-settings-chip"><i class="fa fa-layer-group"></i>{{ $settingGroupCount }} groups</span>
                        <span class="admin-settings-chip"><i class="fa fa-satellite-dish"></i>Twilio {{ $twilioDebug['delivery_mode'] ?? 'unknown' }}</span>
                    </div>
                </div>
                <a href="{{ route('home') }}" class="btn btn-outline-secondary"><i class="fa fa-arrow-left me-2"></i>Back to Dashboard</a>
            </div>
        </div>

        <div class="admin-layout">
            <aside class="d-grid gap-4">
                <section class="admin-settings-section admin-tool">
                    <div class="admin-section-title">Platform Snapshot</div>
                    <p class="admin-section-copy">Quick read on the areas that affect branding, communications, and configuration coverage.</p>
                    <div class="admin-kpi-grid">
                        <div class="admin-kpi">
                            <div class="admin-kpi-label">Branding</div>
                            <div class="admin-kpi-value">{{ $siteLogoPath ? 'Custom' : 'Default' }}</div>
                            <div class="admin-kpi-note">Public logo state</div>
                        </div>
                        <div class="admin-kpi">
                            <div class="admin-kpi-label">Twilio</div>
                            <div class="admin-kpi-value text-capitalize">{{ $twilioDebug['delivery_mode'] ?? 'Unknown' }}</div>
                            <div class="admin-kpi-note">OTP delivery path</div>
                        </div>
                        <div class="admin-kpi">
                            <div class="admin-kpi-label">Groups</div>
                            <div class="admin-kpi-value">{{ $settingGroupCount }}</div>
                            <div class="admin-kpi-note">Editable sections</div>
                        </div>
                    </div>
                </section>

                <section class="admin-settings-section admin-tool">
                    <div class="admin-section-title">Content Tools</div>
                    <p class="admin-section-copy">Jump into the editorial tools used to manage public content and commercial offers.</p>
                    <div class="admin-actions-list">
                        <div class="admin-link-row">
                            <div>
                                <div class="fw-semibold">Coupons</div>
                                <div class="small text-gasq-muted">Billing promotions and credit offers</div>
                            </div>
                            <a href="{{ route('admin.coupons.index') }}" class="btn btn-outline-primary btn-sm">Open</a>
                        </div>
                        <div class="admin-link-row">
                            <div>
                                <div class="fw-semibold">FAQs</div>
                                <div class="small text-gasq-muted">Buyer and vendor help content</div>
                            </div>
                            <a href="{{ route('admin.faqs.index') }}" class="btn btn-outline-primary btn-sm">Open</a>
                        </div>
                        <div class="admin-link-row">
                            <div>
                                <div class="fw-semibold">Page Content</div>
                                <div class="small text-gasq-muted">Editable site sections and copy blocks</div>
                            </div>
                            <a href="{{ route('admin.content-sections.index') }}" class="btn btn-outline-primary btn-sm">Open</a>
                        </div>
                    </div>
                </section>

                <section class="admin-settings-section admin-tool">
                    <div class="admin-section-title">Branding</div>
                    <p class="admin-section-copy">Control the public-facing logo used across the site header, auth screens, and footer.</p>
                    <div class="admin-logo-preview">
                        @if($siteLogoPath)
                            <img src="{{ asset($siteLogoPath) }}" alt="Site logo" style="height: 48px; width: auto;">
                            <div>
                                <div class="fw-semibold">Custom logo active</div>
                                <div class="small text-gasq-muted">This image is currently shown across the platform.</div>
                            </div>
                        @else
                            <div>
                                <div class="fw-semibold">Default branding active</div>
                                <div class="small text-gasq-muted">Upload a new logo to replace the default platform mark.</div>
                            </div>
                        @endif
                    </div>
                    <form action="{{ route('admin.settings.logo') }}" method="POST" enctype="multipart/form-data" class="d-grid gap-2">
                        @csrf
                        <input type="file" name="logo" class="form-control form-control-sm @error('logo') is-invalid @enderror" accept="image/jpeg,image/png,image/gif,image/webp,image/svg+xml">
                        @error('logo')
                            <div class="invalid-feedback d-block">{{ $message }}</div>
                        @enderror
                        <div class="d-flex flex-wrap gap-2">
                            <button type="submit" class="btn btn-primary btn-sm">Upload Logo</button>
                        </div>
                    </form>
                    @if($siteLogoPath)
                        <form action="{{ route('admin.settings.logo.remove') }}" method="POST" class="mt-2" onsubmit="return confirm('Remove custom logo and use default?');">
                            @csrf
                            <button type="submit" class="btn btn-outline-danger btn-sm">Remove Logo</button>
                        </form>
                    @endif
                </section>

                <section class="admin-settings-section admin-tool">
                    <div class="admin-section-title">Twilio Health</div>
                    <p class="admin-section-copy">Review SMS delivery readiness and current runtime configuration before changing related settings.</p>
                    <div class="admin-link-row">
                        <div>
                            <div class="fw-semibold text-capitalize">{{ $twilioDebug['delivery_mode'] ?? 'unknown' }}</div>
                            <div class="small text-gasq-muted">Current delivery mode</div>
                        </div>
                        <a href="{{ route('admin.twilio.show') }}" class="btn btn-outline-primary btn-sm">Open Check</a>
                    </div>
                </section>
            </aside>

            <section class="admin-settings-section admin-settings-panel">
                <form action="{{ route('admin.settings.update') }}" method="POST">
                    @csrf
                    <div class="admin-toolbar">
                        <div>
                            <div class="admin-section-title">Grouped Settings</div>
                            <p class="admin-section-copy mb-0">Edit grouped runtime values without leaving the admin workspace.</p>
                        </div>
                        <button type="submit" class="btn btn-primary"><i class="fa fa-check me-2"></i>Save Settings</button>
                    </div>

                    <div class="admin-group-grid">
                        @foreach($settingsByGroup as $group => $settings)
                            <section class="admin-group">
                                <div class="admin-group-header">
                                    <h2 class="h5 fw-bold mb-1 text-capitalize">{{ $group }}</h2>
                                    <p class="admin-group-note">Configuration values grouped under {{ $group }}.</p>
                                </div>
                                @foreach($settings as $setting)
                                    <div class="admin-group-field">
                                        <label class="form-label">{{ \Illuminate\Support\Str::of($setting->key)->replace('_', ' ')->title() }}</label>
                                        <input
                                            type="text"
                                            name="settings[{{ $setting->key }}][value]"
                                            value="{{ old("settings.{$setting->key}.value", $setting->value) }}"
                                            class="form-control"
                                        >
                                        <input
                                            type="hidden"
                                            name="settings[{{ $setting->key }}][group]"
                                            value="{{ $setting->group }}"
                                        >
                                        <div class="small text-gasq-muted mt-1">{{ $setting->key }}</div>
                                    </div>
                                @endforeach
                            </section>
                        @endforeach
                    </div>

                    <div class="d-flex flex-wrap gap-2 mt-4">
                        <button type="submit" class="btn btn-primary"><i class="fa fa-check me-2"></i>Save Settings</button>
                        <a href="{{ route('home') }}" class="btn btn-outline-secondary">Back to Dashboard</a>
                    </div>
                </form>
            </section>
        </div>
    </div>
</div>
@endsection

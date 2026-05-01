@extends('layouts.app')

@section('title', 'Profile')
@section('header_variant', 'dashboard')

@php
    $profileTitle = $user->isAdmin() ? 'Admin Profile' : 'Buyer Profile';
    $profileSubtitle = $user->isAdmin()
        ? 'Manage your account identity and platform access details.'
        : 'Manage your buyer account details and contact information.';
    $locationLine = trim(implode(', ', array_filter([$user->city, $user->state, $user->zip_code])));
@endphp

@push('styles')
<style>
    .account-shell {
        min-height: calc(100vh - 72px);
        background: #f5f7fb;
    }
    .account-wrap {
        width: min(100%, 1160px);
        margin: 0 auto;
    }
    .account-section {
        background: #fff;
        border: 1px solid #e2e8f2;
        border-radius: 1.1rem;
    }
    .account-hero {
        padding: 1.6rem 1.75rem;
    }
    .account-grid {
        display: grid;
        gap: 1.25rem;
        grid-template-columns: minmax(0, 1.7fr) minmax(280px, .95fr);
    }
    .account-panel {
        padding: 1.35rem 1.45rem;
    }
    .account-chip {
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
    .account-section-title {
        color: #1f2a44;
        font-size: 1.05rem;
        font-weight: 700;
        margin-bottom: .2rem;
    }
    .account-section-copy {
        color: #687389;
        font-size: .95rem;
        margin-bottom: 1rem;
    }
    .account-list {
        display: grid;
        gap: .25rem;
    }
    .account-row {
        display: grid;
        gap: .85rem;
        grid-template-columns: minmax(120px, 170px) minmax(0, 1fr);
        padding: .85rem 0;
        border-top: 1px solid #edf1f6;
    }
    .account-row:first-child {
        border-top: 0;
        padding-top: 0;
    }
    .account-row:last-child {
        padding-bottom: 0;
    }
    .account-label {
        color: #6d7890;
        font-size: .82rem;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: .05em;
    }
    .account-value {
        color: #1f2940;
        font-size: 1rem;
        line-height: 1.5;
    }
    .account-avatar-panel {
        display: grid;
        gap: .95rem;
        justify-items: start;
    }
    .account-status-list {
        display: grid;
        gap: .7rem;
    }
    .account-status-item {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 1rem;
        padding: .8rem .9rem;
        border-radius: .9rem;
        background: #f8fafc;
    }
    .account-status-item span:first-child {
        color: #6d7890;
        font-weight: 600;
    }
    .account-status-item strong {
        color: #1f2940;
    }
    @media (max-width: 767.98px) {
        .account-hero {
            padding: 1.25rem;
        }
        .account-row {
            grid-template-columns: 1fr;
            gap: .25rem;
        }
    }
    @media (max-width: 991.98px) {
        .account-grid {
            grid-template-columns: 1fr;
        }
    }
</style>
@endpush

@section('content')
<div class="account-shell py-4 px-3 px-md-4">
    <div class="account-wrap">
        @if (session('success'))
            <div class="alert alert-success alert-dismissible fade show mb-4" role="alert">
                {{ session('success') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        @endif

        <div class="account-section account-hero mb-4">
            <div class="d-flex flex-wrap align-items-start justify-content-between gap-3">
                <div class="d-flex align-items-start gap-3">
                    <img src="{{ $user->avatar_url }}" alt="" class="rounded-circle border border-3 border-white shadow-sm flex-shrink-0" width="76" height="76">
                    <div>
                        <div class="text-uppercase small fw-semibold text-gasq-muted mb-2">{{ $profileTitle }}</div>
                        <h1 class="display-6 fw-bold mb-2">{{ $user->name }}</h1>
                        <p class="text-gasq-muted mb-3">{{ $profileSubtitle }}</p>
                        <div class="d-flex flex-wrap gap-2">
                            <span class="account-chip"><i class="fa fa-envelope"></i>{{ $user->email }}</span>
                            @if($user->phone)
                                <span class="account-chip"><i class="fa fa-phone"></i>{{ $user->phone }}</span>
                            @endif
                            <span class="account-chip"><i class="fa fa-user-shield"></i>{{ ucfirst($user->user_type ?? 'buyer') }}</span>
                            <span class="account-chip">
                                <i class="fa fa-badge-check"></i>
                                {{ $user->email_verified_at ? 'Email Verified' : 'Email Not Verified' }}
                            </span>
                        </div>
                    </div>
                </div>
                <div class="d-flex flex-wrap gap-2">
                    <a href="{{ route('profile.edit') }}" class="btn btn-primary"><i class="fa fa-pen me-2"></i>Edit Profile</a>
                    <a href="{{ route('discovery-call.index') }}" class="btn btn-outline-secondary"><i class="fa fa-calendar-check me-2"></i>Discovery Call</a>
                </div>
            </div>
        </div>

        <div class="account-grid">
            <div class="d-grid gap-4">
                <section class="account-section account-panel">
                    <div class="account-section-title">Account Details</div>
                    <p class="account-section-copy">Core identity and contact details used across your account and related workflows.</p>
                    <div class="account-list">
                        <div class="account-row">
                            <span class="account-label">Full Name</span>
                            <div class="account-value">{{ $user->name }}</div>
                        </div>
                        <div class="account-row">
                            <span class="account-label">Email Address</span>
                            <div class="account-value">{{ $user->email }}</div>
                        </div>
                        <div class="account-row">
                            <span class="account-label">Company</span>
                            <div class="account-value">{{ $user->company ?: 'Not provided' }}</div>
                        </div>
                        <div class="account-row">
                            <span class="account-label">Phone Number</span>
                            <div class="account-value">{{ $user->phone ?: 'Not provided' }}</div>
                        </div>
                        <div class="account-row">
                            <span class="account-label">Location</span>
                            <div class="account-value">{{ $locationLine !== '' ? $locationLine : 'Not provided' }}</div>
                        </div>
                        <div class="account-row">
                            <span class="account-label">Account Type</span>
                            <div class="account-value text-capitalize">{{ $user->user_type ?? 'buyer' }}</div>
                        </div>
                    </div>
                </section>
            </div>

            <div class="d-grid gap-4">
                <section class="account-section account-panel">
                    <div class="account-section-title">Profile Image</div>
                    <p class="account-section-copy">Keep your account identity current across the platform.</p>
                    <div>
                        <div class="account-avatar-panel">
                            <img src="{{ $user->avatar_url }}" alt="" class="rounded-circle border border-3 border-white shadow-sm" width="88" height="88">
                            <div>
                                <div class="fw-semibold">{{ $user->name }}</div>
                                <div class="text-gasq-muted small">Current avatar</div>
                            </div>
                            <form action="{{ route('profile.avatar.update') }}" method="POST" enctype="multipart/form-data" class="w-100" id="avatarForm">
                                @csrf
                                <label for="avatar" class="form-label">Choose new avatar</label>
                                <input type="file" class="form-control mb-2" id="avatar" name="avatar" accept="image/jpeg,image/png,image/jpg,image/gif" required>
                                <div class="small text-gasq-muted mb-3">JPG, PNG, GIF. Max 2MB.</div>
                                <div class="d-flex flex-wrap gap-2">
                                    <button type="submit" class="btn btn-primary" id="uploadButton" disabled><i class="fa fa-upload me-2"></i>Upload</button>
                                    @if($user->avatar)
                                        <a href="{{ route('profile.avatar.remove') }}" class="btn btn-outline-danger" onclick="event.preventDefault(); if(confirm('Remove your avatar?')) document.getElementById('remove-avatar-form').submit();"><i class="fa fa-trash-alt me-2"></i>Remove</a>
                                    @endif
                                </div>
                            </form>
                            @if($user->avatar)
                                <form id="remove-avatar-form" action="{{ route('profile.avatar.remove') }}" method="POST" class="d-none">@csrf @method('DELETE')</form>
                            @endif
                        </div>
                    </div>
                </section>

                <section class="account-section account-panel">
                    <div class="account-section-title">Status & Security</div>
                    <p class="account-section-copy">A quick snapshot of verification and account timing.</p>
                    <div class="account-status-list">
                        <div class="account-status-item">
                            <span>Account Created</span>
                            <strong>{{ $user->created_at->format('M d, Y') }}</strong>
                        </div>
                        <div class="account-status-item">
                            <span>Last Updated</span>
                            <strong>{{ $user->updated_at->format('M d, Y') }}</strong>
                        </div>
                        <div class="account-status-item">
                            <span>Email Verification</span>
                            <strong>{{ $user->email_verified_at ? 'Verified' : 'Not verified' }}</strong>
                        </div>
                        <div class="account-status-item">
                            <span>Password</span>
                            <strong>Managed from Edit Profile</strong>
                        </div>
                    </div>
                </section>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const avatarInput = document.getElementById('avatar');
    const uploadButton = document.getElementById('uploadButton');

    if (avatarInput && uploadButton) {
        avatarInput.addEventListener('change', function () {
            uploadButton.disabled = !avatarInput.files || avatarInput.files.length === 0;
        });
    }
});
</script>
@endsection

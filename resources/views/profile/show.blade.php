@extends('layouts.app')

@section('title', 'Profile')

@section('content')
<div class="container py-4">
    <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4">
        <div>
            <h1 class="h2 fw-bold mb-1">Profile</h1>
            <p class="text-gasq-muted mb-0">Manage your account information</p>
        </div>
        <a href="{{ route('profile.edit') }}" class="btn btn-primary"><i class="fa fa-pen me-2"></i>Edit Profile</a>
    </div>

    @if (session('success'))
        <div class="alert alert-success alert-dismissible fade show mb-4" role="alert">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    <div class="row g-4">
        <div class="col-lg-6">
            <div class="card gasq-card">
                <div class="card-header">
                    <h3 class="card-title mb-0">Profile Information</h3>
                </div>
                <div class="card-body">
                    <div class="d-flex align-items-start gap-3 mb-4">
                        <img src="{{ $user->avatar_url }}" alt="" class="rounded-circle flex-shrink-0" width="64" height="64">
                        <div class="min-w-0">
                            <div class="fw-semibold">{{ $user->name }}</div>
                            <div class="text-gasq-muted small">{{ $user->email }}</div>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small text-gasq-muted">Name</label>
                        <div>{{ $user->name }}</div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small text-gasq-muted">Email</label>
                        <div>{{ $user->email }}</div>
                    </div>
                    @if($user->company)
                    <div class="mb-3">
                        <label class="form-label small text-gasq-muted">Company</label>
                        <div>{{ $user->company }}</div>
                    </div>
                    @endif
                    @if($user->phone)
                    <div class="mb-3">
                        <label class="form-label small text-gasq-muted">Phone</label>
                        <div>{{ $user->phone }}</div>
                    </div>
                    @endif
                    @if($user->city || $user->state || $user->zip_code)
                    <div class="mb-3">
                        <label class="form-label small text-gasq-muted">Location</label>
                        <div>{{ trim(implode(', ', array_filter([$user->city, $user->state, $user->zip_code]))) ?: '—' }}</div>
                    </div>
                    @endif
                </div>
            </div>

            <div class="card gasq-card mt-4">
                <div class="card-header">
                    <h3 class="card-title mb-0">Avatar</h3>
                </div>
                <div class="card-body">
                    <div class="d-flex align-items-center gap-3 mb-4">
                        <img src="{{ $user->avatar_url }}" alt="" class="rounded-circle" width="56" height="56">
                        <div>
                            <div class="fw-semibold">{{ $user->name }}</div>
                            <p class="text-gasq-muted small mb-0">Current avatar</p>
                        </div>
                    </div>
                    <form action="{{ route('profile.avatar.update') }}" method="POST" enctype="multipart/form-data" id="avatarForm">
                        @csrf
                        <div class="mb-3">
                            <label for="avatar" class="form-label">Choose new avatar</label>
                            <input type="file" class="form-control" id="avatar" name="avatar" accept="image/jpeg,image/png,image/jpg,image/gif" required>
                            <div class="form-text small">JPG, PNG, GIF. Max 2MB.</div>
                        </div>
                        <div class="d-flex gap-2">
                            <button type="submit" class="btn btn-primary" id="uploadButton" disabled><i class="fa fa-upload me-2"></i>Upload</button>
                            @if($user->avatar)
                            <a href="{{ route('profile.avatar.remove') }}" class="btn btn-outline-danger"
                               onclick="event.preventDefault(); if(confirm('Remove your avatar?')) document.getElementById('remove-avatar-form').submit();"><i class="fa fa-trash-alt me-2"></i>Remove</a>
                            @endif
                        </div>
                    </form>
                    @if($user->avatar)
                    <form id="remove-avatar-form" action="{{ route('profile.avatar.remove') }}" method="POST" class="d-none">@csrf @method('DELETE')</form>
                    @endif
                </div>
            </div>
        </div>

        <div class="col-lg-6">
            <div class="card gasq-card">
                <div class="card-header">
                    <h3 class="card-title mb-0">Account Details</h3>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <label class="form-label small text-gasq-muted">Account Created</label>
                        <div>{{ $user->created_at->format('M d, Y') }}</div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small text-gasq-muted">Last Updated</label>
                        <div>{{ $user->updated_at->format('M d, Y') }}</div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small text-gasq-muted">Email Verified</label>
                        <div>
                            @if($user->email_verified_at)
                                <span class="badge bg-success">Verified</span>
                            @else
                                <span class="badge bg-warning text-dark">Not Verified</span>
                            @endif
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small text-gasq-muted">Account Type</label>
                        <div class="text-capitalize">{{ $user->user_type ?? 'buyer' }}</div>
                    </div>
                    <div class="mb-0">
                        <a href="{{ route('discovery-call.index') }}" class="btn btn-outline-primary btn-sm">Schedule Discovery Call</a>
                    </div>
                </div>
            </div>

            <div class="card gasq-card mt-4">
                <div class="card-header">
                    <h3 class="card-title mb-0">Security</h3>
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <div class="d-flex align-items-center gap-3 p-3 rounded border">
                                <span class="rounded-circle bg-primary text-white d-flex align-items-center justify-content-center" style="width: 40px; height: 40px;"><i class="fa fa-lock"></i></span>
                                <div>
                                    <div class="fw-semibold small">Password</div>
                                    <div class="text-gasq-muted small">Change in Edit Profile</div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="d-flex align-items-center gap-3 p-3 rounded border">
                                <span class="rounded-circle bg-success text-white d-flex align-items-center justify-content-center" style="width: 40px; height: 40px;"><i class="fa fa-envelope"></i></span>
                                <div>
                                    <div class="fw-semibold small">Email</div>
                                    <div class="text-gasq-muted small">{{ $user->email_verified_at ? 'Verified' : 'Not verified' }}</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
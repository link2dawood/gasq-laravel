@extends('layouts.app')

@section('title', 'Edit Profile')

@section('content')
<div class="container py-4">
    <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4">
        <div>
            <h1 class="h2 fw-bold mb-1">Edit Profile</h1>
            <p class="text-gasq-muted mb-0">Update your account information and security settings</p>
        </div>
        <a href="{{ route('profile.show') }}" class="btn btn-outline-secondary"><i class="fa fa-arrow-left me-2"></i>Back to Profile</a>
    </div>

    @if ($errors->any())
        <div class="alert alert-danger alert-dismissible fade show mb-4" role="alert">
            <strong>Please fix the following:</strong>
            <ul class="mb-0 mt-2">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
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
                    <form action="{{ route('profile.update') }}" method="POST">
                        @csrf
                        @method('PUT')
                        <div class="d-flex align-items-center gap-3 mb-4">
                            <img src="{{ $user->avatar_url }}" alt="" class="rounded-circle" width="48" height="48">
                            <div>
                                <div class="fw-semibold">{{ $user->name }}</div>
                                <div class="text-gasq-muted small">{{ $user->email }}</div>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Full Name</label>
                            <input type="text" class="form-control @error('name') is-invalid @enderror" name="name" value="{{ old('name', $user->name) }}" placeholder="Your full name">
                            @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Email</label>
                            <input type="email" class="form-control @error('email') is-invalid @enderror" name="email" value="{{ old('email', $user->email) }}" placeholder="your@email.com">
                            @error('email')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Company <span class="text-gasq-muted">(optional)</span></label>
                            <input type="text" class="form-control @error('company') is-invalid @enderror" name="company" value="{{ old('company', $user->company) }}" placeholder="Your company">
                            @error('company')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Phone <span class="text-gasq-muted">(optional)</span></label>
                            <input type="text" class="form-control @error('phone') is-invalid @enderror" name="phone" value="{{ old('phone', $user->phone) }}" placeholder="+1 234 567 8900">
                            @error('phone')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">City</label>
                                <input type="text" class="form-control @error('city') is-invalid @enderror" name="city" value="{{ old('city', $user->city) }}" placeholder="City">
                                @error('city')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-md-3 mb-3">
                                <label class="form-label">State</label>
                                <input type="text" class="form-control @error('state') is-invalid @enderror" name="state" value="{{ old('state', $user->state) }}" placeholder="State">
                                @error('state')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-md-3 mb-3">
                                <label class="form-label">ZIP</label>
                                <input type="text" class="form-control @error('zip_code') is-invalid @enderror" name="zip_code" value="{{ old('zip_code', $user->zip_code) }}" placeholder="ZIP">
                                @error('zip_code')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                        </div>
                        <button type="submit" class="btn btn-primary"><i class="fa fa-check me-2"></i>Update Profile</button>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-lg-6">
            @if(!$user->google_id)
            <div class="card gasq-card">
                <div class="card-header">
                    <h3 class="card-title mb-0">Change Password</h3>
                </div>
                <div class="card-body">
                    <form action="{{ route('profile.password.update') }}" method="POST">
                        @csrf
                        @method('PUT')
                        <div class="mb-3">
                            <label class="form-label">Current Password</label>
                            <input type="password" class="form-control @error('current_password') is-invalid @enderror" name="current_password" placeholder="Current password">
                            @error('current_password')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="mb-3">
                            <label class="form-label">New Password</label>
                            <input type="password" class="form-control @error('password') is-invalid @enderror" name="password" placeholder="New password">
                            @error('password')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="mb-4">
                            <label class="form-label">Confirm New Password</label>
                            <input type="password" class="form-control" name="password_confirmation" placeholder="Confirm new password">
                        </div>
                        <button type="submit" class="btn btn-warning"><i class="fa fa-lock me-2"></i>Update Password</button>
                    </form>
                </div>
            </div>
            @else
            <div class="card gasq-card">
                <div class="card-header">
                    <h3 class="card-title mb-0">Password</h3>
                </div>
                <div class="card-body text-center py-4">
                    <p class="text-gasq-muted mb-3">Your account uses email/password sign-in. Change your password above when needed.</p>
                    <p class="small text-gasq-muted mb-0">If you signed up with a different method, password change may not apply.</p>
                </div>
            </div>
            @endif
        </div>
    </div>
</div>
@endsection

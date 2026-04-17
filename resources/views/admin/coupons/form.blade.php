@extends('layouts.app')

@section('title', $isEdit ? 'Edit Coupon' : 'Add Coupon')

@section('content')
<div class="container py-4">
    <div class="mb-4">
        <h1 class="h2 fw-bold mb-1">{{ $isEdit ? 'Edit Coupon' : 'Add Coupon' }}</h1>
        <p class="text-gasq-muted mb-0"><a href="{{ route('admin.coupons.index') }}">← Back to Coupons</a></p>
    </div>

    @if ($errors->any())
        <div class="alert alert-danger alert-dismissible fade show mb-4" role="alert">
            <ul class="mb-0">@foreach ($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    <div class="card gasq-card">
        <div class="card-body">
            <form method="POST" action="{{ $isEdit ? route('admin.coupons.update', $coupon) : route('admin.coupons.store') }}">
                @csrf
                @if($isEdit) @method('PUT') @endif
                <div class="mb-3">
                    <label class="form-label">Coupon code</label>
                    <input type="text" name="code" class="form-control" value="{{ old('code', $coupon->code) }}" required maxlength="64" style="text-transform: uppercase;">
                </div>
                <div class="row">
                    <div class="col-md-4 mb-3">
                        <label class="form-label">Credits amount</label>
                        <input type="number" name="credits_amount" class="form-control" value="{{ old('credits_amount', $coupon->credits_amount) }}" min="1" required>
                    </div>
                    <div class="col-md-4 mb-3">
                        <label class="form-label">Max redemptions</label>
                        <input type="number" name="max_redemptions" class="form-control" value="{{ old('max_redemptions', $coupon->max_redemptions) }}" min="1" placeholder="Unlimited">
                    </div>
                    <div class="col-md-4 mb-3">
                        <label class="form-label">Expires at</label>
                        <input type="datetime-local" name="expires_at" class="form-control" value="{{ old('expires_at', $coupon->expires_at?->format('Y-m-d\TH:i')) }}">
                    </div>
                </div>
                <div class="mb-3 form-check">
                    <input type="checkbox" name="is_active" value="1" class="form-check-input" id="coupon_active" {{ old('is_active', $coupon->is_active) ? 'checked' : '' }}>
                    <label class="form-check-label" for="coupon_active">Coupon is active</label>
                </div>
                @if($isEdit)
                    <p class="text-gasq-muted small">Redemptions so far: {{ $coupon->redemptions_count ?? $coupon->redemptions()->count() }}</p>
                @endif
                <button type="submit" class="btn btn-primary">{{ $isEdit ? 'Update' : 'Create' }}</button>
            </form>
        </div>
    </div>
</div>
@endsection

@extends('layouts.app')

@section('title', 'Admin Coupons')

@section('content')
<div class="container py-4">
    <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-4">
        <div>
            <h1 class="h2 fw-bold mb-1">Coupons</h1>
            <p class="text-gasq-muted mb-0">Manage coupon codes that add credits to user wallets.</p>
        </div>
        <a href="{{ route('admin.coupons.create') }}" class="btn btn-primary">Add Coupon</a>
    </div>

    @if (session('success'))
        <div class="alert alert-success alert-dismissible fade show mb-4" role="alert">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif
    @if ($errors->any())
        <div class="alert alert-danger alert-dismissible fade show mb-4" role="alert">
            <ul class="mb-0">@foreach ($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    <div class="card gasq-card">
        <div class="card-body">
            @if($coupons->isEmpty())
                <p class="text-gasq-muted mb-0">No coupons yet. <a href="{{ route('admin.coupons.create') }}">Create one</a>.</p>
            @else
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead>
                            <tr>
                                <th>Code</th>
                                <th class="text-end">Credits</th>
                                <th class="text-end">Used</th>
                                <th>Limit</th>
                                <th>Expires</th>
                                <th>Active</th>
                                <th style="width: 140px;"></th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($coupons as $coupon)
                                <tr>
                                    <td class="fw-semibold">{{ $coupon->code }}</td>
                                    <td class="text-end">{{ $coupon->credits_amount }}</td>
                                    <td class="text-end">{{ $coupon->redemptions_count }}</td>
                                    <td>{{ $coupon->max_redemptions ?? 'Unlimited' }}</td>
                                    <td>{{ $coupon->expires_at ? $coupon->expires_at->format('M j, Y H:i') : 'No expiry' }}</td>
                                    <td>{{ $coupon->is_active ? 'Yes' : 'No' }}</td>
                                    <td>
                                        <a href="{{ route('admin.coupons.edit', $coupon) }}" class="btn btn-sm btn-outline-primary me-1">Edit</a>
                                        <form action="{{ route('admin.coupons.destroy', $coupon) }}" method="POST" class="d-inline" onsubmit="return confirm('Delete this coupon?');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-sm btn-outline-danger">Delete</button>
                                        </form>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>
    </div>
    <p class="mt-3 mb-0"><a href="{{ route('admin.tokens') }}" class="text-gasq-muted">Back to Tokens &amp; Wallets</a></p>
</div>
@endsection

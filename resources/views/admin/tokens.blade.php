@extends('layouts.app')

@section('title', 'Admin Tokens & Wallets')

@section('content')
<div class="container py-4">
    <div class="mb-4">
        <h1 class="h2 fw-bold mb-1">Tokens & Wallets</h1>
        <p class="text-gasq-muted mb-0">View and manage user wallets and feature token costs.</p>
    </div>

    @if (session('success'))
        <div class="alert alert-success alert-dismissible fade show mb-4" role="alert">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif
    @if ($errors->any())
        <div class="alert alert-danger alert-dismissible fade show mb-4" role="alert">
            <ul class="mb-0">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    <div class="row g-4">
        <div class="col-lg-7">
            <div class="card gasq-card">
                <div class="card-header d-flex flex-wrap align-items-center justify-content-between gap-2">
                    <h3 class="card-title mb-0">Wallets</h3>
                    <form method="GET" action="{{ route('admin.tokens') }}" class="d-flex gap-2">
                        <input type="text" name="q" value="{{ $search }}" class="form-control form-control-sm" placeholder="Search name or email" style="width: 180px;">
                        <button class="btn btn-sm btn-outline-primary" type="submit">Search</button>
                    </form>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead>
                                <tr>
                                    <th>User</th>
                                    <th>Email</th>
                                    <th>Type</th>
                                    <th class="text-end">Balance</th>
                                    <th>Adjust</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($wallets as $wallet)
                                    <tr>
                                        <td>{{ $wallet->user->name }}</td>
                                        <td>{{ $wallet->user->email }}</td>
                                        <td class="text-capitalize">{{ $wallet->user->user_type ?? 'buyer' }}</td>
                                        <td class="text-end">{{ $wallet->balance }}</td>
                                        <td>
                                            <form action="{{ route('admin.tokens.adjust') }}" method="POST" class="d-flex flex-wrap gap-1 align-items-center">
                                                @csrf
                                                <input type="hidden" name="user_id" value="{{ $wallet->user_id }}">
                                                <input type="number" name="amount" class="form-control form-control-sm" style="width: 90px;" placeholder="+10 / -5">
                                                <select name="grant_type" class="form-select form-select-sm" style="width: 120px;" title="For positive amounts: grant = no email, bonus/free pool = send notification">
                                                    <option value="grant">Adjust only</option>
                                                    <option value="bonus">Bonus (email)</option>
                                                    <option value="free_pool">Free pool (email)</option>
                                                </select>
                                                <input type="text" name="description" class="form-control form-control-sm" style="width: 140px;" placeholder="Reason (optional)">
                                                <button type="submit" class="btn btn-sm btn-primary">Apply</button>
                                            </form>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="5" class="text-gasq-muted text-center py-4">No wallets found.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                    {{ $wallets->links() }}
                </div>
            </div>
        </div>

        <div class="col-lg-5">
            <div class="card gasq-card">
                <div class="card-header">
                    <h3 class="card-title mb-0">Feature token costs</h3>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead>
                                <tr>
                                    <th>Feature</th>
                                    <th class="text-end">Tokens</th>
                                    <th>Active</th>
                                    <th></th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($features as $rule)
                                    <tr>
                                        <form action="{{ route('admin.tokens.features.update', $rule) }}" method="POST">
                                            @csrf
                                            <td style="min-width: 140px;">
                                                <div class="small text-gasq-muted">{{ $rule->feature_key }}</div>
                                                <input type="text" name="feature_name" value="{{ old('feature_name', $rule->feature_name) }}" class="form-control form-control-sm mt-1">
                                            </td>
                                            <td class="text-end">
                                                <input type="number" name="tokens_required" value="{{ old('tokens_required', $rule->tokens_required) }}" class="form-control form-control-sm text-end" min="0" style="width: 80px;">
                                            </td>
                                            <td>
                                                <div class="form-check form-switch mb-0">
                                                    <input class="form-check-input" type="checkbox" name="is_active" value="1" {{ $rule->is_active ? 'checked' : '' }}>
                                                </div>
                                            </td>
                                            <td class="text-end">
                                                <button type="submit" class="btn btn-sm btn-outline-primary">Save</button>
                                            </td>
                                        </form>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="4" class="text-gasq-muted text-center py-4">No feature rules defined.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                    <p class="text-gasq-muted small mt-3 mb-0">
                        These rules control how many credits are consumed per feature run.
                    </p>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

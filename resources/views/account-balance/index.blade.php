@extends('layouts.app')

@section('title', 'Account Balance')

@section('content')
<div class="container">
    <h1 class="h2 mb-4">Account Balance</h1>
    <x-card title="Current balance" class="mb-4">
        <p class="fs-2 mb-0">{{ number_format($balance) }} <span class="text-muted small">credits</span></p>
    </x-card>
    <x-card title="Transaction history">
        @if($transactions->isEmpty())
            <p class="text-muted mb-0">No transactions yet.</p>
        @else
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Type</th>
                            <th>Change</th>
                            <th>Balance after</th>
                            <th>Description</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($transactions as $tx)
                            <tr>
                                <td>{{ $tx->created_at->format('M j, Y H:i') }}</td>
                                <td>{{ $tx->type }}</td>
                                <td class="{{ $tx->tokens_change >= 0 ? 'text-success' : 'text-danger' }}">
                                    {{ $tx->tokens_change >= 0 ? '+' : '' }}{{ $tx->tokens_change }}
                                </td>
                                <td>{{ $tx->balance_after ?? '—' }}</td>
                                <td>
                                    {{ Str::limit($tx->description, 40) }}
                                    @if($tx->type === 'purchase')
                                        <a href="{{ route('reports.receipt', $tx) }}" class="ms-1 small">Receipt</a>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            {{ $transactions->links() }}
        @endif
    </x-card>
</div>
@endsection

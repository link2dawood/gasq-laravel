@extends('layouts.app')

@section('title', 'Payment Successful')

@section('content')
<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-md-8 col-lg-6">
            <div class="text-center mb-4">
                <div class="text-gasq-success mb-3" style="font-size: 3rem;">
                    <i class="fas fa-check-circle"></i>
                </div>
                <h1 class="gasq-page-title mb-2">Payment successful</h1>
                <p class="gasq-page-subtitle">Your credits have been added to your account.</p>
            </div>
            <x-card title="Current balance" class="mb-4">
                <p class="fs-2 mb-0">{{ number_format($balance) }} credits</p>
                <a href="{{ route('account-balance') }}" class="btn btn-sm btn-outline-primary mt-2">View history</a>
            </x-card>
            <div class="text-center">
                <a href="{{ route('credits') }}" class="btn btn-primary">Buy more credits</a>
                <a href="{{ route('home') }}" class="btn btn-outline-primary ms-2">Back to dashboard</a>
            </div>
        </div>
    </div>
</div>
@endsection

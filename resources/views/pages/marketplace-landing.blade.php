@extends('layouts.app')

@section('title', 'Marketplace')

@section('content')
<div class="container py-5">
    <h1 class="h2 mb-4">Security Services Marketplace</h1>
    <p class="lead text-muted">Browse jobs and connect buyers with qualified security vendors.</p>
    <a href="{{ route('login') }}" class="btn btn-primary">Sign in to access the marketplace</a>
</div>
@endsection

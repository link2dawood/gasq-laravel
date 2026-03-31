@extends('layouts.app')

@section('header_variant', 'dashboard')

@section('title', 'Post Job')

@section('content')
<div class="container py-5">
    <div class="text-center mb-5">
        <div class="d-inline-block px-4 py-2 rounded-pill bg-secondary text-white mb-4">
            Laravel Job Posting
        </div>
        <h1 class="display-4 fw-bold mb-3">Post a Security Job</h1>
        <p class="lead text-gasq-muted mx-auto" style="max-width: 900px;">
            Use the authenticated job workflow to create a real listing. This page replaces the old embedded SPA route
            with the same entry point in Blade.
        </p>
        <div class="d-flex justify-content-center gap-3 flex-wrap mt-4">
            @auth
                <a class="btn btn-primary btn-lg" href="{{ route('jobs.create') }}">Create job posting</a>
                <a class="btn btn-outline-primary btn-lg" href="{{ route('jobs.index') }}">My jobs</a>
            @else
                <a class="btn btn-primary btn-lg" href="{{ route('login') }}">Log in to post</a>
                <a class="btn btn-outline-primary btn-lg" href="{{ route('register') }}">Sign up</a>
            @endauth
            <a class="btn btn-outline-secondary btn-lg" href="{{ route('job-board') }}">Browse board</a>
        </div>
    </div>

    <div class="row g-4 justify-content-center">
        <div class="col-lg-8">
            <x-card title="What you will add" subtitle="In the job editor">
                <ul class="text-gasq-muted small mb-0 ps-3">
                    <li class="mb-2">Title, location, and schedule summary</li>
                    <li class="mb-2">Scope, skills, and compliance notes</li>
                    <li class="mb-2">Budget or bid instructions for vendors</li>
                    <li>Documents or links (when enabled)</li>
                </ul>
            </x-card>
        </div>
    </div>
</div>
@endsection

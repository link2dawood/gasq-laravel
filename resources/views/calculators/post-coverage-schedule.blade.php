@extends('layouts.app')

@section('header_variant', 'dashboard')

@section('title', 'Post Coverage Schedule')

@section('content')
<div class="container py-5">
    <div class="text-center mb-5">
        <div class="d-inline-block px-4 py-2 rounded-pill bg-secondary text-white mb-4">
            UI Preview Only
        </div>
        <h1 class="display-4 fw-bold mb-3">Coverage Schedule</h1>
        <p class="lead text-gasq-muted mx-auto" style="max-width: 900px;">
            Static sample of weekly security posts and hours. No calculations or saving—use this layout as a reference
            for your coverage plan.
        </p>
        <div class="d-flex justify-content-center gap-3 flex-wrap mt-4">
            @auth
                <a class="btn btn-primary btn-lg" href="{{ route('jobs.create') }}">Post a Job</a>
            @else
                <a class="btn btn-primary btn-lg" href="{{ route('login') }}">Log in to post</a>
            @endauth
            <a class="btn btn-outline-primary btn-lg" href="{{ url('/open-bid-offer') }}">Open Bid Offer</a>
        </div>
    </div>

    <x-card title="Example weekly coverage grid" subtitle="Illustrative posts and shift hours">
        <div class="table-responsive">
            <table class="table table-striped align-middle small">
                <thead>
                    <tr>
                        <th>Post</th>
                        <th class="text-center">Mon</th>
                        <th class="text-center">Tue</th>
                        <th class="text-center">Wed</th>
                        <th class="text-center">Thu</th>
                        <th class="text-center">Fri</th>
                        <th class="text-center">Sat</th>
                        <th class="text-center">Sun</th>
                        <th class="text-end">Hours / wk</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td class="fw-semibold">Main lobby</td>
                        <td class="text-center font-monospace">12</td>
                        <td class="text-center font-monospace">12</td>
                        <td class="text-center font-monospace">12</td>
                        <td class="text-center font-monospace">12</td>
                        <td class="text-center font-monospace">12</td>
                        <td class="text-center font-monospace">8</td>
                        <td class="text-center font-monospace">8</td>
                        <td class="text-end font-monospace fw-semibold">80</td>
                    </tr>
                    <tr>
                        <td class="fw-semibold">Loading dock</td>
                        <td class="text-center font-monospace">8</td>
                        <td class="text-center font-monospace">8</td>
                        <td class="text-center font-monospace">8</td>
                        <td class="text-center font-monospace">8</td>
                        <td class="text-center font-monospace">8</td>
                        <td class="text-center font-monospace">4</td>
                        <td class="text-center font-monospace">—</td>
                        <td class="text-end font-monospace fw-semibold">44</td>
                    </tr>
                    <tr>
                        <td class="fw-semibold">Parking patrol</td>
                        <td class="text-center font-monospace">4</td>
                        <td class="text-center font-monospace">4</td>
                        <td class="text-center font-monospace">4</td>
                        <td class="text-center font-monospace">4</td>
                        <td class="text-center font-monospace">4</td>
                        <td class="text-center font-monospace">6</td>
                        <td class="text-center font-monospace">6</td>
                        <td class="text-end font-monospace fw-semibold">32</td>
                    </tr>
                </tbody>
            </table>
        </div>
        <p class="text-gasq-muted small mb-0 mt-3">
            Replace with your real posts when you publish a job or export a schedule from the main tools.
        </p>
    </x-card>
</div>
@endsection

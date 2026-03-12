@props(['reportType'])
@php
    $type = $reportType; // instant-estimator, main-menu, contract-analysis, security-billing, mobile-patrol, mobile-patrol-comparison
@endphp
<div class="mt-3 pt-3 border-top">
    <p class="small text-muted mb-2">Download or email this report</p>
    <div class="d-flex flex-wrap gap-2 align-items-center">
        <a href="{{ route('reports.download', ['type' => $type]) }}" class="btn btn-sm btn-outline-primary">Download PDF</a>
        <form action="{{ route('reports.email') }}" method="POST" class="d-inline-flex gap-2 align-items-center">
            @csrf
            <input type="hidden" name="type" value="{{ $type }}">
            <input type="email" name="email" class="form-control form-control-sm" placeholder="Email address" value="{{ auth()->user()?->email }}" style="width: 180px;" required>
            <button type="submit" class="btn btn-sm btn-outline-secondary">Email report</button>
        </form>
    </div>
</div>

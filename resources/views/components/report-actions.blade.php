@props(['reportType', 'label' => 'Download or email this report'])
@php
    $type = $reportType; // calculator type used by ReportController/ReportService
@endphp
<div class="mt-3 pt-3 border-top">
    <p class="small text-muted mb-2">{{ $label }}</p>
    {{-- classes (not ids) so multiple report blocks can coexist and all be guarded --}}
    <div class="report-stale-warning alert alert-warning py-2 px-3 small d-none" role="alert"></div>
    <div class="d-flex flex-wrap gap-2 align-items-center">
        <a href="{{ route('reports.download', ['type' => $type]) }}" class="report-download-link btn btn-sm btn-outline-primary">Download PDF</a>
        <form action="{{ route('reports.email') }}" method="POST" class="d-inline-flex flex-wrap gap-2 align-items-center">
            @csrf
            <input type="hidden" name="type" value="{{ $type }}">
            <input type="text" name="email" class="form-control form-control-sm" placeholder="Email address (commas for more)" value="{{ auth()->user()?->email }}" style="width: 240px;" required>
            <input type="text" name="email2" class="form-control form-control-sm" placeholder="Second email (optional)" style="width: 200px;">
            <button type="submit" class="report-email-submit btn btn-sm btn-outline-secondary">Email report</button>
        </form>
    </div>
</div>

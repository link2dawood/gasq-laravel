@props(['reportType'])
@php
    $type = $reportType; // calculator type used by ReportController/ReportService
@endphp
<div class="mt-3 pt-3 border-top">
    <p class="small text-muted mb-2">Download or email this report</p>
    <div id="reportStaleWarning" class="alert alert-warning py-2 px-3 small d-none" role="alert"></div>
    <div class="d-flex flex-wrap gap-2 align-items-center">
        <a id="reportDownloadLink" href="{{ route('reports.download', ['type' => $type]) }}" class="btn btn-sm btn-outline-primary">Download PDF</a>
        <form action="{{ route('reports.email') }}" method="POST" class="d-inline-flex flex-wrap gap-2 align-items-center">
            @csrf
            <input type="hidden" name="type" value="{{ $type }}">
            <input type="text" name="email" class="form-control form-control-sm" placeholder="Email address (commas for more)" value="{{ auth()->user()?->email }}" style="width: 240px;" required>
            <input type="text" name="email2" class="form-control form-control-sm" placeholder="Second email (optional)" style="width: 200px;">
            <button id="reportEmailSubmit" type="submit" class="btn btn-sm btn-outline-secondary">Email report</button>
        </form>
    </div>
</div>

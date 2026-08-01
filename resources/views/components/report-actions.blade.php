@props(['reportType', 'label' => 'Download or email this report'])
@php
    $type = $reportType; // calculator type used by ReportController/ReportService
    // Only preparers (vendors/admins) can attach on-site survey notes/photos.
    $canAttach = auth()->check()
        && ((method_exists(auth()->user(), 'isVendor') && auth()->user()->isVendor())
            || (method_exists(auth()->user(), 'isAdmin') && auth()->user()->isAdmin()));
@endphp
<div class="mt-3 pt-3 border-top">
    <p class="small text-muted mb-2">{{ $label }}</p>
    {{-- classes (not ids) so multiple report blocks can coexist and all be guarded --}}
    <div class="report-stale-warning alert alert-warning py-2 px-3 small d-none" role="alert"></div>
    <div class="d-flex flex-wrap gap-2 align-items-start">
        <a href="{{ route('reports.download', ['type' => $type]) }}" class="report-download-link btn btn-sm btn-outline-primary">Download PDF</a>
        <form action="{{ route('reports.email') }}" method="POST" enctype="multipart/form-data" class="d-flex flex-column gap-2">
            @csrf
            <input type="hidden" name="type" value="{{ $type }}">
            <div class="d-flex flex-wrap gap-2 align-items-center">
                <input type="text" name="email" class="form-control form-control-sm" placeholder="Email address (commas for more)" value="{{ auth()->user()?->email }}" style="width: 240px;" required>
                <input type="text" name="email2" class="form-control form-control-sm" placeholder="Second email (optional)" style="width: 200px;">
                <button type="submit" class="report-email-submit btn btn-sm btn-outline-secondary">Email report</button>
            </div>
            @if($canAttach)
                {{-- On-site survey: attach notes + photos/files that email with the report. --}}
                <details class="small">
                    <summary class="text-primary" style="cursor:pointer;">&#43; Add on-site survey notes &amp; photos</summary>
                    <div class="mt-2" style="max-width: 460px;">
                        <textarea name="notes" rows="3" class="form-control form-control-sm mb-2" placeholder="On-site survey notes for the client (optional)…" maxlength="5000"></textarea>
                        <input type="file" name="attachments[]" multiple accept="image/*,.pdf,.doc,.docx,.txt" class="form-control form-control-sm">
                        <div class="form-text">Photos or files to send with the report. Up to 8 files, ~8&nbsp;MB each; keep the total under ~25&nbsp;MB (about 3–5 photos) so the email delivers.</div>
                    </div>
                </details>
            @endif
        </form>
    </div>
</div>

@props([
    'reportType',
    'label' => 'Download or email this report',
    // Offer the vendor a password that the buyer must type to OPEN the PDF.
    // The password is never emailed with the file — the vendor passes it on
    // separately (phone/text), which is the whole point of setting one.
    'passwordProtect' => false,
])
@php
    $type = $reportType; // calculator type used by ReportController/ReportService
    // Only preparers (vendors/admins) can attach on-site survey notes/photos.
    $canAttach = auth()->check()
        && ((method_exists(auth()->user(), 'isVendor') && auth()->user()->isVendor())
            || (method_exists(auth()->user(), 'isAdmin') && auth()->user()->isAdmin()));
@endphp
<div class="report-actions mt-3 pt-3 border-top">
    <p class="small text-muted mb-2">{{ $label }}</p>
    {{-- classes (not ids) so multiple report blocks can coexist and all be guarded --}}
    <div class="report-stale-warning alert alert-warning py-2 px-3 small d-none" role="alert"></div>
    {{-- Inline send result. Populated by the XHR submit below so the page — and the
         calculator inputs on it — survive an email send. --}}
    <div class="report-email-status alert py-2 px-3 small d-none" role="status"></div>
    <div class="d-flex flex-wrap gap-2 align-items-start">
        {{-- POST when a password may be attached, so it travels in the body and
             never lands in browser history or the server's access log. --}}
        <form action="{{ route('reports.download') }}" method="{{ $passwordProtect ? 'POST' : 'GET' }}" class="report-download-form">
            @if($passwordProtect)
                @csrf
                <input type="hidden" name="pdf_password" class="report-password-mirror" value="">
            @endif
            <input type="hidden" name="type" value="{{ $type }}">
            <button type="submit" class="report-download-link btn btn-sm btn-outline-primary">Download PDF</button>
        </form>
        <form action="{{ route('reports.email') }}" method="POST" enctype="multipart/form-data" class="report-email-form d-flex flex-column gap-2">
            @csrf
            <input type="hidden" name="type" value="{{ $type }}">
            <div class="d-flex flex-wrap gap-2 align-items-center">
                <input type="text" name="email" class="form-control form-control-sm" placeholder="Email address (commas for more)" value="{{ auth()->user()?->email }}" style="width: 240px;" required>
                <input type="text" name="email2" class="form-control form-control-sm" placeholder="Second email (optional)" style="width: 200px;">
                <button type="submit" class="report-email-submit btn btn-sm btn-outline-secondary">Email report</button>
            </div>
            @if($passwordProtect)
                <div class="d-flex flex-wrap gap-2 align-items-center">
                    <input type="text" name="pdf_password" class="report-password-input form-control form-control-sm" placeholder="Password to open the PDF (optional)" maxlength="32" autocomplete="off" style="width: 260px;">
                    <span class="form-text mb-0">Leave blank and the PDF opens normally. If you set one, the buyer must type it to open the file &mdash; give it to them by phone or text, not in this email.</span>
                </div>
            @endif
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

@once
@push('scripts')
<script>
// Email the report without reloading the page.
//
// The form used to submit normally, and the controller answered with a redirect
// back. That reload re-ran each calculator's init JS, which reset every input to
// its default — so a user who wanted to send two reports lost their figures after
// the first send. Sending over XHR keeps the calculator exactly as they left it.
//
// Progressive enhancement: without JS the form still posts normally and the
// controller still redirects back, so nothing is lost.
document.addEventListener('submit', async function (event) {
    const form = event.target.closest('.report-email-form');
    if (!form) return;

    event.preventDefault();

    const button = form.querySelector('.report-email-submit');
    const box = form.closest('.report-actions')?.querySelector('.report-email-status');
    const originalLabel = button ? button.textContent : '';

    const show = (kind, message) => {
        if (!box) return;
        box.className = 'report-email-status alert py-2 px-3 small alert-' + kind;
        box.textContent = message;
    };

    if (button) {
        button.disabled = true;
        button.textContent = 'Sending…';
    }
    show('info', 'Sending report…');

    try {
        const response = await fetch(form.action, {
            method: 'POST',
            body: new FormData(form),
            credentials: 'same-origin',
            headers: {
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
            },
        });

        let data = {};
        try { data = await response.json(); } catch (_) { /* non-JSON error page */ }

        if (response.ok && data.ok) {
            show('success', data.message || 'Report sent.');
            // Deliberately NOT resetting the form: the addresses stay put so a
            // second report can be sent straight after, which is the whole point.
        } else if (response.status === 402) {
            show('warning', data.message || 'You do not have enough credits to generate this report.');
        } else if (response.status === 419) {
            show('danger', 'Your session expired. Please refresh the page and sign in again.');
        } else {
            show('danger', data.message || 'The report could not be sent. Please try again.');
        }
    } catch (error) {
        show('danger', 'The report could not be sent — check your connection and try again.');
    } finally {
        if (button) {
            // Only hand the button back if the calculator's stale-guard hasn't
            // disabled it while we were sending (see setReportSyncState) —
            // blindly clearing `disabled` would re-enable a stale report.
            button.disabled = button.classList.contains('disabled');
            button.textContent = originalLabel || 'Email report';
        }
    }
});

// Keep the Download button's hidden password in step with the one typed in the
// email row, so both routes produce an identically locked file.
document.addEventListener('input', function (event) {
    const field = event.target.closest('.report-password-input');
    if (!field) return;

    const block = field.closest('.report-actions');
    const mirror = block?.querySelector('.report-password-mirror');
    if (mirror) mirror.value = field.value;
});
</script>
@endpush
@endonce

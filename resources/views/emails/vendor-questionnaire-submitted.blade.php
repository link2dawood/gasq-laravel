@php
    $vendor = $questionnaire->vendor;
    $job = $questionnaire->jobPosting;
@endphp
<!doctype html>
<html>
<body style="font-family: Arial, Helvetica, sans-serif; color:#222; line-height:1.5;">
    <p>Hi{{ $job?->user?->name ? ' ' . $job->user->name : '' }},</p>

    <p>
        <strong>{{ $vendor?->name ?? 'A vendor' }}</strong>
        has accepted your offer for <strong>“{{ $job?->title ?? 'your job' }}”</strong> and submitted
        the GASQ Vendor Qualification Questionnaire.
    </p>

    <p>
        <strong>Status:</strong>
        @if($questionnaire->is_responsive)
            <span style="background:#d1e7dd;color:#0a3622;padding:2px 8px;border-radius:10px;font-size:12px;">RESPONSIVE</span>
        @endif
        @if($questionnaire->is_responsible)
            <span style="background:#d1e7dd;color:#0a3622;padding:2px 8px;border-radius:10px;font-size:12px;margin-left:4px;">RESPONSIBLE</span>
        @endif
    </p>

    <p>The full response is attached as a PDF along with the vendor's supporting documents. You can also review it online:</p>

    <p>
        <a href="{{ $reviewUrl }}"
           style="background:#0d6efd;color:#fff;padding:10px 18px;border-radius:6px;text-decoration:none;display:inline-block;">
            Review vendor response
        </a>
    </p>

    <p style="color:#666;font-size:12px;">
        This secure link expires on
        <strong>{{ $questionnaire->buyer_review_expires_at?->format('M j, Y') }}</strong>.
    </p>

    <p style="color:#666;font-size:13px;margin-top:32px;">— GASQ</p>
</body>
</html>

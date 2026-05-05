@php
    $vendor = $submission->vendor;
    $job = $submission->jobPosting;
@endphp
<!doctype html>
<html>
<body style="font-family: Arial, Helvetica, sans-serif; color:#222; line-height:1.5;">
    <p>Hi{{ $submission->buyer?->name ? ' ' . $submission->buyer->name : '' }},</p>

    <p>
        <strong>{{ $vendor?->name ?? 'A vendor' }}</strong>@if($vendor?->company)
        ({{ $vendor->company }})@endif sent you an estimate for your job
        <strong>“{{ $job?->title ?? 'your project' }}”</strong>.
    </p>

    <p>The full estimate is attached as a PDF, and you can also view it online:</p>

    <p>
        <a href="{{ $viewUrl }}"
           style="background:#0d6efd;color:#fff;padding:10px 18px;border-radius:6px;text-decoration:none;display:inline-block;">
            View estimate
        </a>
    </p>

    <p style="color:#666;font-size:13px;margin-top:32px;">
        — GASQ
    </p>
</body>
</html>

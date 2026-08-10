@php
    $title = $job?->title ?? 'a security opportunity';
@endphp
<!doctype html>
<html>
<body style="font-family: Arial, Helvetica, sans-serif; color:#1e293b; line-height:1.55;">
    <p>Hi {{ $vendorName }},</p>

    <p>
        You&rsquo;ve been invited to <strong>interview</strong> for the security opportunity
        <strong>&ldquo;{{ $title }}&rdquo;</strong>@if($job?->location) in {{ $job->location }}@endif.
    </p>

    <p>
        GASQ runs an <strong>interview-first</strong> process: you&rsquo;ll meet the buyer and be evaluated on
        capability. Your submitted pricing stays <strong>sealed</strong> and is revealed only after interviews
        are complete &mdash; so the conversation is about fit, not just price.
    </p>

    <p style="margin:26px 0;">
        <a href="{{ $scheduleUrl }}" style="background:#153a81;color:#ffffff;padding:12px 22px;border-radius:8px;text-decoration:none;font-weight:700;display:inline-block;">Choose your interview time &rarr;</a>
    </p>

    <p style="color:#64748b;font-size:13px;">
        If the button doesn&rsquo;t work, view your interview invitations here:<br>
        <a href="{{ $listUrl }}" style="color:#153a81;">{{ $listUrl }}</a>
    </p>

    <p>Thanks,<br>Get A Security Quote (GASQ)</p>
</body>
</html>

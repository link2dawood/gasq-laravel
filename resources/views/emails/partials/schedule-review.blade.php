@php
    $bookingScheme = parse_url($bookingUrl ?? '', PHP_URL_SCHEME);
    $canSchedule = filter_var($bookingUrl ?? null, FILTER_VALIDATE_URL)
        && ($bookingScheme === 'https' || ($bookingScheme === 'http' && app()->environment('local', 'testing')));
    $scheduleLabel = $scheduleLabel ?? 'Schedule Your Estimate & Scope Review';
@endphp
@if($canSchedule)
<p>Choose an available date and time, then confirm your appointment.</p>
<table role="presentation" cellpadding="0" cellspacing="0" border="0" style="margin:18px 0;"><tr><td bgcolor="#153a81" style="border-radius:4px;text-align:center;"><a href="{{ $bookingUrl }}" style="background:#153a81;border:1px solid #153a81;border-radius:4px;color:#ffffff;display:inline-block;font-family:Arial,Helvetica,sans-serif;font-size:15px;font-weight:bold;line-height:22px;padding:14px 20px;text-decoration:none;">{{ $scheduleLabel }}</a></td></tr></table>
<p style="font-size:12px;color:#475569;">If the button does not open, copy this booking link into your browser:<br><a href="{{ $bookingUrl }}" style="color:#153a81;overflow-wrap:anywhere;word-break:break-all;">{{ $bookingUrl }}</a></p>
@else
<p><strong>{{ $scheduleLabel }}</strong><br>Please reply to this email to arrange your appointment.</p>
@endif

@php
    $title = $job?->title ?? 'the security interview';
    $formats = ['virtual' => 'Video call', 'phone' => 'Phone', 'in_person' => 'In person'];
    $fmt = $formats[$interview->format] ?? ($interview->format ? ucfirst(str_replace('_', ' ', $interview->format)) : 'To be confirmed');
@endphp
<!doctype html>
<html>
<body style="font-family: Arial, Helvetica, sans-serif; color:#1e293b; line-height:1.55;">
    @if($audience === 'buyer')
        <p>Hi {{ $buyer?->name ?? 'there' }},</p>
        <p>
            <strong>{{ $vendor?->name ?? 'A vendor' }}</strong>@if($vendor?->company) ({{ $vendor->company }})@endif
            has scheduled their interview for <strong>&ldquo;{{ $title }}&rdquo;</strong>.
        </p>
    @else
        <p>Hi {{ $vendor?->name ?? 'there' }},</p>
        <p>Your interview for <strong>&ldquo;{{ $title }}&rdquo;</strong> is confirmed. Details below.</p>
    @endif

    <table style="border-collapse:collapse;margin:16px 0;">
        <tr>
            <td style="padding:5px 16px 5px 0;color:#64748b;">When</td>
            <td style="padding:5px 0;font-weight:700;">{{ $whenLabel !== '' ? $whenLabel : 'See your invitation' }}</td>
        </tr>
        @if($interview->timezone)
        <tr>
            <td style="padding:5px 16px 5px 0;color:#64748b;">Timezone</td>
            <td style="padding:5px 0;">{{ $interview->timezone }}</td>
        </tr>
        @endif
        <tr>
            <td style="padding:5px 16px 5px 0;color:#64748b;">Format</td>
            <td style="padding:5px 0;">{{ $fmt }}</td>
        </tr>
        @if($interview->location)
        <tr>
            <td style="padding:5px 16px 5px 0;color:#64748b;">{{ $interview->format === 'virtual' ? 'Link' : 'Where' }}</td>
            <td style="padding:5px 0;">{{ $interview->location }}</td>
        </tr>
        @endif
    </table>

    <p style="margin:22px 0;">
        <a href="{{ $googleUrl }}" style="background:#153a81;color:#ffffff;padding:10px 18px;border-radius:8px;text-decoration:none;font-weight:700;display:inline-block;">Add to Google Calendar</a>
        &nbsp;
        <a href="{{ $outlookUrl }}" style="border:1px solid #153a81;color:#153a81;padding:10px 18px;border-radius:8px;text-decoration:none;font-weight:700;display:inline-block;">Add to Outlook</a>
    </p>

    <p style="color:#64748b;font-size:13px;">A calendar file (.ics) is attached so you can add this to any calendar app.</p>

    @if($audience !== 'buyer')
        <p style="color:#64748b;font-size:13px;">Reminder: your pricing stays sealed until interviews are complete. Come ready to talk capability.</p>
    @endif

    <p>Thanks,<br>Get A Security Quote (GASQ)</p>
</body>
</html>

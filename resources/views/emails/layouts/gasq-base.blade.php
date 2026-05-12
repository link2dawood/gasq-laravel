{{--
    Shared email layout — wraps content in a consistent branded shell.
    Usage:
        @extends('emails.layouts.gasq-base', ['preheader' => 'Short summary…'])
        @section('content') …your HTML… @endsection
--}}
<!doctype html>
<html>
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>{{ $title ?? 'GASQ Notification' }}</title>
</head>
<body style="margin:0;padding:0;background:#f6f8fb;font-family:Arial,Helvetica,sans-serif;color:#1f2937;line-height:1.55;">

<span style="display:none;visibility:hidden;opacity:0;height:0;width:0;overflow:hidden;">{{ $preheader ?? '' }}</span>

<table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%" style="background:#f6f8fb;padding:24px 12px;">
    <tr>
        <td align="center">
            <table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%" style="max-width:640px;background:#ffffff;border-radius:10px;overflow:hidden;box-shadow:0 1px 3px rgba(15,23,42,0.06);">
                <tr>
                    <td style="background:#0d6efd;padding:18px 28px;color:#fff;">
                        <strong style="font-size:18px;letter-spacing:.2px;">GASQ</strong>
                        <span style="opacity:.85;font-size:12px;margin-left:6px;">GetASecurityQuoteNow</span>
                    </td>
                </tr>
                <tr>
                    <td style="padding:28px;">
                        @yield('content')
                    </td>
                </tr>
                <tr>
                    <td style="background:#f1f5f9;padding:16px 28px;color:#64748b;font-size:12px;">
                        CFO Tested · CFO Approved · (470) 633-2816 ·
                        <a href="mailto:info@getasecurityquote.com" style="color:#0d6efd;text-decoration:none;">info@getasecurityquote.com</a>
                    </td>
                </tr>
            </table>
        </td>
    </tr>
</table>

</body>
</html>

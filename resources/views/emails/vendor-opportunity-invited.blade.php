<!doctype html>
<html>
<body style="font-family: Arial, Helvetica, sans-serif; color:#1f2937; line-height:1.55; max-width:680px; margin:0 auto; padding:18px; background:#f6f8fb;">

<div style="background:#fff; padding:20px; border-radius:6px;">

@include('partials.lead-summary', [
    'invitation' => $invitation,
    'redacted' => true,
    'isHtmlEmail' => true,
])

<p style="text-align:center;margin:22px 0;">
    <a href="{{ $url }}"
       style="background:#198754;color:#fff;padding:14px 28px;border-radius:6px;text-decoration:none;display:inline-block;font-weight:700;font-size:15px;">
        I ACCEPT BID OFFER
    </a>
    &nbsp;
    <a href="{{ $url }}?decline=1"
       style="background:#fff;color:#dc3545;border:2px solid #dc3545;padding:12px 26px;border-radius:6px;text-decoration:none;display:inline-block;font-weight:700;font-size:15px;">
        I DECLINE BID OFFER
    </a>
</p>

<p style="color:#64748b;font-size:11px;text-align:center;margin-top:18px;">
    Buyer information is partially redacted to protect lead integrity. Full contact details unlock when you accept this offer.<br>
    Vendor selection is limited to a first-come, first-serve qualified pool. Spots close as vendors accept.
</p>

<p style="color:#64748b;font-size:11px;text-align:center;margin-top:14px;">
    Screened using the GASQ Workforce-to-Post™ qualification system.<br>
    Reply directly to this email with any questions.
</p>

<hr style="border:none;border-top:1px solid #e5e7eb;margin:18px 0;">
<p style="font-size:11px;color:#6b7280;text-align:center;">
    <strong>GASQ Vendor Network Team</strong> · GetASecurityQuoteNow<br>
    (470) 633-2816 · info@getasecurityquote.com · getasecurityquotenow.com
</p>

</div>
</body>
</html>

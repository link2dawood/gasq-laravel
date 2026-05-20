<!doctype html>
<html>
<body style="font-family: Arial, Helvetica, sans-serif; color:#1f2937; line-height:1.55; max-width:680px; margin:0 auto; padding:18px; background:#f6f8fb;">

<div style="background:#fff; padding:24px; border-radius:6px;">

<p style="font-size:14px;margin:0 0 12px 0;">Hello {{ $vendorName }},</p>

<p style="font-size:14px;margin:0 0 18px 0;">
    A new pre-qualified security services opportunity is available through the GASQ Vendor Network.
</p>

<h3 style="font-size:15px;font-weight:700;color:#1f2937;margin:0 0 10px 0;">Opportunity Snapshot</h3>

@include('partials.lead-summary', [
    'invitation' => $invitation,
    'redacted' => true,
    'isHtmlEmail' => true,
])

<h3 style="font-size:15px;font-weight:700;color:#1f2937;margin:22px 0 10px 0;">Qualification Status</h3>
<ul style="margin:0 0 14px 0;padding-left:20px;font-size:13px;line-height:1.7;">
    <li style="{{ $decisionMakerVerified ? '' : 'opacity:.55;' }}">
        <span style="display:inline-block;width:14px;color:{{ $decisionMakerVerified ? '#198754' : '#9ca3af' }};">{{ $decisionMakerVerified ? '✓' : '•' }}</span>
        Decision Maker Verified
    </li>
    <li style="{{ $phoneVerified ? '' : 'opacity:.55;' }}">
        <span style="display:inline-block;width:14px;color:{{ $phoneVerified ? '#198754' : '#9ca3af' }};">{{ $phoneVerified ? '✓' : '•' }}</span>
        Phone Number Verified
    </li>
    <li style="{{ $budgetConfirmed ? '' : 'opacity:.55;' }}">
        <span style="display:inline-block;width:14px;color:{{ $budgetConfirmed ? '#198754' : '#9ca3af' }};">{{ $budgetConfirmed ? '✓' : '•' }}</span>
        Budget Confirmed
    </li>
    <li style="{{ $scopeCompleted ? '' : 'opacity:.55;' }}">
        <span style="display:inline-block;width:14px;color:{{ $scopeCompleted ? '#198754' : '#9ca3af' }};">{{ $scopeCompleted ? '✓' : '•' }}</span>
        Scope Defined
    </li>
    <li style="{{ $moveForward ? '' : 'opacity:.55;' }}">
        <span style="display:inline-block;width:14px;color:{{ $moveForward ? '#198754' : '#9ca3af' }};">{{ $moveForward ? '✓' : '•' }}</span>
        Buyer Prepared to Interview and Move Forward
    </li>
</ul>

<p style="font-size:12px;color:#4b5563;margin:0 0 18px 0;">
    This opportunity has been screened using the GASQ Workforce-to-Post&trade; qualification process.
</p>

<p style="font-size:14px;font-weight:600;margin:0 0 10px 0;">Please respond below:</p>

<p style="text-align:center;margin:18px 0;">
    <a href="{{ $url }}"
       style="background:#198754;color:#fff;padding:14px 28px;border-radius:6px;text-decoration:none;display:inline-block;font-weight:700;font-size:15px;">
        Accept Opportunity
    </a>
    &nbsp;
    <a href="{{ $url }}?decline=1"
       style="background:#fff;color:#dc3545;border:2px solid #dc3545;padding:12px 26px;border-radius:6px;text-decoration:none;display:inline-block;font-weight:700;font-size:15px;">
        Decline (Not Interested)
    </a>
</p>

<p style="color:#64748b;font-size:11px;text-align:center;margin-top:18px;">
    Buyer information is partially redacted to protect lead integrity. Full contact details unlock when you accept this opportunity.<br>
    Vendor selection is limited to a first-come, first-serve qualified pool. Spots close as vendors accept.
</p>

<hr style="border:none;border-top:1px solid #e5e7eb;margin:22px 0 14px 0;">

<p style="font-size:13px;margin:0 0 4px 0;">Best regards,</p>
<p style="font-size:13px;font-weight:700;margin:0 0 14px 0;">GASQ Vendor Network Team</p>

<p style="font-size:11px;color:#6b7280;text-align:center;margin:0;">
    GetASecurityQuoteNow · (470) 633-2816 · info@getasecurityquote.com · getasecurityquotenow.com
</p>

</div>
</body>
</html>

<!doctype html>
<html>
<body style="font-family: Arial, Helvetica, sans-serif; color:#222; line-height:1.6; max-width:640px; margin:0 auto; padding:24px;">

<p>Hi{{ $job->user?->name ? ' ' . $job->user->name : '' }},</p>

<p>Thank you for your interest in working with <strong>GetASecurityQuoteNow (GASQ)</strong>.</p>

<p>After a thorough review of your questionnaire responses, we regret to inform you that your request does not currently meet our minimum lead qualification standards required for distribution to our prequalified vendor network.</p>

<p>At least one or more responses did not align with our core qualification criteria, which are designed to ensure:</p>
<ul>
    <li>Verified decision-maker authority</li>
    <li>Confirmed budget or pricing alignment</li>
    <li>Commitment to interview and engage qualified vendors</li>
    <li>Readiness to move forward under established service conditions</li>
</ul>

<p style="background:#fff3cd;color:#664d03;padding:12px 16px;border-radius:6px;">
    Because of this, your request has been placed on <strong>"Pending Qualification Status"</strong> and will not be released to vendors at this time.
</p>

<h3 style="margin-top:32px;">What You Can Do Next</h3>
<p>We encourage you to revisit and update your responses to meet the qualification requirements. Once your submission reflects a qualified status, we will promptly re-evaluate and activate your request for vendor distribution.</p>

<p style="margin:24px 0;">
    <a href="{{ $updateUrl }}"
       style="background:#0d6efd;color:#fff;padding:12px 22px;border-radius:6px;text-decoration:none;display:inline-block;font-weight:600;">
        Update My Questionnaire
    </a>
</p>

<h3 style="margin-top:32px;">Important Note</h3>
<p>All qualified submissions benefit from:</p>
<ul>
    <li>Access to our pre-screened vendor network</li>
    <li>Vendor Replacement Guarantee</li>
    <li>Price Lock Guarantee</li>
    <li>Structured interview coordination and bid acceptance process</li>
</ul>

<p>We appreciate your understanding and remain available to assist you in completing the qualification process.</p>

<p style="margin-top:32px;color:#666;font-size:13px;">— Get A Security Quote Now Team</p>

</body>
</html>

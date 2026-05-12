<!doctype html>
<html>
<body style="font-family: Arial, Helvetica, sans-serif; color:#222; line-height:1.6; max-width:640px; margin:0 auto; padding:24px; background:#f8f9fa;">

<div style="background:#fff; padding:28px; border-radius:8px;">

<p style="margin:0 0 16px;">Hello Security Professional,</p>

<p style="background:#dc3545;color:#fff;padding:14px 18px;border-radius:6px;font-size:18px;font-weight:600;text-align:center;margin:16px 0;">
    🚨 New Pre-Qualified Security Contract Opportunity Released
</p>

<p>You've been selected to review a budget-verified, decision-maker approved contract through the GASQ network.</p>

<h3 style="border-bottom:2px solid #0d6efd;padding-bottom:6px;margin-top:28px;">🔎 Opportunity Snapshot</h3>
<ul style="padding-left:20px;">
    <li><strong>Location:</strong> {{ $job?->location ?? 'Location TBD' }}</li>
    <li><strong>Service Type:</strong> {{ $serviceType }}</li>
    <li><strong>Start Timeline:</strong> {{ str_replace('_', ' ', $startTimeline) }}</li>
    <li><strong>Total Contract Value:</strong> {{ $contractValueFull }} (Verified)</li>
    <li><strong>Total Credits to Respond:</strong> {{ $creditsToUnlock }}</li>
</ul>

<h3 style="border-bottom:2px solid #0d6efd;padding-bottom:6px;margin-top:28px;">👤 Buyer Preview (Redacted for Protection)</h3>
<ul style="padding-left:20px;">
    <li><strong>Decision Maker Name:</strong> {{ $redactedName }}</li>
    <li><strong>Email:</strong> {{ $redactedEmail }}</li>
    <li><strong>Phone:</strong> {{ $redactedPhone }}</li>
    <li><strong>Phone Verified:</strong> {{ $phoneVerified ? 'Yes' : 'No' }}</li>
    <li><strong>Decision Maker Verified:</strong> {{ $decisionMakerVerified ? 'Yes' : 'No' }}</li>
</ul>

<h3 style="border-bottom:2px solid #0d6efd;padding-bottom:6px;margin-top:28px;">📊 Qualification Status</h3>
<ul style="padding-left:20px;list-style:none;">
    <li>{!! ($decisionMakerVerified && $budgetConfirmed) ? '✅' : '⏳' !!} Decision Maker / Budget / Bid Offer Verified</li>
    <li>{!! $scopeCompleted ? '✅' : '⏳' !!} Scope Defined</li>
    <li>{!! $moveForward ? '✅' : '⏳' !!} Ready to Move Forward</li>
    <li>👥 <strong>Vendor Responses:</strong> {{ $currentAccepts }} of {{ $maxAccepts }} Accepted</li>
</ul>

<p style="background:#cfe2ff;color:#084298;padding:12px 16px;border-radius:6px;">
    👉 You are among the first vendors invited to respond.
</p>

<h3 style="border-bottom:2px solid #198754;padding-bottom:6px;margin-top:28px;">💰 What This Means</h3>
<p>This is not a general inquiry. This buyer:</p>
<ul style="padding-left:20px;">
    <li>Has confirmed funding</li>
    <li>Has approved pricing range</li>
    <li>Is actively selecting a vendor</li>
</ul>

<h3 style="border-bottom:2px solid #198754;padding-bottom:6px;margin-top:28px;">🚀 Your Next Step</h3>
<p>To proceed:</p>
<ul style="padding-left:20px;list-style:none;">
    <li>✅ Accept Opportunity (Unlock full buyer details &amp; contact)</li>
    <li>❌ Decline if not a fit</li>
</ul>

<p style="text-align:center;margin:28px 0;">
    <a href="{{ $url }}"
       style="background:#198754;color:#fff;padding:14px 28px;border-radius:6px;text-decoration:none;display:inline-block;font-weight:700;font-size:16px;">
        👉 VIEW &amp; RESPOND TO OPPORTUNITY
    </a>
</p>

<h3 style="border-bottom:2px solid #fd7e14;padding-bottom:6px;margin-top:28px;">⏳ Response Window</h3>
<p style="background:#fff3cd;color:#664d03;padding:12px 16px;border-radius:6px;">
    Limited to <strong>{{ $maxAccepts }} qualified vendors</strong> only.<br>
    First come. First serve. Spots close as vendors accept.
</p>

<h3 style="border-bottom:2px solid #6c757d;padding-bottom:6px;margin-top:28px;">🔐 GASQ Notice</h3>
<p style="font-size:14px;color:#555;">
    Buyer information is partially redacted to protect lead integrity.
    Full contact details are released upon acceptance.
</p>
<p style="font-size:14px;color:#555;">
    This opportunity has been screened using the GASQ Workforce-to-Post™ qualification system to eliminate unqualified leads and reduce bid risk.
</p>

<p style="font-size:14px;color:#555;">If you have any questions, reply directly to this email.</p>

<p style="margin-top:32px;font-weight:600;">GASQ Vendor Network Team<br><span style="color:#666;font-weight:400;">GetASecurityQuoteNow</span></p>

<hr style="border:none;border-top:1px solid #eee;margin-top:24px;">
<p style="font-size:12px;color:#888;">
    You are receiving this alert because you are a verified vendor in the GASQ network.
</p>

</div>
</body>
</html>

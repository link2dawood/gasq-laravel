<!doctype html>
<html>
<body style="font-family: Arial, Helvetica, sans-serif; color:#222; line-height:1.6; max-width:640px; margin:0 auto; padding:24px;">

<p>Hi{{ $job->user?->name ? ' ' . $job->user->name : '' }},</p>

<p>Thank you for completing your submission with <strong>GetASecurityQuoteNow (GASQ)</strong>.</p>

<p>We're pleased to inform you that your request has been successfully qualified and is now <strong>ACTIVE</strong> within our pre-screened vendor network.</p>

<p style="background:#d1e7dd;color:#0a3622;padding:12px 16px;border-radius:6px;font-size:16px;">
    ✅ <strong>Qualification Status: APPROVED</strong>
</p>

<p>Your submission meets all required criteria:</p>
<ul>
    <li>Verified decision-maker authority</li>
    <li>Confirmed budget and pricing alignment</li>
    <li>Commitment to interview qualified vendors</li>
    <li>Ready-to-move-forward status</li>
</ul>

<h3 style="margin-top:32px;">📊 Real-Time Vendor Match Status</h3>
<p>You can now track vendor engagement in real time:</p>
<ul>
    <li><strong>Vendors Accepted:</strong> {{ $vendorsAccepted }} of {{ $vendorTargetCount }}</li>
    <li><strong>Open Slots Remaining:</strong> {{ $openSlots }}</li>
    <li><strong>Response Activity:</strong> {{ $responseActivity }}</li>
    <li><strong>Interview Phase:</strong> {{ $interviewPhase }}</li>
</ul>
<p style="color:#555;font-size:13px;margin-top:-6px;">
    <em>(This is a snapshot at send time. Live updates are always available on your dashboard.)</em>
</p>

<p style="margin:24px 0;">
    <a href="{{ $dashboardUrl }}"
       style="background:#0d6efd;color:#fff;padding:12px 22px;border-radius:6px;text-decoration:none;display:inline-block;font-weight:600;">
        Track Vendor Activity
    </a>
</p>

<h3 style="margin-top:32px;">🔄 What Happens Next</h3>
<p>Your project is now visible to up to <strong>{{ $vendorTargetCount }} qualified vendors</strong>. As they review your request:</p>
<ul>
    <li>Vendors will accept or decline based on your approved price and scope</li>
    <li>Once accepted, vendors will move into the interview scheduling phase</li>
    <li>You will receive instant notifications as activity occurs</li>
</ul>

<h3 style="margin-top:32px;">🤝 Your Confirmed Commitments</h3>
<p>As part of your qualification, you agreed to:</p>
<ul>
    <li>Interview all qualified vendors prior to selection</li>
    <li>Move forward if a vendor meets your requirements at your approved price</li>
</ul>

<h3 style="margin-top:32px;">🛡️ Your GASQ Protections</h3>
<ul>
    <li><strong>Vendor Replacement Guarantee</strong></li>
    <li><strong>Price Lock Guarantee</strong></li>
</ul>
<p style="color:#555;font-size:14px;">All commitments are backed by structured enforcement and vendor participation standards.</p>

<h3 style="margin-top:32px;">🚀 Next Step: Prepare for Interviews</h3>
<p>As vendors continue to accept your request, you'll be prompted to schedule interviews directly through your dashboard.</p>

<p style="background:#fff3cd;color:#664d03;padding:12px 16px;border-radius:6px;margin-top:24px;">
    💡 <strong>GASQ Insight:</strong> Projects with active real-time engagement typically reach full vendor acceptance ({{ $vendorTargetCount }}/{{ $vendorTargetCount }}) within hours — not days. Early responsiveness ensures you secure top-performing vendors first.
</p>

<p style="margin-top:32px;color:#666;font-size:13px;">— Get A Security Quote Now Team</p>

</body>
</html>

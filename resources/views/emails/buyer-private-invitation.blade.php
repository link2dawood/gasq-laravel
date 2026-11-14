<!doctype html>
<html>
<body style="font-family: Arial, Helvetica, sans-serif; color:#222; line-height:1.6; max-width:640px; margin:0 auto; padding:24px;">

<p>Hi {{ $buyerFirstName }},</p>

<p>Thank you for considering {{ $vendorCompanyName }} for your security needs.</p>

<p>Please use your private link below to access and complete the GASQ Buyer Intake Form:</p>

<p style="margin:24px 0;">
    <a href="{{ $invitationUrl }}" style="background:#0d6efd;color:#fff;padding:14px 28px;border-radius:6px;text-decoration:none;display:inline-block;font-weight:700;">Open your private intake form</a>
</p>

<p style="word-break:break-all;font-size:13px;color:#555;">{{ $invitationUrl }}</p>

<p>Please provide:</p>

<ul style="padding-left:20px;">
    <li style="margin-bottom:6px;">Decision-maker's name and contact information.</li>
    <li style="margin-bottom:6px;">Projected or approved security budget.</li>
    <li style="margin-bottom:6px;">Anticipated start date and end date, if applicable.</li>
    <li style="margin-bottom:6px;">Requested scope of services, including location, coverage schedule, and staffing needs.</li>
</ul>

<p>After completing the form, please return it directly to {{ $vendorContactName }} at <a href="mailto:{{ $vendorEmail }}">{{ $vendorEmail }}</a>.</p>

<p>If your budget or service requirements are still being developed, please indicate that on the form.</p>

<p>Once we receive your completed form, we will review your requirements and contact you to arrange a brief scope review and any necessary site assessment before preparing your estimate.</p>

<p>This invitation is intended for your organization. Please keep the link private.</p>

<p style="margin-top:28px;">Thank you,</p>

<p style="margin:0;">
    {{ $vendorContactName }}<br>
    {{ $vendorCompanyName }}<br>
    @if($vendorPhone){{ $vendorPhone }}<br>@endif
    <a href="mailto:{{ $vendorEmail }}">{{ $vendorEmail }}</a><br>
    GASQ Network Vendor
</p>

<p style="margin-top:24px;font-style:italic;color:#555;">Know Before You Buy.</p>

</body>
</html>

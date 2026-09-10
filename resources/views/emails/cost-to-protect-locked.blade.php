{{--
    Covering email for the LOCKED PREVIEW of the Cost to Protect estimate.

    Deliberately quotes no figures: the attachment masks every number, so a cover
    email that stated the savings would hand over exactly what the preview
    withholds. The unlocked report goes out separately.
--}}
<!DOCTYPE html>
<html>
<head><meta charset="utf-8"><title>GASQ Cost to Protect™ Estimate — Locked Preview</title></head>
<body style="font-family: Arial, Helvetica, sans-serif; color:#1e293b; font-size:14px; line-height:1.55;">

<p>Dear Valued Client,</p>

<p>Attached is a preview of your GASQ Certified™ Cost to Protect™ Estimate.</p>

<p style="margin-left:8px;">
    @if(!empty($reportNumber))<strong>Report Number:</strong> {{ $reportNumber }}<br>@endif
    <strong>Date Prepared:</strong> {{ $datePrepared }}
</p>

<p>The preview shows the complete analysis — the methodology, the coverage
assumptions, the comparison structure and every category of cost included in the
estimate. <strong>The figures themselves are withheld.</strong></p>

<p>To release the unlocked report, with the full Buyer Internal and Vendor
Outsourcing Cost to Protect figures, the capital recovery and the payback period,
please reply to this email or contact your GASQ representative.</p>

@if(!empty($surveyorNotes))
<p><strong>Notes from your GASQ representative:</strong></p>
<div style="margin:6px 0 10px 0; padding:10px 12px; background:#f1f5f9; border-left:3px solid #153a81; white-space:pre-line;">{{ $surveyorNotes }}</div>
@endif

<p>Respectfully,<br>
Get A Security Quote (GASQ) Team</p>

<p style="color:#475569;">
    Get A Security Quote™<br>
    The Financial Procurement System for Security Services™<br>
    The Industry Pricing Referee™
</p>

<p style="color:#475569; font-weight:bold;">Know Before You Buy. Qualifications First. Price Last.</p>

<hr style="border:none; border-top:1px solid #cbd5e1; margin:20px 0;">

<p style="font-size:11px; color:#64748b; line-height:1.5;">
    <strong>CONFIDENTIALITY NOTICE:</strong>
    This appraisal report contains proprietary and confidential information intended solely for the named recipient. Unauthorized reproduction, redistribution, reverse engineering, commercial use, or creation of derivative works is prohibited without written authorization from GASQ.
</p>

</body>
</html>

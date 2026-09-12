{{--
    Covering email for the BUYER EDITION of the Cost to Protect estimate.

    Quotes no vendor figures: the attachment shows the buyer's own in-house cost
    in full and withholds the vendor cost, the capital recovered and the payback
    period. A cover email that stated the savings would hand over exactly what
    the edition withholds. The complete report goes out separately.
--}}
<!DOCTYPE html>
<html>
<head><meta charset="utf-8"><title>GASQ Cost to Protect™ Estimate — Buyer Edition</title></head>
<body style="font-family: Arial, Helvetica, sans-serif; color:#1e293b; font-size:14px; line-height:1.55;">

<p>Dear Valued Client,</p>

<p>Attached is your GASQ Certified™ Cost to Protect™ Estimate, Buyer Edition.</p>

<p style="margin-left:8px;">
    @if(!empty($reportNumber))<strong>Report Number:</strong> {{ $reportNumber }}<br>@endif
    <strong>Date Prepared:</strong> {{ $datePrepared }}
</p>

<p>This edition reports <strong>your own in-house Cost to Protect in full</strong>: what this
scope costs to deliver with your own workforce annually and per hour, the staff
required, and the coverage hours behind both figures. It also sets out the full
methodology and every category of cost the estimate accounts for.</p>

<p>The vendor outsourcing cost, the operational capital recovered and the payback
period are withheld. To release the complete estimate with those figures, please
reply to this email or contact your GASQ representative.</p>

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

<!doctype html>
<html>
<body style="font-family: Arial, Helvetica, sans-serif; color:#222; line-height:1.6; max-width:680px; margin:0 auto; padding:24px;">

<h2 style="margin:0 0 4px;">GASQ Buyer Intake Form</h2>
<p style="color:#666;margin:0 0 20px;">Know Before You Buy. Submitted {{ $lead->created_at->format('M j, Y g:i A') }} through {{ $vendorCompanyName }}'s private invitation link.</p>

@if($forBuyer)
<p style="margin:0 0 24px;padding:12px 16px;border-left:4px solid #198754;background:#f2faf5;">
    Hi {{ \Illuminate\Support\Str::of($lead->contact_name)->trim()->explode(' ')->first() }}, thank you. Your completed form has been returned to <strong>{{ $vendorContactName }}</strong> at <a href="mailto:{{ $vendorEmail }}">{{ $vendorEmail }}</a>@if($vendorPhone), {{ $vendorPhone }}@endif.
    They will review your requirements and contact you to arrange a brief scope review and any necessary site assessment before preparing your estimate. A copy of your answers is below for your records.
</p>
@else
<p style="margin:0 0 24px;padding:12px 16px;border-left:4px solid #0d6efd;background:#f4f8ff;">
    <strong>Internal review status: {{ $lead->qualificationStatusLabel() }}</strong><br>
    <span style="color:#555;">{{ $lead->qualificationStatusGuidance() }}</span>
</p>
@endif

<h3 style="margin:0 0 8px;">Contact and project information</h3>
<table style="width:100%;border-collapse:collapse;margin-bottom:24px;">
    <tr><td style="padding:5px 0;color:#666;width:42%;">Company/property name</td><td>{{ $lead->company ?: 'Not supplied' }}</td></tr>
    <tr><td style="padding:5px 0;color:#666;">Contact name and title</td><td>{{ $lead->contact_name }}{{ $lead->contact_title ? ', '.$lead->contact_title : '' }}</td></tr>
    <tr><td style="padding:5px 0;color:#666;">Phone/email</td><td>{{ $lead->phone }} · <a href="mailto:{{ $lead->email }}">{{ $lead->email }}</a></td></tr>
    <tr><td style="padding:5px 0;color:#666;">Service location/address</td><td>{{ $lead->site_address }}</td></tr>
    <tr><td style="padding:5px 0;color:#666;">Authorized decision-maker</td><td>{{ $lead->decision_maker_name ?: 'Not supplied' }}</td></tr>
    <tr><td style="padding:5px 0;color:#666;">Decision-maker's phone</td><td>{{ $lead->decision_maker_phone ?: 'Not supplied' }}</td></tr>
    <tr><td style="padding:5px 0;color:#666;">Decision-maker's email</td><td>{{ $lead->decision_maker_email ?: 'Not supplied' }}</td></tr>
    <tr><td style="padding:5px 0;color:#666;">Approved budget</td><td><strong>{{ $lead->approvedBudgetLabel() }}</strong></td></tr>
    <tr><td style="padding:5px 0;color:#666;">Requested start date</td><td>{{ $lead->start_date?->format('M j, Y') ?: 'Not supplied' }}</td></tr>
    <tr><td style="padding:5px 0;color:#666;">Service duration/end date</td><td>{{ $lead->service_end_date?->format('M j, Y') ?: 'Not supplied' }}</td></tr>
    <tr><td style="padding:5px 0;color:#666;">Coverage</td><td>{{ $lead->coverageLabel() }}</td></tr>
    <tr><td style="padding:5px 0;color:#666;">Total weekly service hours</td><td>{{ $lead->weekly_hours ?: 'Not supplied' }}</td></tr>
    <tr><td style="padding:5px 0;color:#666;">Service type</td><td>{{ $lead->serviceTypeLabel() }}</td></tr>
    <tr><td style="padding:5px 0;color:#666;">Business industry</td><td>{{ $lead->industry ?: 'Not supplied' }}</td></tr>
    <tr><td style="padding:5px 0;color:#666;">Preferred contact method</td><td>{{ \App\Support\BuyerQualificationQuestionnaire::CONTACT_METHODS[$lead->preferred_contact_method] ?? 'Not supplied' }}</td></tr>
</table>

@if($lead->scope)
<h3 style="margin:0 0 8px;">Requirements</h3>
<div style="white-space:pre-wrap;overflow-wrap:anywhere;margin-bottom:24px;padding:12px 16px;background:#f7f7f7;">{{ $lead->scope }}</div>
@endif

@foreach($sections as $section)
<h3 style="margin:0 0 8px;">{{ $section['title'] }}</h3>
<table style="width:100%;border-collapse:collapse;margin-bottom:20px;">
    @foreach($section['rows'] as $row)
    <tr style="border-bottom:1px solid #eee;">
        <td style="padding:7px 8px 7px 0;color:#666;width:6%;vertical-align:top;">{{ $row['number'] }}</td>
        <td style="padding:7px 8px 7px 0;vertical-align:top;">{{ $row['text'] }}</td>
        <td style="padding:7px 0;text-align:right;vertical-align:top;white-space:nowrap;@if($row['is_no']) color:#b02a37;font-weight:700; @endif">{{ $row['answer'] }}</td>
    </tr>
    @endforeach
</table>
@endforeach

@if($clarifications)
<h3 style="margin:0 0 8px;">Clarify before the qualification meeting</h3>
<p style="color:#555;margin:0 0 8px;">A "No" should prompt clarification rather than automatic rejection. Confirm these points with the buyer:</p>
<ul style="margin:0 0 24px;padding-left:20px;">
    @foreach($clarifications as $item)
    <li style="margin-bottom:4px;">{{ $item }}</li>
    @endforeach
</ul>
@endif

@if($forBuyer)
<p style="color:#888;font-size:12px;border-top:1px solid #eee;padding-top:14px;">
    Reply to this email to reach {{ $vendorContactName }} at {{ $vendorCompanyName }} directly. If your budget or service requirements are still being developed, tell them so and they will work through it with you.
</p>
@else
<p style="margin:28px 0;">
    <a href="{{ $leadsUrl }}" style="background:#0d6efd;color:#fff;padding:13px 26px;border-radius:6px;text-decoration:none;display:inline-block;font-weight:600;">Open this lead and set its status</a>
</p>

<p style="color:#888;font-size:12px;border-top:1px solid #eee;padding-top:14px;">
    This submission is tracked on {{ $vendorCompanyName }}'s buyer enquiries dashboard. A copy has also been sent to {{ $lead->contact_name }} at {{ $lead->email }}. Reply to this email to answer {{ $lead->contact_name }} directly.
</p>
@endif

</body>
</html>

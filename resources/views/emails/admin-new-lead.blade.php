<!doctype html>
<html>
<body style="font-family: Arial, Helvetica, sans-serif; color:#222; line-height:1.6; max-width:640px; margin:0 auto; padding:24px;">

@php
    use App\Support\LeadFormatting;
    $redactedName = LeadFormatting::redactName($buyer?->name);
    $redactedEmail = LeadFormatting::redactEmail($buyer?->email);
    $redactedPhone = LeadFormatting::redactPhone($buyer?->phone);
    $redactedLocation = LeadFormatting::locationShort($job?->location);
    $dmVerified = (bool) ($opportunity->decision_maker_verified ?? false);
    $phoneVerified = (bool) ($buyer?->phone_verified ?? false);
    $companyRedacted = LeadFormatting::redactName($buyer?->company);
@endphp

<h2 style="margin:0 0 4px;">📥 New GASQ Lead — Tier {{ $tier }}</h2>
<p style="color:#666;margin:0 0 20px;">
    {{ $tierLabel }}
</p>

<table style="width:100%;border-collapse:collapse;margin-bottom:20px;">
    <tr>
        <td style="padding:6px 0;color:#666;width:35%;">Buyer (Decision Maker)</td>
        <td>
            <strong>{{ $redactedName }}</strong>
            @if($dmVerified)
                <span title="Decision maker verified" style="color:#198754;font-weight:700;">✓</span>
            @endif
            ({{ $redactedEmail }})
        </td>
    </tr>
    <tr><td style="padding:6px 0;color:#666;">Company</td><td>{{ $companyRedacted ?: '—' }}</td></tr>
    <tr>
        <td style="padding:6px 0;color:#666;">Phone</td>
        <td>{{ $redactedPhone }} {{ $phoneVerified ? '✅' : '' }}</td>
    </tr>
    <tr><td style="padding:6px 0;color:#666;">Job</td><td>{{ $job?->title ?? '—' }}</td></tr>
    <tr><td style="padding:6px 0;color:#666;">Location</td><td>{{ $redactedLocation ?: '—' }}</td></tr>
    <tr><td style="padding:6px 0;color:#666;">Contract Value</td><td><strong>{{ $contractValueFull }}</strong> ({{ $contractValueShort }})</td></tr>
    <tr><td style="padding:6px 0;color:#666;">Vendor Target</td><td>{{ $opportunity->vendor_target_count ?? 0 }} vendors</td></tr>
</table>

<h3>Qualification Flags</h3>
<ul style="list-style:none;padding-left:0;">
    <li>{!! $opportunity->decision_maker_verified ? '✅' : '❌' !!} Decision Maker Verified</li>
    <li>{!! $opportunity->budget_confirmed ? '✅' : '❌' !!} Budget Confirmed</li>
    <li>{!! $opportunity->scope_completed ? '✅' : '❌' !!} Scope Completed</li>
    <li>{!! $opportunity->timeline_ready ? '✅' : '❌' !!} Timeline Ready</li>
    <li>{!! $opportunity->move_forward_confirmed ? '✅' : '❌' !!} Move-Forward Confirmed</li>
</ul>

<p style="margin:28px 0;text-align:center;">
    <a href="{{ route('vendor-leads.index') }}"
       style="background:#198754;color:#fff;padding:14px 28px;border-radius:6px;text-decoration:none;display:inline-block;font-weight:700;font-size:15px;margin-right:8px;">
        ✓ Accept Lead
    </a>
    <a href="{{ $adminUrl }}"
       style="background:#0d6efd;color:#fff;padding:14px 22px;border-radius:6px;text-decoration:none;display:inline-block;font-weight:600;">
        Open in Dashboard
    </a>
</p>

<p style="color:#666;font-size:12px;background:#f6f8fb;border-left:3px solid #b91c1c;padding:10px 14px;border-radius:4px;">
    <strong>Buyer contact information is redacted.</strong> Full name, email, phone, and street address are hidden until a vendor accepts the opportunity from the in-app dashboard. Vendors must spend credits to unlock unredacted details.
</p>

<p style="color:#666;font-size:13px;margin-top:18px;">
    @if($tier === 'B')
        This lead requires your approval before invitations are sent. Review the questionnaire, then approve or hold.
    @elseif($tier === 'C')
        This buyer was emailed the "Not Qualified" template and prompted to update their questionnaire. Reach out if you want to assist.
    @else
        Vendor invitations have been sent automatically. No action required unless you want to follow up with the buyer.
    @endif
</p>

<p style="margin-top:32px;color:#666;font-size:13px;">— GASQ System</p>

</body>
</html>

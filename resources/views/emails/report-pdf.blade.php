<!DOCTYPE html>
<html>
<head><meta charset="utf-8"><title>Your Report</title></head>
<body style="font-family: sans-serif;">
<p>Your requested GASQ calculator report is attached to this email.</p>

@if(!empty($surveyorNotes) || !empty($attachmentCount))
<p><strong>Notes from your GASQ representative:</strong></p>
@if(!empty($surveyorNotes))
<div style="margin:6px 0 10px 0; padding:10px 12px; background:#f1f5f9; border-left:3px solid #153a81; white-space:pre-line;">{{ $surveyorNotes }}</div>
@endif
@if(!empty($attachmentCount))
<p>{{ $attachmentCount }} on-site {{ \Illuminate\Support\Str::plural('file', $attachmentCount) }} (photos/documents) {{ $attachmentCount === 1 ? 'is' : 'are' }} attached alongside your report.</p>
@endif
@endif

<p>Thanks,<br>{{ config('app.name') }}</p>
</body>
</html>

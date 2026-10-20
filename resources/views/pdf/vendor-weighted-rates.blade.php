@php
  $t = $weighted['totals'];
  $money = fn ($v, $dec = 2) => \App\Support\Currency::symbol() . number_format($v, $dec);
  $number = fn ($v, $dec = 0) => number_format($v, $dec);
  $percent = fn ($v) => $v === null ? 'Unavailable' : number_format($v * 100, 2) . '%';
  $logo = 'file://' . public_path('images/site-logo.png');
  $cards = [
    ['Weighted pay wage', $money($t['weightedPayWage']), $weighted['wageSource'], 'navy'],
    ['Weighted bill rate', $money($t['weightedBillRate']), 'Cost to deliver protection · per hour', 'orange'],
    ['Annual role hours', $number($t['annualRoleHours'], 2), 'Payroll hours across listed positions', 'green'],
    ['Annual bill cost', $money($t['annualBillCost']), 'Exact position-level total', 'navy'],
    ['Weighted wage share', $percent($t['overallWageShare']), 'Total annual wages ÷ annual bill cost', 'green'],
    ['Staff listed', $number($t['headcount']), 'Entered employee headcount', 'orange'],
  ];
@endphp
<!DOCTYPE html>
<html lang="en">
<head><meta charset="utf-8"><title>GASQ Vendor Weighted Rate Dashboard</title>
<style>
  @page { margin:28pt 30pt 35pt; }
  body { font-family:DejaVu Sans,sans-serif; color:#19334f; font-size:10pt; line-height:12pt; margin:0; }
  table { border-collapse:collapse; width:100%; } td,th { vertical-align:top; }
  h1 { font-size:21pt; line-height:26pt; font-weight:normal; margin:8pt 0 3pt; } h2 { font-size:17pt; line-height:22pt; font-weight:normal; margin:8pt 0 4pt; }
  p { margin:4pt 0; line-height:1.4; } .muted { color:#61758b; } .small { font-size:9pt; }
  .header { border-bottom:4pt solid #19334f; background:#eaf0f8; }
  .header td { padding:3pt 10pt; font-size:8.5pt; line-height:11pt; vertical-align:middle; } .logo { width:70pt; }
  .orange-line { border-bottom:3pt solid #f26c20; display:inline-block; padding-bottom:8pt; }
  .metadata { background:#eef3f9; margin:5pt 0 6pt; } .metadata td { padding:3pt 10pt; width:25%; }
  .label { font-size:8.5pt; line-height:11pt; color:#61758b; text-transform:uppercase; margin-bottom:4pt; }
  .cards { table-layout:fixed; border-spacing:0; margin-bottom:4pt; } .cards td { width:33.33%; padding:0 7pt 4pt 0; }
  .card { background:#eef3f9; } .band { padding:3pt 10pt; color:white; background:#19334f; font-size:8.5pt; line-height:11pt; text-transform:uppercase; }
  .value { font-size:22pt; line-height:27pt; text-align:center; padding:2pt 7pt 0; } .sub { text-align:center; padding:0 7pt 2pt; font-size:8.5pt; line-height:11pt; color:#61758b; }
  .orange { background:#fff0e8; } .orange .band { background:#f26c20; } .orange .value { color:#ec6018; }
  .green { background:#e8f4ed; } .green .band { background:#18834d; } .green .value { color:#18834d; }
  .schedule td { text-align:center; padding:4pt 8pt; border-right:1pt solid #d4dfeb; width:25%; } .schedule .metric { font-size:14pt; line-height:18pt; } .schedule p { margin:2pt 0; }
  .basis { margin:5pt 0; } .basis td { width:50%; padding-right:10pt; } .basis .content { background:#eef3f9; padding:5pt 10pt; font-size:9pt; } .basis p { margin:2pt 0; line-height:12pt; }
  .note { background:#fff5d9; padding:6pt 10pt; margin-top:5pt; font-size:8.5pt; line-height:1.25; }
  .page-break { page-break-before:always; }
  .roster { table-layout:fixed; font-size:8pt; margin-top:10pt; } .roster thead { display:table-header-group; }
  .roster th { background:#19334f; color:white; font-weight:normal; padding:6pt 4pt; line-height:10pt; }
  .roster td { padding:2pt 4pt; line-height:1.1; border-bottom:0.6pt solid #d4dfeb; } .roster tr { page-break-inside:avoid; }
  .roster .small { font-size:7.5pt; line-height:1.1; }
  .roster .numeric { text-align:right; } .roster .total td { background:#eaf1f8; font-weight:bold; }
  .readme { background:#eaf1f8; padding:6pt 11pt; font-size:9pt; line-height:12.5pt; }
  .signature { border:0.6pt solid #d4dfeb; padding:6pt 10pt; margin-top:8pt; page-break-inside:avoid; }
  .signature p { font-size:9pt; margin:1.5pt 0; line-height:11.5pt; }
  .signature-mark { font-family:DejaVu Serif,serif; font-style:italic; font-size:22pt; line-height:27pt; }
</style></head>
<body>
<table class="header"><tr><td width="20%"><img class="logo" src="{{ $logo }}" alt="GASQ"></td><td>INDEPENDENT. DATA-DRIVEN. HIGHER STANDARDS.</td><td style="text-align:right"><span class="orange-line">REAL DATA. REAL INSIGHTS.</span></td></tr></table>
<h1>VENDOR WEIGHTED RATE DASHBOARD</h1>
<p class="muted">{{ $weighted['modeLabel'] }} · {{ $weighted['rateSource'] }} · {{ $selectionLabel }}</p>
<table class="metadata"><tr>
  <td><div class="label">Buyer / site</div>{{ $buyerSite }}</td><td><div class="label">Vendor</div>{{ $vendorName }}</td>
  <td><div class="label">Prepared</div>{{ $datePrepared ?? (isset($electronicSignature) ? $electronicSignature->signed_at->format('F j, Y') : now()->format('F j, Y')) }}</td><td><div class="label">Report version</div>GASQ v1.2</td>
</tr></table>
<table class="cards">@foreach(array_chunk($cards, 3) as $row)<tr>@foreach($row as [$title,$value,$subtitle,$color])<td><div class="card {{ $color }}"><div class="band">{{ $title }}</div><div class="value">{{ $value }}</div><div class="sub">{{ $subtitle }}</div></div></td>@endforeach</tr>@endforeach</table>
<div class="band">Schedule totals &amp; calculation basis</div>
<table class="schedule"><tr>
  @foreach([[$number($t['weeklyHours'],2),'Weekly role hours'],[$number($t['monthlyHours'],2),'Monthly role hours'],[$money($t['weeklyBillCost']),'Weekly bill cost'],[$money($t['monthlyBillCost']),'Monthly bill cost']] as [$value,$title])
    <td><div class="metric">{{ $value }}</div><p class="small muted">{{ $title }}</p></td>
  @endforeach
</tr></table>
<table class="basis"><tr><td><div class="band">Annual bill cost</div><div class="content">
  <p><strong>{{ $money($t['annualBillCost']) }} · exact role-level total</strong></p>
  <p>{{ $money($t['weightedBillRate'],4) }}/hr × {{ $number($t['annualRoleHours'],2) }} annual payroll hours</p>
  <p class="muted small">Main-calculator billable service hours remain separate.</p>
</div></td><td><div class="band" style="background:#18834d">Rate build &amp; wage share</div><div class="content">
  <p>Weighted pay: {{ $money($t['annualWages']) }} annual wages ÷ {{ $number($t['annualRoleHours'],2) }} hours = {{ $money($t['weightedPayWage'],4) }}/hr.</p>
  <p>Weighted bill: {{ $money($t['annualBillCost']) }} ÷ {{ $number($t['annualRoleHours'],2) }} hours = {{ $money($t['weightedBillRate'],4) }}/hr.</p>
  <p>Wage share: {{ $percent($t['overallWageShare']) }} using full-precision annual totals.</p>
</div></td></tr></table>
<div class="note"><strong>ROUNDING &amp; STAFFING NOTE</strong><br>
  Annual bill cost is summed from role rows. The displayed {{ $money($t['weightedBillRate']) }} rate × {{ $number($t['annualRoleHours'],2) }} hours gives {{ $money($t['roundedRateAnnualCost']) }}; its difference from the exact total is {{ $money($t['roundingDifference']) }}.
  Staff listed is the entered headcount. Monthly hours and costs use annual totals ÷ 12. Weekly bill cost sums each role’s weekly hours × bill rate; it is not annual cost ÷ 52 when role weeks differ. Shared assumptions: {{ $number($weightedInputs['weeklyHoursPerEmployee'],2) }} hours/employee/week and {{ $number($weightedInputs['weeksPerYear'],2) }} weeks; overrides appear in the schedule.
</div>
<div class="page-break"></div>
<table class="header"><tr><td><img class="logo" src="{{ $logo }}" alt="GASQ"></td><td style="text-align:right">VENDOR WEIGHTED BILL RATE<br><span class="muted">COST TO DELIVER PROTECTION</span></td></tr></table>
<h2>Vendor Weighted Bill Rate · Cost to Deliver Protection</h2>
<p class="muted">{{ $money($t['weightedPayWage']) }}/hr weighted pay · {{ $money($t['weightedBillRate']) }}/hr weighted bill · {{ $number($t['annualRoleHours'],2) }} annual payroll hours · {{ $number($t['headcount']) }} employees</p>
<div class="readme"><strong>How to read this schedule</strong> · Wage share = role pay wage ÷ bill rate. Blank actual wages in Current Bill Rate Analysis use bill rate × 7 ÷ 18, labeled “Implied pay wage — model estimate.” These estimates are not verified employee compensation. GASQ Wage-Based Pricing uses wage × 18 ÷ 7, the fixed GASQ 6:1 model rate.</div>
<table class="roster"><colgroup><col style="width:17%"><col style="width:4%"><col style="width:7%"><col style="width:8%"><col style="width:6%"><col style="width:7%"><col style="width:9%"><col style="width:7%"><col style="width:9%"><col style="width:8%"><col style="width:7%"><col style="width:11%"></colgroup>
<thead><tr><th style="width:17%">Position / classification</th><th style="width:4%">Count</th><th style="width:7%">Pay rate/hr</th><th style="width:8%">Combined hourly wages</th><th style="width:6%">Wage share</th><th style="width:7%">Weekly hours</th><th style="width:9%">Weekly bill cost</th><th style="width:7%">Monthly hours</th><th style="width:9%">Monthly bill cost</th><th style="width:8%">Annual role hours</th><th style="width:7%">Bill rate/hr</th><th style="width:11%">Annual bill cost</th></tr></thead>
<tbody>@foreach($weighted['roles'] as $role)<tr>
  <td>{{ $role['label'] }}<div class="small muted">{{ $number($role['weeklyHoursPerEmployee'],2) }} hr/week × {{ $number($role['weeksPerYear'],2) }} weeks</div></td>
  <td class="numeric">{{ $number($role['headcount']) }}</td><td class="numeric">{{ $money($role['payRate']) }}<div class="small muted">{{ $role['paySource'] === 'implied' ? 'Model estimate' : 'Actual wage' }}</div></td>
  @foreach([['combinedHourlyWages','money'],['wageShareOfBillRate','percent'],['weeklyHours','number'],['weeklyBillCost','money'],['monthlyHours','number'],['monthlyBillCost','money'],['annualRoleHours','number'],['billRate','money'],['annualBillCost','money']] as [$field,$format])
    <td class="numeric">{{ $format === 'money' ? $money($role[$field]) : ($format === 'percent' ? $percent($role[$field]) : $number($role[$field],2)) }}</td>
  @endforeach
</tr>@endforeach
<tr class="total"><td>TOTAL / WEIGHTED</td><td class="numeric">{{ $number($t['headcount']) }}</td>
  @foreach([['weightedPayWage','money'],['combinedHourlyWages','money'],['overallWageShare','percent'],['weeklyHours','number'],['weeklyBillCost','money'],['monthlyHours','number'],['monthlyBillCost','money'],['annualRoleHours','number'],['weightedBillRate','money'],['annualBillCost','money']] as [$field,$format])
    <td class="numeric">{{ $format === 'money' ? $money($t[$field]) : ($format === 'percent' ? $percent($t[$field]) : $number($t[$field],2)) }}</td>
  @endforeach
</tr></tbody></table>
<p class="small"><strong>Wage source:</strong> {{ $weighted['wageSource'] }}. <strong>Total annual wages:</strong> {{ $money($t['annualWages']) }}. Full precision is retained before display rounding.</p>
<p class="small muted">Private vendor working data. This document does not submit a bid or reveal sealed pricing. Buyer-facing disclosure follows the platform’s existing sealed-price reveal process.</p>
@if(isset($electronicSignature))
  <div class="signature">
    <table><tr><td style="width:35%">
      <div class="label">Electronic signature</div>
      @if($electronicSignature->method === 'drawn')
        <img src="{{ $electronicSignature->signature_image }}" style="width:170pt;height:42.5pt" alt="Electronic signature">
      @else
        <div class="signature-mark">{{ $electronicSignature->signer_name }}</div>
      @endif
    </td><td>
      <p><strong>{{ $electronicSignature->signer_name }}</strong>@if($electronicSignature->signer_title) · {{ $electronicSignature->signer_title }}@endif</p>
      <p class="small">Signed {{ $electronicSignature->signed_at->utc()->format('F j, Y H:i:s') }} UTC · {{ $electronicSignature->method === 'drawn' ? 'Drawn signature' : 'Typed signature' }}</p>
      <p class="small">{{ $electronicSignature->consent }}</p>
      <p class="small muted">Signature record: {{ $electronicSignature->report_id }}</p>
    </td></tr></table>
  </div>
@endif
</body></html>

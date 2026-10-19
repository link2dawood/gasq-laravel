@extends('emails.layouts.gasq-base', [
    'title' => 'GASQ Vendor Weighted Rate Report',
    'preheader' => 'Understand the staffing, wages, bill rates, and costs behind the price of protection.',
])

@section('content')
<p>Hello {{ $recipientName }},</p>

<p>Whether you purchase security services or provide them, understanding how the price is built supports better decisions.</p>

<p>Attached is the GASQ Vendor Weighted Rate Report, dated {{ $datePrepared }}. It combines the wages and bill rates of the listed security officers and support staff into weighted averages based on each role’s scheduled hours.</p>

<p>The report shows staffing positions, employee counts, scheduled hours, weighted wages and bill rates, wage share, and weekly, monthly, and annual costs.</p>

<p><strong>For buyers:</strong> It helps you understand the staffing behind the price and provides a basis for comparing costs with your current security budget for equivalent coverage.</p>

<p><strong>For vendors:</strong> It helps you review how each role contributes to your overall rate, assess staffing assumptions, and explain the cost to deliver protection.</p>

<p>The listed hours represent staffing and payroll hours. Customer billable coverage hours and the allocation of shared or dedicated support resources should be confirmed when reviewing a specific service proposal.</p>

<p><strong>The attachment is password-protected. Reply “ACCESS” or contact me directly, and I’ll provide your password separately.</strong></p>

<p>I’m available for a 10-minute walkthrough to explain the figures and discuss how the report applies to your purchasing or pricing decisions.</p>

<p>Please forward this email to the person responsible for your security budget or vendor pricing. If forwarding isn’t possible, let me know how best to coordinate a review.</p>

<p>Thank you,</p>

<p>Get A Security Quote Team<br>
Know Before You Buy. Know Before You Bid.<br>
<a href="tel:+14706332816">(470) 633-2816</a><br>
<a href="mailto:info@getasecurityquote.com">info@getasecurityquote.com</a></p>
@endsection

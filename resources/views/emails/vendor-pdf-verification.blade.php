@extends('emails.layouts.gasq-base', ['title' => 'Verify your GASQ PDF access', 'preheader' => 'Your verification code for protected PDF access.'])
@section('content')
<p>Your verification code for estimate {{ $estimateNumber }} is:</p>
<p style="font-size:28px;letter-spacing:4px;font-weight:bold;">{{ $code }}</p>
<p>This code expires in 10 minutes. It verifies access to this delivery only. It is not the PDF password.</p>
<p>If you did not request access, you can ignore this email.</p>
@endsection

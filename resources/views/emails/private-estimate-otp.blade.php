@extends('emails.layouts.gasq-base', ['preheader' => 'Your Get A Security Quote verification code.'])

@section('content')
    <h2 style="margin:0 0 12px;font-size:20px;color:#0b2447;">Your verification code</h2>

    <p style="margin:0 0 16px;">Enter this code to open estimate {{ $estimate->public_id }}:</p>

    <p style="margin:0 0 20px;font-size:32px;font-weight:bold;letter-spacing:6px;color:#0b2447;">{{ $code }}</p>

    <p style="margin:0 0 8px;font-size:13px;color:#6b7280;">
        The code expires in {{ $expiresInMinutes }} minutes and can only be used once.
    </p>
    <p style="margin:0;font-size:13px;color:#6b7280;">
        If you didn't request this, you can ignore this email. Nobody can open the estimate without the code.
    </p>
@endsection

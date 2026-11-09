<!doctype html><html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><meta name="robots" content="noindex,nofollow"><title>GASQ Protected PDF Access</title>
<style>body{font-family:Arial,sans-serif;background:#f5f7fb;color:#1e3558;margin:0;line-height:1.6}main{max-width:640px;margin:48px auto;padding:28px;background:white;border-top:4px solid #1e3558}button{background:#1e3558;color:white;border:0;padding:12px 18px;cursor:pointer}input{padding:10px;font-size:16px}form{margin:20px 0}.notice{padding:12px;background:#edf3fa}code{font-size:20px;overflow-wrap:anywhere}label{display:block}</style></head><body><main>
<h1>Protected PDF access</h1><p>Estimate {{ $delivery->snapshot['number'] }}</p>
@if(session('status'))<p class="notice" role="status">{{ session('status') }}</p>@endif
@if($errors->any())<div role="alert">@foreach($errors->all() as $error)<p>{{ $error }}</p>@endforeach</div>@endif
@if($delivery->access_revoked_at || ($delivery->access_expires_at && $delivery->access_expires_at->isPast()))
<p>Online access is revoked or expired. Please contact your vendor. Previously downloaded files are not affected.</p>
@elseif(!$delivery->password_released_at)
<p>The PDF password is restricted until your appointment review is complete and your vendor manually releases access.</p>
<p>Use the scheduling link in your estimate email to arrange your review.</p>
@elseif(!$verified)
<p>Your vendor released access on {{ $delivery->password_released_at->utc()->format('M j, Y g:i A') }} UTC. Verify the original buyer email to retrieve the password.</p>
<form method="POST" action="{{ route('vendor-pdf.code', $delivery->scheduling_token) }}">@csrf<button type="submit">Email my verification code</button></form>
<form method="POST" action="{{ route('vendor-pdf.verify', $delivery->scheduling_token) }}">@csrf<label for="code">Six-digit verification code</label><input id="code" name="code" inputmode="numeric" pattern="[0-9]{6}" maxlength="6" autocomplete="one-time-code" required><button type="submit">Verify email</button></form>
@else
<p>Your email is verified for this delivery. Online access expires {{ $delivery->access_expires_at->utc()->format('M j, Y g:i A') }} UTC.</p>
@if($revealedPassword !== null)<p class="notice">PDF password: <code>{{ $revealedPassword }}</code></p>@endif
<form method="POST" action="{{ route('vendor-pdf.reveal', $delivery->scheduling_token) }}">@csrf<button type="submit">Reveal PDF password</button></form>
<form method="POST" action="{{ route('vendor-pdf.download', $delivery->scheduling_token) }}">@csrf<button type="submit">Download protected PDF</button></form>
<p class="notice">Online password reveals and PDF downloads are recorded. Opening a saved PDF outside this website cannot be tracked.</p>
@endif
<p>For help, reply to your estimate email.</p>
</main></body></html>

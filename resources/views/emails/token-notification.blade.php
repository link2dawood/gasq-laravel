<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Credits notification</title>
    <style>
        body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif; line-height: 1.6; color: #374151; }
        .container { max-width: 600px; margin: 0 auto; padding: 20px; }
        .header { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 30px; text-align: center; border-radius: 8px 8px 0 0; }
        .header.free-pool { background: linear-gradient(135deg, #10b981 0%, #059669 100%); }
        .content { background: white; padding: 30px; border: 1px solid #e5e7eb; border-top: none; border-radius: 0 0 8px 8px; }
        .footer { text-align: center; padding: 20px; color: #6b7280; font-size: 14px; }
        .button { display: inline-block; padding: 12px 24px; background: #667eea; color: white !important; text-decoration: none; border-radius: 6px; font-weight: 600; }
        .button.green { background: #10b981; }
        .badge { display: inline-block; padding: 4px 12px; background: #f3f4f6; border-radius: 4px; font-weight: 600; }
        .highlight { color: #667eea; font-weight: 700; font-size: 24px; }
        .highlight.green { color: #10b981; }
        .box { text-align: center; margin: 30px 0; background: #f0fdf4; padding: 20px; border-radius: 8px; }
    </style>
</head>
<body>
    <div class="container">
        @if(in_array($type, ['bonus', 'grant']))
            <div class="header">
                <h1 style="margin:0;">Bonus credits received</h1>
            </div>
            <div class="content">
                <p>Hi {{ $userName }}!</p>
                <p>You've received a bonus:</p>
                <div class="box">
                    <span class="highlight">+{{ $tokensChange }} credits</span>
                    @if($reason)
                        <p style="margin-top: 10px;"><span class="badge">{{ $reason }}</span></p>
                    @endif
                </div>
                <p><strong>Current balance:</strong> {{ $currentBalance }} credits</p>
                <p>Use your credits for calculator reports, job posts, and more.</p>
                <p style="text-align: center; margin-top: 24px;">
                    <a href="{{ url('/credits') }}" class="button">View my credits</a>
                </p>
            </div>
        @elseif(in_array($type, ['free_pool', 'free_pool_refresh']))
            <div class="header free-pool">
                <h1 style="margin:0;">Monthly free credits</h1>
            </div>
            <div class="content">
                <p>Hi {{ $userName }}!</p>
                <p>Free credits have been added to your account.</p>
                <div class="box">
                    <p style="font-size: 18px; margin-bottom: 10px;"><strong>Credits added</strong></p>
                    <span class="highlight green">+{{ $tokensChange }} credits</span>
                    @if($reason)
                        <p style="margin-top: 10px;">{{ $reason }}</p>
                    @endif
                </div>
                <p><strong>Current balance:</strong> {{ $currentBalance }} credits</p>
                <p style="text-align: center; margin-top: 24px;">
                    <a href="{{ url('/credits') }}" class="button green">View my credits</a>
                </p>
            </div>
        @else
            {{-- purchase or default --}}
            <div class="header">
                <h1 style="margin:0;">Purchase confirmed</h1>
            </div>
            <div class="content">
                <p>Hi {{ $userName }}!</p>
                <p>Your credits have been added to your account.</p>
                <div class="box">
                    <p style="font-size: 18px; margin-bottom: 10px;"><strong>Credits added</strong></p>
                    <span class="highlight green">+{{ $tokensChange }} credits</span>
                </div>
                <p><strong>New balance:</strong> {{ $currentBalance }} credits</p>
                <p style="text-align: center; margin-top: 24px;">
                    <a href="{{ url('/') }}" class="button">Go to dashboard</a>
                </p>
            </div>
        @endif
        <div class="footer">
            <p>{{ config('app.name') }} – Making security procurement smarter and more transparent.</p>
        </div>
    </div>
</body>
</html>

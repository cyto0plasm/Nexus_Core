<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
    <title>Password Reset — Nexus</title>
    <style>
        :root { --brand: #4F46E5; }
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { background-color: #f5f5f5; font-family: 'Segoe UI', Arial, sans-serif; color: #1a1a1a; }
        .wrapper { padding: 24px 12px; }
        .container { max-width: 460px; margin: 0 auto; background: #ffffff; border-radius: 12px; overflow: hidden; border: 1px solid #e4e4e4; }
        .header { background-color: var(--brand); padding: 24px 28px; }
        .header .logo { font-size: 18px; font-weight: 700; color: #ffffff; letter-spacing: 2px; text-transform: uppercase; }
        .body { padding: 28px; }
        .title { font-size: 17px; font-weight: 600; margin-bottom: 10px; }
        .subtitle { font-size: 14px; color: #666; line-height: 1.6; margin-bottom: 28px; }
        .otp-label { font-size: 11px; text-transform: uppercase; letter-spacing: 1.5px; color: #999; margin-bottom: 10px; }
        .otp-box { background-color: #f8f8ff; border: 1.5px solid var(--brand); border-radius: 8px; padding: 20px 12px; text-align: center; margin-bottom: 20px; }
        .code { font-size: 40px; font-weight: 700; letter-spacing: 8px; color: var(--brand); display: block; word-break: break-all; }
        .expiry { font-size: 13px; color: #e74c3c; margin-bottom: 24px; }
        .divider { border: none; border-top: 1px solid #eeeeee; margin-bottom: 20px; }
        .ignore { font-size: 13px; color: #999; line-height: 1.6; }
        .footer { background-color: #fafafa; border-top: 1px solid #eeeeee; padding: 16px 28px; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 8px; }
        .footer .brand { font-size: 12px; font-weight: 700; letter-spacing: 1.5px; text-transform: uppercase; color: var(--brand); }
        .footer .copy { font-size: 11px; color: #bbb; }
    </style>
</head>
<body>
    <div class="wrapper">
        <div class="container">
            <div class="header">
                <div class="logo">Nexus</div>
            </div>
            <div class="body">
                <p class="title">Password Reset Request</p>
                <p class="subtitle">We received a request to reset your password. Use the code below to proceed.</p>
                <p class="otp-label">Your OTP Code</p>
                <div class="otp-box">
                    <span class="code">{{ $otp }}</span>
                </div>
                <p class="expiry">This code expires in {{ config('otp.expires_minutes') ?? '10' }} minutes.</p>
                <hr class="divider">
                <p class="ignore">If you didn't request a password reset, you can safely ignore this email. Your account remains secure.</p>
            </div>
            <div class="footer">
                <span class="brand">Nexus</span>
                <span class="copy">&copy; {{ date('Y') }} Nexus. All rights reserved.</span>
            </div>
        </div>
    </div>
</body>
</html>

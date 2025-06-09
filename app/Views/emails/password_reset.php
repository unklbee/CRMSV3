<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Password Reset Request</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            color: #333;
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
        }
        .header {
            background: linear-gradient(135deg, #dc2626, #b91c1c);
            color: white;
            padding: 30px 20px;
            text-align: center;
            border-radius: 8px 8px 0 0;
        }
        .content {
            background: #f8fafc;
            padding: 30px 20px;
            border-radius: 0 0 8px 8px;
        }
        .button {
            display: inline-block;
            background: #dc2626;
            color: white;
            padding: 12px 30px;
            text-decoration: none;
            border-radius: 6px;
            margin: 20px 0;
        }
        .alert {
            background: #fef2f2;
            border: 1px solid #fecaca;
            color: #991b1b;
            padding: 15px;
            border-radius: 6px;
            margin: 20px 0;
        }
        .footer {
            text-align: center;
            margin-top: 30px;
            padding-top: 20px;
            border-top: 1px solid #e5e7eb;
            color: #6b7280;
            font-size: 14px;
        }
        .code {
            background: #f3f4f6;
            padding: 2px 6px;
            border-radius: 4px;
            font-family: monospace;
        }
    </style>
</head>
<body>
<div class="header">
    <h1>🔐 Password Reset Request</h1>
</div>

<div class="content">
    <h2>Hello <?= esc($first_name) ?>!</h2>

    <p>We received a request to reset the password for your account. If you made this request, click the button below to reset your password.</p>

    <div style="text-align: center;">
        <a href="<?= $reset_url ?>" class="button">Reset My Password</a>
    </div>

    <div class="alert">
        <strong>⏰ Important:</strong> This password reset link will expire in <strong><?= $expires_in ?></strong> for security reasons.
    </div>

    <p>If the button above doesn't work, you can copy and paste the following link into your browser:</p>
    <p class="code"><?= $reset_url ?></p>

    <hr style="margin: 30px 0; border: none; border-top: 1px solid #e5e7eb;">

    <h3>🛡️ Security Notice</h3>
    <p>If you didn't request this password reset, please ignore this email. Your password will remain unchanged.</p>

    <p>For security reasons:</p>
    <ul>
        <li>Never share this reset link with anyone</li>
        <li>Our team will never ask for your password</li>
        <li>Always verify the sender of password reset emails</li>
    </ul>

    <p>If you're having trouble or didn't request this reset, please contact our support team immediately.</p>

    <p>Best regards,<br>
        <strong>The Security Team</strong></p>
</div>

<div class="footer">
    <p>This email was sent because a password reset was requested for your account.</p>
    <p>If you believe this email was sent in error, please contact support.</p>
</div>
</body>
</html>
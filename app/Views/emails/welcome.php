<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Welcome to Our Platform</title>
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
            background: linear-gradient(135deg, #2563eb, #1e40af);
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
            background: #2563eb;
            color: white;
            padding: 12px 30px;
            text-decoration: none;
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
    </style>
</head>
<body>
<div class="header">
    <h1>🎉 Welcome to Our Platform!</h1>
</div>

<div class="content">
    <h2>Hello <?= esc($first_name) ?>!</h2>

    <p>Thank you for joining our platform! We're excited to have you as part of our community.</p>

    <p>Your account has been successfully created and you can now access all the features of our service management platform.</p>

    <h3>What you can do:</h3>
    <ul>
        <li>Access your personalized dashboard</li>
        <li>Request services and track their progress</li>
        <li>Create and manage support tickets</li>
        <li>View your billing and payment history</li>
        <li>Download important documents</li>
    </ul>

    <div style="text-align: center;">
        <a href="<?= $login_url ?>" class="button">Login to Your Account</a>
    </div>

    <p>If you have any questions or need assistance, please don't hesitate to contact our support team.</p>

    <p>Best regards,<br>
        <strong>The Platform Team</strong></p>
</div>

<div class="footer">
    <p>This email was sent to you because you created an account on our platform.</p>
    <p>If you didn't create this account, please contact us immediately.</p>
</div>
</body>
</html>
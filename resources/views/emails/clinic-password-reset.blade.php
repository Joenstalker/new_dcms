<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset Your Password</title>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            line-height: 1.6;
            color: #333;
            background-color: #f5f5f5;
            margin: 0;
            padding: 0;
        }
        .container {
            max-width: 600px;
            margin: 20px auto;
            background-color: #fff;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }
        .header {
            background: linear-gradient(135deg, {{ $brandingColor }} 0%, #0d47a1 100%);
            color: white;
            padding: 30px 20px;
            text-align: center;
        }
        .header h1 {
            margin: 0;
            font-size: 28px;
            font-weight: 700;
        }
        .header p {
            margin: 10px 0 0 0;
            font-size: 14px;
            opacity: 0.9;
        }
        .content {
            padding: 40px;
        }
        .greeting {
            font-size: 16px;
            margin-bottom: 20px;
        }
        .message {
            color: #666;
            font-size: 14px;
            line-height: 1.8;
            margin-bottom: 30px;
        }
        .cta-button {
            display: inline-block;
            background-color: {{ $brandingColor }};
            color: white;
            padding: 14px 40px;
            border-radius: 8px;
            text-decoration: none;
            font-weight: 600;
            text-align: center;
            margin: 20px 0;
            transition: opacity 0.3s;
        }
        .cta-button:hover {
            opacity: 0.9;
        }
        .link-section {
            margin-top: 30px;
            padding-top: 20px;
            border-top: 1px solid #e0e0e0;
            font-size: 13px;
            color: #999;
        }
        .link-section p {
            margin: 10px 0;
        }
        .link-section a {
            color: {{ $brandingColor }};
            text-decoration: none;
        }
        .footer {
            background-color: #f9f9f9;
            padding: 25px;
            text-align: center;
            font-size: 12px;
            color: #999;
            border-top: 1px solid #e0e0e0;
        }
        .footer-brand {
            font-weight: 600;
            color: #333;
            margin-bottom: 10px;
        }
        .security-note {
            background-color: #fff3cd;
            border-left: 4px solid #ffc107;
            padding: 15px;
            margin-top: 20px;
            border-radius: 4px;
            font-size: 13px;
            color: #856404;
        }
        .clinic-name {
            font-weight: 600;
            color: {{ $brandingColor }};
            margin: 10px 0;
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- Header -->
        <div class="header">
            <h1>🔐 Password Reset</h1>
            <p v-if="tenantName">{{ $tenantName }} Clinic Management System</p>
        </div>

        <!-- Main Content -->
        <div class="content">
            <div class="greeting">
                Hello <strong>{{ $user->name }}</strong>,
            </div>

            <div class="message">
                <p>We received a request to reset the password for your account. If you didn't make this request, you can safely ignore this email.</p>

                <p>To reset your password, click the button below:</p>
            </div>

            <!-- CTA Button -->
            <div style="text-align: center;">
                <a href="{{ $resetLink }}" class="cta-button">Reset Password</a>
            </div>

            <p style="color: #999; font-size: 13px; text-align: center; margin-top: 15px;">
                or copy this link in your browser:
            </p>
            <div style="background-color: #f5f5f5; padding: 12px; border-radius: 4px; word-break: break-all; font-size: 12px; color: #666;">
                {{ $resetLink }}
            </div>

            <!-- Security Note -->
            <div class="security-note">
                <strong>⚠️ For your security:</strong>
                <ul style="margin: 8px 0; padding-left: 20px;">
                    <li>Never share this link with anyone</li>
                    <li>This link expires in 60 minutes</li>
                    <li>If you didn't request this, please secure your account immediately</li>
                </ul>
            </div>

            <!-- Link Section -->
            <div class="link-section">
                <p><strong>Need help?</strong></p>
                <p>If you're having trouble clicking the button, contact our support team at <a href="mailto:support@dcms.app">support@dcms.app</a></p>
            </div>
        </div>

        <!-- Footer -->
        <div class="footer">
            <div class="footer-brand">DCMS - Dental Clinic Management System</div>
            <p style="margin: 8px 0;">This is an automated email. Please do not reply to this address.</p>
            <p style="margin: 8px 0;">© {{ now()->year }} DCMS. All rights reserved.</p>
        </div>
    </div>
</body>
</html>

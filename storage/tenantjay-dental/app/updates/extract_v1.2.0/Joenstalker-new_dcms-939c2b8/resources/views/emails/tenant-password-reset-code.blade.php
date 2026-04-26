<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Your Reset Code</title>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            line-height: 1.6;
            color: #333;
            background-color: #f8fafc;
            margin: 0;
            padding: 0;
        }
        .container {
            max-width: 600px;
            margin: 20px auto;
            background-color: #fff;
            border-radius: 24px;
            overflow: hidden;
            box-shadow: 0 10px 25px rgba(0,0,0,0.05);
        }
        .header {
            background: linear-gradient(135deg, {{ $brandingColor }} 0%, #1e293b 100%);
            color: white;
            padding: 40px 20px;
            text-align: center;
        }
        .header h1 {
            margin: 0;
            font-size: 32px;
            font-weight: 800;
            letter-spacing: -0.5px;
        }
        .header p {
            margin: 10px 0 0 0;
            font-size: 14px;
            opacity: 0.9;
            font-weight: 500;
        }
        .content {
            padding: 40px;
        }
        .greeting {
            font-size: 18px;
            font-weight: 700;
            margin-bottom: 16px;
            color: #1e293b;
        }
        .message {
            color: #64748b;
            font-size: 15px;
            line-height: 1.6;
            margin-bottom: 32px;
        }
        .code-container {
            text-align: center;
            margin: 40px 0;
        }
        .code-box {
            display: inline-block;
            background-color: #f1f5f9;
            border: 2px dashed {{ $brandingColor }};
            padding: 24px 48px;
            border-radius: 20px;
            transition: all 0.3s ease;
        }
        .code-text {
            font-size: 42px;
            font-weight: 800;
            letter-spacing: 12px;
            color: {{ $brandingColor }};
            font-family: 'Courier New', Courier, monospace;
        }
        .security-note {
            background-color: #fff7ed;
            border-left: 4px solid #f97316;
            padding: 20px;
            margin-top: 32px;
            border-radius: 12px;
            font-size: 14px;
            color: #9a3412;
        }
        .footer {
            background-color: #f1f5f9;
            padding: 30px;
            text-align: center;
            font-size: 12px;
            color: #94a3b8;
            border-top: 1px solid #e2e8f0;
        }
        .footer-brand {
            font-weight: 700;
            color: #475569;
            margin-bottom: 8px;
            font-size: 14px;
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- Header -->
        <div class="header">
            <h1>Verification Code</h1>
            <p>{{ $tenantName ?? 'Clinic Management System' }}</p>
        </div>

        <!-- Main Content -->
        <div class="content">
            <div class="greeting">
                Hello {{ $user->name }},
            </div>

            <div class="message">
                <p>We received a request to reset your clinic account password. Please use the following 6-digit verification code to complete the process:</p>
            </div>

            <!-- Code Display -->
            <div class="code-container">
                <div class="code-box">
                    <span class="code-text">{{ $code }}</span>
                </div>
            </div>

            <p style="color: #94a3b8; font-size: 13px; text-align: center; margin-top: 10px;">
                Enter this code in the login modal to proceed.
            </p>

            <!-- Security Note -->
            <div class="security-note">
                <strong>🛡️ For your security:</strong>
                <ul style="margin: 10px 0; padding-left: 20px;">
                    <li>Never share this code with anyone, including our staff.</li>
                    <li>This code is valid for 60 minutes.</li>
                    <li>If you didn't request this, you can safely ignore this email.</li>
                </ul>
            </div>
        </div>

        <!-- Footer -->
        <div class="footer">
            <div class="footer-brand">DCMS - Dental Clinic Management System</div>
            <p>© {{ date('Y') }} DCMS. Secure Clinic Access.</p>
        </div>
    </div>
</body>
</html>

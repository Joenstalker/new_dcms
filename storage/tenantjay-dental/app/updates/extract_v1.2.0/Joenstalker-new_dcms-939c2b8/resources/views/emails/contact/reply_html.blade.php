<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reply from DCMS Support</title>
    <style>
        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;
            background-color: #f4f7f6;
            margin: 0;
            padding: 0;
            color: #333333;
        }
        .email-wrapper {
            width: 100%;
            background-color: #f4f7f6;
            padding: 40px 20px;
        }
        .email-card {
            max-width: 600px;
            margin: 0 auto;
            background-color: #ffffff;
            border-radius: 16px;
            overflow: hidden;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.05);
        }
        .email-header {
            background: linear-gradient(135deg, #1B3A4B 0%, #2B7CB3 100%);
            padding: 40px 40px;
            text-align: center;
        }
        .logo {
            max-width: 180px;
            height: auto;
            margin-bottom: 0;
        }
        .email-body {
            padding: 40px;
            background-color: #ffffff;
        }
        .greeting {
            font-size: 24px;
            font-weight: 700;
            color: #1B3A4B;
            margin-top: 0;
            margin-bottom: 24px;
        }
        .message-content {
            font-size: 16px;
            line-height: 1.6;
            color: #4a5568;
            margin-bottom: 30px;
            white-space: pre-wrap;
        }
        .quote-box {
            background-color: #f8fafc;
            border-left: 4px solid #5EBD6A;
            padding: 20px;
            border-radius: 0 8px 8px 0;
            margin-bottom: 30px;
        }
        .quote-label {
            font-size: 12px;
            font-weight: 700;
            text-transform: uppercase;
            color: #718096;
            margin-bottom: 10px;
            letter-spacing: 0.05em;
        }
        .quote-text {
            font-size: 14px;
            line-height: 1.6;
            color: #4a5568;
            margin: 0;
            font-style: italic;
        }
        .signature {
            font-size: 16px;
            font-weight: 600;
            color: #2B7CB3;
            margin-bottom: 0;
        }
        .email-footer {
            background-color: #f8fafc;
            padding: 30px 40px;
            text-align: center;
            border-top: 1px solid #e2e8f0;
        }
        .footer-text {
            font-size: 13px;
            color: #a0aec0;
            margin: 0;
            line-height: 1.5;
        }
        .footer-link {
            color: #2B7CB3;
            text-decoration: none;
            font-weight: 500;
        }
    </style>
</head>
<body>
    <div class="email-wrapper">
        <div class="email-card">
            <div class="email-header">
                <!-- Using Laravel embed for inline attachment to bypass Gmail blocking base64 -->
                <img src="{{ $message->embed(public_path('images/dcms-logo.png')) }}" alt="DCMS Logo" class="logo">
            </div>
            
            <div class="email-body">
                <h1 class="greeting">Hello {{ $contactMessage->name }},</h1>
                
                <div class="message-content">
                    {{ $replyText }}
                </div>
                
                <div class="quote-box">
                    <div class="quote-label">Your Message ({{ $contactMessage->created_at->format('M d, Y') }})</div>
                    <p class="quote-text">"{{ $contactMessage->message }}"</p>
                </div>
                
                <p class="signature">Warm regards,<br>The DCMS Support Team</p>
            </div>
            
            <div class="email-footer">
                <p class="footer-text">
                    This email was sent in response to your contact form submission on <a href="{{ config('app.url') }}" class="footer-link">DCMS</a>.<br>
                    If you did not make this request, please ignore this email.
                </p>
            </div>
        </div>
    </div>
</body>
</html>

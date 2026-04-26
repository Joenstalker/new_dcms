@php
    /** @var \App\Models\Concern $concern */
@endphp

<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $clinicName }} - Reply</title>
</head>
<body style="margin:0;padding:0;background:#f6f7fb;font-family:Arial,Helvetica,sans-serif;color:#111827;">
<div style="max-width:640px;margin:0 auto;padding:24px;">
    <div style="background:#ffffff;border:1px solid #e5e7eb;border-radius:16px;padding:24px;">
        <div style="font-weight:800;font-size:14px;letter-spacing:0.12em;text-transform:uppercase;color:#6b7280;">
            {{ $clinicName }}
        </div>
        <h1 style="margin:12px 0 0 0;font-size:20px;line-height:1.3;color:#111827;">
            We replied to your message
        </h1>

        <div style="margin-top:16px;padding:16px;border-radius:12px;background:#f9fafb;border:1px solid #eef2f7;">
            <div style="font-size:12px;font-weight:700;color:#6b7280;text-transform:uppercase;letter-spacing:0.12em;">Your message</div>
            @if(!empty($concern->subject))
                <div style="margin-top:10px;font-size:13px;color:#111827;"><strong>Subject:</strong> {{ $concern->subject }}</div>
            @endif
            <div style="margin-top:8px;font-size:13px;color:#111827;white-space:pre-wrap;">{{ $concern->message }}</div>
        </div>

        <div style="margin-top:16px;padding:16px;border-radius:12px;background:#ecfeff;border:1px solid #cffafe;">
            <div style="font-size:12px;font-weight:700;color:#0f766e;text-transform:uppercase;letter-spacing:0.12em;">Reply</div>
            <div style="margin-top:8px;font-size:13px;color:#0f172a;white-space:pre-wrap;">{{ $replyMessage }}</div>
        </div>

        <div style="margin-top:18px;font-size:12px;color:#6b7280;line-height:1.5;">
            If you have more details to add, just reply to this email or contact the clinic again through the website.
        </div>
    </div>

    <div style="text-align:center;margin-top:14px;font-size:11px;color:#9ca3af;">
        Sent on {{ now()->format('Y-m-d H:i') }}
    </div>
</div>
</body>
</html>


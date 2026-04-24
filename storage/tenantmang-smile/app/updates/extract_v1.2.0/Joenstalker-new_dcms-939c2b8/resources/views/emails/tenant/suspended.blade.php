<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Clinic Access Suspended</title>
</head>
<body style="margin: 0; padding: 0; background-color: #f9fafb; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;">
    <table border="0" cellpadding="0" cellspacing="0" width="100%" style="max-width: 600px; margin: 40px auto; background-color: #ffffff; border-radius: 12px; overflow: hidden; box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);">
        <!-- Top Accent -->
        <tr>
            <td style="background-color: #dc2626; padding: 4px;"></td>
        </tr>

        <!-- Header -->
        <tr>
            <td style="padding: 40px 40px 20px 40px; text-align: center;">
                <div style="background-color: #fee2e2; border-radius: 50%; width: 64px; height: 64px; margin: 0 auto 24px auto; display: inline-block; text-align: center; line-height: 64px;">
                    <span style="font-size: 32px;">⚠️</span>
                </div>
                <h1 style="color: #111827; font-size: 24px; font-weight: 800; margin: 0; letter-spacing: -0.025em;">Clinic Access Suspended</h1>
            </td>
        </tr>

        <!-- Content -->
        <tr>
            <td style="padding: 0 40px 40px 40px;">
                <p style="color: #4b5563; font-size: 16px; line-height: 24px; margin-bottom: 24px; text-align: center;">
                    Hello, we are writing to inform you that access to your clinic platform, <strong>{{ $tenant->name ?? $tenant->id }}</strong>, has been suspended by the system administrator.
                </p>

                <!-- Reason Box -->
                <div style="background-color: #fef2f2; border-left: 4px solid #ef4444; padding: 20px; border-radius: 4px; margin-bottom: 32px;">
                    <p style="color: #991b1b; font-size: 14px; font-weight: 700; margin: 0 0 8px 0; text-transform: uppercase; letter-spacing: 0.05em;">Reason for Suspension:</p>
                    <p style="color: #b91c1c; font-size: 16px; font-style: italic; margin: 0; line-height: 1.5;">
                        "{{ $reason }}"
                    </p>
                </div>

                <p style="color: #4b5563; font-size: 14px; line-height: 20px; margin-bottom: 32px; text-align: center;">
                    During suspension, your staff and patients will not be able to access the clinic management dashboard or public booking pages.
                </p>

                <!-- Action Button -->
                <div style="text-align: center; margin-bottom: 32px;">
                    <a href="{{ $tenantUrl }}" style="background-color: #111827; color: #ffffff; padding: 14px 28px; border-radius: 8px; font-size: 16px; font-weight: 600; text-decoration: none; display: inline-block; transition: background-color 0.2s;">
                        Visit Portal & Contact Support
                    </a>
                </div>

                <p style="color: #6b7280; font-size: 14px; text-align: center; margin: 0;">
                    To resolve this issue, please visit your clinic URL above and use the "Contact Support" feature to reach our administration team.
                </p>
            </td>
        </tr>

        <!-- Footer -->
        <tr>
            <td style="background-color: #f9fafb; padding: 24px 40px; border-top: 1px solid #e5e7eb; text-align: center;">
                <p style="color: #9ca3af; font-size: 12px; margin: 0;">
                    &copy; {{ date('Y') }} Dental Clinic Management System. All rights reserved.
                </p>
            </td>
        </tr>
    </table>
</body>
</html>

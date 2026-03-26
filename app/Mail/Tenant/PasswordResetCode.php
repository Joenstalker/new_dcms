<?php

namespace App\Mail\Tenant;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class PasswordResetCode extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public $user;
    public $code;
    public $tenantName;
    public $brandingColor;

    /**
     * Create a new message instance.
     */
    public function __construct($user, $code, $tenantName = null, $brandingColor = null)
    {
        $this->user = $user;
        $this->code = $code;
        $this->tenantName = $tenantName;
        $this->brandingColor = $brandingColor ?? '#3b82f6';
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            from: env('MAIL_FROM_ADDRESS', 'noreply@dcms.app'),
            subject: 'Your Password Reset Verification Code',
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'emails.tenant-password-reset-code',
            with: [
                'user' => $this->user,
                'code' => $this->code,
                'tenantName' => $this->tenantName,
                'brandingColor' => $this->brandingColor,
            ],
        );
    }

    /**
     * Get the attachments for the message.
     *
     * @return array<int, \Illuminate\Mail\Mailables\Attachment>
     */
    public function attachments(): array
    {
        return [];
    }
}

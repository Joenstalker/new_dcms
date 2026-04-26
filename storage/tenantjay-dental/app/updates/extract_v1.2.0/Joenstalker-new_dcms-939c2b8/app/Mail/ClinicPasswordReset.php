<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class ClinicPasswordReset extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public $user;
    public $resetLink;
    public $tenantName;
    public $brandingColor;

    /**
     * Create a new message instance.
     */
    public function __construct($user, $resetLink, $tenantName = null, $brandingColor = null)
    {
        $this->user = $user;
        $this->resetLink = $resetLink;
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
            replyTo: [env('MAIL_FROM_ADDRESS', 'support@dcms.app')],
            subject: 'Reset Your Clinic Account Password',
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'emails.clinic-password-reset',
            with: [
                'user' => $this->user,
                'resetLink' => $this->resetLink,
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

<?php

namespace App\Mail;

use App\Models\PendingRegistration;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class TenantRejected extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public PendingRegistration $registration,
        public ?string $rejectionMessage = null
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Application Status: Rejected | DCMS',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.registration.rejected',
        );
    }
}

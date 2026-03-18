<?php

namespace App\Mail;

use App\Models\PendingRegistration;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class RegistrationPending extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public PendingRegistration $registration
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Registration Received - Awaiting Verification | DCMS',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.registration.pending',
        );
    }
}

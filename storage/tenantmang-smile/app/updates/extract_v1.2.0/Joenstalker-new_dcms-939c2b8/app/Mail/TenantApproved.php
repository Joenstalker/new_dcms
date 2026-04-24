<?php

namespace App\Mail;

use App\Models\PendingRegistration;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class TenantApproved extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public PendingRegistration $registration,
        public string $tenantUrl
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Your Clinic is Now Live! | DCMS',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.registration.approved',
        );
    }
}

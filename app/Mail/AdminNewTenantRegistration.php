<?php

namespace App\Mail;

use App\Models\PendingRegistration;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class AdminNewTenantRegistration extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public PendingRegistration $registration
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'New Tenant Registration Pending Approval | DCMS',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.admin.new_tenant_registration',
        );
    }
}

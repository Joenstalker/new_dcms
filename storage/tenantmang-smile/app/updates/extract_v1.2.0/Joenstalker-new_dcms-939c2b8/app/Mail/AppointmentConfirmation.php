<?php

namespace App\Mail;

use App\Models\Appointment;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class AppointmentConfirmation extends Mailable
{
    use Queueable, SerializesModels;

    public Appointment $appointment;
    public $tenant;
    public bool $isApproval;

    public function __construct(Appointment $appointment, $tenant, bool $isApproval = false)
    {
        $this->appointment = $appointment;
        $this->tenant = $tenant;
        $this->isApproval = $isApproval;
    }

    public function envelope(): Envelope
    {
        $subject = $this->isApproval
            ? 'Your Appointment Has Been Confirmed'
            : 'Appointment Booking Confirmation';

        return new Envelope(
            subject: $subject,
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.appointment-confirmation',
        );
    }
}

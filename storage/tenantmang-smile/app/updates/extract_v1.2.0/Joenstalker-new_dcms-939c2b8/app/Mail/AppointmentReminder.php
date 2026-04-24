<?php

namespace App\Mail;

use App\Models\Appointment;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class AppointmentReminder extends Mailable
{
    use Queueable, SerializesModels;

    public Appointment $appointment;
    public $tenant;

    public function __construct(Appointment $appointment, $tenant)
    {
        $this->appointment = $appointment;
        $this->tenant = $tenant;
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Appointment Reminder — Tomorrow',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.appointment-reminder',
        );
    }
}

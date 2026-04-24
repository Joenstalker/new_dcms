<?php

namespace App\Mail\Tenant;

use App\Models\Concern;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class ConcernReplyMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public Concern $concern,
        public string $replyMessage,
        public string $clinicName,
    ) {
    }

    public function envelope(): Envelope
    {
        $subject = $this->concern->subject ? 'Re: '.$this->concern->subject : 'Reply to your message';

        return new Envelope(
            subject: '['.$this->clinicName.'] '.$subject,
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.tenant.concern-reply',
            with: [
                'concern' => $this->concern,
                'replyMessage' => $this->replyMessage,
                'clinicName' => $this->clinicName,
            ],
        );
    }
}


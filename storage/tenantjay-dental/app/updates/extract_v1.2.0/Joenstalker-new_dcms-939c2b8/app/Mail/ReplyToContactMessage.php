<?php

namespace App\Mail;

use App\Models\ContactMessage;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class ReplyToContactMessage extends Mailable
{
    use SerializesModels;

    public $contactMessage;
    public $replyText;
    protected $attachmentFiles;

    /**
     * Create a new message instance.
     */
    public function __construct(ContactMessage $contactMessage, string $replyText, array $attachmentFiles = [])
    {
        $this->contactMessage = $contactMessage;
        $this->replyText = $replyText;
        $this->attachmentFiles = $attachmentFiles;
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Re: Your inquiry to DCMS - ' . substr($this->contactMessage->message, 0, 30) . '...',
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'emails.contact.reply_html',
        );
    }

    /**
     * Get the attachments for the message.
     *
     * @return array<int, \Illuminate\Mail\Mailables\Attachment>
     */
    public function attachments(): array
    {
        $attachments = [];
        foreach ($this->attachmentFiles as $file) {
            $attachments[] = \Illuminate\Mail\Mailables\Attachment::fromPath($file->getRealPath())
                ->as($file->getClientOriginalName())
                ->withMime($file->getMimeType());
        }
        return $attachments;
    }
}

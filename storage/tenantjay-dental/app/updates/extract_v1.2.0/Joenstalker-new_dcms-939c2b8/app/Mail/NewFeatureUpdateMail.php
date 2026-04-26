<?php

namespace App\Mail;

use App\Models\Feature;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class NewFeatureUpdateMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public Feature $feature,
        public Tenant $tenant,
        public User $user
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: "🚀 New Feature Available: {$this->feature->name}",
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'emails.features.update_announced',
        );
    }
}

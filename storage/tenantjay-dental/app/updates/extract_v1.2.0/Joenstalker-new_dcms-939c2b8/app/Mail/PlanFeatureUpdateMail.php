<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class PlanFeatureUpdateMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public \App\Models\SubscriptionPlan $plan,
        public array $features,
        public \App\Models\Tenant $tenant,
        public \App\Models\User $user,
        public bool $isAdvertisement = false
    ) {}

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        $featureCount = count($this->features);
        $subject = $this->isAdvertisement 
            ? "New Features available for '{$this->plan->name}'" 
            : "Update Available: {$featureCount} new features for '{$this->tenant->name}'";

        if ($featureCount === 1) {
            $feature = $this->features[0];
            $isRoadmap = in_array($feature->implementation_status, [
                \App\Models\Feature::STATUS_COMING_SOON,
                \App\Models\Feature::STATUS_IN_DEVELOPMENT
            ]);
            if ($isRoadmap) {
                $subject = "Roadmap Announcement: {$feature->name}";
            }
        }

        return new Envelope(
            subject: $subject,
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            markdown: 'emails.features.plan_update_digest',
        );
    }

    /**
     * Get the attachments for the message.
     *
     * @return array<int, Attachment>
     */
    public function attachments(): array
    {
        return [];
    }
}

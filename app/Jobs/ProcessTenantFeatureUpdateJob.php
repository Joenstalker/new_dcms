<?php

namespace App\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

use App\Models\Tenant;
use App\Models\Feature;
use App\Models\SubscriptionPlan;
use App\Models\TenantFeatureUpdate;
use App\Mail\PlanFeatureUpdateMail;
use Illuminate\Bus\Batchable;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Foundation\Bus\Dispatchable;

class ProcessTenantFeatureUpdateJob implements ShouldQueue
{
    use Batchable, Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     */
    public function __construct(
        protected Tenant $tenant,
        protected SubscriptionPlan $plan,
        protected array $features, // Array of Feature models or collections
        protected bool $isAdvertisement
    ) {}

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        if ($this->batch()?->cancelled()) {
            return;
        }

        try {
            // 1. If NOT an advertisement, create the technical update records
            if (!$this->isAdvertisement) {
                foreach ($this->features as $feature) {
                    TenantFeatureUpdate::updateOrCreate(
                        [
                            'tenant_id' => $this->tenant->id,
                            'feature_id' => $feature->id,
                        ],
                        [
                            'status' => TenantFeatureUpdate::STATUS_PENDING,
                            'batch_id' => $this->batch()?->id,
                        ]
                    );
                }
            }

            // 2. Find the administrator User for emailing
            $adminEmail = $this->tenant->email ?? null;
            if ($adminEmail) {
                $admin = \App\Models\User::where('email', $adminEmail)->first();
                if ($admin) {
                    Mail::to($admin->email)->send(new PlanFeatureUpdateMail(
                        $this->plan,
                        $this->features,
                        $this->tenant,
                        $admin,
                        $this->isAdvertisement
                    ));
                }
            }
        } catch (\Exception $e) {
            Log::error("Failed to process OTA update for tenant [{$this->tenant->id}]: " . $e->getMessage());
            
            // Mark individual failures if possible (though batch tracks it too)
            if (!$this->isAdvertisement) {
                foreach ($this->features as $feature) {
                    $update = TenantFeatureUpdate::where('tenant_id', $this->tenant->id)
                        ->where('feature_id', $feature->id)
                        ->first();
                    
                    if ($update) {
                        $update->update([
                            'status' => 'failed',
                            'failure_reason' => $e->getMessage()
                        ]);
                    }
                }
            }
            
            throw $e; // Re-throw to let the batch handle it
        }
    }
}

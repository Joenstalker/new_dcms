<?php

namespace App\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

use App\Models\SubscriptionPlan;
use App\Models\Tenant;
use App\Models\Subscription;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Log;
use Illuminate\Bus\Batchable;
use Illuminate\Bus\Queueable as BusQueueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue as ContractsQueueShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class PushPlanUpdatesJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     */
    public function __construct(
        protected SubscriptionPlan $plan
    ) {}

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        // 1. Get all features attached to the plan that haven't been pushed yet
        $features = $this->plan->features()->wherePivotNull('pushed_at')->get();
        if ($features->isEmpty()) {
            return;
        }

        $allTenants = Tenant::all();
        $jobs = [];

        foreach ($allTenants as $tenant) {
            // Determine if the tenant is subscribed to THIS specific plan
            $isSubscribedToPlan = Subscription::where('tenant_id', $tenant->id)
                ->where('subscription_plan_id', $this->plan->id)
                ->where('stripe_status', 'active')
                ->exists();

            $jobs[] = new ProcessTenantFeatureUpdateJob(
                $tenant instanceof Tenant ? $tenant : Tenant::find($tenant->id),
                $this->plan,
                $features->all(),
                !$isSubscribedToPlan // isAdvertisement
            );
        }

        // 2. Dispatch as a batch
        if (!empty($jobs)) {
            Bus::batch($jobs)
                ->name("OTA Sync: {$this->plan->name}")
                ->then(function ($batch) use ($features) {
                    // All jobs completed successfully...
                    foreach ($features as $feature) {
                        $this->plan->features()->updateExistingPivot($feature->id, ['pushed_at' => now()]);
                    }
                    Log::info("Batch [{$batch->id}] completed successfully for plan [{$this->plan->name}].");
                })
                ->catch(function ($batch, \Throwable $e) {
                    // First batch job failure detected...
                    Log::error("Batch [{$batch->id}] failed for plan [{$this->plan->name}]: " . $e->getMessage());
                })
                ->finally(function ($batch) {
                    // The batch has finished executing...
                })
                ->dispatch();
        }
    }
}

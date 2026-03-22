<?php

namespace App\Console\Commands\Feature;

use App\Models\Feature;
use App\Models\SubscriptionPlan;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class SyncLegacyFeatures extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'features:sync-legacy {--dry-run : Display changes without saving}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Sync feature values from legacy SubscriptionPlan columns to the dynamic features pivot table.';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Starting legacy feature synchronization...');

        $plans = SubscriptionPlan::all();
        $dryRun = $this->option('dry-run');

        if ($dryRun) {
            $this->warn('DRY RUN MODE: No changes will be saved to the database.');
        }

        $mappings = [
            'has_qr_booking' => 'qr_booking',
            'has_sms' => 'sms_notifications',
            'has_branding' => 'custom_branding',
            'has_analytics' => 'advanced_analytics',
            'has_priority_support' => 'priority_support',
            'has_multi_branch' => 'multi_branch',
            'max_users' => 'max_users',
            'max_patients' => 'max_patients',
            'max_appointments' => 'max_appointments',
            'report_level' => 'report_level',
            'max_storage_mb' => 'max_storage_mb',
        ];

        foreach ($plans as $plan) {
            $this->info("\nProcessing plan: {$plan->name}");
            
            $syncData = [];

            foreach ($mappings as $column => $featureKey) {
                $feature = Feature::where('key', $featureKey)->first();
                
                if (!$feature) {
                    $this->error("Feature key '{$featureKey}' not found in database. Skipping mapping for '{$column}'.");
                    continue;
                }

                $value = $plan->{$column};
                
                // Skip if column value is null and it's not a numeric feature (where null means unlimited)
                if ($value === null && $feature->type !== 'numeric' && $feature->type !== 'tiered') {
                    $this->line(" - Skipping '{$featureKey}': legacy column '{$column}' is null.");
                    continue;
                }

                $pivotData = match ($feature->type) {
                    'boolean' => ['value_boolean' => (bool)$value],
                    'numeric' => ['value_numeric' => $value], // Keep null for unlimited
                    'tiered' => ['value_tier' => $value],
                    default => [],
                };

                $syncData[$feature->id] = $pivotData;
                
                $displayValue = $value === null ? 'Unlimited (null)' : var_export($value, true);
                $this->line(" - Mapping '{$column}' ({$displayValue}) to feature '{$featureKey}'");
            }

            if (!$dryRun && !empty($syncData)) {
                $plan->features()->syncWithoutDetaching($syncData);
                $this->info("✓ Features synced for {$plan->name}");
            }
        }

        $this->info("\nLegacy feature synchronization completed!");
        
        return Command::SUCCESS;
    }
}

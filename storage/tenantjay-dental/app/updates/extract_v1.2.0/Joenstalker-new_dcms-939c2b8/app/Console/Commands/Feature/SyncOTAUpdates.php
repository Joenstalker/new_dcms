<?php

namespace App\Console\Commands\Feature;

use App\Models\Feature;
use App\Models\Tenant;
use App\Models\TenantFeatureUpdate;
use App\Services\FeatureOTAUpdateService;
use Illuminate\Console\Command;

class SyncOTAUpdates extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'features:sync-ota {--all : Sync all features, including inactive ones}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Sync existing features to tenant OTA update records';

    /**
     * Execute the console command.
     */
    public function handle(FeatureOTAUpdateService $otaService)
    {
        $features = Feature::when(!$this->option('all'), function ($query) {
            $query->where('is_active', true);
        })->get();

        $this->info("Syncing {$features->count()} features to eligible tenants...");

        $totalCreated = 0;
        foreach ($features as $feature) {
            $this->comment("Processing feature: {$feature->name}...");
            $count = $otaService->createUpdateRecordsForEligibleTenants($feature);
            $totalCreated += $count;
        }

        $this->info("DONE! Created {$totalCreated} new tenant update records.");
    }
}

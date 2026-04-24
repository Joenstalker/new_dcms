<?php

namespace App\Jobs;

use App\Services\AppVersionService;
use App\Services\FileUpdateService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Artisan;

class UpdateFilesJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $version;
    public $zipUrl;

    public $tries = 1; // Only try once as file system changes are destructive
    public $timeout = 600; // 10 minutes for download + composer install

    /**
     * Create a new job instance.
     */
    public function __construct(string $version, string $zipUrl)
    {
        $this->version = $version;
        $this->zipUrl = $zipUrl;
    }

    /**
     * Execute the job.
     */
    public function handle(FileUpdateService $fileUpdateService): void
    {
        Log::info("Starting UpdateFilesJob for version {$this->version}");

        try {
            // 1. Update Files
            $success = $fileUpdateService->updateFromZip($this->zipUrl, $this->version);
            
            if (!$success) {
                throw new \Exception("File update failed.");
            }

            // 2. Run Composer Install (if needed)
            // Note: This might be slow and needs composer in PATH
            Log::info("Running composer install...");
            shell_exec('composer install --no-interaction --prefer-dist --optimize-autoloader');

            // 3. Clear Caches
            Log::info("Clearing caches...");
            Artisan::call('optimize:clear');

            Log::info("UpdateFilesJob completed successfully for version {$this->version}");
            
            // 4. Optionally trigger DB migrations for all tenants
            // This can be done via the existing UpgradeTenantJob if needed,
            // but usually, the system:check-updates command handles this.
            
        } catch (\Exception $e) {
            Log::error("UpdateFilesJob failed: " . $e->getMessage());
            throw $e;
        }
    }
}

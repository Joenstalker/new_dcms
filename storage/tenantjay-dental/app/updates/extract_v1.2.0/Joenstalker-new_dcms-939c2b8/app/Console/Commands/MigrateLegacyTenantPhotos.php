<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Tenant;
use App\Models\Patient;
use Illuminate\Support\Facades\Storage;

class MigrateLegacyTenantPhotos extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'tenants:migrate-photos-base64';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Migrates legacy file-based patient photos to Base64 strings across all tenants.';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $tenants = Tenant::all();
        $this->info("Found {$tenants->count()} tenants. Starting legacy photo migration to Base64...");

        foreach ($tenants as $tenant) {
            $tenant->run(function() use ($tenant) {
                $patients = Patient::whereNotNull('photo_path')->get();
                
                $migrated = 0;
                $skippedBase64 = 0;
                $missingFiles = 0;

                foreach ($patients as $patient) {
                    if (str_starts_with($patient->photo_path, 'data:image')) {
                        $skippedBase64++;
                        continue;
                    }

                    // This is a legacy path. Try to read it from the tenant's exact active storage disk.
                    if (Storage::disk('public')->exists($patient->photo_path)) {
                        $fileContents = Storage::disk('public')->get($patient->photo_path);
                        $mimeType = Storage::disk('public')->mimeType($patient->photo_path);
                        
                        $base64Data = base64_encode($fileContents);
                        
                        $patient->update([
                            'photo_path' => 'data:' . $mimeType . ';base64,' . $base64Data
                        ]);
                        
                        // Clean up the old file since we moved it into the specific column directly
                        Storage::disk('public')->delete($patient->photo_path);
                        $migrated++;
                    } else {
                        // The file doesn't actually exist on disk anymore (403 or 404 territory).
                        $missingFiles++;
                        // Optionally nullify it so it doesn't break UI:
                        $patient->update(['photo_path' => null]);
                    }
                }

                if ($migrated > 0 || $missingFiles > 0) {
                    $this->line("Tenant [{$tenant->id}]: Migrated {$migrated} photos, Skipped {$skippedBase64} modern records, Nullified {$missingFiles} missing files.");
                }
            });
        }
        
        $this->info("Data migration completed successfully.");
    }
}

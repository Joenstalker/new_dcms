<?php

namespace App\Services;

use App\Models\SystemRelease;
use App\Services\AppVersionService;
use Illuminate\Support\Facades\Cache;

class ReleaseService
{
    /**
     * Get the code-level application version.
     */
    public function currentVersion(): string
    {
        return config('app_version.version', '1.0.0');
    }

    /**
     * Get the latest registered release from the database.
     */
    public function latestRelease(): ?SystemRelease
    {
        return Cache::remember('latest_system_release', 3600, function () {
            return SystemRelease::orderBy('released_at', 'desc')
                ->orderBy('id', 'desc')
                ->first();
        });
    }

    /**
     * Proactively sync the latest release from GitHub into the database.
     */
    public function syncLatestRelease(): ?SystemRelease
    {
        try {
            // Clear version cache to force a fresh check from GitHub
            AppVersionService::clearCache();
            
            $ghVersion = AppVersionService::getVersion();
            if (! empty($ghVersion)) {
                $release = SystemRelease::updateOrCreate(
                    ['version' => $ghVersion],
                    [
                        'release_notes' => 'Synced from GitHub',
                        'released_at' => now(),
                        'is_mandatory' => false,
                        'requires_db_update' => false
                    ]
                );

                Cache::forget('latest_system_release');
                return $release;
            }
        } catch (\Throwable $e) {
            Log::error('ReleaseService: Failed to sync latest release from GitHub: ' . $e->getMessage());
        }

        return $this->latestRelease();
    }

    /**
     * Register a version into the system_releases schema.
     */
    public function registerRelease(
        ?string $version = null,
        ?string $releaseNotes = null,
        bool $isMandatory = false,
        bool $requiresDbUpdate = false
        ): SystemRelease
    {
        $targetVersion = $version ?? $this->currentVersion();

        $release = SystemRelease::firstOrCreate(
        ['version' => $targetVersion],
        [
            'release_notes' => $releaseNotes,
            'released_at' => now(),
            'is_mandatory' => $isMandatory,
            'requires_db_update' => $requiresDbUpdate,
        ]
        );

        Cache::forget('latest_system_release');

        return $release;
    }
}

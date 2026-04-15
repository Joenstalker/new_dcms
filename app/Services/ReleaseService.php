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
            // We order by ID desc as a fallback in case multiple share the same released_at
            $release = SystemRelease::orderBy('released_at', 'desc')
                ->orderBy('id', 'desc')
                ->first();

            if (! $release) {
                // If no SystemRelease exists in DB, attempt to fetch the latest GitHub release
                try {
                    $ghVersion = AppVersionService::getVersion();
                    if (! empty($ghVersion)) {
                        $release = SystemRelease::firstOrCreate(
                            ['version' => $ghVersion],
                            ['release_notes' => null, 'released_at' => now(), 'is_mandatory' => false, 'requires_db_update' => false]
                        );
                    }
                } catch (\Throwable $e) {
                    // ignore and return null -- we still want the cached value to be null
                }
            }

            return $release;
        });
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

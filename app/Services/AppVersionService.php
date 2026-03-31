<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class AppVersionService
{
    /**
     * Get the current application version based on official GitHub Releases.
     */
    public static function getVersion(): string
    {
        return Cache::remember('app_version', 3600, function () {
            try {
                // Fetch the latest release from the public repository
                // We use a short timeout to ensure the app UI never hangs if GitHub is down
                $response = Http::timeout(3)->get('https://api.github.com/repos/Joenstalker/new_dcms/releases/latest');
                
                if ($response->successful()) {
                    $data = $response->json();
                    if (!empty($data['tag_name'])) {
                        return $data['tag_name'];
                    }
                }
            } catch (\Exception $e) {
                Log::error('Failed to get GitHub release version: ' . $e->getMessage());
            }

            // Graceful Degradation Fallback if the repo has 0 releases or GitHub is rate-limiting
            return 'v1.0.0'; 
        });
    }

    /**
     * Clear the cached version. Call this during deployments or post-updates.
     */
    public static function clearCache(): void
    {
        Cache::forget('app_version');
    }
}

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
        $cacheFile = storage_path('framework/cache/app_version.json');
        
        // Check if we have a valid cache file and it's less than 1 hour old
        if (file_exists($cacheFile) && (time() - filemtime($cacheFile) < 3600)) {
            $data = json_decode(file_get_contents($cacheFile), true);
            if (!empty($data['version'])) {
                return $data['version'];
            }
        }

        try {
            // Fetch the latest release from the public repository
            $response = Http::timeout(3)->get('https://api.github.com/repos/Joenstalker/new_dcms/releases/latest');
            
            if ($response->successful()) {
                $data = $response->json();
                if (!empty($data['tag_name'])) {
                    $version = $data['tag_name'];
                    // Save to local file cache
                    file_put_contents($cacheFile, json_encode(['version' => $version]));
                    return $version;
                }
            }
        } catch (\Exception $e) {
            Log::error('Failed to get GitHub release version: ' . $e->getMessage());
        }

        // Graceful Degradation Fallback
        return 'v1.0.0'; 
    }

    /**
     * Clear the cached version.
     */
    public static function clearCache(): void
    {
        $cacheFile = storage_path('framework/cache/app_version.json');
        if (file_exists($cacheFile)) {
            unlink($cacheFile);
        }
    }
}

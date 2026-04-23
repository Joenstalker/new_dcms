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
        $cacheFile = base_path('storage/framework/cache/app_version.json');

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

                    // Ensure the directory exists
                    if (!is_dir(dirname($cacheFile))) {
                        mkdir(dirname($cacheFile), 0755, true);
                    }

                    // Save to local file cache
                    file_put_contents($cacheFile, json_encode(['version' => $version]));
                    return $version;
                }
            }
        }
        catch (\Exception $e) {
            Log::error('Failed to get GitHub release version: ' . $e->getMessage());
        }

        // Graceful Degradation Fallback
        return 'v1.0.0';
    }

    /**
     * Get full release history from GitHub.
     */
    public static function getReleaseHistory(): array
    {
        return Cache::remember('github_release_history', 3600, function () {
            try {
                $response = Http::withHeaders([
                    'Accept' => 'application/vnd.github.v3+json',
                ])->timeout(5)->get('https://api.github.com/repos/Joenstalker/new_dcms/releases');

                if ($response->successful()) {
                    return $response->json();
                }
            }
            catch (\Exception $e) {
                Log::error('Failed to fetch GitHub release history: ' . $e->getMessage());
            }
            return [];
        });
    }

    /**
     * Get the download URL for a specific release (ZIP).
     */
    public static function getDownloadUrl(string $version): ?string
    {
        try {
            $response = Http::timeout(5)->get("https://api.github.com/repos/Joenstalker/new_dcms/releases/tags/{$version}");

            if ($response->successful()) {
                $data = $response->json();
                return $data['zipball_url'] ?? null;
            }
        } catch (\Exception $e) {
            Log::error("Failed to get download URL for version {$version}: " . $e->getMessage());
        }

        return null;
    }

    /**
     * Clear the cached version.
     */
    public static function clearCache(): void
    {
        Cache::forget('github_release_history');
        $cacheFile = base_path('storage/framework/cache/app_version.json');
        if (file_exists($cacheFile)) {
            unlink($cacheFile);
        }
    }
}

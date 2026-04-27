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

        // Check if we have a valid cache file and it's less than 1 minute old
        if (file_exists($cacheFile) && (time() - filemtime($cacheFile) < 60)) {
            $data = json_decode(file_get_contents($cacheFile), true);
            if (!empty($data['version'])) {
                return $data['version'];
            }
        }

        try {
            $gitHubService = app(GitHubService::class);
            $version = $gitHubService->getLatestReleaseTag();

            if ($version) {
                // Ensure the directory exists
                if (!is_dir(dirname($cacheFile))) {
                    mkdir(dirname($cacheFile), 0755, true);
                }

                // Save to local file cache
                file_put_contents($cacheFile, json_encode(['version' => $version]));
                return $version;
            }
        }
        catch (\Exception $e) {
            Log::error('Failed to get GitHub release version via GitHubService: ' . $e->getMessage());
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
                $token = config('services.github.token');
                $repo = config('services.github.repo');

                $request = Http::withHeaders([
                    'Accept' => 'application/vnd.github.v3+json',
                    'User-Agent' => 'Laravel-OTA-Check',
                ]);

                if (! empty($token)) {
                    $request->withToken($token);
                }

                $response = $request->timeout(10)
                    ->get("https://api.github.com/repos/{$repo}/releases");

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
            $token = config('services.github.token');
            $repo = config('services.github.repo');

            $request = Http::withHeaders([
                'Accept' => 'application/vnd.github.v3+json',
                'User-Agent' => 'Laravel-OTA-Check',
            ]);

            if (! empty($token)) {
                $request->withToken($token);
            }

            $response = $request->timeout(10)
                ->get("https://api.github.com/repos/{$repo}/releases/tags/{$version}");

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

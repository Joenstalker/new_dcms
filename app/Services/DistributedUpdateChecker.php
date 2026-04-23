<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use App\Models\SystemRelease;

class DistributedUpdateChecker
{
    /**
     * Check for updates and return structured info.
     *
     * @return array
     */
    public function check(): array
    {
        $currentVersion = $this->getLocalVersion();

        $latestRelease = $this->fetchLatestFromGitHub();

        if ($latestRelease) {
            $this->cacheRelease($latestRelease);

            $latestVersion = $latestRelease['tag_name'] ?? null;
            $isNewer = $latestVersion ? $this->isNewerVersion($latestVersion, $currentVersion) : false;

            return [
                'hasUpdate' => $isNewer,
                'current' => $currentVersion,
                'latest' => $latestVersion,
                'release' => $latestRelease,
                'fromCache' => false,
            ];
        }

        return $this->fallbackCheck($currentVersion);
    }

    private function getLocalVersion(): string
    {
        if ($v = config('app.version')) {
            return (string) $v;
        }

        $installed = SystemRelease::whereNotNull('installed_at')
            ->orderBy('installed_at', 'desc')
            ->value('version');

        if ($installed) {
            return (string) $installed;
        }

        if ($cached = Cache::get('app.version')) {
            return (string) $cached;
        }

        return 'v1.0.0';
    }

    private function fetchLatestFromGitHub(): ?array
    {
        try {
            $response = Http::withHeaders([
                'Accept' => 'application/vnd.github.v3+json',
                'User-Agent' => 'DCMS-UpdateChecker/1.0',
            ])->timeout(5)->get('https://api.github.com/repos/Joenstalker/new_dcms/releases/latest');

            if ($response->successful()) {
                return $response->json();
            }

            Log::warning('GitHub API returned non-success', ['status' => $response->status()]);
            return null;
        } catch (\Exception $e) {
            Log::warning('Failed to fetch GitHub release', ['error' => $e->getMessage()]);
            return null;
        }
    }

    private function cacheRelease(array $release): void
    {
        Cache::put('github_latest_release', $release, 300);

        $dir = storage_path('framework/cache/releases');
        if (! is_dir($dir)) {
            @mkdir($dir, 0755, true);
        }

        $file = $dir . '/latest.json';
        @file_put_contents($file, json_encode([
            'release' => $release,
            'cached_at' => now()->toIso8601String(),
        ]));

        if (! empty($release['tag_name'])) {
            Cache::put('github_latest_version', $release['tag_name'], 3600);
        }
    }

    private function isNewerVersion(string $latest, string $current): bool
    {
        $latestClean = ltrim($latest, 'vV');
        $currentClean = ltrim($current, 'vV');

        return version_compare($latestClean, $currentClean, '>');
    }

    private function fallbackCheck(string $currentVersion): array
    {
        if ($cached = Cache::get('github_latest_release')) {
            $latest = $cached['tag_name'] ?? ($cached[0]['tag_name'] ?? null);
            return [
                'hasUpdate' => $latest ? $this->isNewerVersion($latest, $currentVersion) : false,
                'current' => $currentVersion,
                'latest' => $latest,
                'release' => $cached,
                'fromCache' => true,
            ];
        }

        $file = storage_path('framework/cache/releases/latest.json');
        if (file_exists($file) && (time() - filemtime($file) < 3600)) {
            $data = @json_decode(file_get_contents($file), true);
            $latest = $data['release']['tag_name'] ?? null;
            return [
                'hasUpdate' => $latest ? $this->isNewerVersion($latest, $currentVersion) : false,
                'current' => $currentVersion,
                'latest' => $latest,
                'release' => $data['release'] ?? null,
                'fromCache' => true,
            ];
        }

        return [
            'hasUpdate' => false,
            'current' => $currentVersion,
            'latest' => null,
            'release' => null,
            'fromCache' => false,
            'error' => 'Unable to check for updates (no network, no cache)',
        ];
    }
}

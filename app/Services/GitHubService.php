<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class GitHubService
{
    /**
     * Fetch the latest release tag from GitHub.
     *
     * @return string|null
     */
    public function getLatestReleaseTag(): ?string
    {
        $token = config('services.github.token');
        $repo = config('services.github.repo');

        if (!$repo) {
            Log::warning('GitHubService: GITHUB_REPO not configured.');
            return null;
        }

        try {
            $request = Http::withHeaders([
                'Accept' => 'application/vnd.github.v3+json',
                'User-Agent' => 'Laravel-OTA-Check',
            ]);

            if ($token) {
                $request = $request->withToken($token);
            }

            $response = $request->timeout(10)
                ->get("https://api.github.com/repos/{$repo}/releases/latest");

            if ($response->successful()) {
                return $response->json('tag_name');
            }

            Log::error('GitHubService: Failed to fetch latest release.', [
                'status' => $response->status(),
                'body' => $response->body(),
            ]);
        } catch (\Exception $e) {
            Log::error('GitHubService: Exception during API call.', [
                'message' => $e->getMessage(),
            ]);
        }

        return null;
    }
}

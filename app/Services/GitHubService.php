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
            $buildRequest = static function (?string $authToken = null) {
                $request = Http::withHeaders([
                    'Accept' => 'application/vnd.github.v3+json',
                    'User-Agent' => 'Laravel-OTA-Check',
                ])->timeout(10);

                return ! empty($authToken) ? $request->withToken($authToken) : $request;
            };

            $response = $buildRequest($token)
                ->get("https://api.github.com/repos/{$repo}/releases/latest");

            if ($response->successful()) {
                $tag = $response->json('tag_name');
                if (! empty($tag)) {
                    return $tag;
                }
            }

            // Fallback without token for cases where token visibility is restricted.
            $fallback = $buildRequest()
                ->get("https://api.github.com/repos/{$repo}/releases/latest");
            if ($fallback->successful()) {
                $tag = $fallback->json('tag_name');
                if (! empty($tag)) {
                    return $tag;
                }
            }

            Log::error('GitHubService: Failed to fetch latest release.', [
                'status' => $response->status(),
                'body' => $response->body(),
                'fallback_status' => $fallback->status(),
                'fallback_body' => $fallback->body(),
            ]);
        } catch (\Exception $e) {
            Log::error('GitHubService: Exception during API call.', [
                'message' => $e->getMessage(),
            ]);
        }

        return null;
    }
}

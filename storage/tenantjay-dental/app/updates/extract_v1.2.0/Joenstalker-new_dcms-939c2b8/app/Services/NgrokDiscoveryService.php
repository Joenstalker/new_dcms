<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use App\Models\SystemSetting;

class NgrokDiscoveryService
{
    /**
     * Automatically discover the Central (Laptop A) ngrok URL.
     */
    public function discoverCentralUrl(): ?string
    {
        $apiKey = config('services.ngrok.api_key');
        
        if (!$apiKey) {
            Log::warning('Ngrok Auto-Discovery: No API key found in config/env.');
            return null;
        }

        try {
            // Fetch all active tunnels for this ngrok account
            $response = Http::withToken($apiKey)
                ->get('https://api.ngrok.com/tunnels');

            if (!$response->successful()) {
                Log::error('Ngrok API Request Failed: ' . $response->body());
                return null;
            }

            $tunnels = $response->json()['tunnels'] ?? [];

            foreach ($tunnels as $tunnel) {
                $url = $tunnel['public_url'] ?? null;
                
                if (!$url) continue;

                // 1. Skip if this is actually Laptop B's own URL
                // We compare the tunnel's host with our current request host
                $tunnelHost = parse_url($url, PHP_URL_HOST);
                $currentHost = request()->getHost();

                if ($tunnelHost === $currentHost) {
                    continue;
                }

                // Also skip if it matches our local APP_URL (if set to ngrok)
                $myAppUrl = rtrim(config('app.url'), '/');
                if (stripos($url, $myAppUrl) !== false && $myAppUrl !== 'http://localhost:8080') {
                    continue;
                }

                // 2. Identify Central:
                // - Match by name 'central' (if configured)
                // - OR simply pick the OTHER tunnel on the account (Demo fallback)
                if ($this->isCentralTunnel($tunnel) || count($tunnels) >= 2) {
                    Log::info("Ngrok Auto-Discovery: Identified Central at {$url}");
                    
                    // Save it so we don't have to call the API every single time
                    SystemSetting::set('central_api_url', $url);
                    
                    return $url;
                }
            }
        } catch (\Exception $e) {
            Log::error('Ngrok Auto-Discovery Error: ' . $e->getMessage());
        }

        return null;
    }

    /**
     * Logic to determine if a tunnel belongs to "Central".
     * In a demo, Laptop A usually starts the tunnel first or has a specific name.
     */
    protected function isCentralTunnel(array $tunnel): bool
    {
        // Option 1: Match by a specific tunnel name if you configured one
        if (($tunnel['name'] ?? '') === 'central') {
            return true;
        }

        // Option 2: Match by metadata (if Laptop A adds metadata)
        if (str_contains($tunnel['metadata'] ?? '', 'role:central')) {
            return true;
        }

        // Option 3: Fallback - If there's only one other tunnel on the account, it's probably Central
        // But for safety, we usually rely on names.
        return false;
    }
}

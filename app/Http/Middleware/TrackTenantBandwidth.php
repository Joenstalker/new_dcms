<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use App\Models\Tenant;
use Illuminate\Support\Facades\Log;

class TrackTenantBandwidth
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        // Only track if we have a resolved tenant
        $tenantId = tenant('id');
        if (!$tenantId) {
            return $response;
        }

        try {
            // 1. Calculate Request Size (Body + approximate headers)
            $requestSize = strlen($request->getContent());
            foreach ($request->headers->all() as $name => $values) {
                $requestSize += strlen($name) + strlen(implode('', $values));
            }

            // 2. Calculate Response Size (Body + approximate headers)
            // Note: getContent() might be heavy for large files/downloads,
            // but for standard web/API responses it's fine.
            $responseSize = 0;
            if (method_exists($response, 'getContent')) {
                $responseSize = strlen($response->getContent());
            }
            
            foreach ($response->headers->all() as $name => $values) {
                $responseSize += strlen($name) + strlen(implode('', $values));
            }

            $totalBytes = $requestSize + $responseSize;

            if ($totalBytes > 0) {
                // Low-level DB increment on the central database 'tenants' table
                // This bypasses model events for maximum performance as a background update
                Tenant::where('id', $tenantId)->increment('bandwidth_used_bytes', $totalBytes);
            }
        } catch (\Exception $e) {
            // Never break the app for a tracking failure
            Log::warning("Bandwidth tracking failed for tenant {$tenantId}: " . $e->getMessage());
        }

        return $response;
    }
}

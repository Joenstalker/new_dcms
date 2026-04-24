<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class TrackTenantRequests
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        $tenantId = tenant('id');
        if (!$tenantId || $this->shouldExclude($request)) {
            return $response;
        }

        try {
            $date = now()->toDateString();
            $isApiRequest = $request->is('api/*') || $request->is('*/api/*');
            $isPublicRequest = !auth()->check();

            DB::connection($this->metricsConnection())
                ->table('tenant_usage_metrics')
                ->upsert(
                    [[
                        'tenant_id' => (string) $tenantId,
                        'date' => $date,
                        'bandwidth_bytes' => 0,
                        'request_count' => 1,
                        'api_request_count' => $isApiRequest ? 1 : 0,
                        'public_request_count' => $isPublicRequest ? 1 : 0,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]],
                    ['tenant_id', 'date'],
                    [
                        'request_count' => DB::raw('request_count + 1'),
                        'api_request_count' => DB::raw('api_request_count + ' . ($isApiRequest ? '1' : '0')),
                        'public_request_count' => DB::raw('public_request_count + ' . ($isPublicRequest ? '1' : '0')),
                        'updated_at' => now(),
                    ]
                );
        } catch (\Throwable $e) {
            Log::warning("Request tracking failed for tenant {$tenantId}: " . $e->getMessage());
        }

        return $response;
    }

    private function shouldExclude(Request $request): bool
    {
        $path = ltrim($request->path(), '/');

        if ($request->path() === 'up') {
            return true;
        }

        return $request->is([
            'up',
            'build/*',
            'storage/*',
            'tenant-storage/*',
            'settings/branding/logo/*',
            '*.css',
            '*.js',
            '*.map',
            '*.png',
            '*.jpg',
            '*.jpeg',
            '*.gif',
            '*.webp',
            '*.svg',
            '*.ico',
            '*.woff',
            '*.woff2',
            '*.ttf',
        ]) || str_starts_with($path, '_debugbar/');
    }

    private function metricsConnection(): string
    {
        if (app()->runningUnitTests()) {
            return config('database.default');
        }

        return 'central';
    }
}
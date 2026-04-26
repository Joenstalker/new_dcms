<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class TenantStorageController extends Controller
{
    /**
     * Serve a file from the current tenant's storage directory.
     *
     * This controller is called within the tenant context
     * (InitializeTenancyBySubdomain has already run), so
     * storage_path() and the 'public' disk both point to
     * the current tenant's isolated directory.
     *
     * @param  string  $path  Relative path within the tenant's public storage
     */
    public function serve(string $path)
    {
        $disk = Storage::disk('public');

        // Debug: log what we're trying to serve
        Log::debug('TenantStorageController::serve', [
            'path' => $path,
            'tenant_id' => tenant()?->getTenantKey(),
            'disk_root' => $disk->path(''),
            'exists' => $disk->exists($path),
        ]);

        if (!$disk->exists($path)) {
            Log::warning('TenantStorageController: file not found', ['path' => $path, 'full' => $disk->path($path)]);
            abort(404);
        }

        // Prevent directory traversal — normalize the path and verify it stays within root
        $normalizedPath = str_replace(['\\', '..'], ['/', ''], $path);
        $realPath = realpath($disk->path($normalizedPath));
        $rootPath = realpath($disk->path(''));

        if ($realPath === false || $rootPath === false || !str_starts_with($realPath, $rootPath)) {
            Log::warning('TenantStorageController: traversal blocked', [
                'realPath' => $realPath,
                'rootPath' => $rootPath,
                'path' => $path,
            ]);
            abort(403);
        }

        $contents = $disk->get($path);
        $mimeType = $disk->mimeType($path) ?: 'application/octet-stream';

        return response($contents, 200, [
            'Content-Type' => $mimeType,
            'Cache-Control' => 'public, max-age=604800, immutable',
        ]);
    }
}

<?php

namespace App\Services;

use App\Models\TenantFileObject;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class TenantStorageUsageService
{
    public function recordPut(string $disk, string $path, ?int $bytes = null, ?string $tenantId = null): void
    {
        $tenantId = $tenantId ?: (string) (tenant()?->getTenantKey() ?? '');
        if ($tenantId === '' || $path === '') {
            return;
        }

        $bytes = $bytes ?? $this->safeSize($disk, $path);
        if ($bytes === null) {
            return;
        }

        try {
            $existing = TenantFileObject::query()
                ->where('tenant_id', $tenantId)
                ->where('disk', $disk)
                ->where('path', $path)
                ->first();

            $previousBytes = $existing ? (int) $existing->bytes : 0;
            $delta = (int) $bytes - $previousBytes;

            TenantFileObject::query()->updateOrCreate(
                [
                    'tenant_id' => $tenantId,
                    'disk' => $disk,
                    'path' => $path,
                ],
                [
                    'bytes' => (int) $bytes,
                ]
            );

            if ($delta !== 0) {
                $this->adjustTenantStorageBytes($tenantId, $delta);
            }
        } catch (\Throwable $e) {
            Log::warning('Failed to record tenant file put', [
                'tenant_id' => $tenantId,
                'disk' => $disk,
                'path' => $path,
                'bytes' => $bytes,
                'error' => $e->getMessage(),
            ]);
        }
    }

    public function recordDelete(string $disk, string $path, ?string $tenantId = null): void
    {
        $tenantId = $tenantId ?: (string) (tenant()?->getTenantKey() ?? '');
        if ($tenantId === '' || $path === '') {
            return;
        }

        try {
            $existing = TenantFileObject::query()
                ->where('tenant_id', $tenantId)
                ->where('disk', $disk)
                ->where('path', $path)
                ->first();

            $bytes = null;
            if ($existing) {
                $bytes = (int) $existing->bytes;
                $existing->delete();
            } else {
                $bytes = $this->safeSize($disk, $path);
            }

            if ($bytes !== null && $bytes > 0) {
                $this->adjustTenantStorageBytes($tenantId, -$bytes);
            }
        } catch (\Throwable $e) {
            Log::warning('Failed to record tenant file delete', [
                'tenant_id' => $tenantId,
                'disk' => $disk,
                'path' => $path,
                'error' => $e->getMessage(),
            ]);
        }
    }

    public function reconcileTenantDisk(string $tenantId, string $disk, ?string $prefix = null): int
    {
        $tenantId = (string) $tenantId;
        if ($tenantId === '') {
            return 0;
        }

        $prefix = $prefix !== null ? trim($prefix, '/') : null;

        $files = [];
        try {
            $diskFs = Storage::disk($disk);
            $files = $prefix ? $diskFs->allFiles($prefix) : $diskFs->allFiles();
        } catch (\Throwable $e) {
            Log::warning('Failed to list files for reconciliation', [
                'tenant_id' => $tenantId,
                'disk' => $disk,
                'prefix' => $prefix,
                'error' => $e->getMessage(),
            ]);
        }

        $seen = [];
        $sum = 0;

        foreach ($files as $path) {
            $path = (string) $path;
            $size = $this->safeSize($disk, $path);
            if ($size === null) {
                continue;
            }

            $seen[$path] = true;
            $sum += $size;

            TenantFileObject::query()->updateOrCreate(
                [
                    'tenant_id' => $tenantId,
                    'disk' => $disk,
                    'path' => $path,
                ],
                [
                    'bytes' => (int) $size,
                ]
            );
        }

        // Remove ledger entries that are no longer present (optionally scoped to prefix)
        $staleQuery = TenantFileObject::query()
            ->where('tenant_id', $tenantId)
            ->where('disk', $disk);

        if ($prefix) {
            $staleQuery->where('path', 'like', $prefix.'/%');
        }

        $stale = $staleQuery->pluck('path')->all();
        foreach ($stale as $path) {
            if (! isset($seen[$path])) {
                TenantFileObject::query()
                    ->where('tenant_id', $tenantId)
                    ->where('disk', $disk)
                    ->where('path', $path)
                    ->delete();
            }
        }

        // Authoritative reset of storage_used_bytes for this tenant (file bytes only)
        $authoritative = (int) TenantFileObject::query()
            ->where('tenant_id', $tenantId)
            ->sum('bytes');

        DB::connection($this->centralConnection())
            ->table('tenants')
            ->where('id', $tenantId)
            ->update([
                'storage_used_bytes' => $authoritative,
                'last_storage_reconciled_at' => now(),
            ]);

        return $authoritative;
    }

    private function safeSize(string $disk, string $path): ?int
    {
        try {
            $fs = Storage::disk($disk);
            if (! $fs->exists($path)) {
                return null;
            }

            $size = $fs->size($path);
            if (! is_int($size)) {
                return null;
            }

            return max(0, $size);
        } catch (\Throwable) {
            return null;
        }
    }

    private function centralConnection(): string
    {
        if (app()->runningUnitTests()) {
            return config('database.default');
        }

        return 'central';
    }

    private function adjustTenantStorageBytes(string $tenantId, int $deltaBytes): void
    {
        if ($deltaBytes === 0) {
            return;
        }

        try {
            $current = (int) DB::connection($this->centralConnection())
                ->table('tenants')
                ->where('id', $tenantId)
                ->value('storage_used_bytes');

            $next = max(0, $current + $deltaBytes);

            DB::connection($this->centralConnection())
                ->table('tenants')
                ->where('id', $tenantId)
                ->update(['storage_used_bytes' => $next]);
        } catch (\Throwable $e) {
            Log::warning('Failed to adjust tenant storage bytes', [
                'tenant_id' => $tenantId,
                'delta_bytes' => $deltaBytes,
                'error' => $e->getMessage(),
            ]);
        }
    }
}


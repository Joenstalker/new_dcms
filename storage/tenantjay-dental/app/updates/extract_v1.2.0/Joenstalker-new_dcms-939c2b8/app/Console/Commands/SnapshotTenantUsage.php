<?php

namespace App\Console\Commands;

use App\Models\Tenant;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Collection;

class SnapshotTenantUsage extends Command
{
    protected $signature = 'tenants:snapshot-usage {--tenant_id= : Limit to a single tenant ID} {--date= : Snapshot date (YYYY-MM-DD), defaults to today} {--chunk=100 : Tenants per chunk}';

    protected $description = 'Upserts daily tenant usage snapshots (file/db/total bytes) into tenant_usage_metrics.';

    public function handle(): int
    {
        $tenantId = (string) ($this->option('tenant_id') ?? '');
        $date = (string) ($this->option('date') ?: now()->toDateString());
        $chunk = max(1, (int) $this->option('chunk'));

        $query = Tenant::query();
        if ($tenantId !== '') {
            $query->where('id', $tenantId);
        }

        $total = $query->count();
        $this->info("Snapshotting storage usage for {$total} tenant(s) on {$date}...");

        $processed = 0;

        $query->orderBy('id')->chunk($chunk, function (Collection $tenants) use ($date, &$processed) {
            $rows = [];
            $now = now();

            foreach ($tenants as $tenant) {
                /** @var Tenant $tenant */
                try {
                    $file = (int) ($tenant->storage_used_bytes ?? 0);
                    $db = (int) ($tenant->db_used_bytes ?? 0);
                    $totalBytes = max(0, $file + $db);

                    $rows[] = [
                        'tenant_id' => (string) $tenant->id,
                        'date' => $date,
                        'bandwidth_bytes' => 0,
                        'request_count' => 0,
                        'api_request_count' => 0,
                        'public_request_count' => 0,
                        'file_used_bytes' => max(0, $file),
                        'db_used_bytes' => max(0, $db),
                        'total_used_bytes' => $totalBytes,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ];
                } catch (\Throwable $e) {
                    Log::warning('Failed to build tenant usage snapshot row', [
                        'tenant_id' => (string) $tenant->id,
                        'error' => $e->getMessage(),
                    ]);
                }
            }

            if (! empty($rows)) {
                DB::connection($this->centralConnection())
                    ->table('tenant_usage_metrics')
                    ->upsert(
                        $rows,
                        ['tenant_id', 'date'],
                        ['file_used_bytes', 'db_used_bytes', 'total_used_bytes', 'updated_at']
                    );

                $processed += count($rows);
            }
        });

        $this->info("Done. Upserted {$processed} snapshot row(s).");

        return Command::SUCCESS;
    }

    private function centralConnection(): string
    {
        if (app()->runningUnitTests()) {
            return config('database.default');
        }

        return 'central';
    }
}


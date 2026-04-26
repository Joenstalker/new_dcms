<?php

namespace App\Console\Commands;

use App\Models\Tenant;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Collection;

class MeasureTenantDbUsage extends Command
{
    protected $signature = 'tenants:measure-db-usage {--tenant_id= : Limit to a single tenant ID} {--chunk=50 : Tenants per chunk}';

    protected $description = 'Measures each tenant database size (bytes) and stores it on central tenants.db_used_bytes.';

    public function handle(): int
    {
        $tenantId = (string) ($this->option('tenant_id') ?? '');
        $chunk = max(1, (int) $this->option('chunk'));

        $query = Tenant::query();
        if ($tenantId !== '') {
            $query->where('id', $tenantId);
        }

        $total = $query->count();
        $this->info("Measuring DB usage for {$total} tenant(s)...");

        $processed = 0;

        $query->orderBy('id')->chunk($chunk, function (Collection $tenants) use (&$processed) {
            foreach ($tenants as $tenant) {
                /** @var Tenant $tenant */
                try {
                    $dbName = (string) ($tenant->database_name ?? $tenant->getDatabaseName());
                    if ($dbName === '') {
                        continue;
                    }

                    $bytes = $this->measureMySqlDatabaseBytes($dbName);

                    DB::connection($this->centralConnection())
                        ->table('tenants')
                        ->where('id', (string) $tenant->id)
                        ->update([
                            'db_used_bytes' => max(0, (int) $bytes),
                            'last_db_measured_at' => now(),
                        ]);

                    $processed++;
                } catch (\Throwable $e) {
                    Log::warning('Failed to measure tenant DB usage', [
                        'tenant_id' => (string) $tenant->id,
                        'error' => $e->getMessage(),
                    ]);
                }
            }
        });

        $this->info("Done. Updated {$processed} tenant(s).");

        return Command::SUCCESS;
    }

    private function measureMySqlDatabaseBytes(string $dbName): int
    {
        $result = DB::connection($this->centralConnection())
            ->select(
                'SELECT COALESCE(SUM(data_length + index_length), 0) AS size_bytes FROM information_schema.TABLES WHERE table_schema = ?',
                [$dbName]
            );

        return (int) ($result[0]->size_bytes ?? 0);
    }

    private function centralConnection(): string
    {
        if (app()->runningUnitTests()) {
            return config('database.default');
        }

        return 'central';
    }
}


<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Config;

class DistributedUpdateExecutor
{
    protected BackupService $backupService;

    public function __construct(BackupService $backupService)
    {
        $this->backupService = $backupService;
    }

    /**
     * Execute an update to a given tag. Returns array with status and logs.
     */
    public function executeUpdate(string $tag, array $release, string $executedBy = 'system'): array
    {
        $central = DB::connection('central');

        $historyId = $central->table('update_history')->insertGetId([
            'environment_id' => $this->getEnvironmentId(),
            'from_version' => $this->getCurrentVersion(),
            'to_version' => $tag,
            'status' => 'pending',
            'executed_by' => $executedBy,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $log = [];

        // 1) create backup
        $backup = $this->backupService->createBackup("pre_update_{$tag}");
        $central->table('update_history')->where('id', $historyId)->update(['backup_id' => $backup['id'] ?? null, 'status' => 'in_progress', 'updated_at' => now()]);

        try {
            // Fetch tags
            $this->runCmd('git fetch --all --tags', $log);

            // Checkout tag into a new branch
            $branch = 'update-' . preg_replace('/[^A-Za-z0-9_\-]/', '_', $tag) . '-' . now()->format('YmdHis');
            $this->runCmd("git checkout -b {$branch} {$tag}", $log);

            // Composer install
            if (file_exists(base_path('composer.json'))) {
                $this->runCmd('composer install --no-dev --optimize-autoloader --no-interaction', $log);
            }

            // Run migrations
            $this->runCmd('php artisan migrate --force', $log);

            // Optional frontend build
            if (file_exists(base_path('package.json'))) {
                $this->runCmd('npm ci --no-audit --no-fund', $log);
                $this->runCmd('npm run build --silent', $log);
            }

            // Mark system_releases installed
            $central->table('system_releases')->where('version', $tag)->update([
                'installed_at' => now(),
                'environment_id' => $this->getEnvironmentId(),
                'backup_id' => $backup['id'] ?? null,
            ]);

            $central->table('update_history')->where('id', $historyId)->update(['status' => 'completed', 'log_output' => implode("\n", $log), 'duration_seconds' => 0, 'updated_at' => now()]);

            return ['ok' => true, 'history_id' => $historyId, 'logs' => $log];
        } catch (\Throwable $e) {
            Log::error('DistributedUpdateExecutor: update failed', ['error' => $e->getMessage()]);
            $central->table('update_history')->where('id', $historyId)->update(['status' => 'failed', 'error_message' => $e->getMessage(), 'log_output' => implode("\n", $log), 'updated_at' => now()]);

            return ['ok' => false, 'history_id' => $historyId, 'error' => $e->getMessage(), 'logs' => $log];
        }
    }

    private function getEnvironmentId(): string
    {
        return (Config::get('app.env') ?: 'production') . '@' . gethostname();
    }

    private function getCurrentVersion(): string
    {
        return Config::get('app.version') ?: 'v1.0.0';
    }

    private function runCmd(string $cmd, array &$log): void
    {
        $log[] = "$ " . $cmd;
        @exec($cmd . ' 2>&1', $out, $rc);
        if (! empty($out)) {
            $log = array_merge($log, $out);
        }
        if ($rc !== 0) {
            throw new \RuntimeException("Command failed: {$cmd}");
        }
    }
}

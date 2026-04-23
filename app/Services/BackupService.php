<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Config;

class BackupService
{
    public function createBackup(string $label = null): array
    {
        $id = uniqid('backup_', true);
        $timestamp = now()->format('YmdHis');
        $dir = storage_path("app/backups/{$id}_{$timestamp}");

        if (! is_dir($dir)) {
            @mkdir($dir, 0755, true);
        }

        $meta = [
            'id' => $id,
            'label' => $label,
            'created_at' => now()->toDateTimeString(),
            'path' => $dir,
            'artifacts' => [],
        ];

        // 1) Dump SQLite DB by copying file
        try {
            $default = Config::get('database.default');
            $conn = Config::get("database.connections.{$default}");

            if ($default === 'sqlite' || ($conn['driver'] ?? null) === 'sqlite') {
                $dbPath = $conn['database'] ?? database_path('database.sqlite');
                if (file_exists($dbPath)) {
                    $target = $dir . DIRECTORY_SEPARATOR . 'db.sqlite';
                    copy($dbPath, $target);
                    $meta['artifacts']['sqlite'] = basename($target);
                }
            } else {
                // Try mysqldump/pg_dump if available
                if (($conn['driver'] ?? null) === 'mysql') {
                    $user = $conn['username'] ?? '';
                    $pass = $conn['password'] ?? '';
                    $host = $conn['host'] ?? '127.0.0.1';
                    $port = $conn['port'] ?? 3306;
                    $database = $conn['database'] ?? '';
                    $outFile = $dir . DIRECTORY_SEPARATOR . 'dump.sql';
                    $cmd = "mysqldump -h {$host} -P {$port} -u {$user} " . ($pass !== '' ? "-p'{$pass}'" : '') . " {$database} > " . escapeshellarg($outFile);
                    @exec($cmd, $o, $r);
                    if ($r === 0 && file_exists($outFile)) {
                        $meta['artifacts']['mysqldump'] = basename($outFile);
                    }
                }
                if (($conn['driver'] ?? null) === 'pgsql') {
                    $outFile = $dir . DIRECTORY_SEPARATOR . 'dump.pgsql';
                    $cmd = "pg_dump --file=" . escapeshellarg($outFile);
                    @exec($cmd, $o, $r);
                    if ($r === 0 && file_exists($outFile)) {
                        $meta['artifacts']['pg_dump'] = basename($outFile);
                    }
                }
            }
        } catch (\Throwable $e) {
            Log::warning('BackupService: DB backup failed', ['error' => $e->getMessage()]);
            $meta['db_error'] = $e->getMessage();
        }

        // 2) Archive current code (git archive)
        try {
            $archive = $dir . DIRECTORY_SEPARATOR . 'code.tar';
            @exec("git archive --format=tar --output=" . escapeshellarg($archive) . " HEAD", $out, $rc);
            if ($rc === 0 && file_exists($archive)) {
                $meta['artifacts']['code_archive'] = basename($archive);
            }
        } catch (\Throwable $e) {
            Log::warning('BackupService: code archive failed', ['error' => $e->getMessage()]);
        }

        // 3) Save metadata
        @file_put_contents($dir . DIRECTORY_SEPARATOR . 'meta.json', json_encode($meta, JSON_PRETTY_PRINT));

        return $meta;
    }

    public function restoreBackup(string $backupPath): bool
    {
        if (! is_dir($backupPath)) {
            return false;
        }

        $metaFile = $backupPath . DIRECTORY_SEPARATOR . 'meta.json';
        if (! file_exists($metaFile)) {
            return false;
        }

        $meta = json_decode(file_get_contents($metaFile), true);

        // Restore SQLite if present
        try {
            if (! empty($meta['artifacts']['sqlite'])) {
                $default = Config::get('database.default');
                $conn = Config::get("database.connections.{$default}");
                $dbPath = $conn['database'] ?? database_path('database.sqlite');
                copy($backupPath . DIRECTORY_SEPARATOR . $meta['artifacts']['sqlite'], $dbPath);
            }
        } catch (\Throwable $e) {
            Log::warning('BackupService: restore sqlite failed', ['error' => $e->getMessage()]);
            return false;
        }

        // Restore code archive (not automatic) — leave instruction in logs
        Log::info('BackupService: restore completed (DB restored if applicable). Please extract code archive manually if needed.', ['path' => $backupPath]);

        return true;
    }
}

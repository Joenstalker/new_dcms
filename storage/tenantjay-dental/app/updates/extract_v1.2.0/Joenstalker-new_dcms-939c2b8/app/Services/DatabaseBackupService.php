<?php

namespace App\Services;

use App\Models\Tenant;
use Illuminate\Support\Facades\Log;
use Spatie\DbDumper\Databases\MySql;
use ZipArchive;

class DatabaseBackupService
{
    /**
     * Backup all databases (central + tenants)
     */
    public function backupAllDatabases(): array
    {
        $timestamp = now()->format('Y-m-d_H-i-s');
        $backupDir = storage_path("app/backups/{$timestamp}");
        $zipFile = storage_path("app/backups/{$timestamp}.zip");

        // Create backup directory
        if (! is_dir($backupDir)) {
            mkdir($backupDir, 0755, true);
        }

        $backups = [];

        try {
            // Backup central database
            $centralBackup = $this->backupCentralDatabase($backupDir);
            $backups['central'] = $centralBackup;

            // Backup tenant databases
            $tenants = Tenant::all();
            /** @var Tenant $tenant */
            foreach ($tenants as $tenant) {
                $tenantBackup = $this->backupTenantDatabase($tenant, $backupDir);
                if ($tenantBackup) {
                    $backups["tenant_{$tenant->id}"] = $tenantBackup;
                }
            }

            // Create ZIP file
            $this->createZipFile($backupDir, $zipFile);

            // Clean up directory
            $this->cleanupDirectory($backupDir);

            return [
                'success' => true,
                'zip_file' => $zipFile,
                'backups' => $backups,
                'timestamp' => $timestamp,
            ];

        } catch (\Exception $e) {
            Log::error('Database backup failed: '.$e->getMessage());

            // Clean up on failure
            if (is_dir($backupDir)) {
                $this->cleanupDirectory($backupDir);
            }
            if (file_exists($zipFile)) {
                unlink($zipFile);
            }

            return [
                'success' => false,
                'error' => $e->getMessage(),
                'timestamp' => $timestamp,
            ];
        }
    }

    /**
     * Backup central database
     */
    protected function backupCentralDatabase(string $backupDir): array
    {
        $fileName = 'central.sql';
        $filePath = $backupDir.'/'.$fileName;

        $dumper = $this->createDumper(config('database.connections.mysql.database'));
        $dumper->dumpToFile($filePath);

        return [
            'file' => $fileName,
            'path' => $filePath,
            'size' => filesize($filePath),
        ];
    }

    /**
     * Backup tenant database
     */
    protected function backupTenantDatabase(Tenant $tenant, string $backupDir): ?array
    {
        try {
            $dumpPath = $this->getDumpBinaryPath();

            // Switch to tenant database context
            $tenant->run(function () use ($tenant, $backupDir, $dumpPath) {
                $fileName = "tenant_{$tenant->id}.sql";
                $filePath = $backupDir.'/'.$fileName;

                $dumper = $this->createDumper($tenant->database_name, $dumpPath);
                $dumper->dumpToFile($filePath);

                return [
                    'file' => $fileName,
                    'path' => $filePath,
                    'size' => filesize($filePath),
                ];
            });

            $fileName = "tenant_{$tenant->id}.sql";
            $filePath = $backupDir.'/'.$fileName;

            return [
                'file' => $fileName,
                'path' => $filePath,
                'size' => filesize($filePath),
            ];

        } catch (\Exception $e) {
            Log::error("Failed to backup tenant {$tenant->id}: ".$e->getMessage());

            return null;
        }
    }

    /**
     * Create database dumper.
     */
    protected function createDumper(string $dbName, ?string $dumpPath = null): MySql
    {
        /** @var MySql $dumper */
        $dumper = MySql::create()
            ->setDbName($dbName)
            ->setUserName(config('database.connections.mysql.username'))
            ->setPassword(config('database.connections.mysql.password'))
            ->setHost(config('database.connections.mysql.host'))
            ->setPort(config('database.connections.mysql.port'))
            ->setSocket('');

        if ($dumpPath ??= $this->getDumpBinaryPath()) {
            $dumper->setDumpBinaryPath($dumpPath);
        }

        if (PHP_OS_FAMILY === 'Windows') {
            $dumper->addExtraOption('--protocol=TCP');
        }

        return $dumper;
    }

    /**
     * Create ZIP file from backup directory
     */
    protected function getDumpBinaryPath(): string
    {
        $path = trim((string) config('database.tools.mysqldump_path', ''));
        if (! $path) {
            return '';
        }

        // On Windows, append mysqldump.exe if directory is provided
        if (PHP_OS_FAMILY === 'Windows' && is_dir($path)) {
            $path = rtrim($path, '\\/').DIRECTORY_SEPARATOR.'mysqldump.exe';
        }

        return $path;
    }

    protected function createZipFile(string $sourceDir, string $zipFile): void
    {
        $zip = new ZipArchive;

        if ($zip->open($zipFile, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            throw new \Exception('Cannot create ZIP file');
        }

        $files = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($sourceDir),
            \RecursiveIteratorIterator::LEAVES_ONLY
        );

        foreach ($files as $file) {
            if (! $file->isDir()) {
                $filePath = $file->getRealPath();
                $relativePath = substr($filePath, strlen($sourceDir) + 1);
                $zip->addFile($filePath, $relativePath);
            }
        }

        $zip->close();
    }

    /**
     * Clean up backup directory
     */
    protected function cleanupDirectory(string $dir): void
    {
        if (! is_dir($dir)) {
            return;
        }

        $files = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($dir, \RecursiveDirectoryIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::CHILD_FIRST
        );

        foreach ($files as $file) {
            if ($file->isDir()) {
                rmdir($file->getRealPath());
            } else {
                unlink($file->getRealPath());
            }
        }

        rmdir($dir);
    }

    /**
     * Clean up old backup files
     */
    public function cleanupOldBackups(int $retentionDays = 7): int
    {
        $backupDir = storage_path('app/backups');
        if (! is_dir($backupDir)) {
            return 0;
        }

        $cutoffDate = now()->subDays($retentionDays);
        $deletedCount = 0;

        $files = glob($backupDir.'/*.zip');
        foreach ($files as $file) {
            $fileDate = \DateTime::createFromFormat('Y-m-d_H-i-s', basename($file, '.zip'));
            if ($fileDate && $fileDate < $cutoffDate) {
                unlink($file);
                $deletedCount++;
            }
        }

        return $deletedCount;
    }
}

<?php

namespace App\Jobs;

use App\Models\BackupLog;
use App\Services\DatabaseBackupService;
use App\Services\GoogleDriveService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

class BackupJob implements ShouldQueue
{
    use Queueable;

    public int $timeout = 3600; // 1 hour timeout

    public int $tries = 3;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public bool $uploadToDrive = true
    ) {}

    /**
     * Execute the job.
     */
    public function handle(
        DatabaseBackupService $backupService,
        GoogleDriveService $driveService
    ): void {
        $startTime = now();

        try {
            Log::info('Starting database backup job');

            // Create backup log entry
            $backupLog = BackupLog::create([
                'status' => 'running',
                'started_at' => $startTime,
            ]);

            // Perform database backup
            $result = $backupService->backupAllDatabases();

            if (! $result['success']) {
                throw new \Exception('Backup failed: '.($result['error'] ?? 'Unknown error'));
            }

            $backupLog->update([
                'status' => 'uploading',
                'file_path' => $result['zip_file'],
                'file_size' => filesize($result['zip_file']),
                'backup_details' => json_encode($result['backups']),
            ]);

            // Upload to Google Drive if requested
            if ($this->uploadToDrive && $driveService->isConnected()) {
                $fileName = basename($result['zip_file']);
                $driveFileId = $driveService->uploadFile(
                    $result['zip_file'],
                    $fileName
                );

                $backupLog->update([
                    'status' => 'completed',
                    'drive_file_id' => $driveFileId,
                    'completed_at' => now(),
                ]);

                Log::info('Backup completed and uploaded to Google Drive', [
                    'file' => $fileName,
                    'drive_id' => $driveFileId,
                ]);
            } else {
                $backupLog->update([
                    'status' => 'completed',
                    'completed_at' => now(),
                ]);

                Log::info('Backup completed (no upload to Google Drive)');
            }

            // Clean up old backups
            $deletedCount = $backupService->cleanupOldBackups();
            if ($deletedCount > 0) {
                Log::info("Cleaned up {$deletedCount} old backup files");
            }

            // Clean up local ZIP file
            if (file_exists($result['zip_file'])) {
                unlink($result['zip_file']);
            }

        } catch (\Exception $e) {
            Log::error('Backup job failed: '.$e->getMessage(), [
                'exception' => $e,
            ]);

            if (isset($backupLog)) {
                $backupLog->update([
                    'status' => 'failed',
                    'error_message' => $e->getMessage(),
                    'completed_at' => now(),
                ]);
            }

            throw $e;
        }
    }

    /**
     * Handle job failure
     */
    public function failed(\Throwable $exception): void
    {
        Log::error('Backup job failed permanently', [
            'exception' => $exception->getMessage(),
        ]);

        // Could send notification to admin here
    }
}

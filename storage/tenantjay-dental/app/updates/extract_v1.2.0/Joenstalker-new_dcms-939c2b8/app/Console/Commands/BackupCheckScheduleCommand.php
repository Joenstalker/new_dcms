<?php

namespace App\Console\Commands;

use App\Jobs\BackupJob;
use App\Models\SystemSetting;
use App\Services\GoogleDriveService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class BackupCheckScheduleCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'backup:check-schedule';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Check if backup should run based on schedule settings';

    /**
     * Execute the console command.
     */
    public function handle(GoogleDriveService $driveService)
    {
        // Check if auto backup is enabled
        $autoBackupEnabled = SystemSetting::get('auto_backup_enabled', false);
        if (! $autoBackupEnabled) {
            $this->info('Auto backup is disabled. Skipping.');

            return Command::SUCCESS;
        }

        // Get backup settings
        $frequency = SystemSetting::get('backup_frequency', 'daily');
        $backupTime = SystemSetting::get('backup_time', '02:00');

        // Check if it's time to run backup
        $shouldRun = $this->shouldRunBackup($frequency, $backupTime);

        if (! $shouldRun) {
            $this->info('Not time for backup yet. Next check in 1 minute.');

            return Command::SUCCESS;
        }

        // Check if Google Drive is connected (required for auto backup)
        if (! $driveService->isConnected()) {
            Log::warning('Auto backup scheduled but Google Drive not connected. Skipping backup.');
            $this->error('Google Drive not connected. Cannot run auto backup.');

            return Command::FAILURE;
        }

        $this->info('Time to run backup. Dispatching backup job...');

        // Dispatch backup job
        BackupJob::dispatch(true); // true = upload to drive

        $this->info('Backup job dispatched successfully.');

        return Command::SUCCESS;
    }

    /**
     * Determine if backup should run based on frequency and time
     */
    protected function shouldRunBackup(string $frequency, string $backupTime): bool
    {
        $now = now();

        // Parse backup time
        [$hour, $minute] = array_map('intval', explode(':', $backupTime));
        $scheduledTime = $now->copy()->setHour($hour)->setMinute($minute)->setSecond(0);

        // Check frequency
        if ($frequency === 'daily') {
            // Run if current time matches scheduled time (within 1 minute window)
            return $now->between(
                $scheduledTime->copy()->subMinute(),
                $scheduledTime->copy()->addMinute()
            );
        } elseif ($frequency === 'weekly') {
            // Run on Monday at scheduled time
            if ($now->dayOfWeek !== 1) { // Monday = 1
                return false;
            }

            return $now->between(
                $scheduledTime->copy()->subMinute(),
                $scheduledTime->copy()->addMinute()
            );
        }

        return false;
    }
}

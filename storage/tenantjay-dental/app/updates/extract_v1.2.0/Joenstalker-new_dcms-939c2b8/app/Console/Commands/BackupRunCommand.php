<?php

namespace App\Console\Commands;

use App\Jobs\BackupJob;
use App\Services\GoogleDriveService;
use Illuminate\Console\Command;

class BackupRunCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'backup:run {--no-upload : Skip uploading to Google Drive}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Run database backup for central and all tenant databases';

    /**
     * Execute the console command.
     */
    public function handle(GoogleDriveService $driveService)
    {
        $uploadToDrive = ! $this->option('no-upload');

        if ($uploadToDrive && ! $driveService->isConnected()) {
            $this->error('Google Drive is not connected. Use --no-upload to skip upload, or connect Google Drive first.');

            return Command::FAILURE;
        }

        $this->info('Starting database backup...');

        if ($uploadToDrive) {
            $this->info('Backup will be uploaded to Google Drive');
        } else {
            $this->info('Backup will be created locally only');
        }

        // Dispatch the backup job
        BackupJob::dispatch($uploadToDrive);

        $this->info('Backup job has been queued successfully.');

        if ($uploadToDrive) {
            $this->info('You can check the status in the admin system settings.');
        }

        return Command::SUCCESS;
    }
}

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $settings = [
            [
                'key' => 'google_drive_connected',
                'value' => 'false',
                'type' => 'boolean',
                'group' => 'backup',
                'description' => 'Whether Google Drive is connected for backups',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'key' => 'google_drive_access_token',
                'value' => null,
                'type' => 'string',
                'group' => 'backup',
                'description' => 'Encrypted Google Drive access token',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'key' => 'google_drive_refresh_token',
                'value' => null,
                'type' => 'string',
                'group' => 'backup',
                'description' => 'Encrypted Google Drive refresh token',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'key' => 'google_drive_token_expires',
                'value' => null,
                'type' => 'integer',
                'group' => 'backup',
                'description' => 'Google Drive token expiration timestamp',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'key' => 'auto_backup_enabled',
                'value' => 'false',
                'type' => 'boolean',
                'group' => 'backup',
                'description' => 'Whether automatic backups are enabled',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'key' => 'backup_frequency',
                'value' => 'daily',
                'type' => 'string',
                'group' => 'backup',
                'description' => 'Backup frequency: daily or weekly',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'key' => 'backup_time',
                'value' => '02:00',
                'type' => 'string',
                'group' => 'backup',
                'description' => 'Backup time in HH:MM format',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'key' => 'backup_retention_days',
                'value' => '7',
                'type' => 'integer',
                'group' => 'backup',
                'description' => 'Number of days to keep backups',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ];

        DB::table('system_settings')->insert($settings);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::table('system_settings')->whereIn('key', [
            'google_drive_connected',
            'google_drive_access_token',
            'google_drive_refresh_token',
            'google_drive_token_expires',
            'auto_backup_enabled',
            'backup_frequency',
            'backup_time',
            'backup_retention_days',
        ])->delete();
    }
};

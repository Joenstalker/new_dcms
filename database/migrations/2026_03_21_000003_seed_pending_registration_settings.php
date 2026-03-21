<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration 
{
    /**
     * Run the migrations.
     * 
     * Seeds default system settings for pending registration management:
     * - Default pending timeout (hours)
     * - Reminder settings
     * - Auto-approve settings
     */
    public function up(): void
    {
        $settings = [
            // Pending Registration Settings
            [
                'key' => 'pending_timeout_default_hours',
                'value' => '168',
                'type' => 'integer',
                'group' => 'registration',
                'description' => 'Default pending time in hours before expiry (default: 168 = 7 days)',
            ],
            [
                'key' => 'pending_reminder_global_enabled',
                'value' => 'true',
                'type' => 'boolean',
                'group' => 'registration',
                'description' => 'Enable automatic reminders for pending registrations approaching expiry',
            ],
            [
                'key' => 'pending_reminder_hours_before',
                'value' => '24',
                'type' => 'integer',
                'group' => 'registration',
                'description' => 'Hours before expiry to send reminder notification',
            ],
            [
                'key' => 'pending_auto_approve_enabled',
                'value' => 'false',
                'type' => 'boolean',
                'group' => 'registration',
                'description' => 'Enable automatic approval for pending registrations after timeout',
            ],
            [
                'key' => 'pending_auto_approve_hours',
                'value' => '168',
                'type' => 'integer',
                'group' => 'registration',
                'description' => 'Hours after which to auto-approve pending registrations (default: 168 = 7 days)',
            ],
        ];

        foreach ($settings as $setting) {
            // Only insert if the key doesn't already exist
            $exists = DB::table('system_settings')
                ->where('key', $setting['key'])
                ->exists();

            if (!$exists) {
                DB::table('system_settings')->insert([
                    'key' => $setting['key'],
                    'value' => $setting['value'],
                    'type' => $setting['type'],
                    'group' => $setting['group'],
                    'description' => $setting['description'],
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $keys = [
            'pending_timeout_default_hours',
            'pending_reminder_global_enabled',
            'pending_reminder_hours_before',
            'pending_auto_approve_enabled',
            'pending_auto_approve_hours',
        ];

        DB::table('system_settings')
            ->whereIn('key', $keys)
            ->delete();
    }
};

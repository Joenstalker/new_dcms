<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration 
{
    /**
     * Run the migrations.
     * Seeds default system settings for pending registration features.
     */
    public function up(): void
    {
        $settings = [
            // Pending timeout defaults
            [
                'key' => 'pending_timeout_default_hours',
                'value' => '168', // 7 days
                'type' => 'integer',
                'group' => 'registrations',
                'description' => 'Default hours before pending registration expires (default: 168 hours = 7 days)',
            ],

            // Reminder settings
            [
                'key' => 'pending_reminder_global_enabled',
                'value' => 'true',
                'type' => 'boolean',
                'group' => 'registrations',
                'description' => 'Enable/disable reminder emails globally for pending registrations',
            ],
            [
                'key' => 'pending_reminder_hours_before',
                'value' => '24',
                'type' => 'integer',
                'group' => 'registrations',
                'description' => 'Hours before expiry to send reminder email (default: 24 hours)',
            ],

            // Auto-approve settings
            [
                'key' => 'pending_auto_approve_enabled',
                'value' => 'false',
                'type' => 'boolean',
                'group' => 'registrations',
                'description' => 'Enable/disable auto-approve globally for pending registrations',
            ],
            [
                'key' => 'pending_auto_approve_hours',
                'value' => '168',
                'type' => 'integer',
                'group' => 'registrations',
                'description' => 'Hours after which to auto-approve pending registration (default: 168 hours = 7 days)',
            ],
        ];

        foreach ($settings as $setting) {
            // Check if setting already exists
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

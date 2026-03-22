<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
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
                'key' => 'pending_timeout_default_hours',
                'value' => '168',
                'type' => 'integer',
                'group' => 'registrations',
                'description' => 'Default hours before pending registration expires',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'key' => 'pending_reminder_global_enabled',
                'value' => 'true',
                'type' => 'boolean',
                'group' => 'registrations',
                'description' => 'Enable/disable reminder emails globally',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'key' => 'pending_reminder_hours_before',
                'value' => '24',
                'type' => 'integer',
                'group' => 'registrations',
                'description' => 'Hours before expiry to send reminder',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'key' => 'pending_auto_approve_enabled',
                'value' => 'false',
                'type' => 'boolean',
                'group' => 'registrations',
                'description' => 'Enable/disable auto-approve globally',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'key' => 'pending_auto_approve_hours',
                'value' => '168',
                'type' => 'integer',
                'group' => 'registrations',
                'description' => 'Hours after which auto-approve triggers',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ];

        foreach ($settings as $setting) {
            $exists = DB::table('system_settings')
                ->where('key', $setting['key'])
                ->exists();

            if (!$exists) {
                DB::table('system_settings')->insert($setting);
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::table('system_settings')->whereIn('key', [
            'pending_timeout_default_hours',
            'pending_reminder_global_enabled',
            'pending_reminder_hours_before',
            'pending_auto_approve_enabled',
            'pending_auto_approve_hours',
        ])->delete();
    }
};

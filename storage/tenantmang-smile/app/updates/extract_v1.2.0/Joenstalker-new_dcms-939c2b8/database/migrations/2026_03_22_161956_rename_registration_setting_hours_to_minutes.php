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
        // 1. Rename column in pending_registrations
        Schema::table('pending_registrations', function (Blueprint $table) {
            $table->renameColumn('pending_timeout_hours', 'pending_timeout_minutes');
        });

        // Multiply existing row values by 60
        DB::statement('UPDATE pending_registrations SET pending_timeout_minutes = pending_timeout_minutes * 60 WHERE pending_timeout_minutes IS NOT NULL');

        // 2. Update system_settings keys and values
        $keysToConvert = [
            'pending_timeout_default_hours' => 'pending_timeout_default_minutes',
            'pending_reminder_hours_before' => 'pending_reminder_minutes_before',
            'pending_auto_approve_hours' => 'pending_auto_approve_minutes',
        ];

        foreach ($keysToConvert as $oldKey => $newKey) {
            $setting = DB::table('system_settings')->where('key', $oldKey)->first();
            if ($setting) {
                DB::table('system_settings')
                    ->where('key', $oldKey)
                    ->update([
                        'key' => $newKey,
                        'value' => (int)$setting->value * 60
                    ]);
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // 1. Rename column back
        Schema::table('pending_registrations', function (Blueprint $table) {
            $table->renameColumn('pending_timeout_minutes', 'pending_timeout_hours');
        });

        // Divide existing row values by 60
        DB::statement('UPDATE pending_registrations SET pending_timeout_hours = pending_timeout_hours / 60 WHERE pending_timeout_hours IS NOT NULL');

        // 2. Revert system_settings keys and values
        $keysToRevert = [
            'pending_timeout_default_minutes' => 'pending_timeout_default_hours',
            'pending_reminder_minutes_before' => 'pending_reminder_hours_before',
            'pending_auto_approve_minutes' => 'pending_auto_approve_hours',
        ];

        foreach ($keysToRevert as $oldKey => $newKey) {
            $setting = DB::table('system_settings')->where('key', $oldKey)->first();
            if ($setting) {
                DB::table('system_settings')
                    ->where('key', $oldKey)
                    ->update([
                        'key' => $newKey,
                        'value' => max(1, round((int)$setting->value / 60))
                    ]);
            }
        }
    }
};

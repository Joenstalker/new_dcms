<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (!Schema::hasTable('branding_settings')) {
            return;
        }

        $defaults = [
            [
                'key' => 'login_max_attempts',
                'value' => '5',
                'type' => 'string',
            ],
            [
                'key' => 'login_lockout_minutes',
                'value' => '15',
                'type' => 'string',
            ],
        ];

        foreach ($defaults as $setting) {
            DB::table('branding_settings')->updateOrInsert(
                ['key' => $setting['key']],
                [
                    'value' => $setting['value'],
                    'type' => $setting['type'],
                    'updated_at' => now(),
                    'created_at' => now(),
                ]
            );
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (!Schema::hasTable('branding_settings')) {
            return;
        }

        DB::table('branding_settings')
            ->whereIn('key', ['login_max_attempts', 'login_lockout_minutes'])
            ->delete();
    }
};

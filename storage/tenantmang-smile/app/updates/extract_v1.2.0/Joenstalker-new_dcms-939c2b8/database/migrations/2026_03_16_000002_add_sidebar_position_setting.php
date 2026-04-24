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
        // Check if the setting already exists before inserting
        $exists = DB::table('system_settings')->where('key', 'sidebar_position')->exists();

        if (!$exists) {
            DB::table('system_settings')->insert([
                'key' => 'sidebar_position',
                'value' => 'left',
                'type' => 'string',
                'group' => 'branding',
                'description' => 'Sidebar position: left or right',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::table('system_settings')->where('key', 'sidebar_position')->delete();
    }
};

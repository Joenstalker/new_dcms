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
        // Inject 'maintenance' into the existing ENUM list
        DB::statement("ALTER TABLE features MODIFY COLUMN implementation_status ENUM('coming_soon', 'in_development', 'beta', 'active', 'deprecated', 'maintenance') DEFAULT 'coming_soon'");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Revert back safely
        DB::statement("ALTER TABLE features MODIFY COLUMN implementation_status ENUM('coming_soon', 'in_development', 'beta', 'active', 'deprecated') DEFAULT 'coming_soon'");
    }
};

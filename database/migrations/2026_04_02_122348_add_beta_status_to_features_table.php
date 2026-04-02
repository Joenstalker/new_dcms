<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration 
{
    public function up(): void
    {
        // Inject 'beta' into the existing ENUM list without destroying structure
        DB::statement("ALTER TABLE features MODIFY COLUMN implementation_status ENUM('coming_soon', 'in_development', 'beta', 'active', 'deprecated') DEFAULT 'coming_soon'");
    }

    public function down(): void
    {
        // Revert back safely
        DB::statement("ALTER TABLE features MODIFY COLUMN implementation_status ENUM('coming_soon', 'in_development', 'active', 'deprecated') DEFAULT 'coming_soon'");
    }
};

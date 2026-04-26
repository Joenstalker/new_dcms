<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration 
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('system_releases', function (Blueprint $table) {
            // Rename columns if they exist
            if (Schema::hasColumn('system_releases', 'version_name')) {
                $table->renameColumn('version_name', 'version');
            }
            if (Schema::hasColumn('system_releases', 'published_at')) {
                $table->renameColumn('published_at', 'released_at');
            }

            // Add new columns if missing
            if (!Schema::hasColumn('system_releases', 'is_mandatory')) {
                $table->boolean('is_mandatory')->default(false)->after('released_at');
            }
            if (!Schema::hasColumn('system_releases', 'requires_db_update')) {
                $table->boolean('requires_db_update')->default(false)->after('is_mandatory');
            }

            // Drop redundant columns
            if (Schema::hasColumn('system_releases', 'is_published')) {
                $table->dropColumn('is_published');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('system_releases', function (Blueprint $table) {
        // No easy reverse for renames/drops in this complex sync
        });
    }
};

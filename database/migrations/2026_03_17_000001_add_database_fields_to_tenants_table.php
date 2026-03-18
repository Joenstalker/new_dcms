<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration 
{
    /**
     * Run the migrations.
     *
     * This migration adds database name and connection fields to support
     * hashed tenant database naming (tenant_[hash]_db format).
     */
    public function up(): void
    {
        Schema::table('tenants', function (Blueprint $table) {
            // Database name field - stores the hashed database name
            // Format: tenant_[16char_hash]_db
            $table->string('database_name', 64)->nullable()->unique();

            // Database connection name for dynamic connections
            // Format: tenant_{$tenantId}
            $table->string('database_connection', 50)->nullable();

            // Index for faster lookups
            $table->index('database_name');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tenants', function (Blueprint $table) {
            $table->dropIndex(['database_name']);
            $table->dropColumn(['database_name', 'database_connection']);
        });
    }
};

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
        Schema::table('patients', function (Blueprint $table) {
            // Add tenant_id for cross-tenant prevention
            $table->string('tenant_id', 64)->nullable()->after('id');
            $table->index('tenant_id');

            // Add new encrypted fields
            $table->string('ic_number')->nullable()->after('email');
            $table->string('emergency_contact')->nullable()->after('notes');
            $table->string('emergency_phone')->nullable()->after('emergency_contact');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('patients', function (Blueprint $table) {
            $table->dropIndex(['tenant_id']);
            $table->dropColumn(['tenant_id', 'ic_number', 'emergency_contact', 'emergency_phone']);
        });
    }
};

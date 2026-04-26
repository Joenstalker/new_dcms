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
        Schema::table('appointments', function (Blueprint $table) {
            $table->string('tenant_id', 64)->nullable()->after('id');
            $table->index('tenant_id');
        });

        Schema::table('treatments', function (Blueprint $table) {
            $table->string('tenant_id', 64)->nullable()->after('id');
            $table->index('tenant_id');
        });

        Schema::table('invoices', function (Blueprint $table) {
            $table->string('tenant_id', 64)->nullable()->after('id');
            $table->index('tenant_id');
        });

        Schema::table('invoice_items', function (Blueprint $table) {
            $table->string('tenant_id', 64)->nullable()->after('id');
            $table->index('tenant_id');
        });

        Schema::table('payments', function (Blueprint $table) {
            $table->string('tenant_id', 64)->nullable()->after('id');
            $table->index('tenant_id');
        });

        Schema::table('services', function (Blueprint $table) {
            $table->string('tenant_id', 64)->nullable()->after('id');
            $table->index('tenant_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('appointments', function (Blueprint $table) {
            $table->dropIndex(['tenant_id']);
            $table->dropColumn('tenant_id');
        });

        Schema::table('treatments', function (Blueprint $table) {
            $table->dropIndex(['tenant_id']);
            $table->dropColumn('tenant_id');
        });

        Schema::table('invoices', function (Blueprint $table) {
            $table->dropIndex(['tenant_id']);
            $table->dropColumn('tenant_id');
        });

        Schema::table('invoice_items', function (Blueprint $table) {
            $table->dropIndex(['tenant_id']);
            $table->dropColumn('tenant_id');
        });

        Schema::table('payments', function (Blueprint $table) {
            $table->dropIndex(['tenant_id']);
            $table->dropColumn('tenant_id');
        });

        Schema::table('services', function (Blueprint $table) {
            $table->dropIndex(['tenant_id']);
            $table->dropColumn('tenant_id');
        });
    }
};

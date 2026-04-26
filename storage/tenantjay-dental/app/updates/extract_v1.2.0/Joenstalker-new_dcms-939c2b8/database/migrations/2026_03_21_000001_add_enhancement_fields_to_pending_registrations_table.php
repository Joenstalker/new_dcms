<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration 
{
    /**
     * Run the migrations.
     * 
     * Adds fields for:
     * - Dynamic pending timeout management
     * - Reminder system
     * - Auto-approve feature
     * - Expiry history tracking
     */
    public function up(): void
    {
        Schema::table('pending_registrations', function (Blueprint $table) {
            // Pending timeout management
            $table->unsignedInteger('pending_timeout_hours')->nullable()->after('expires_at');

            // Reminder system
            $table->boolean('reminder_enabled')->nullable()->after('pending_timeout_hours');
            $table->timestamp('reminder_sent_at')->nullable()->after('reminder_enabled');

            // Auto-approve
            $table->boolean('auto_approve_enabled')->nullable()->after('reminder_sent_at');
            $table->timestamp('auto_approve_scheduled_at')->nullable()->after('auto_approve_enabled');

            // Extended expiry tracking
            $table->timestamp('original_expires_at')->nullable()->after('auto_approve_scheduled_at');
            $table->json('expiry_history')->nullable()->after('original_expires_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('pending_registrations', function (Blueprint $table) {
            $table->dropColumn([
                'pending_timeout_hours',
                'reminder_enabled',
                'reminder_sent_at',
                'auto_approve_enabled',
                'auto_approve_scheduled_at',
                'original_expires_at',
                'expiry_history',
            ]);
        });
    }
};

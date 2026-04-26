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
        Schema::connection('central')->table('pending_registrations', function (Blueprint $table) {
            $table->boolean('pending_refund_timer_enabled')->default(false)->after('auto_approve_scheduled_at');
            $table->integer('pending_refund_timer_minutes')->nullable()->after('pending_refund_timer_enabled');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::connection('central')->table('pending_registrations', function (Blueprint $table) {
            $table->dropColumn(['pending_refund_timer_enabled', 'pending_refund_timer_minutes']);
        });
    }
};

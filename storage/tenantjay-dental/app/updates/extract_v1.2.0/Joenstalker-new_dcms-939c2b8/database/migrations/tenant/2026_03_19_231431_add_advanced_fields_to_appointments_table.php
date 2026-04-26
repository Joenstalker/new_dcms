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
            $table->text('guest_address')->nullable()->after('guest_email');
            $table->json('guest_medical_history')->nullable()->after('guest_address');
            $table->string('booking_reference')->nullable()->unique()->after('guest_medical_history');
            
            // Re-comment status to include pending
            $table->string('status')->default('scheduled')->change(); // scheduled, completed, cancelled, walk-in, pending
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('appointments', function (Blueprint $table) {
            $table->dropColumn(['guest_address', 'guest_medical_history', 'booking_reference']);
        });
    }
};

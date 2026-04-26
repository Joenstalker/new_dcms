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
        Schema::table('subscriptions', function (Blueprint $table) {
            $table->enum('billing_cycle', ['monthly', 'yearly'])->nullable()->after('stripe_price');
            $table->string('payment_method')->nullable()->after('billing_cycle');
            $table->enum('payment_status', ['pending', 'paid', 'failed', 'cancelled'])->nullable()->after('payment_method');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('subscriptions', function (Blueprint $table) {
            $table->dropColumn(['billing_cycle', 'payment_method', 'payment_status']);
        });
    }
};

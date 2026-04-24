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
     * - Payment method enable/disable toggles (Stripe, GCash, PayMaya, Bank Transfer)
     * - Billing cycle end date
     */
    public function up(): void
    {
        Schema::table('subscriptions', function (Blueprint $table) {
            // Payment method enable/disable toggles
            $table->boolean('stripe_enabled')->default(true)->after('payment_method');
            $table->boolean('gcash_enabled')->default(false)->after('stripe_enabled');
            $table->boolean('paymaya_enabled')->default(false)->after('gcash_enabled');
            $table->boolean('bank_transfer_enabled')->default(false)->after('paymaya_enabled');

            // Billing cycle management
            $table->timestamp('billing_cycle_end')->nullable()->after('ends_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('subscriptions', function (Blueprint $table) {
            $table->dropColumn([
                'stripe_enabled',
                'gcash_enabled',
                'paymaya_enabled',
                'bank_transfer_enabled',
                'billing_cycle_end',
            ]);
        });
    }
};

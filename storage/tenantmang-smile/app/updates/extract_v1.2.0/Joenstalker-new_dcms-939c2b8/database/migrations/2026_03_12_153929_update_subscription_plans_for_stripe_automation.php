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
        Schema::table('subscription_plans', function (Blueprint $table) {
            $table->string('stripe_product_id')->nullable()->after('stripe_id');
            $table->string('stripe_monthly_price_id')->nullable()->after('stripe_product_id');
            $table->string('stripe_yearly_price_id')->nullable()->after('stripe_monthly_price_id');
            $table->renameColumn('stripe_id', 'legacy_stripe_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('subscription_plans', function (Blueprint $table) {
            if (Schema::hasColumn('subscription_plans', 'legacy_stripe_id')) {
                $table->renameColumn('legacy_stripe_id', 'stripe_id');
            }
            $table->dropColumn(['stripe_product_id', 'stripe_monthly_price_id', 'stripe_yearly_price_id']);
        });
    }
};

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
        Schema::create('subscription_usage_overages', function (Blueprint $table) {
            $table->id();
            $table->string('tenant_id')->index();
            $table->foreignId('subscription_id')->constrained('subscriptions')->cascadeOnDelete();
            $table->string('metric', 20)->index(); // storage | bandwidth

            $table->timestamp('billing_period_start')->index();
            $table->timestamp('billing_period_end')->index();

            $table->unsignedBigInteger('included_bytes')->default(0);
            $table->unsignedBigInteger('used_bytes')->default(0);
            $table->unsignedBigInteger('overage_bytes')->default(0);

            $table->decimal('billable_quantity_gb', 12, 4)->default(0);
            $table->decimal('unit_price_per_gb', 10, 2)->default(0);
            $table->decimal('amount', 12, 2)->default(0);
            $table->string('currency', 3)->default('PHP');

            $table->string('stripe_invoice_item_id')->nullable()->unique();
            $table->string('stripe_invoice_id')->nullable()->index();
            $table->string('stripe_event_id')->nullable()->index();

            $table->string('status', 20)->default('pending')->index(); // pending | skipped | dry_run | failed
            $table->json('metadata')->nullable();

            $table->timestamps();

            $table->unique([
                'subscription_id',
                'metric',
                'billing_period_start',
                'billing_period_end',
            ], 'subscription_usage_overages_unique_period_metric');

            $table->foreign('tenant_id')->references('id')->on('tenants')->cascadeOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('subscription_usage_overages');
    }
};

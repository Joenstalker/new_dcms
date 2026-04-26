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
        Schema::create('tenant_limit_overage_consents', function (Blueprint $table) {
            $table->id();
            $table->string('tenant_id')->index();
            $table->foreignId('subscription_id')->constrained('subscriptions')->cascadeOnDelete();
            $table->string('metric', 32)->index();
            $table->dateTime('billing_period_start')->index();
            $table->dateTime('billing_period_end')->index();
            $table->string('status', 20)->default('accepted')->index();
            $table->timestamp('accepted_at')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->unique([
                'tenant_id',
                'subscription_id',
                'metric',
                'billing_period_start',
                'billing_period_end',
            ], 'tenant_limit_overage_consents_unique');

            $table->foreign('tenant_id')->references('id')->on('tenants')->cascadeOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tenant_limit_overage_consents');
    }
};

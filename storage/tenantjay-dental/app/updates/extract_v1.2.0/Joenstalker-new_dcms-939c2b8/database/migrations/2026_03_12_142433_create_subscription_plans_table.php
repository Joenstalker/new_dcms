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
        Schema::create('subscription_plans', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('stripe_id')->nullable()->unique(); // Stripe Product ID
            $table->decimal('price_monthly', 8, 2)->default(0);
            $table->decimal('price_yearly', 8, 2)->default(0);
            $table->integer('max_users')->default(1);
            $table->integer('max_patients')->nullable(); // null means unlimited
            $table->integer('max_appointments')->nullable(); // null means unlimited
            
            // Feature flags
            $table->boolean('has_sms')->default(false);
            $table->boolean('has_branding')->default(false);
            $table->boolean('has_analytics')->default(false);
            
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('subscription_plans');
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration 
{
    /**
     * Run the migrations.
     * 
     * This table stores pending tenant registrations before admin approval.
     * After payment, the tenant is stored here with 'pending' status until
     * admin approves or rejects the application.
     */
    public function up(): void
    {
        Schema::create('pending_registrations', function (Blueprint $table) {
            $table->id();
            $table->string('subdomain', 63)->unique();
            $table->string('clinic_name');
            $table->string('first_name');
            $table->string('last_name');
            $table->string('email');
            $table->string('phone', 20)->nullable();

            // Address fields (only stored in pending, not in tenant DB)
            $table->string('street')->nullable();
            $table->string('barangay')->nullable();
            $table->string('city')->nullable();
            $table->string('province')->nullable();

            // Password is stored temporarily (will be moved to tenant DB on approval)
            $table->string('password');

            // Plan and payment info
            $table->foreignId('subscription_plan_id')->constrained('subscription_plans');
            $table->string('billing_cycle')->default('monthly');
            $table->string('stripe_payment_intent_id')->nullable();
            $table->string('stripe_session_id')->nullable();
            $table->decimal('amount_paid', 10, 2)->nullable();

            // Status and verification
            $table->enum('status', ['pending', 'approved', 'rejected', 'refunded'])->default('pending');
            $table->string('verification_token', 64)->unique();
            $table->timestamp('expires_at');
            $table->timestamp('approved_at')->nullable();
            $table->timestamp('rejected_at')->nullable();
            $table->text('admin_rejection_message')->nullable();

            $table->timestamps();

            $table->index('status');
            $table->index('expires_at');
            $table->index('email');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pending_registrations');
    }
};

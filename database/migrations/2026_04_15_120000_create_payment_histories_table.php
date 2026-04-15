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
        Schema::create('payment_histories', function (Blueprint $table) {
            $table->id();
            $table->string('tenant_id')->nullable()->index();
            $table->foreignId('subscription_id')->nullable()->constrained('subscriptions')->nullOnDelete();
            $table->foreignId('pending_registration_id')->nullable()->constrained('pending_registrations')->nullOnDelete();

            $table->string('transaction_code', 7)->unique();
            $table->string('status', 20)->index(); // success, failed, refund
            $table->string('transaction_type', 30)->index(); // registration, renewal, upgrade, downgrade, etc.

            $table->decimal('amount', 12, 2)->default(0);
            $table->string('currency', 3)->default('PHP');

            $table->string('payment_method_label')->nullable();
            $table->string('payment_method_brand', 20)->nullable();
            $table->string('payment_method_last4', 4)->nullable();

            $table->string('description')->nullable();
            $table->string('billed_to_name')->nullable();
            $table->string('billed_to_email')->nullable();
            $table->text('billed_to_address')->nullable();

            $table->string('stripe_event_id')->nullable()->unique();
            $table->string('stripe_event_type')->nullable();
            $table->string('stripe_session_id')->nullable()->index();
            $table->string('stripe_payment_intent_id')->nullable()->index();
            $table->string('stripe_invoice_id')->nullable()->index();
            $table->string('stripe_charge_id')->nullable()->index();
            $table->string('stripe_refund_id')->nullable()->index();

            $table->json('metadata')->nullable();
            $table->timestamp('paid_at')->nullable()->index();
            $table->timestamps();

            $table->index(['tenant_id', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payment_histories');
    }
};

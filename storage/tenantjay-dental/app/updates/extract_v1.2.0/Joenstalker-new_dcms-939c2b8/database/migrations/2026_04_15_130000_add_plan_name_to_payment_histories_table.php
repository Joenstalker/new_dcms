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
        Schema::table('payment_histories', function (Blueprint $table) {
            if (! Schema::hasColumn('payment_histories', 'plan_name')) {
                $table->string('plan_name')->nullable()->after('transaction_type');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('payment_histories', function (Blueprint $table) {
            if (Schema::hasColumn('payment_histories', 'plan_name')) {
                $table->dropColumn('plan_name');
            }
        });
    }
};

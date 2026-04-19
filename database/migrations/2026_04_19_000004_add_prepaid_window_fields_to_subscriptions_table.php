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
            $table->unsignedTinyInteger('prepaid_months')->default(1)->after('billing_cycle_end');
            $table->unsignedTinyInteger('limit_multiplier')->default(1)->after('prepaid_months');
            $table->dateTime('prepaid_started_at')->nullable()->after('limit_multiplier');
            $table->dateTime('prepaid_ends_at')->nullable()->after('prepaid_started_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('subscriptions', function (Blueprint $table) {
            $table->dropColumn([
                'prepaid_months',
                'limit_multiplier',
                'prepaid_started_at',
                'prepaid_ends_at',
            ]);
        });
    }
};

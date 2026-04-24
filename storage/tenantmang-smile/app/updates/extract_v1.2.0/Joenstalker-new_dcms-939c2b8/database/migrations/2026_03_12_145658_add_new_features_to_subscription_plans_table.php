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
            $table->boolean('has_qr_booking')->default(true)->after('max_appointments');
            $table->boolean('has_priority_support')->default(false)->after('has_analytics');
            $table->boolean('has_multi_branch')->default(false)->after('has_priority_support');
            $table->string('report_level')->default('basic')->after('has_multi_branch'); // basic, enhanced, advanced
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('subscription_plans', function (Blueprint $table) {
            //
        });
    }
};

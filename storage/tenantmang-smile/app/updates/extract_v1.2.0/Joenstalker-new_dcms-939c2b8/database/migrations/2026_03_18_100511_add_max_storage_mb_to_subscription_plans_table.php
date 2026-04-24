<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('subscription_plans', function (Blueprint $table) {
            $table->integer('max_storage_mb')->default(500)->after('report_level');
        });

        // Set default values for existing plans
        DB::table('subscription_plans')->where('name', 'Basic')->update(['max_storage_mb' => 500]);
        DB::table('subscription_plans')->where('name', 'Professional')->update(['max_storage_mb' => 2000]);
        DB::table('subscription_plans')->where('name', 'Enterprise')->update(['max_storage_mb' => 10000]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('subscription_plans', function (Blueprint $table) {
            $table->dropColumn('max_storage_mb');
        });
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('subscription_plans', function (Blueprint $table) {
            $table->decimal('storage_overage_price_per_gb', 10, 2)
                ->default(0)
                ->after('max_bandwidth_mb');

            $table->decimal('bandwidth_overage_price_per_gb', 10, 2)
                ->default(0)
                ->after('storage_overage_price_per_gb');
        });

        // Explicit per-plan defaults (can be changed later in Admin > Plans).
        DB::table('subscription_plans')->whereIn('name', ['Basic'])->update([
            'max_bandwidth_mb' => 2048,
        ]);

        DB::table('subscription_plans')->whereIn('name', ['Pro', 'Professional'])->update([
            'max_bandwidth_mb' => 10240,
        ]);

        DB::table('subscription_plans')->whereIn('name', ['Ultimate', 'Enterprise'])->update([
            'max_bandwidth_mb' => 51200,
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('subscription_plans', function (Blueprint $table) {
            $table->dropColumn([
                'storage_overage_price_per_gb',
                'bandwidth_overage_price_per_gb',
            ]);
        });
    }
};

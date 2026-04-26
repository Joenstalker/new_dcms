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
        Schema::table('tenant_feature_updates', function (Blueprint $table) {
            $table->string('status')->default('pending')->change();
            $table->string('batch_id')->nullable()->index()->after('id');
            $table->text('failure_reason')->nullable()->after('applied_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (!Schema::hasTable('tenant_feature_updates')) {
            return;
        }

        $driver = DB::connection()->getDriverName();

        if ($driver === 'sqlite') {
            Schema::table('tenant_feature_updates', function (Blueprint $table) {
                if (Schema::hasColumn('tenant_feature_updates', 'batch_id')) {
                    $table->dropIndex('tenant_feature_updates_batch_id_index');
                    $table->dropColumn('batch_id');
                }

                if (Schema::hasColumn('tenant_feature_updates', 'failure_reason')) {
                    $table->dropColumn('failure_reason');
                }
            });

            return;
        }

        Schema::table('tenant_feature_updates', function (Blueprint $table) {
            $table->dropColumn(['batch_id', 'failure_reason']);
        });
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tenants', function (Blueprint $table) {
            $table->unsignedBigInteger('db_used_bytes')->default(0)->after('storage_used_bytes');
            $table->timestamp('last_db_measured_at')->nullable()->after('db_used_bytes');
            $table->timestamp('last_storage_reconciled_at')->nullable()->after('last_db_measured_at');
        });
    }

    public function down(): void
    {
        Schema::table('tenants', function (Blueprint $table) {
            $table->dropColumn(['db_used_bytes', 'last_db_measured_at', 'last_storage_reconciled_at']);
        });
    }
};


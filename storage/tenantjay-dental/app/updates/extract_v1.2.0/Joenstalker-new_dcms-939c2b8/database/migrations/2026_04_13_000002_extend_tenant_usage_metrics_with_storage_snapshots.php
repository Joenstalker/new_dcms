<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tenant_usage_metrics', function (Blueprint $table) {
            $table->unsignedBigInteger('file_used_bytes')->default(0)->after('public_request_count');
            $table->unsignedBigInteger('db_used_bytes')->default(0)->after('file_used_bytes');
            $table->unsignedBigInteger('total_used_bytes')->default(0)->after('db_used_bytes');
        });
    }

    public function down(): void
    {
        Schema::table('tenant_usage_metrics', function (Blueprint $table) {
            $table->dropColumn(['file_used_bytes', 'db_used_bytes', 'total_used_bytes']);
        });
    }
};


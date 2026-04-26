<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tenants', function (Blueprint $table) {
            $table->json('operating_hours')->nullable()->after('landing_page_config');
            $table->boolean('online_booking_enabled')->default(true)->after('operating_hours');
        });
    }

    public function down(): void
    {
        Schema::table('tenants', function (Blueprint $table) {
            $table->dropColumn(['operating_hours', 'online_booking_enabled']);
        });
    }
};

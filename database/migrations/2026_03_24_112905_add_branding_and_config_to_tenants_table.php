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
        Schema::table('tenants', function (Blueprint $table) {
            $table->string('logo_login_path')->nullable()->after('logo_path');
            $table->string('logo_booking_path')->nullable()->after('logo_login_path');
            $table->string('font_family')->default('Inter')->after('logo_booking_path');
            $table->json('enabled_features')->nullable()->after('font_family');
            $table->json('landing_page_config')->nullable()->after('enabled_features');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tenants', function (Blueprint $table) {
            $table->dropColumn([
                'logo_login_path',
                'logo_booking_path',
                'font_family',
                'enabled_features',
                'landing_page_config'
            ]);
        });
    }
};

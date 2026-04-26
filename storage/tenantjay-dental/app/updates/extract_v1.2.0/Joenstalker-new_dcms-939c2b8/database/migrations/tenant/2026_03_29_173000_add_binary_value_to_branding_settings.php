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
        Schema::table('branding_settings', function (Blueprint $table) {
            // Use LONGBLOB for binary data storage to avoid memory-heavy base64 processing
            // We use 'longblob' for MySQL which supports up to 4GB, plenty for logos.
            $table->binary('binary_value')->nullable()->after('value');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('branding_settings', function (Blueprint $table) {
            $table->dropColumn('binary_value');
        });
    }
};

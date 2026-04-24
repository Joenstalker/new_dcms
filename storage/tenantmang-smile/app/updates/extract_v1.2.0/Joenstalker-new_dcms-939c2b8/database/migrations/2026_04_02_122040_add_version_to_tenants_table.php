<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration 
{
    public function up(): void
    {
        Schema::table('tenants', function (Blueprint $table) {
            $table->string('version')->nullable()->after('domain_id');
        });

        // Safe query avoiding Eloquent instantiation inside migrations. 
        // We set the default version for any existing tenants seamlessly to match Phase 3 rules.
        try {
            $currentVersion = config('app_version.version', '1.0.0');
            DB::table('tenants')
                ->whereNull('version')
                ->update(['version' => $currentVersion]);
        }
        catch (\Exception $e) {
        // Ignore if running in a dry-run or disconnected mode
        }
    }

    public function down(): void
    {
        Schema::table('tenants', function (Blueprint $table) {
            $table->dropColumn('version');
        });
    }
};

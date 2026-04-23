<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::connection('central')->table('system_releases', function (Blueprint $table) {
            if (! Schema::connection('central')->hasColumn('system_releases', 'installed_at')) {
                $table->timestamp('installed_at')->nullable()->after('requires_db_update');
            }
            if (! Schema::connection('central')->hasColumn('system_releases', 'environment_id')) {
                $table->string('environment_id')->nullable()->after('installed_at');
            }
            if (! Schema::connection('central')->hasColumn('system_releases', 'backup_id')) {
                $table->string('backup_id')->nullable()->after('environment_id');
            }
        });
    }

    public function down(): void
    {
        Schema::connection('central')->table('system_releases', function (Blueprint $table) {
            if (Schema::connection('central')->hasColumn('system_releases', 'backup_id')) {
                $table->dropColumn('backup_id');
            }
            if (Schema::connection('central')->hasColumn('system_releases', 'environment_id')) {
                $table->dropColumn('environment_id');
            }
            if (Schema::connection('central')->hasColumn('system_releases', 'installed_at')) {
                $table->dropColumn('installed_at');
            }
        });
    }
};

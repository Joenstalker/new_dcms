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
            $table->string('database')->nullable()->after('id');
        });

        // Populate the new column from existing JSON data if possible
        $tenants = DB::table('tenants')->get();
        foreach ($tenants as $tenant) {
            $data = json_decode($tenant->data, true);
            $dbName = $data['database_name'] ?? $data['database'] ?? null;
            if ($dbName) {
                DB::table('tenants')->where('id', $tenant->id)->update(['database' => $dbName]);
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tenants', function (Blueprint $table) {
            $table->dropColumn('database');
        });
    }
};

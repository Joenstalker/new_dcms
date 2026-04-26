<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration 
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (DB::connection('central')->getDriverName() !== 'mysql') {
            return;
        }

        Schema::connection('central')->table('support_tickets', function (Blueprint $table) {
            $table->string('tenant_id')->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (DB::connection('central')->getDriverName() !== 'mysql') {
            return;
        }

        Schema::connection('central')->table('support_tickets', function (Blueprint $table) {
            $table->unsignedBigInteger('tenant_id')->change();
        });
    }
};

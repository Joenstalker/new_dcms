<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration 
{
    public function up(): void
    {
        Schema::create('tenant_upgrade_logs', function (Blueprint $table) {
            $table->id();
            $table->string('tenant_id');
            $table->string('from_version')->nullable();
            $table->string('to_version');
            $table->string('status'); // running, success, failed
            $table->longText('log_output')->nullable();
            $table->timestamps();

            // Hardcode constraints strictly against standard SaaS schema tables
            $table->foreign('tenant_id')->references('id')->on('tenants')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tenant_upgrade_logs');
    }
};

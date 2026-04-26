<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tenant_file_objects', function (Blueprint $table) {
            $table->id();
            $table->string('tenant_id');
            $table->string('disk')->default('public');
            $table->string('path');
            $table->unsignedBigInteger('bytes')->default(0);
            $table->string('hash')->nullable();
            $table->timestamps();

            $table->unique(['tenant_id', 'disk', 'path']);
            $table->index(['tenant_id', 'disk']);
            $table->foreign('tenant_id')->references('id')->on('tenants')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tenant_file_objects');
    }
};


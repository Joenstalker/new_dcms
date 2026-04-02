<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration 
{
    public function up(): void
    {
        Schema::create('system_releases', function (Blueprint $table) {
            $table->id();
            $table->string('version')->unique(); // e.g., v1.0.0, v1.1.0
            $table->text('release_notes')->nullable();
            $table->timestamp('released_at')->nullable();
            $table->boolean('is_mandatory')->default(false);
            $table->boolean('requires_db_update')->default(false);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('system_releases');
    }
};

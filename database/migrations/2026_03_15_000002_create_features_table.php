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
        Schema::create('features', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique(); // qr_booking, max_patients, etc.
            $table->string('name'); // Display name
            $table->text('description')->nullable(); // Feature description
            $table->enum('type', ['boolean', 'numeric', 'tiered']);
            $table->string('category')->nullable(); // core, limits, addons, reports, expansion
            $table->json('options')->nullable(); // For tiered: ['basic', 'enhanced', 'advanced']
            $table->integer('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('features');
    }
};

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
        Schema::create('tenant_feature_updates', function (Blueprint $table) {
            $table->id();
            $table->uuid('tenant_id')->index();
            $table->foreignId('feature_id')->constrained()->cascadeOnDelete();
            $table->enum('status', ['pending', 'applied', 'dismissed'])->default('pending');
            $table->timestamp('applied_at')->nullable();
            $table->timestamps();

            $table->unique(['tenant_id', 'feature_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tenant_feature_updates');
    }
};

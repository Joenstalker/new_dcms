<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration 
{
    public function up(): void
    {
        Schema::create('tenant_features', function (Blueprint $table) {
            $table->id();
            $table->uuid('tenant_id')->index();
            $table->foreignId('feature_id')->constrained('features')->cascadeOnDelete();
            $table->boolean('is_enabled')->default(true);
            $table->text('override_reason')->nullable();
            $table->timestamps();

            $table->unique(['tenant_id', 'feature_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tenant_features');
    }
};

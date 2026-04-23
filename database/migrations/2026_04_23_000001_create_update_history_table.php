<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::connection('central')->create('update_history', function (Blueprint $table) {
            $table->id();
            $table->string('environment_id');
            $table->string('from_version');
            $table->string('to_version');
            $table->enum('status', ['pending', 'in_progress', 'completed', 'failed', 'rolled_back']);
            $table->string('backup_id')->nullable();
            $table->longText('log_output')->nullable();
            $table->longText('error_message')->nullable();
            $table->string('executed_by')->nullable();
            $table->integer('duration_seconds')->nullable();
            $table->timestamps();

            $table->index(['environment_id', 'created_at']);
            $table->index(['status']);
        });
    }

    public function down(): void
    {
        Schema::connection('central')->dropIfExists('update_history');
    }
};

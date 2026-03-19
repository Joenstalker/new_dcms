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
        Schema::table('features', function (Blueprint $table) {
            $table->enum('implementation_status', ['coming_soon', 'in_development', 'active', 'deprecated'])
                ->default('coming_soon')
                ->after('is_active');
            $table->string('code_identifier')
                ->nullable()
                ->after('implementation_status');
            $table->date('announced_at')
                ->nullable()
                ->after('code_identifier');
            $table->date('released_at')
                ->nullable()
                ->after('announced_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('features', function (Blueprint $table) {
            $table->dropColumn([
                'implementation_status',
                'code_identifier',
                'announced_at',
                'released_at',
            ]);
        });
    }
};

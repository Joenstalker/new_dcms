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
        Schema::table('services', function (Blueprint $table) {
            // Drop foreign key first
            $table->dropForeign(['approved_by']);
            $table->dropColumn(['status', 'approved_by']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('services', function (Blueprint $table) {
            $table->string('status')->default('pending')->after('price');
            $table->foreignId('approved_by')->nullable()->after('created_by')->constrained('users');
        });
    }
};

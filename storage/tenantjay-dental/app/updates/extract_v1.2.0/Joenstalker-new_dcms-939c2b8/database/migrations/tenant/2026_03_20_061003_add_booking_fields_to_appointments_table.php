<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('appointments', function (Blueprint $table) {
            $table->string('photo_path')->nullable()->after('notes');
            $table->foreignId('dentist_id')->nullable()->after('patient_id')->constrained('users')->nullOnDelete();
            $table->enum('type', ['appointment', 'recall', 'event', 'birthday', 'online_booking'])->default('appointment')->after('status');
        });
    }

    public function down(): void
    {
        Schema::table('appointments', function (Blueprint $table) {
            $table->dropForeign(['dentist_id']);
            $table->dropColumn(['photo_path', 'dentist_id', 'type']);
        });
    }
};

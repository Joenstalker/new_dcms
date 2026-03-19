<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('patients', function (Blueprint $table) {
            $table->decimal('balance', 10, 2)->default(0)->after('notes');
            $table->text('operation_history')->nullable()->after('medical_history');
            $table->dateTime('last_visit_time')->nullable()->after('balance');
        });
    }

    public function down(): void
    {
        Schema::table('patients', function (Blueprint $table) {
            $table->dropColumn(['balance', 'operation_history', 'last_visit_time']);
        });
    }
};

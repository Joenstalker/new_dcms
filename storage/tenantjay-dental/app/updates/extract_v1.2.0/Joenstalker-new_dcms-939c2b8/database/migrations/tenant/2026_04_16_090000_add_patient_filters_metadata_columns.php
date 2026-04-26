<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('patients', function (Blueprint $table) {
            if (!Schema::hasColumn('patients', 'patient_type')) {
                $table->string('patient_type', 20)->nullable()->after('gender');
                $table->index('patient_type');
            }

            if (!Schema::hasColumn('patients', 'tags')) {
                $table->json('tags')->nullable()->after('notes');
            }

            if (!Schema::hasColumn('patients', 'first_visit_at')) {
                $table->date('first_visit_at')->nullable()->after('last_visit_time');
                $table->index('first_visit_at');
            }

            if (!Schema::hasColumn('patients', 'last_recall_at')) {
                $table->date('last_recall_at')->nullable()->after('first_visit_at');
                $table->index('last_recall_at');
            }
        });
    }

    public function down(): void
    {
        Schema::table('patients', function (Blueprint $table) {
            if (Schema::hasColumn('patients', 'last_recall_at')) {
                $table->dropColumn('last_recall_at');
            }

            if (Schema::hasColumn('patients', 'first_visit_at')) {
                $table->dropColumn('first_visit_at');
            }

            if (Schema::hasColumn('patients', 'tags')) {
                $table->dropColumn('tags');
            }

            if (Schema::hasColumn('patients', 'patient_type')) {
                $table->dropColumn('patient_type');
            }
        });
    }
};

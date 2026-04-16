<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('treatments', function (Blueprint $table) {
            $table->foreignId('service_id')->nullable()->after('appointment_id')->constrained('services')->nullOnDelete();
            $table->string('payment_account', 30)->nullable()->after('cost');
            $table->decimal('discount', 10, 2)->default(0)->after('payment_account');
            $table->decimal('total_amount_due', 10, 2)->default(0)->after('discount');
            $table->decimal('amount_paid', 10, 2)->default(0)->after('total_amount_due');
            $table->boolean('is_last_visit')->default(false)->after('amount_paid');
            $table->foreignId('linked_treatment_id')->nullable()->after('is_last_visit')->constrained('treatments')->nullOnDelete();
            $table->decimal('commission_deductions', 10, 2)->default(0)->after('linked_treatment_id');
            $table->decimal('commission_percentage', 5, 2)->default(0)->after('commission_deductions');
            $table->decimal('commission_net', 10, 2)->default(0)->after('commission_percentage');
            $table->boolean('commission_use_percentage')->default(true)->after('commission_net');
            $table->boolean('schedule_next_visit')->default(false)->after('commission_use_percentage');
            $table->dateTime('next_visit_at')->nullable()->after('schedule_next_visit');
            $table->text('next_visit_procedure')->nullable()->after('next_visit_at');
            $table->foreignId('next_visit_dentist_id')->nullable()->after('next_visit_procedure')->constrained('users')->nullOnDelete();
            $table->text('next_visit_remarks')->nullable()->after('next_visit_dentist_id');
        });
    }

    public function down(): void
    {
        Schema::table('treatments', function (Blueprint $table) {
            $table->dropConstrainedForeignId('next_visit_dentist_id');
            $table->dropConstrainedForeignId('linked_treatment_id');
            $table->dropConstrainedForeignId('service_id');

            $table->dropColumn([
                'payment_account',
                'discount',
                'total_amount_due',
                'amount_paid',
                'is_last_visit',
                'commission_deductions',
                'commission_percentage',
                'commission_net',
                'commission_use_percentage',
                'schedule_next_visit',
                'next_visit_at',
                'next_visit_procedure',
                'next_visit_remarks',
            ]);
        });
    }
};

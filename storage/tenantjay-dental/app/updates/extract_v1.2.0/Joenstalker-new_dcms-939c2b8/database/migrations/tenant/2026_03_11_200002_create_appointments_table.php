<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('appointments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('patient_id')->nullable()->constrained('patients')->nullOnDelete();
            
            // For guest details from QR code if patient is not yet registered
            $table->string('guest_first_name')->nullable();
            $table->string('guest_last_name')->nullable();
            $table->string('guest_phone')->nullable();
            $table->string('guest_email')->nullable();
            
            // Appointment details
            $table->dateTime('appointment_date');
            $table->string('status')->default('scheduled'); // scheduled, completed, cancelled, walk-in
            $table->string('service')->nullable();
            $table->text('notes')->nullable();
            
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('appointments');
    }
};

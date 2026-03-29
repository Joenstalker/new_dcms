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
        Schema::create('branding_settings', function (Blueprint $table) {
            $table->id();
            
            // Clinic Details
            $table->string('clinic_name')->nullable();
            $table->string('clinic_email')->nullable();
            $table->string('clinic_phone')->nullable();
            $table->text('clinic_address')->nullable();
            
            // Visual Identity
            $table->string('primary_color', 7)->default('#0ea5e9');
            $table->longText('logo_base64')->nullable();
            $table->longText('logo_login_base64')->nullable();
            $table->longText('logo_booking_base64')->nullable();
            
            // Config & Features (JSON)
            $table->json('font_family')->nullable();
            $table->json('enabled_features')->nullable();
            $table->json('operating_hours')->nullable();
            $table->json('portal_config')->nullable();
            $table->json('landing_page_config')->nullable();
            
            // Text Content
            $table->string('hero_title')->nullable();
            $table->string('hero_subtitle')->nullable();
            $table->text('about_us_description')->nullable();
            
            // Logic
            $table->boolean('online_booking_enabled')->default(true);
            
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('branding_settings');
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Create the new granular table structure
        Schema::create('branding_configs', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique();
            $table->longText('value')->nullable();
            $table->string('type')->default('string'); // For casting: string, json, array
            $table->timestamps();
        });

        // 2. Migrate data from old wide-row table if it exists
        if (Schema::hasTable('branding_settings')) {
            $row = DB::table('branding_settings')->where('id', 1)->first();
            if ($row) {
                $columns = Schema::getColumnListing('branding_settings');
                foreach ($columns as $column) {
                    if (in_array($column, ['id', 'created_at', 'updated_at'])) continue;
                    
                    $value = $row->$column;
                    if ($value !== null) {
                        DB::table('branding_configs')->updateOrInsert(
                            ['key' => $column],
                            [
                                'value' => is_array($value) || is_object($value) ? json_encode($value) : (string)$value,
                                'type' => (is_array($value) || is_object($value)) ? 'json' : 'string',
                                'created_at' => now(),
                                'updated_at' => now(),
                            ]
                        );
                    }
                }
            }
            // 3. Drop the old wide-row table
            Schema::dropIfExists('branding_settings');
        }

        // 4. Rename the new table to the original name to keep consistency
        Schema::rename('branding_configs', 'branding_settings');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('branding_settings');
        
        // Re-create the wide-row table if reverted
        Schema::create('branding_settings', function (Blueprint $table) {
            $table->id();
            $table->string('clinic_name')->nullable();
            $table->string('clinic_email')->nullable();
            $table->string('clinic_phone')->nullable();
            $table->text('clinic_address')->nullable();
            $table->string('primary_color')->default('#0ea5e9');
            $table->longText('logo_base64')->nullable();
            $table->longText('logo_login_base64')->nullable();
            $table->longText('logo_booking_base64')->nullable();
            $table->json('font_family')->nullable();
            $table->json('enabled_features')->nullable();
            $table->json('operating_hours')->nullable();
            $table->json('portal_config')->nullable();
            $table->json('landing_page_config')->nullable();
            $table->string('hero_title')->nullable();
            $table->string('hero_subtitle')->nullable();
            $table->text('about_us_description')->nullable();
            $table->boolean('online_booking_enabled')->default(true);
            $table->timestamps();
        });
    }
};

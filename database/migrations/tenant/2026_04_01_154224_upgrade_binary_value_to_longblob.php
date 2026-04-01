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
        // Upgrade to LONGBLOB to support high-res images (up to 4GB)
        // We use raw statement because binary('col')->length() doesn't always translate to LONGBLOB correctly on some DB drivers
        DB::statement('ALTER TABLE branding_settings MODIFY binary_value LONGBLOB');

        // Migrate keys: replace spaces with underscores for all existing records
        $settings = DB::table('branding_settings')->get();
        foreach ($settings as $setting) {
            if (str_contains($setting->key, ' ')) {
                $newKey = str_replace(' ', '_', $setting->key);
                
                // Check if the new key already exists to avoid duplicate key error
                $exists = DB::table('branding_settings')->where('key', $newKey)->exists();
                
                if ($exists) {
                    // Update binary value into existing underscore record if binary is currently null there
                    DB::table('branding_settings')
                        ->where('key', $newKey)
                        ->whereNull('binary_value')
                        ->update(['binary_value' => $setting->binary_value]);
                        
                    // Delete the old space-filled record
                    DB::table('branding_settings')->where('id', $setting->id)->delete();
                } else {
                    // Simply rename if no conflict
                    DB::table('branding_settings')->where('id', $setting->id)->update(['key' => $newKey]);
                }
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement('ALTER TABLE branding_settings MODIFY binary_value BLOB');
    }
};

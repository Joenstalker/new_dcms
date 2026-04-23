<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use App\Models\SystemSetting;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        SystemSetting::create([
            'key' => 'central_api_url',
            'value' => 'http://localhost:8000', // Default for local dev
            'type' => 'string',
            'group' => 'maintenance',
            'description' => 'The ngrok URL of Laptop A (Central) to poll for the latest release metadata.',
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        SystemSetting::where('key', 'central_api_url')->delete();
    }
};

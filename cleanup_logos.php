<?php

define('LARAVEL_START', microtime(true));
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;

foreach (\App\Models\Tenant::all() as $tenant) {
    tenancy()->initialize($tenant);
    echo "Processing Tenant: {$tenant->id}\n";
    
    $rows = DB::table('branding_settings')
        ->whereIn('key', ['logo_base64', 'logo_login_base64', 'logo_booking_base64'])
        ->whereNotNull('value')
        ->get();
        
    foreach ($rows as $row) {
        if (str_starts_with($row->value, 'data:image/')) {
            $base64 = substr($row->value, strpos($row->value, ',') + 1);
            $binary = base64_decode($base64);
            
            if ($binary) {
                DB::table('branding_settings')
                    ->where('id', $row->id)
                    ->update([
                        'binary_value' => $binary,
                        'value' => null,
                        'type' => 'binary',
                        'updated_at' => now()
                    ]);
                echo "  Migrated {$row->key}\n";
            }
        }
    }
}
echo "Migration Complete.\n";

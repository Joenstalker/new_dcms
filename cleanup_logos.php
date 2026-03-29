<?php

define('LARAVEL_START', microtime(true));
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;

// Increase memory limit for this script (images can be large)
ini_set('memory_limit', '2G');

$migrated = 0;
$skipped = 0;
$errors = 0;

// Use cursor to avoid loading all tenants into memory at once
foreach (\App\Models\Tenant::cursor() as $tenant) {
    try {
        tenancy()->initialize($tenant);
    } catch (\Exception $e) {
        echo "  SKIP Tenant {$tenant->id}: {$e->getMessage()}\n";
        continue;
    }
    
    echo "Processing Tenant: {$tenant->id}\n";
    
    // Process one row at a time using cursor to minimize memory
    $rows = DB::table('branding_settings')
        ->whereIn('key', ['logo_base64', 'logo_login_base64', 'logo_booking_base64'])
        ->whereNotNull('value')
        ->where('value', '!=', '')
        ->cursor();
        
    foreach ($rows as $row) {
        $value = $row->value;
        
        if (!str_starts_with($value, 'data:image/')) {
            echo "  SKIP {$row->key}: not a data:image/ value\n";
            $skipped++;
            unset($value);
            continue;
        }
        
        // Extract base64 payload
        $commaPos = strpos($value, ',');
        if ($commaPos === false) {
            echo "  SKIP {$row->key}: malformed data URL\n";
            $skipped++;
            unset($value);
            continue;
        }
        
        $base64 = substr($value, $commaPos + 1);
        // Free the original large string immediately
        unset($value);
        
        $binary = base64_decode($base64, true); // strict mode
        // Free base64 string immediately
        unset($base64);
        
        if ($binary === false || $binary === '') {
            echo "  SKIP {$row->key}: base64 decode failed\n";
            $skipped++;
            continue;
        }
        
        try {
            // Use PDO LOB streaming to avoid building huge query strings
            $tmpFile = tempnam(sys_get_temp_dir(), 'logo_');
            file_put_contents($tmpFile, $binary);
            // Free binary string from memory
            unset($binary);
            
            $pdo = DB::getPdo();
            $stmt = $pdo->prepare(
                "UPDATE branding_settings SET `binary_value` = ?, `value` = NULL, `type` = 'binary', `updated_at` = NOW() WHERE `id` = ?"
            );
            
            $fp = fopen($tmpFile, 'rb');
            $stmt->bindParam(1, $fp, \PDO::PARAM_LOB);
            $stmt->bindParam(2, $row->id, \PDO::PARAM_INT);
            $stmt->execute();
            fclose($fp);
            
            unlink($tmpFile);
            unset($tmpFile, $fp, $stmt);
            
            echo "  MIGRATED {$row->key}\n";
            $migrated++;
            
        } catch (\Exception $e) {
            echo "  ERROR {$row->key}: {$e->getMessage()}\n";
            $errors++;
        }
    }
    
    // Force garbage collection between tenants
    gc_collect_cycles();
}

echo "\n========== Summary ==========\n";
echo "Migrated: $migrated\n";
echo "Skipped:  $skipped\n";
echo "Errors:   $errors\n";
echo "Done.\n";

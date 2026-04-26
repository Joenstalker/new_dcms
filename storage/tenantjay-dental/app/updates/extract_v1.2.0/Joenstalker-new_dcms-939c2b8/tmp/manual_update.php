<?php

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';

use App\Models\Tenant;
use Illuminate\Support\Facades\DB;

$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$tenant = Tenant::first();
if (!$tenant) {
    echo "NO_TENANT_FOUND";
    exit;
}

echo "BEFORE: " . ($tenant->branding_color ?? 'NULL') . "\n";
echo "FILLABLE: " . implode(',', $tenant->getFillable()) . "\n";

// Use DB directly to check what's in the 'data' column
$data = DB::table('tenants')->where('id', $tenant->id)->value('data');
echo "DB_DATA: " . $data . "\n";

// Attempt update
$tenant->branding_color = '#9843A3';
$tenant->save();

$tenant->refresh();
echo "AFTER_SAVE: " . ($tenant->branding_color ?? 'NULL') . "\n";

$data_after = DB::table('tenants')->where('id', $tenant->id)->value('data');
echo "DB_DATA_AFTER: " . $data_after . "\n";

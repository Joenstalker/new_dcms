<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Tenant;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

echo "=== PROFILE PICTURE UPLOAD DIAGNOSTIC ===\n\n";

$tenantId = 'joenil';
$tenant = Tenant::find($tenantId);

if (!$tenant) {
    echo "ERROR: Tenant not found: $tenantId\n";
    exit(1);
}

echo "STEP 1: Tenant Information\n";
echo "  Tenant ID: " . $tenant->id . "\n";
echo "  Database: " . $tenant->database_name . "\n\n";

try {
    tenancy()->initialize($tenant);
    echo "STEP 2: Tenancy Initialized\n";
    echo "  Current tenant: " . tenant()->get('id') . "\n";
    echo "  Current DB: " . DB::connection()->getDatabaseName() . "\n\n";
} catch (\Exception $e) {
    echo "ERROR: Failed to initialize tenancy: " . $e->getMessage() . "\n";
    exit(1);
}

echo "STEP 3: Storage Configuration\n";
$storageRoot = storage_path();
echo "  Default storage_path(): " . $storageRoot . "\n";

$publicDisk = Storage::disk('public');
echo "  Storage::disk('public') class: " . get_class($publicDisk) . "\n";

$root = $publicDisk->path('');
echo "  Physical root path: " . $root . "\n\n";

echo "STEP 4: Check if storage directory exists\n";
$tenantStoragePath = $storageRoot . '/tenant/' . $tenantId;
echo "  Tenant storage path: " . $tenantStoragePath . "\n";
echo "  Directory exists: " . (is_dir($tenantStoragePath) ? 'YES' : 'NO') . "\n";
if (!is_dir($tenantStoragePath)) {
    echo "  Creating tenant storage directory...\n";
    if (mkdir($tenantStoragePath, 0755, true)) {
        echo "  Created successfully.\n";
    } else {
        echo "  FAILED to create directory.\n";
    }
}
echo "\n";

echo "STEP 5: Get authenticated user\n";
$user = User::first();
if (!$user) {
    echo "ERROR: No users found in tenant database.\n";
    exit(1);
}
echo "  User ID: " . $user->id . "\n";
echo "  User email: " . $user->email . "\n";
echo "  Current profile_picture: " . ($user->profile_picture ?? 'null') . "\n\n";

echo "STEP 6: Create test image data (base64)\n";
$testImageData = base64_decode('R0lGODlhAQABAIAAAP///wAAACH5BAEAAAAALAAAAAABAAEAAAICRAEAOw==');
echo "  Test image size: " . strlen($testImageData) . " bytes\n\n";

echo "STEP 7: Attempt file write\n";
$fileName = 'profile-pictures/' . $user->id . '-' . Str::random(10) . '.gif';
echo "  Target filename: " . $fileName . "\n";

try {
    $putResult = Storage::disk('public')->put($fileName, $testImageData);
    echo "  Storage::put() result: " . ($putResult ? 'SUCCESS' : 'FAILED') . "\n";
    
    if (!$putResult) {
        echo "ERROR: Storage::put() returned false.\n";
    }
} catch (\Exception $e) {
    echo "EXCEPTION during Storage::put(): " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
    $putResult = false;
}

echo "\nSTEP 8: Verify file was saved\n";
if ($putResult) {
    $exists = Storage::disk('public')->exists($fileName);
    echo "  File exists after write: " . ($exists ? 'YES' : 'NO') . "\n";
    
    if ($exists) {
        $size = Storage::disk('public')->size($fileName);
        echo "  File size: " . $size . " bytes\n";
        
        $fullPath = $root . '/' . $fileName;
        echo "  Full path check: " . (file_exists($fullPath) ? 'EXISTS' : 'NOT FOUND') . "\n";
    }
    
    echo "\nSTEP 9: Cleanup test file\n";
    $deleteResult = Storage::disk('public')->delete($fileName);
    echo "  Delete result: " . ($deleteResult ? 'SUCCESS' : 'FAILED') . "\n";
}

echo "\n=== DIAGNOSTIC COMPLETE ===\n";

tenancy()->end();

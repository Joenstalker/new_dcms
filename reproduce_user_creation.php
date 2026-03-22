<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Tenant;
use App\Models\User;
use App\Models\PendingRegistration;
use Illuminate\Support\Facades\DB;

$tenantId = 'joenil';
$tenant = Tenant::find($tenantId);
$registration = PendingRegistration::where('subdomain', $tenantId)->first();

if (!$tenant || !$registration) {
    echo "Tenant or Registration not found.\n";
    exit;
}

echo "=== ATTEMPTING MANUAL USER CREATION FOR JOENIL ===\n";

try {
    tenancy()->initialize($tenant);
    
    echo "Current Database: " . DB::connection()->getDatabaseName() . "\n";
    
    // Check if table exists
    if (!Schema::hasTable('users')) {
        echo "ERROR: 'users' table DOES NOT EXIST in tenant database.\n";
        exit;
    }
    
    $userData = [
        'name' => $registration->first_name . ' ' . $registration->last_name,
        'email' => $registration->email,
        'password' => $registration->password,
    ];
    
    echo "Creating user with data: " . json_encode($userData) . "\n";
    
    $user = User::create($userData);
    
    echo "SUCCESS! User created with ID: " . $user->id . "\n";
    
    // Assign role
    if ($user) {
        $user->assignRole('Owner');
        echo "Role 'Owner' assigned successfully.\n";
    }
    
    tenancy()->end();
} catch (\Exception $e) {
    echo "CAUGHT EXCEPTION: " . $e->getMessage() . "\n";
    echo "STACK TRACE: \n" . $e->getTraceAsString() . "\n";
}

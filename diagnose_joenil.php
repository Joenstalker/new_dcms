<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Tenant;
use App\Models\User;
use App\Models\PendingRegistration;
use Illuminate\Support\Facades\DB;

echo "=== DIAGNOSTIC: JOENIL TENANT ===\n\n";

$tenantId = 'joenil';
$tenant = Tenant::find($tenantId);

if (!$tenant) {
    echo "Tenant '$tenantId' NOT FOUND in central DB.\n";
} else {
    echo "Tenant ID: " . $tenant->id . "\n";
    echo "Status: " . ($tenant->status ?? 'unknown') . "\n";
    echo "Database: " . $tenant->database_name . "\n";
    
    try {
        tenancy()->initialize($tenant);
        $userCount = User::count();
        echo "Users in Tenant DB: " . $userCount . "\n";
        
        if ($userCount > 0) {
            $users = User::all();
            foreach ($users as $user) {
                echo " - " . $user->email . " (Created at: " . $user->created_at . ")\n";
            }
        } else {
            echo " [!] CRITICAL: No users found in tenant database.\n";
        }
        tenancy()->end();
    } catch (\Exception $e) {
        echo "ERROR initializing tenancy: " . $e->getMessage() . "\n";
    }
}

echo "\n--- PENDING REGISTRATION INFO ---\n";
$registration = PendingRegistration::where('subdomain', $tenantId)->first();

if (!$registration) {
    echo "No PendingRegistration found for subdomain '$tenantId'.\n";
} else {
    echo "Registration ID: " . $registration->id . "\n";
    echo "Status: " . $registration->status . "\n";
    echo "Email: " . $registration->email . "\n";
    echo "Subdomain: " . $registration->subdomain . "\n";
    echo "Approved At: " . ($registration->approved_at ?? 'Not approved yet') . "\n";
    echo "Password format: " . (empty($registration->password) ? 'EMPTY' : 'HAS PASSWORD') . "\n";
}

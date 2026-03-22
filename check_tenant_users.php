<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$tenantId = 'levy';
$tenant = App\Models\Tenant::where('id', $tenantId)->first();

if ($tenant) {
    echo "Tenant: " . $tenant->id . " - DB: " . $tenant->database_name . "\n";
    tenancy()->initialize($tenant);
    $users = App\Models\User::all();
    echo "User count in tenant DB: " . $users->count() . "\n";
    foreach ($users as $user) {
        echo "- " . $user->email . " (Created at: " . $user->created_at . ")\n";
    }
} else {
    echo "Tenant not found.\n";
}

<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$tenant = App\Models\Tenant::first();
if ($tenant) {
    tenancy()->initialize($tenant);
    echo "Tenant Table Raw: \n";
    print_r($tenant->getAttributes()['enabled_features'] ?? 'null');
    echo "\n\nTenant Model Cast: \n";
    print_r($tenant->enabled_features);
    echo "\n\nBranding Table: \n";
    print_r(App\Services\TenantBrandingService::get('enabled_features'));
}
else {
    echo "No tenant found.";
}

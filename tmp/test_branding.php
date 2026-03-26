<?php

use App\Models\Tenant;

$tenant = Tenant::first();
if (!$tenant) {
    echo "No tenant found.\n";
    exit;
}

echo "Tenant: " . $tenant->name . "\n";
echo "Branding Color: " . ($tenant->branding_color ?? 'NULL') . "\n";
echo "Can Customize: " . ($tenant->canCustomizeBranding() ? 'YES' : 'NO') . "\n";

// Test update
$tenant->branding_color = '#FF0000';
$tenant->save();
$tenant->refresh();

echo "Updated Branding Color: " . $tenant->branding_color . "\n";

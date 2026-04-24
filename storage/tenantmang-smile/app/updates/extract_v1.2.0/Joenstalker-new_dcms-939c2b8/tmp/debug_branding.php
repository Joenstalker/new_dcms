<?php

use App\Models\SystemSetting;
use App\Models\Tenant;

echo "System Settings:\n";
echo "Primary Color: " . SystemSetting::get('primary_color', '#0ea5e9') . "\n";
echo "Platform Name: " . SystemSetting::get('platform_name', 'DCMS') . "\n";

$tenant = Tenant::first();
if ($tenant) {
    echo "\nFirst Tenant:\n";
    echo "ID: " . $tenant->id . "\n";
    echo "Name: " . $tenant->name . "\n";
    echo "Branding Color: " . ($tenant->branding_color ?? 'NULL') . "\n";
    echo "canCustomizeBranding: " . ($tenant->canCustomizeBranding() ? 'YES' : 'NO') . "\n";
}

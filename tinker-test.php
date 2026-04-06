<?php
$tenant = App\Models\Tenant::find('joensmile');
echo "Tenant array output for database_name: " . ($tenant->toArray()['database_name'] ?? 'NOT FOUND') . "\n";

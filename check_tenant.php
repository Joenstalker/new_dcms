<?php

require 'vendor/autoload.php';

$app = require 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$tenant = \App\Models\Tenant::find('junjunsmile');

if ($tenant) {
    echo 'Tenant ID: ' . $tenant->id . PHP_EOL;
    echo 'Tenant Name: ' . $tenant->name . PHP_EOL;
    echo 'Database Name: ' . $tenant->database_name . PHP_EOL;
    echo 'Database Connection: ' . $tenant->database_connection . PHP_EOL;
} else {
    echo 'Tenant not found' . PHP_EOL;
}

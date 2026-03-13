<?php

require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Stancl\Tenancy\Database\Models\Domain;

echo "Updating tenant domains...\n";

foreach (Domain::all() as $domain) {
    if (str_contains($domain->domain, '.dcms.test')) {
        $newDomain = str_replace('.dcms.test', '.localhost', $domain->domain);
        $domain->update(['domain' => $newDomain]);
        echo "Updated: {$domain->domain} -> {$newDomain}\n";
    }
}

echo "Domain update complete.\n";

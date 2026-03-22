<?php
$file = 'd:/dentistmng/dcms_new/dcms/database/seeders/FeatureSeeder.php';
$content = file_get_contents($file);

// Basic Plan Update
if (!str_contains($content, "'max_storage_mb' => ['value_numeric' => 500]")) {
    $content = preg_replace(
        '/(\$basicFeatures\s*=\s*\[.*?)(\s*\];)/s',
        "$1    'max_storage_mb' => ['value_numeric' => 500],\n        $2",
        $content
    );
}

// Pro Plan Update
if (!str_contains($content, "'max_storage_mb' => ['value_numeric' => 5000]")) {
    $content = preg_replace(
        '/(\$proFeatures\s*=\s*\[.*?)(\s*\];)/s',
        "$1    'max_storage_mb' => ['value_numeric' => 5000],\n        $2",
        $content
    );
}

file_put_contents($file, $content);
echo "Seeder updated successfully.\n";

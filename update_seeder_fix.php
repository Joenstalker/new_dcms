<?php
$file = 'd:/dentistmng/dcms_new/dcms/database/seeders/FeatureSeeder.php';
$content = file_get_contents($file);

// Add to basicFeatures if missing
if (!str_contains($content, "'max_storage_mb' => ['value_numeric' => 500]")) {
    $content = str_replace(
        "'multi_branch' => ['value_boolean' => false],\n        ];",
        "'multi_branch' => ['value_boolean' => false],\n            'max_storage_mb' => ['value_numeric' => 500],\n        ];",
        $content
    );
}

// Add to proFeatures if missing
if (!str_contains($content, "'max_storage_mb' => ['value_numeric' => 5000]")) {
    $content = str_replace(
        "'multi_branch' => ['value_boolean' => false],\n        ];",
        "'multi_branch' => ['value_boolean' => false],\n            'max_storage_mb' => ['value_numeric' => 5000],\n        ];",
        $content
    );
}

file_put_contents($file, $content);
echo "Seeder updated successfully.\n";

<?php
$file = 'app/Http/Controllers/Tenant/SettingsController.php';
$content = file_get_contents($file);

$old = <<<PHP
        // Decode JSON strings if sent via multipart/form-data
        foreach (['font_family', 'enabled_features', 'landing_page_config', 'portal_config', 'operating_hours'] as \$field) {
            if (is_string(\$request->input(\$field)) && (str_starts_with(\$request->input(\$field), '[') || str_starts_with(\$request->input(\$field), '{'))) {
                \$decoded = json_decode(\$request->input(\$field), true);
                if (json_last_error() === JSON_ERROR_NONE) {
                    \$request->merge([\$field => \$decoded]);
                }
            }
        }
PHP;

$new = <<<PHP
        // Decode JSON strings and gracefully force arrays from Inertia FormData
        foreach (['font_family', 'enabled_features', 'landing_page_config', 'portal_config', 'operating_hours'] as \$field) {
            \$value = \$request->input(\$field);
            if (is_array(\$value) || is_null(\$value)) {
                continue;
            }
            if (is_string(\$value)) {
                \$trimmed = trim(\$value);
                if (in_array(\$trimmed, ['', 'null', 'undefined', '[]', '{}'])) {
                    \$request->merge([\$field => []]);
                } elseif (str_starts_with(\$trimmed, '[') || str_starts_with(\$trimmed, '{')) {
                    \$decoded = json_decode(\$trimmed, true);
                    \$request->merge([\$field => (json_last_error() === JSON_ERROR_NONE && is_array(\$decoded)) ? \$decoded : []]);
                } else {
                    \$request->merge([\$field => array_filter(array_map('trim', explode(',', \$trimmed)))]);
                }
            } else {
                \$request->merge([\$field => [\$value]]);
            }
        }
        \Illuminate\Support\Facades\Log::info("BRANDING PAYLOAD [" . \$request->method() . "]", [
            'enabled_features' => \$request->input('enabled_features'),
            'type' => gettype(\$request->input('enabled_features'))
        ]);
PHP;

// Normalize line endings for reliable replacement
$old = str_replace("\r\n", "\n", $old);
$new = str_replace("\r\n", "\n", $new);
$content = str_replace("\r\n", "\n", $content);

if (strpos($content, $old) !== false) {
    $content = str_replace($old, $new, $content);
    file_put_contents($file, $content);
    echo "SUCCESS: Replaced string precisely.\n";
}
else {
    echo "FAILED: Could not find old string in file.\n";
}

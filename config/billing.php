<?php

return [
    'overage' => [
        'enabled' => filter_var(env('BILLING_OVERAGE_ENABLED', true), FILTER_VALIDATE_BOOL),
        'dry_run' => filter_var(env('BILLING_OVERAGE_DRY_RUN', env('APP_ENV') !== 'production'), FILTER_VALIDATE_BOOL),
        'default_max_storage_mb' => (int) env('BILLING_DEFAULT_MAX_STORAGE_MB', 500),
        'default_max_bandwidth_mb' => (int) env('BILLING_DEFAULT_MAX_BANDWIDTH_MB', 2048),
    ],
];

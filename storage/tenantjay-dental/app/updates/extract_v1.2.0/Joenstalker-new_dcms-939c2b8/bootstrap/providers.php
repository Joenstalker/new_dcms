<?php

use App\Providers\AppServiceProvider;

return [
    AppServiceProvider::class ,
    App\Providers\TenancyServiceProvider::class ,
    App\Providers\TenantDatabaseServiceProvider::class ,
    App\Providers\SystemSettingsServiceProvider::class ,
];


<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Database-seeded admin (AdminUserSeeder)
    |--------------------------------------------------------------------------
    |
    | Define ADMIN_SEED_EMAIL and ADMIN_SEED_PASSWORD in .env — never commit real
    | credentials in seeders. ADMIN_SEED_GOOGLE_EMAIL is optional documentation
    | for which Google account to use when testing admin Google OAuth.
    |
    */
    'email' => env('ADMIN_SEED_EMAIL'),
    'password' => env('ADMIN_SEED_PASSWORD'),
    'name' => env('ADMIN_SEED_NAME', 'DCMS Admin'),
    'google_email' => env('ADMIN_SEED_GOOGLE_EMAIL'),
];

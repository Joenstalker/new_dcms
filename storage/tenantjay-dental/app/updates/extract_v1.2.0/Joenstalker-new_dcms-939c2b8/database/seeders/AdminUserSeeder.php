<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminUserSeeder extends Seeder
{
    public function run(): void
    {
        $email = config('admin_seed.email');
        $password = config('admin_seed.password');

        if (blank($email) || blank($password)) {
            $this->command?->warn('AdminUserSeeder skipped: set ADMIN_SEED_EMAIL and ADMIN_SEED_PASSWORD in .env');

            return;
        }

        User::updateOrCreate(
            ['email' => $email],
            [
                'name' => config('admin_seed.name'),
                'password' => Hash::make($password),
                'is_admin' => true,
            ]
        );
    }
}

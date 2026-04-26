<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Central Seeding
        $this->call([
            AdminUserSeeder::class,
            SubscriptionPlanSeeder::class,
            FeatureSeeder::class,
            SystemReleaseSeeder::class,
        ]);

        // Tenant Seeding
        if (tenant()) {
            $this->call([
                RolesAndPermissionsSeeder::class,
            ]);

            User::firstOrCreate(
                ['email' => 'test@example.com'],
                [
                    'name' => 'Test User',
                    'password' => bcrypt('password'),
                ]
            );

            $user = User::where('email', 'test@example.com')->first();
            if ($user) {
                $user->assignRole('Owner');
            }
        }
    }
}

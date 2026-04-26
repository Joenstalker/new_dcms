<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Database\Factories\StaffFactory;
use App\Models\User;

class TenantStaffSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(?int $count = null): void
    {
        // Generate a random number of staff members between 1 and 50 if count is not provided
        $count = $count ?? rand(1, 50);
        
        $this->command?->info("Seeding {$count} staff members...");

        for ($i = 0; $i < $count; $i++) {
            $factory = StaffFactory::new();
            
            // Alternate between Dentist and Assistant roles
            if ($i % 2 === 0) {
                $factory->dentist()->create();
            } else {
                $factory->assistant()->create();
            }
        }

        $this->command?->info("Successfully seeded staff members.");
    }
}

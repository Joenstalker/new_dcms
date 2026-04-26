<?php

namespace Database\Seeders;

use App\Models\Patient;
use Illuminate\Database\Seeder;

class TenantPatientSeeder extends Seeder
{
    public function run(int $count = 10): void
    {
        $count = max(1, min($count, 50));

        Patient::factory()
            ->count($count)
            ->create();
    }
}

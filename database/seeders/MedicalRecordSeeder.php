<?php

namespace Database\Seeders;

use App\Models\MedicalRecord;
use Database\Factories\MedicalRecordFactory;
use Illuminate\Database\Seeder;

class MedicalRecordSeeder extends Seeder
{
    public function run(): void
    {
        $defaults = MedicalRecordFactory::defaultChecklist();

        foreach ($defaults as $item) {
            MedicalRecord::updateOrCreate(
                ['name' => $item['name']],
                MedicalRecord::factory()
                    ->make([
                        'name' => $item['name'],
                        'description' => $item['description'],
                        'is_active' => true,
                        'sort_order' => $item['sort_order'],
                    ])
                    ->toArray()
            );
        }
    }
}

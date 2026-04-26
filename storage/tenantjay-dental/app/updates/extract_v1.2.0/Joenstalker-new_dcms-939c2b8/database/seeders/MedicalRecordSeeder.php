<?php

namespace Database\Seeders;

use App\Models\MedicalRecord;
use Illuminate\Database\Seeder;

class MedicalRecordSeeder extends Seeder
{
    public function run(): void
    {
        $defaults = [
            ['name' => 'Diabetes', 'description' => 'Patient has a known history of diabetes.', 'sort_order' => 10],
            ['name' => 'Heart Condition', 'description' => 'Patient reports cardiovascular disease or related condition.', 'sort_order' => 20],
            ['name' => 'Asthma', 'description' => 'Patient has asthma or recurring breathing concerns.', 'sort_order' => 30],
            ['name' => 'Allergies', 'description' => 'Patient has known allergies (medicine, food, or environmental).', 'sort_order' => 40],
            ['name' => 'Hypertension', 'description' => 'Patient has elevated blood pressure or is under treatment for it.', 'sort_order' => 50],
            ['name' => 'Epilepsy', 'description' => 'Patient has a history of seizures or epilepsy.', 'sort_order' => 60],
        ];

        foreach ($defaults as $item) {
            MedicalRecord::updateOrCreate(
                ['name' => $item['name']],
                [
                    'description' => $item['description'],
                    'is_active' => true,
                    'sort_order' => $item['sort_order'],
                ]
            );
        }
    }
}

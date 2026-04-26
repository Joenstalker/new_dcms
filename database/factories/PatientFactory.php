<?php

namespace Database\Factories;

use App\Models\Branch;
use App\Models\Patient;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Patient>
 */
class PatientFactory extends Factory
{
    protected $model = Patient::class;

    public function definition(): array
    {
        $dateOfBirth = $this->faker->dateTimeBetween('-75 years', '-1 year');
        $firstVisitAt = $this->faker->dateTimeBetween('-3 years', 'now');
        $lastRecallAt = $this->faker->optional(0.65)->dateTimeBetween($firstVisitAt, '+6 months');
        $age = now()->diffInYears($dateOfBirth);
        $patientType = $age <= 12
            ? 'pediatric'
            : ($age <= 17 ? 'adolescent' : 'adult');
        $initialBalance = $this->faker->randomFloat(2, 0, 2500);
        $phone = '09' . $this->faker->numerify('#########');
        $branchId = Branch::query()->value('id');

        return [
            'tenant_id' => tenant()?->getTenantKey(),
            'branch_id' => $branchId,
            'first_name' => $this->faker->firstName(),
            'last_name' => $this->faker->lastName(),
            'email' => $this->faker->optional(0.8)->safeEmail(),
            'phone' => $phone,
            'date_of_birth' => $dateOfBirth,
            'gender' => $this->faker->randomElement(['Male', 'Female']),
            'address' => $this->faker->address(),
            'medical_history' => $this->faker->optional(0.35)->sentence(),
            'operation_history' => $this->faker->optional(0.25)->sentence(),
            'patient_type' => $patientType,
            'tags' => $this->faker->optional(0.5)->randomElements(
                ['ortho', 'senior', 'vip', 'new', 'recall'],
                $this->faker->numberBetween(1, 2)
            ),
            'first_visit_at' => $firstVisitAt,
            'last_recall_at' => $lastRecallAt,
            'last_visit_time' => $this->faker->dateTimeBetween($firstVisitAt, 'now'),
            'initial_balance' => $initialBalance,
            'balance' => $initialBalance,
        ];
    }
}

<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class StaffFactory extends Factory
{
    protected $model = User::class;

    public function definition(): array
    {
        return [
            'name' => $this->faker->name(),
            'email' => $this->faker->unique()->safeEmail(),
            'email_verified_at' => now(),
            'password' => Hash::make('password'),
            'remember_token' => Str::random(10),
            'requires_password_change' => false,
            'calendar_color' => $this->faker->safeHexColor(),
        ];
    }

    /**
     * Configure the factory to create a dentist.
     */
    public function dentist(): static
    {
        return $this->afterCreating(function (User $user) {
            Role::firstOrCreate(['name' => 'Dentist']);
            $user->assignRole('Dentist');
            
            // Assign default permissions if needed, similar to StaffController
            $this->assignDefaultPermissions($user, 'Dentist');
        });
    }

    /**
     * Configure the factory to create an assistant.
     */
    public function assistant(): static
    {
        return $this->afterCreating(function (User $user) {
            Role::firstOrCreate(['name' => 'Assistant']);
            $user->assignRole('Assistant');
            
            $this->assignDefaultPermissions($user, 'Assistant');
        });
    }

    /**
     * Assign default permissions to the user based on role.
     */
    private function assignDefaultPermissions(User $user, string $role): void
    {
        $defaultPermissions = [
            'Dentist' => [
                'view dashboard',
                'manage appointments',
                'view patients',
                'create patients',
                'edit patients',
                'view medical records',
                'create medical records',
                'edit medical records',
                'view progress notes',
                'create progress notes',
                'edit progress notes',
            ],
            'Assistant' => [
                'view dashboard',
                'manage appointments',
                'view patients',
                'view medical records',
                'view progress notes',
            ]
        ];

        $permissions = $defaultPermissions[$role] ?? ['view dashboard'];
        
        foreach ($permissions as $permissionName) {
            Permission::firstOrCreate(['name' => $permissionName]);
        }

        $user->syncPermissions($permissions);
    }
}

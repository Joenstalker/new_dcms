<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class RolesAndPermissionsSeeder extends Seeder
{
    public function run(): void
    {
        // Reset cached roles and permissions
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // Create Permissions
        $permissions = [
            'manage clinic',
            'manage settings',
            'manage subscription',
            'view reports',
            'access public booking',
            
            // Services
            'view services', 'create services', 'edit services', 'delete services', 'approve services',
            
            // Patients
            'view patients', 'create patients', 'edit patients', 'delete patients',
            
            // Appointments
            'view appointments', 'create appointments', 'edit appointments', 'delete appointments',
            
            // Treatments
            'view treatments', 'create treatments',
            
            // Billing
            'view billing', 'create billing', 'edit billing',
            
            // Staff
            'view staff', 'create staff', 'edit staff', 'delete staff',
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission]);
        }

        // Create Roles and Assign Permissions
        
        // Owner (Admin)
        $owner = Role::firstOrCreate(['name' => 'Owner']);
        $owner->givePermissionTo(Permission::all());

        // Dentist
        $dentist = Role::firstOrCreate(['name' => 'Dentist']);
        $dentist->givePermissionTo([
            'view appointments', 'create appointments', 'edit appointments', 'delete appointments',
            'view patients', 'create patients', 'edit patients',
            'view treatments', 'create treatments',
            'view reports',
            'view services', 'create services', 'edit services', 'approve services',
        ]);

        // Assistant
        $assistant = Role::firstOrCreate(['name' => 'Assistant']);
        $assistant->givePermissionTo([
            'view appointments', 'create appointments',
            'view patients', 'create patients',
            'view billing', 'create billing',
            'view services', 'create services',
        ]);

        // Patient
        $patient = Role::firstOrCreate(['name' => 'Patient']);
        $patient->givePermissionTo([
            'access public booking',
        ]);
    }
}

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
            'manage settings',
            'manage subscription',
            'access billing portal',
            'manage security settings',
            'manage system features',
            'manage system updates',
            'view analytics',
            'manage branches',
            'view reports',
            'access public booking',
            'view concerns', 'manage concerns', 'reply concerns',
            
            // Services
            'view services', 'create services', 'edit services', 'delete services', 'approve services',
            
            // Patients
            'view patients', 'create patients', 'edit patients', 'delete patients',
            
            // Appointments
            'view appointments', 'create appointments', 'edit appointments', 'delete appointments',
            
            // Treatments
            'view treatments', 'create treatments', 'edit treatments', 'delete treatments',

            // Progress notes (separate RBAC controls from treatments)
            'progress notes', 'view progress notes', 'create progress notes', 'edit progress notes', 'delete progress notes',

            // Medical records (booking/checklist templates)
            'view medical records', 'create medical records', 'edit medical records', 'delete medical records',
            
            // Billing
            'view billing', 'create billing', 'edit billing',
            
            // Staff
            'view staff', 'create staff', 'edit staff', 'delete staff',

            // Dashboard
            'view dashboard',

            // Staff Settings (personal, permission-gated per section)
            'manage own calendar', 'manage own notifications', 'manage own working hours',

            // Custom Branding (delegatable to Assistant)
            'manage clinic branding',

            // Activity Logs
            'view activity logs',

            // Support Chat
            'access support chat',
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission]);
        }

        // Cleanup legacy permission no longer used by tenant modules.
        $legacyManageClinic = Permission::where('name', 'manage clinic')->first();
        if ($legacyManageClinic) {
            $legacyManageClinic->roles()->detach();
            $legacyManageClinic->users()->detach();
            $legacyManageClinic->delete();
        }

        // Create Roles (Owner retains all permissions)
        $owner = Role::firstOrCreate(['name' => 'Owner']);
        $owner->givePermissionTo(Permission::all());

        // Dentist, Assistant, Patient remain as Role identifiers
        Role::firstOrCreate(['name' => 'Dentist']);
        Role::firstOrCreate(['name' => 'Assistant']);
        
        $patient = Role::firstOrCreate(['name' => 'Patient']);
        $patient->givePermissionTo(['access public booking']);
    }
}

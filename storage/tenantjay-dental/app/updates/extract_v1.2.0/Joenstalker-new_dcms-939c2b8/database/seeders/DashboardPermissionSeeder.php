<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class DashboardPermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Reset cached roles and permissions
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // Create Dashboard permission
        $permission = Permission::firstOrCreate(['name' => 'view dashboard']);

        // Give to Owner role
        $ownerRole = Role::where('name', 'Owner')->first();
        if ($ownerRole) {
            $ownerRole->givePermissionTo($permission);
        }
    }
}

<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Illuminate\Support\Facades\Log;

class DumpPermissionsSeeder extends Seeder
{
    public function run(): void
    {
        $tenantId = tenant('id') ?? 'Central';
        Log::info("=== FULL PERMISSION DUMP for Tenant: {$tenantId} ===");

        $permissions = Permission::all();
        foreach ($permissions as $p) {
            Log::info("ID: {$p->id} | NAME: '{$p->name}' | GUARD: '{$p->guard_name}'");
        }
    }
}

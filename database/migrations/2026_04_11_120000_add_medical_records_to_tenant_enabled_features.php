<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $tenants = DB::table('tenants')
            ->select(['id', 'enabled_features'])
            ->get();

        foreach ($tenants as $tenant) {
            $enabled = $tenant->enabled_features;

            if (is_string($enabled) && $enabled !== '') {
                $decoded = json_decode($enabled, true);
                $enabled = is_array($decoded) ? $decoded : [];
            }

            if (! is_array($enabled)) {
                $enabled = [];
            }

            if (in_array('medical_records', $enabled, true)) {
                continue;
            }

            $enabled[] = 'medical_records';

            DB::table('tenants')
                ->where('id', $tenant->id)
                ->update([
                    'enabled_features' => json_encode(array_values(array_unique($enabled))),
                ]);
        }
    }

    public function down(): void
    {
        $tenants = DB::table('tenants')
            ->select(['id', 'enabled_features'])
            ->get();

        foreach ($tenants as $tenant) {
            $enabled = $tenant->enabled_features;

            if (is_string($enabled) && $enabled !== '') {
                $decoded = json_decode($enabled, true);
                $enabled = is_array($decoded) ? $decoded : [];
            }

            if (! is_array($enabled)) {
                continue;
            }

            $updated = array_values(array_filter($enabled, fn ($key) => $key !== 'medical_records'));

            DB::table('tenants')
                ->where('id', $tenant->id)
                ->update([
                    'enabled_features' => json_encode($updated),
                ]);
        }
    }
};

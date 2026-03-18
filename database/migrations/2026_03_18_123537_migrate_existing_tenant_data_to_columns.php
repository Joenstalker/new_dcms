<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $tenants = \App\Models\Tenant::all();

        foreach ($tenants as $tenant) {
            // In Stancl Tenancy, the data JSON is decoded into the 'data' property
            // We can also access keys directly if they were stored in 'data'
            $data = $tenant->data;
            
            // Get domain_id if it exists
            $domain = \Stancl\Tenancy\Database\Models\Domain::where('tenant_id', $tenant->id)->first();

            $tenant->update([
                'name' => $tenant->name ?? ($data['clinic_name'] ?? ($data['name'] ?? null)),
                'owner_name' => $tenant->owner_name ?? ($data['admin_name'] ?? ($data['owner_name'] ?? null)),
                'email' => $tenant->email ?? ($data['email'] ?? ($data['admin_email'] ?? null)),
                'phone' => $tenant->phone ?? ($data['phone'] ?? null),
                'street' => $tenant->street ?? ($data['street'] ?? null),
                'barangay' => $tenant->barangay ?? ($data['barangay'] ?? null),
                'city' => $tenant->city ?? ($data['city'] ?? null),
                'province' => $tenant->province ?? ($data['province'] ?? null),
                'status' => $tenant->status ?? ($data['status'] ?? ($data['subscription_status'] ?? 'active')),
                'domain_id' => $domain ? $domain->id : null,
            ]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        //
    }
};

<?php
$tenants = DB::table('tenants')->whereNull('name')->orWhereNull('owner_name')->get();
$fixedCount = 0;

foreach ($tenants as $tenant) {
    // 1. Try to fetch from PendingRegistration first (most reliable for recent tenants like joensmile)
    $pending = DB::table('pending_registrations')->where('subdomain', $tenant->id)->first();

    $name = $tenant->name;
    $owner_name = $tenant->owner_name;
    $email = $tenant->email;
    $phone = $tenant->phone;

    if ($pending) {
        $name = $name ?? $pending->clinic_name;
        $owner_name = $owner_name ?? ($pending->first_name . ' ' . $pending->last_name);
        $email = $email ?? $pending->email;
        $phone = $phone ?? $pending->phone;
    }

    // 2. Try to fetch from 'data' JSON fallback if pending is absent
    if (!empty($tenant->data) && $tenant->data !== '{}') {
        $data = json_decode($tenant->data, true);
        if (is_array($data)) {
            $name = $name ?? ($data['name'] ?? null);
            $owner_name = $owner_name ?? ($data['owner_name'] ?? null);
            $email = $email ?? ($data['email'] ?? null);
            $phone = $phone ?? ($data['phone'] ?? null);
        }
    }

    // Update the record directly
    if ($name || $owner_name) {
        DB::table('tenants')->where('id', $tenant->id)->update([
            'name' => $name,
            'owner_name' => $owner_name,
            'email' => $email,
            'phone' => $phone
        ]);
        echo "Fixed tenant: {$tenant->id}\n";
        $fixedCount++;
    }
}

echo "Total tenants fixed: {$fixedCount}\n";

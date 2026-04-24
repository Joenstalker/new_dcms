<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Service;
use App\Models\User;

class ServiceSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Standard stancl/tenancy check
        $tenant = tenant();

        if (!$tenant) {
            // Wait, maybe it's called manually and manually bound? We can do the user's fallback
            $tenant = app()->bound('tenant') ? app('tenant') : \App\Models\Tenant::first();
        }

        if (!$tenant) {
            if ($this->command) {
                $this->command->error('No tenant found. Please create a tenant first or bind one to the container.');
            }
            return;
        }

        if ($this->command) {
            $tenantName = $tenant->name ?? 'Unknown';
            $tenantId = $tenant->id ?? $tenant->getTenantKey();
            $this->command->info("Seeding services for tenant: {$tenantName} ({$tenantId})");
        }

        // We must fetch an existing user in the tenant database to assign as the creator.
        $owner = User::role('Owner')->first() ?? User::first();
        if (!$owner && $this->command) {
            $this->command->warn("No users found to set as created_by/approved_by! Creating a dummy user is not recommended, skipping services.");
            return;
        }
        $ownerId = $owner ? $owner->id : 1;

        $services = [
            ['name' => 'Dental Consultation', 'category' => 'Consultation', 'amount' => 500, 'duration' => 15, 'auto' => true, 'status' => true],
            ['name' => 'Oral Prophylaxis (Cleaning)', 'category' => 'Preventive', 'amount' => 1200, 'duration' => 45, 'auto' => true, 'status' => true],
            ['name' => 'Deep Scaling', 'category' => 'Preventive', 'amount' => 2500, 'duration' => 60, 'auto' => true, 'status' => true],
            ['name' => 'Fluoride Treatment', 'category' => 'Preventive', 'amount' => 1000, 'duration' => 30, 'auto' => true, 'status' => true],
            ['name' => 'Pit and Fissure Sealant', 'category' => 'Preventive', 'amount' => 1200, 'duration' => 30, 'auto' => true, 'status' => true],
            ['name' => 'Simple Tooth Extraction', 'category' => 'Surgery', 'amount' => 1500, 'duration' => 30, 'auto' => true, 'status' => true],
            ['name' => 'Surgical Extraction', 'category' => 'Surgery', 'amount' => 4000, 'duration' => 60, 'auto' => false, 'status' => true],
            ['name' => 'Wisdom Tooth Extraction', 'category' => 'Surgery', 'amount' => 8000, 'duration' => 90, 'auto' => false, 'status' => true],
            ['name' => 'Temporary Filling', 'category' => 'Restoration', 'amount' => 800, 'duration' => 30, 'auto' => true, 'status' => true],
            ['name' => 'Light Cure Filling', 'category' => 'Restoration', 'amount' => 1500, 'duration' => 45, 'auto' => true, 'status' => true],
            ['name' => 'Composite Restoration', 'category' => 'Restoration', 'amount' => 2500, 'duration' => 60, 'auto' => true, 'status' => true],
            ['name' => 'Glass Ionomer Filling', 'category' => 'Restoration', 'amount' => 1800, 'duration' => 45, 'auto' => true, 'status' => true],
            ['name' => 'Root Canal Treatment (Anterior)', 'category' => 'Endodontics', 'amount' => 7000, 'duration' => 90, 'auto' => false, 'status' => true],
            ['name' => 'Root Canal Treatment (Premolar)', 'category' => 'Endodontics', 'amount' => 9000, 'duration' => 120, 'auto' => false, 'status' => true],
            ['name' => 'Root Canal Treatment (Molar)', 'category' => 'Endodontics', 'amount' => 12000, 'duration' => 150, 'auto' => false, 'status' => true],
            ['name' => 'Dental Crown (PFM)', 'category' => 'Prosthodontics', 'amount' => 15000, 'duration' => 120, 'auto' => false, 'status' => true],
            ['name' => 'Dental Crown (Zirconia)', 'category' => 'Prosthodontics', 'amount' => 25000, 'duration' => 120, 'auto' => false, 'status' => true],
            ['name' => 'Post and Core', 'category' => 'Prosthodontics', 'amount' => 6000, 'duration' => 60, 'auto' => false, 'status' => true],
            ['name' => 'Dental Bridge (Per Unit)', 'category' => 'Prosthodontics', 'amount' => 15000, 'duration' => 120, 'auto' => false, 'status' => true],
            ['name' => 'Complete Denture (Upper)', 'category' => 'Prosthodontics', 'amount' => 25000, 'duration' => 120, 'auto' => false, 'status' => true],
            ['name' => 'Complete Denture (Lower)', 'category' => 'Prosthodontics', 'amount' => 25000, 'duration' => 120, 'auto' => false, 'status' => true],
            ['name' => 'Partial Denture (Acrylic)', 'category' => 'Prosthodontics', 'amount' => 15000, 'duration' => 120, 'auto' => false, 'status' => true],
            ['name' => 'Partial Denture (Flexible)', 'category' => 'Prosthodontics', 'amount' => 20000, 'duration' => 120, 'auto' => false, 'status' => true],
            ['name' => 'Dentures Adjustment', 'category' => 'Prosthodontics', 'amount' => 1000, 'duration' => 30, 'auto' => true, 'status' => true],
            ['name' => 'Tooth Whitening (In-Office)', 'category' => 'Cosmetic', 'amount' => 12000, 'duration' => 90, 'auto' => true, 'status' => true],
            ['name' => 'Home Whitening Kit', 'category' => 'Cosmetic', 'amount' => 8000, 'duration' => 30, 'auto' => true, 'status' => true],
            ['name' => 'Dental Veneers', 'category' => 'Cosmetic', 'amount' => 20000, 'duration' => 90, 'auto' => false, 'status' => true],
            ['name' => 'Orthodontic Braces (Metal)', 'category' => 'Orthodontics', 'amount' => 50000, 'duration' => 60, 'auto' => false, 'status' => true],
            ['name' => 'Ceramic Braces', 'category' => 'Orthodontics', 'amount' => 70000, 'duration' => 60, 'auto' => false, 'status' => true],
            ['name' => 'Self-Ligating Braces', 'category' => 'Orthodontics', 'amount' => 90000, 'duration' => 60, 'auto' => false, 'status' => true],
            ['name' => 'Braces Adjustment', 'category' => 'Orthodontics', 'amount' => 1500, 'duration' => 30, 'auto' => true, 'status' => true],
            ['name' => 'Retainer', 'category' => 'Orthodontics', 'amount' => 8000, 'duration' => 30, 'auto' => true, 'status' => true],
            ['name' => 'Dental X-ray (Periapical)', 'category' => 'Diagnostics', 'amount' => 500, 'duration' => 10, 'auto' => true, 'status' => true],
            ['name' => 'Panoramic X-ray', 'category' => 'Diagnostics', 'amount' => 1500, 'duration' => 15, 'auto' => true, 'status' => true],
            ['name' => 'Cephalometric X-ray', 'category' => 'Diagnostics', 'amount' => 1500, 'duration' => 15, 'auto' => true, 'status' => true],
            ['name' => 'TMJ Evaluation', 'category' => 'Diagnostics', 'amount' => 2000, 'duration' => 30, 'auto' => true, 'status' => true],
            ['name' => 'Gum Surgery', 'category' => 'Periodontics', 'amount' => 12000, 'duration' => 90, 'auto' => false, 'status' => true],
            ['name' => 'Frenectomy', 'category' => 'Surgery', 'amount' => 8000, 'duration' => 60, 'auto' => false, 'status' => true],
            ['name' => 'Dental Implant Placement', 'category' => 'Implant', 'amount' => 80000, 'duration' => 120, 'auto' => false, 'status' => true],
            ['name' => 'Implant Crown', 'category' => 'Implant', 'amount' => 30000, 'duration' => 90, 'auto' => false, 'status' => true],
            ['name' => 'Bone Grafting', 'category' => 'Implant', 'amount' => 20000, 'duration' => 120, 'auto' => false, 'status' => true],
            ['name' => 'Sinus Lift', 'category' => 'Implant', 'amount' => 35000, 'duration' => 120, 'auto' => false, 'status' => true],
            ['name' => 'Mouth Guard', 'category' => 'Appliance', 'amount' => 5000, 'duration' => 30, 'auto' => true, 'status' => true],
            ['name' => 'Night Guard', 'category' => 'Appliance', 'amount' => 7000, 'duration' => 30, 'auto' => true, 'status' => true],
            ['name' => 'Tooth Splinting', 'category' => 'Restoration', 'amount' => 5000, 'duration' => 60, 'auto' => false, 'status' => true],
            ['name' => 'Emergency Treatment', 'category' => 'Emergency', 'amount' => 1500, 'duration' => 30, 'auto' => true, 'status' => true],
            ['name' => 'Dry Socket Treatment', 'category' => 'Emergency', 'amount' => 2000, 'duration' => 30, 'auto' => true, 'status' => true],
            ['name' => 'Re-cement Crown', 'category' => 'Restoration', 'amount' => 1500, 'duration' => 30, 'auto' => true, 'status' => true],
            ['name' => 'Dental Polishing', 'category' => 'Preventive', 'amount' => 800, 'duration' => 30, 'auto' => true, 'status' => true],
            ['name' => 'Medical Certificate Issuance', 'category' => 'Document', 'amount' => 300, 'duration' => 10, 'auto' => true, 'status' => true],
        ];

        foreach ($services as $service) {
            Service::updateOrCreate(
            [
                // Found by name
                'name' => $service['name'],
            ],
            [
                // Mapped to actual schema (category, colors, etc are not natively supported)
                'description' => $service['name'] . ' procedure | ' . $service['category'],
                'price' => $service['amount'],
                'status' => 'approved',
                'created_by' => $ownerId,
                'approved_by' => $ownerId,
            ]
            );
        }

        if ($this->command) {
            $this->command->info('Services seeded successfully.');
        }
    }
}

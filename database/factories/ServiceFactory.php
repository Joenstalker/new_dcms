<?php

namespace Database\Factories;

use App\Models\Service;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Service>
 */
class ServiceFactory extends Factory
{
    protected $model = Service::class;

    public function definition(): array
    {
        return [
            'tenant_id' => tenant()?->getTenantKey(),
            'name' => ucfirst($this->faker->unique()->words(3, true)),
            'description' => $this->faker->sentence(),
            'price' => $this->faker->randomFloat(2, 300, 25000),
            'created_by' => User::query()->value('id') ?? 1,
        ];
    }

    public static function defaultCatalog(): array
    {
        return [
            ['name' => 'Dental Consultation', 'category' => 'Consultation', 'amount' => 500],
            ['name' => 'Oral Prophylaxis (Cleaning)', 'category' => 'Preventive', 'amount' => 1200],
            ['name' => 'Deep Scaling', 'category' => 'Preventive', 'amount' => 2500],
            ['name' => 'Fluoride Treatment', 'category' => 'Preventive', 'amount' => 1000],
            ['name' => 'Pit and Fissure Sealant', 'category' => 'Preventive', 'amount' => 1200],
            ['name' => 'Simple Tooth Extraction', 'category' => 'Surgery', 'amount' => 1500],
            ['name' => 'Surgical Extraction', 'category' => 'Surgery', 'amount' => 4000],
            ['name' => 'Wisdom Tooth Extraction', 'category' => 'Surgery', 'amount' => 8000],
            ['name' => 'Temporary Filling', 'category' => 'Restoration', 'amount' => 800],
            ['name' => 'Light Cure Filling', 'category' => 'Restoration', 'amount' => 1500],
            ['name' => 'Composite Restoration', 'category' => 'Restoration', 'amount' => 2500],
            ['name' => 'Glass Ionomer Filling', 'category' => 'Restoration', 'amount' => 1800],
            ['name' => 'Root Canal Treatment (Anterior)', 'category' => 'Endodontics', 'amount' => 7000],
            ['name' => 'Root Canal Treatment (Premolar)', 'category' => 'Endodontics', 'amount' => 9000],
            ['name' => 'Root Canal Treatment (Molar)', 'category' => 'Endodontics', 'amount' => 12000],
            ['name' => 'Dental Crown (PFM)', 'category' => 'Prosthodontics', 'amount' => 15000],
            ['name' => 'Dental Crown (Zirconia)', 'category' => 'Prosthodontics', 'amount' => 25000],
            ['name' => 'Post and Core', 'category' => 'Prosthodontics', 'amount' => 6000],
            ['name' => 'Dental Bridge (Per Unit)', 'category' => 'Prosthodontics', 'amount' => 15000],
            ['name' => 'Complete Denture (Upper)', 'category' => 'Prosthodontics', 'amount' => 25000],
            ['name' => 'Complete Denture (Lower)', 'category' => 'Prosthodontics', 'amount' => 25000],
            ['name' => 'Partial Denture (Acrylic)', 'category' => 'Prosthodontics', 'amount' => 15000],
            ['name' => 'Partial Denture (Flexible)', 'category' => 'Prosthodontics', 'amount' => 20000],
            ['name' => 'Dentures Adjustment', 'category' => 'Prosthodontics', 'amount' => 1000],
            ['name' => 'Tooth Whitening (In-Office)', 'category' => 'Cosmetic', 'amount' => 12000],
            ['name' => 'Home Whitening Kit', 'category' => 'Cosmetic', 'amount' => 8000],
            ['name' => 'Dental Veneers', 'category' => 'Cosmetic', 'amount' => 20000],
            ['name' => 'Orthodontic Braces (Metal)', 'category' => 'Orthodontics', 'amount' => 50000],
            ['name' => 'Ceramic Braces', 'category' => 'Orthodontics', 'amount' => 70000],
            ['name' => 'Self-Ligating Braces', 'category' => 'Orthodontics', 'amount' => 90000],
            ['name' => 'Braces Adjustment', 'category' => 'Orthodontics', 'amount' => 1500],
            ['name' => 'Retainer', 'category' => 'Orthodontics', 'amount' => 8000],
            ['name' => 'Dental X-ray (Periapical)', 'category' => 'Diagnostics', 'amount' => 500],
            ['name' => 'Panoramic X-ray', 'category' => 'Diagnostics', 'amount' => 1500],
            ['name' => 'Cephalometric X-ray', 'category' => 'Diagnostics', 'amount' => 1500],
            ['name' => 'TMJ Evaluation', 'category' => 'Diagnostics', 'amount' => 2000],
            ['name' => 'Gum Surgery', 'category' => 'Periodontics', 'amount' => 12000],
            ['name' => 'Frenectomy', 'category' => 'Surgery', 'amount' => 8000],
            ['name' => 'Dental Implant Placement', 'category' => 'Implant', 'amount' => 80000],
            ['name' => 'Implant Crown', 'category' => 'Implant', 'amount' => 30000],
            ['name' => 'Bone Grafting', 'category' => 'Implant', 'amount' => 20000],
            ['name' => 'Sinus Lift', 'category' => 'Implant', 'amount' => 35000],
            ['name' => 'Mouth Guard', 'category' => 'Appliance', 'amount' => 5000],
            ['name' => 'Night Guard', 'category' => 'Appliance', 'amount' => 7000],
            ['name' => 'Tooth Splinting', 'category' => 'Restoration', 'amount' => 5000],
            ['name' => 'Emergency Treatment', 'category' => 'Emergency', 'amount' => 1500],
            ['name' => 'Dry Socket Treatment', 'category' => 'Emergency', 'amount' => 2000],
            ['name' => 'Re-cement Crown', 'category' => 'Restoration', 'amount' => 1500],
            ['name' => 'Dental Polishing', 'category' => 'Preventive', 'amount' => 800],
            ['name' => 'Medical Certificate Issuance', 'category' => 'Document', 'amount' => 300],
        ];
    }
}

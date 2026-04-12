<?php

namespace Database\Seeders;

use App\Models\Feature;
use App\Models\SubscriptionPlan;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;

class FeatureSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * Seeded features are always implementation-active and plan pivots are marked
     * as pushed so local/dev databases match production “live” state without
     * running Push Features from the admin UI.
     */
    public function run(): void
    {
        foreach ($this->featureDefinitions() as $definition) {
            Feature::updateOrCreate(
                ['key' => $definition['key']],
                $this->withActiveImplementationStatus($definition)
            );
        }

        $this->assignFeaturesToPlans();
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function featureDefinitions(): array
    {
        return [
            // === CORE FEATURES (Available to All) ===
            [
                'key' => 'qr_booking',
                'name' => 'QR Code Online Booking',
                'description' => 'Allow patients to book appointments by scanning a QR code',
                'type' => 'boolean',
                'category' => 'core',
                'sort_order' => 1,
                'is_active' => true,
            ],
            [
                'key' => 'appointment_scheduling',
                'name' => 'Appointment Scheduling',
                'description' => 'Calendar-based appointment scheduling and management',
                'type' => 'boolean',
                'category' => 'core',
                'sort_order' => 2,
                'is_active' => true,
            ],
            [
                'key' => 'patient_records',
                'name' => 'Patient Information Management',
                'description' => 'Full CRUD operations for patient records',
                'type' => 'boolean',
                'category' => 'core',
                'sort_order' => 3,
                'is_active' => true,
            ],
            [
                'key' => 'billing_pos',
                'name' => 'Billing & POS',
                'description' => 'Billing, invoicing, and point of sale functionality',
                'type' => 'boolean',
                'category' => 'core',
                'sort_order' => 4,
                'is_active' => true,
            ],
            [
                'key' => 'clinic_setup',
                'name' => 'Clinic Setup & Branding',
                'description' => 'Configure clinic information and customize branding',
                'type' => 'boolean',
                'category' => 'core',
                'sort_order' => 5,
                'is_active' => true,
            ],
            [
                'key' => 'role_based_access',
                'name' => 'Role-Based Access Control',
                'description' => 'Manage user roles: Owner, Dentist, Assistant',
                'type' => 'boolean',
                'category' => 'core',
                'sort_order' => 6,
                'is_active' => true,
            ],

            // === LIMITS ===
            [
                'key' => 'max_users',
                'name' => 'Maximum Staff Users',
                'description' => 'Maximum number of staff user accounts (Owner, Dentists, Assistants)',
                'type' => 'numeric',
                'category' => 'limits',
                'sort_order' => 10,
                'is_active' => true,
            ],
            [
                'key' => 'max_patients',
                'name' => 'Maximum Patients',
                'description' => 'Maximum number of patient records allowed',
                'type' => 'numeric',
                'category' => 'limits',
                'sort_order' => 11,
                'is_active' => true,
            ],
            [
                'key' => 'max_appointments',
                'name' => 'Maximum Appointments',
                'description' => 'Maximum number of appointments per month',
                'type' => 'numeric',
                'category' => 'limits',
                'sort_order' => 12,
                'is_active' => true,
            ],

            // === ADD-ONS ===
            [
                'key' => 'sms_notifications',
                'name' => 'SMS Appointment Notifications',
                'description' => 'Send SMS notifications for appointment reminders and confirmations',
                'type' => 'boolean',
                'category' => 'addons',
                'sort_order' => 20,
                'is_active' => true,
            ],
            [
                'key' => 'custom_branding',
                'name' => 'Custom Clinic Branding',
                'description' => 'Customize the booking page with clinic branding colors and logo',
                'type' => 'boolean',
                'category' => 'addons',
                'sort_order' => 21,
                'is_active' => true,
            ],
            [
                'key' => 'priority_support',
                'name' => 'Priority Support',
                'description' => 'Get priority response times for support requests',
                'type' => 'boolean',
                'category' => 'addons',
                'sort_order' => 22,
                'is_active' => true,
            ],

            // === REPORTS ===
            [
                'key' => 'report_level',
                'name' => 'Report Level',
                'description' => 'Level of reporting and analytics capabilities',
                'type' => 'tiered',
                'category' => 'reports',
                'options' => ['basic', 'enhanced', 'advanced'],
                'sort_order' => 30,
                'is_active' => true,
            ],
            [
                'key' => 'advanced_analytics',
                'name' => 'Advanced Analytics',
                'description' => 'Advanced analytics and performance insights',
                'type' => 'boolean',
                'category' => 'reports',
                'sort_order' => 31,
                'is_active' => true,
            ],

            // === EXPANSION ===
            [
                'key' => 'multi_branch',
                'name' => 'Multi-branch Readiness',
                'description' => 'Ability to manage multiple clinic locations',
                'type' => 'boolean',
                'category' => 'expansion',
                'sort_order' => 40,
                'is_active' => true,
            ],
            [
                'key' => 'max_storage_mb',
                'name' => 'Maximum Storage (MB)',
                'description' => 'Maximum total storage for uploaded files (documents, images, etc.)',
                'type' => 'numeric',
                'category' => 'limits',
                'sort_order' => 13,
                'is_active' => true,
            ],
        ];
    }

    /**
     * @param  array<string, mixed>  $attributes
     * @return array<string, mixed>
     */
    private function withActiveImplementationStatus(array $attributes): array
    {
        return array_merge($attributes, [
            'implementation_status' => Feature::STATUS_ACTIVE,
        ]);
    }

    private function assignFeaturesToPlans(): void
    {
        $pushedAt = Carbon::now();

        foreach ($this->planDefinitions() as $name => $data) {
            $plan = SubscriptionPlan::updateOrCreate(
                ['name' => $name],
                [
                    'price_monthly' => $data['price_monthly'],
                    'price_yearly' => $data['price_yearly'],
                    'max_users' => $data['max_users'],
                    'max_patients' => $data['max_patients'],
                    'max_appointments' => $data['max_appointments'],
                    'max_storage_mb' => $data['max_storage_mb'],
                    'has_qr_booking' => $data['features']['qr_booking']['value_boolean'] ?? false,
                    'has_sms' => $data['features']['sms_notifications']['value_boolean'] ?? false,
                ]
            );

            $this->syncPlanFeatures($plan, $data['features'], $pushedAt);
        }
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    private function planDefinitions(): array
    {
        return [
            'Basic' => [
                'price_monthly' => 1499.00,
                'price_yearly' => 14990.00,
                'max_users' => 4,
                'max_patients' => 150,
                'max_appointments' => 500,
                'max_storage_mb' => 500,
                'features' => [
                    'qr_booking' => ['value_boolean' => true],
                    'appointment_scheduling' => ['value_boolean' => true],
                    'patient_records' => ['value_boolean' => true],
                    'billing_pos' => ['value_boolean' => true],
                    'clinic_setup' => ['value_boolean' => true],
                    'role_based_access' => ['value_boolean' => true],
                    'max_users' => ['value_numeric' => 4],
                    'max_patients' => ['value_numeric' => 150],
                    'max_appointments' => ['value_numeric' => 500],
                    'sms_notifications' => ['value_boolean' => false],
                    'custom_branding' => ['value_boolean' => false],
                    'priority_support' => ['value_boolean' => false],
                    'report_level' => ['value_tier' => 'basic'],
                    'advanced_analytics' => ['value_boolean' => false],
                    'multi_branch' => ['value_boolean' => false],
                    'max_storage_mb' => ['value_numeric' => 500],
                ],
            ],
            'Pro' => [
                'price_monthly' => 2499.00,
                'price_yearly' => 24990.00,
                'max_users' => 10,
                'max_patients' => 1000,
                'max_appointments' => 2000,
                'max_storage_mb' => 5000,
                'features' => [
                    'qr_booking' => ['value_boolean' => true],
                    'appointment_scheduling' => ['value_boolean' => true],
                    'patient_records' => ['value_boolean' => true],
                    'billing_pos' => ['value_boolean' => true],
                    'clinic_setup' => ['value_boolean' => true],
                    'role_based_access' => ['value_boolean' => true],
                    'max_users' => ['value_numeric' => 10],
                    'max_patients' => ['value_numeric' => 1000],
                    'max_appointments' => ['value_numeric' => 2000],
                    'sms_notifications' => ['value_boolean' => true],
                    'custom_branding' => ['value_boolean' => true],
                    'priority_support' => ['value_boolean' => false],
                    'report_level' => ['value_tier' => 'enhanced'],
                    'advanced_analytics' => ['value_boolean' => false],
                    'multi_branch' => ['value_boolean' => false],
                    'max_storage_mb' => ['value_numeric' => 5000],
                ],
            ],
            'Ultimate' => [
                'price_monthly' => 3999.00,
                'price_yearly' => 39990.00,
                'max_users' => 100,
                'max_patients' => null,
                'max_appointments' => null,
                'max_storage_mb' => 50000,
                'features' => [
                    'qr_booking' => ['value_boolean' => true],
                    'appointment_scheduling' => ['value_boolean' => true],
                    'patient_records' => ['value_boolean' => true],
                    'billing_pos' => ['value_boolean' => true],
                    'clinic_setup' => ['value_boolean' => true],
                    'role_based_access' => ['value_boolean' => true],
                    'max_users' => ['value_numeric' => null],
                    'max_patients' => ['value_numeric' => null],
                    'max_appointments' => ['value_numeric' => null],
                    'sms_notifications' => ['value_boolean' => true],
                    'custom_branding' => ['value_boolean' => true],
                    'priority_support' => ['value_boolean' => true],
                    'report_level' => ['value_tier' => 'advanced'],
                    'advanced_analytics' => ['value_boolean' => true],
                    'multi_branch' => ['value_boolean' => true],
                    'max_storage_mb' => ['value_numeric' => 50000],
                ],
            ],
        ];
    }

    /**
     * @param  array<string, array<string, mixed>>  $features
     */
    private function syncPlanFeatures(SubscriptionPlan $plan, array $features, Carbon $pushedAt): void
    {
        $syncData = [];

        foreach ($features as $key => $values) {
            $feature = Feature::where('key', $key)->first();
            if ($feature) {
                $syncData[$feature->id] = array_merge($values, [
                    'pushed_at' => $pushedAt,
                ]);
            }
        }

        $plan->features()->sync($syncData);
    }
}

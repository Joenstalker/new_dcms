<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Feature;
use App\Models\SubscriptionPlan;

class FeatureSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $features = [
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

        // Create all features
        foreach ($features as $featureData) {
            Feature::updateOrCreate(
            ['key' => $featureData['key']],
                $featureData
            );
        }

        // Now assign features to subscription plans
        $this->assignFeaturesToPlans();
    }

    /**
     * Assign feature values to subscription plans.
     */
    private function assignFeaturesToPlans(): void
    {
        $basicPlan = SubscriptionPlan::where('name', 'Basic')->first();
        $proPlan = SubscriptionPlan::where('name', 'Pro')->first();
        $ultimatePlan = SubscriptionPlan::where('name', 'Ultimate')->first();

        if (!$basicPlan || !$proPlan || !$ultimatePlan) {
            return;
        }

        // Basic Plan Features
        $basicFeatures = [
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
            'multi_branch' => ['value_boolean' => false],    'max_storage_mb' => ['value_numeric' => 500],
        
        ];

        // Pro Plan Features
        $proFeatures = [
            'qr_booking' => ['value_boolean' => true],
            'appointment_scheduling' => ['value_boolean' => true],
            'patient_records' => ['value_boolean' => true],
            'billing_pos' => ['value_boolean' => true],
            'clinic_setup' => ['value_boolean' => true],
            'role_based_access' => ['value_boolean' => true],
            'max_users' => ['value_numeric' => 6],
            'max_patients' => ['value_numeric' => 1000],
            'max_appointments' => ['value_numeric' => 2000],
            'sms_notifications' => ['value_boolean' => true],
            'custom_branding' => ['value_boolean' => true],
            'priority_support' => ['value_boolean' => false],
            'report_level' => ['value_tier' => 'enhanced'],
            'advanced_analytics' => ['value_boolean' => false],
            'multi_branch' => ['value_boolean' => false],    'max_storage_mb' => ['value_numeric' => 5000],
        
        ];

        // Ultimate Plan Features
        $ultimateFeatures = [
            'qr_booking' => ['value_boolean' => true],
            'appointment_scheduling' => ['value_boolean' => true],
            'patient_records' => ['value_boolean' => true],
            'billing_pos' => ['value_boolean' => true],
            'clinic_setup' => ['value_boolean' => true],
            'role_based_access' => ['value_boolean' => true],
            'max_users' => ['value_numeric' => 10],
            'max_patients' => ['value_numeric' => null], // Unlimited
            'max_appointments' => ['value_numeric' => null], // Unlimited
            'sms_notifications' => ['value_boolean' => true],
            'custom_branding' => ['value_boolean' => true],
            'priority_support' => ['value_boolean' => true],
            'report_level' => ['value_tier' => 'advanced'],
            'advanced_analytics' => ['value_boolean' => true],
            'multi_branch' => ['value_boolean' => true],
            'max_storage_mb' => ['value_numeric' => 50000],
        ];

        // Sync features to plans
        $this->syncPlanFeatures($basicPlan, $basicFeatures);
        $this->syncPlanFeatures($proPlan, $proFeatures);
        $this->syncPlanFeatures($ultimatePlan, $ultimateFeatures);
    }

    /**
     * Sync features to a specific plan.
     */
    private function syncPlanFeatures(SubscriptionPlan $plan, array $features): void
    {
        $syncData = [];

        foreach ($features as $key => $values) {
            $feature = Feature::where('key', $key)->first();
            if ($feature) {
                $syncData[$feature->id] = $values;
            }
        }

        $plan->features()->sync($syncData);
    }
}

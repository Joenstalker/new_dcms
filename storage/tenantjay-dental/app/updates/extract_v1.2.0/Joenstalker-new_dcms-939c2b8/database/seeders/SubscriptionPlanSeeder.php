<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class SubscriptionPlanSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $plans = [
            [
                'name' => 'Basic',
                'price_monthly' => 700.00,
                'price_yearly' => 7000.00,
                'max_users' => 4,
                'max_patients' => 150,
                'max_appointments' => 500, // Reasonable limit for basic
                'has_qr_booking' => true,
                'has_sms' => false,
                'has_branding' => false,
                'has_analytics' => false,
                'has_priority_support' => false,
                'has_multi_branch' => false,
                'report_level' => 'basic',
                // Stripe price IDs
                'stripe_product_id' => null,
                'stripe_monthly_price_id' => null,
                'stripe_yearly_price_id' => null,
            ],
            [
                'name' => 'Pro',
                'price_monthly' => 1000.00,
                'price_yearly' => 10000.00,
                'max_users' => 6,
                'max_patients' => 1000, 
                'max_appointments' => 2000,
                'has_qr_booking' => true,
                'has_sms' => true,
                'has_branding' => true,
                'has_analytics' => false,
                'has_priority_support' => false,
                'has_multi_branch' => false,
                'report_level' => 'enhanced',
                // Stripe price IDs
                'stripe_product_id' => null,
                'stripe_monthly_price_id' => null,
                'stripe_yearly_price_id' => null,
            ],
            [
                'name' => 'Ultimate',
                'price_monthly' => 2000.00,
                'price_yearly' => 20000.00,
                'max_users' => 10,
                'max_patients' => null, // Unlimited
                'max_appointments' => null, // Unlimited
                'has_qr_booking' => true,
                'has_sms' => true,
                'has_branding' => true,
                'has_analytics' => true,
                'has_priority_support' => true,
                'has_multi_branch' => true,
                'report_level' => 'advanced',
                // Stripe price IDs
                'stripe_product_id' => null,
                'stripe_monthly_price_id' => null,
                'stripe_yearly_price_id' => null,
            ],
        ];

        foreach ($plans as $plan) {
            \App\Models\SubscriptionPlan::updateOrCreate(
                ['name' => $plan['name']],
                $plan
            );
        }
    }
}

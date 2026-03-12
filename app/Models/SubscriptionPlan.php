<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SubscriptionPlan extends Model
    {
        protected $fillable = [
            'name',
            'stripe_product_id',
            'stripe_monthly_price_id',
            'stripe_yearly_price_id',
            'legacy_stripe_id',
            'price_monthly',
            'price_yearly',
            'max_users',
            'max_patients',
            'max_appointments',
            'has_qr_booking',
            'has_sms',
            'has_branding',
            'has_analytics',
            'has_priority_support',
            'has_multi_branch',
            'report_level',
        ];
    
        protected $casts = [
            'price_monthly' => 'decimal:2',
            'price_yearly' => 'decimal:2',
            'max_users' => 'integer',
            'max_patients' => 'integer',
            'max_appointments' => 'integer',
            'has_qr_booking' => 'boolean',
            'has_sms' => 'boolean',
            'has_branding' => 'boolean',
            'has_analytics' => 'boolean',
            'has_priority_support' => 'boolean',
            'has_multi_branch' => 'boolean',
        ];
    
        public function subscriptions()
        {
            return $this->hasMany(Subscription::class);
        }
    }

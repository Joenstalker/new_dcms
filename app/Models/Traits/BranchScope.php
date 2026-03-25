<?php

namespace App\Models\Traits;

use App\Models\Subscription;
use Illuminate\Database\Eloquent\Builder;

/**
 * Trait to automatically scope models to the active branch
 * when multi-branch support is enabled for the tenant.
 */
trait BranchScope
{
    protected static function bootBranchScope()
    {
        static::addGlobalScope('branch', function (Builder $builder) {
            // Only apply if we are in a tenant context
            if (!tenant()) {
                return;
            }

            // Check if multi-branch is enabled in subscription
            $subscription = Subscription::where('tenant_id', tenant('id'))
                ->where('stripe_status', 'active')
                ->latest()
                ->first();

            if ($subscription && $subscription->plan && $subscription->plan->hasFeature('multi_branch')) {
                // Get the current active branch from session, fallback to primary
                $currentBranchId = session('current_branch_id');
                
                if ($currentBranchId) {
                    $builder->where($builder->getModel()->getTable() . '.branch_id', $currentBranchId);
                }
            }
        });
    }

    /**
     * Relationship to the branch.
     */
    public function branch()
    {
        return $this->belongsTo(\App\Models\Branch::class);
    }

    /**
     * Helper to bypass branch scoping if needed (e.g., for reports across branches)
     */
    public static function withoutBranchScope()
    {
        return static::withoutGlobalScope('branch');
    }
}

<script setup>
import { Link } from '@inertiajs/vue3';

defineProps({
    subscription: {
        type: Object,
        default: null
    }
});
</script>

<template>
    <!-- Subscription & Billing Section -->
    <div v-if="subscription" class="mt-8 border-t pt-6">
        <h3 class="text-lg font-medium text-gray-800 mb-4">Subscription & Billing</h3>
        <div class="bg-gray-50 rounded-lg border border-gray-200 p-5 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div class="space-y-1">
                <div class="flex items-center gap-2">
                    <span class="text-sm font-semibold text-gray-700">Current Plan:</span>
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                        {{ subscription.plan_name }}
                    </span>
                    <span
                        class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium"
                        :class="{
                            'bg-green-100 text-green-800': subscription.stripe_status === 'active',
                            'bg-yellow-100 text-yellow-800': subscription.stripe_status === 'trialing',
                            'bg-red-100 text-red-800': subscription.stripe_status === 'past_due' || subscription.stripe_status === 'unpaid',
                            'bg-gray-100 text-gray-800': subscription.stripe_status === 'canceled',
                        }"
                    >
                        {{ subscription.stripe_status }}
                    </span>
                </div>
                <p class="text-xs text-gray-500">
                    Billing cycle: <span class="font-medium capitalize">{{ subscription.billing_cycle }}</span>
                </p>
                <div class="flex flex-wrap gap-2 mt-2">
                    <span v-if="subscription.max_patients" class="text-xs text-gray-600">
                        Up to <strong>{{ subscription.max_patients }}</strong> patients
                    </span>
                    <span v-else class="text-xs text-gray-600">Unlimited patients</span>
                    <span class="text-gray-300">·</span>
                    <span class="text-xs text-gray-600">
                        Up to <strong>{{ subscription.max_users }}</strong> staff users
                    </span>
                    <span v-if="subscription.has_sms" class="text-xs text-green-600 font-medium">· SMS ✓</span>
                    <span v-if="subscription.has_analytics" class="text-xs text-green-600 font-medium">· Analytics ✓</span>
                    <span v-if="subscription.has_multi_branch" class="text-xs text-green-600 font-medium">· Multi-branch ✓</span>
                </div>
            </div>
            <Link
                :href="route('billing.portal')"
                class="inline-flex items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 transition-colors whitespace-nowrap"
            >
                <svg class="w-4 h-4 mr-2" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 8.25h19.5M2.25 9h19.5m-16.5 5.25h6m-6 2.25h3m-3.75 3h15a2.25 2.25 0 0 0 2.25-2.25V6.75A2.25 2.25 0 0 0 19.5 4.5h-15a2.25 2.25 0 0 0-2.25 2.25v10.5A2.25 2.25 0 0 0 4.5 19.5Z" />
                </svg>
                Manage Billing
            </Link>
        </div>
    </div>
</template>

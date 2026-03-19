<script setup>
defineProps({
    subscription: {
        type: Object,
        default: null
    }
});
</script>

<template>
    <!-- Current Plan Banner -->
    <div v-if="subscription" class="mb-8 bg-gradient-to-r from-indigo-500 to-purple-600 rounded-lg shadow-lg p-6 text-white">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-indigo-100 text-sm font-medium">Current Plan</p>
                <h3 class="text-2xl font-bold mt-1">{{ subscription.plan_name }}</h3>
                <p class="text-indigo-100 text-sm mt-1">
                    {{ subscription.billing_cycle === 'monthly' ? 'Monthly' : 'Yearly' }} billing
                </p>
            </div>
            <div class="text-right">
                <span 
                    class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium"
                    :class="{
                        'bg-green-100 text-green-800': subscription.stripe_status === 'active',
                        'bg-yellow-100 text-yellow-800': subscription.stripe_status === 'trialing',
                        'bg-red-100 text-red-800': subscription.stripe_status === 'past_due',
                    }"
                >
                    {{ subscription.stripe_status }}
                </span>
            </div>
        </div>
    </div>
</template>

<script setup>
const props = defineProps({
    subscription: {
        type: Object,
        default: null
    },
    hasUpdates: {
        type: Boolean,
        default: false
    }
});
</script>

<template>
    <!-- Current Plan Banner -->
    <div v-if="subscription" class="mb-8 bg-gradient-to-r from-indigo-500 to-purple-600 rounded-lg shadow-lg p-6 text-white relative overflow-hidden">
        <!-- Decoration -->
        <div class="absolute -right-6 -top-6 w-32 h-32 bg-white opacity-10 rounded-full"></div>
        
        <div class="flex items-center justify-between relative z-10">
            <div>
                <p class="text-indigo-100 text-sm font-medium">Current Plan</p>
                <div class="flex items-center gap-3 mt-1">
                    <h3 class="text-2xl font-bold">{{ subscription.plan_name }}</h3>
                    <div v-if="hasUpdates" class="flex items-center">
                        <a 
                            :href="route('settings.updates')" 
                            class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-bold bg-orange-400 text-white hover:bg-orange-500 transition-colors shadow-sm animate-bounce"
                        >
                            <svg class="w-3 h-3 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M13 10V3L4 14h7v7l9-11h-7z" />
                            </svg>
                            UPDATES AVAILABLE
                        </a>
                    </div>
                </div>
                <p class="text-indigo-100 text-sm mt-1">
                    {{ subscription.billing_cycle === 'monthly' ? 'Monthly' : 'Yearly' }} billing
                </p>
            </div>
            <div class="text-right">
                <span 
                    class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium"
                    :class="{
                        'bg-green-100/20 text-green-100 border border-green-100/30': subscription.stripe_status === 'active',
                        'bg-yellow-100/20 text-yellow-100 border border-yellow-100/30': subscription.stripe_status === 'trialing',
                        'bg-red-100/20 text-red-100 border border-red-100/30': subscription.stripe_status === 'past_due',
                    }"
                >
                    {{ subscription.stripe_status?.toUpperCase() }}
                </span>
            </div>
        </div>
    </div>
</template>

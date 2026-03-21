<script setup>
import { Link } from '@inertiajs/vue3';

defineProps({
    plans: {
        type: Array,
        default: () => [],
    },
});
</script>

<template>
    <div class="mt-12 bg-base-100 shadow-sm ring-1 ring-base-300 sm:rounded-2xl p-8 border border-base-300">
        <div class="sm:flex sm:items-center sm:justify-between mb-8">
            <div>
                <h3 class="text-xl font-black text-base-content tracking-tight">Subscription Plans Overview</h3>
                <p class="mt-2 text-sm text-base-content/50">A quick glance at how features map to your current pricing tiers.</p>
            </div>
        </div>
        
        <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
            <div v-for="plan in plans" :key="plan.id" class="rounded-3xl border border-base-300 p-8 shadow-sm flex flex-col justify-between hover:border-primary/50 hover:bg-base-200/30 transition-all duration-300 group">
                <div>
                    <h4 class="text-lg font-black leading-8 text-base-content">{{ plan.name }}</h4>
                    <p class="mt-4 flex items-baseline gap-x-2">
                        <span class="text-4xl font-black tracking-tighter text-base-content">₱{{ plan.price_monthly }}</span>
                        <span class="text-sm font-bold leading-6 text-base-content/40">/month</span>
                    </p>
                </div>
                <div class="mt-8 pt-6 border-t border-base-300/50">
                    <Link
                        :href="`/admin/plans/${plan.id}/edit`"
                        class="text-primary hover:text-primary-focus font-bold text-sm inline-flex items-center gap-2 group-hover:translate-x-1 transition-transform duration-300"
                    >
                        Manage Plan Features 
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5L21 12m0 0l-7.5 7.5M21 12H3" />
                        </svg>
                    </Link>
                </div>
            </div>
        </div>
    </div>
</template>

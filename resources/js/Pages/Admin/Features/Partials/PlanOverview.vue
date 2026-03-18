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
    <div class="mt-12 bg-white shadow-sm ring-1 ring-gray-900/5 sm:rounded-xl p-6 lg:p-8">
        <div class="sm:flex sm:items-center sm:justify-between mb-6">
            <div>
                <h3 class="text-base font-semibold leading-6 text-gray-900">Subscription Plans Overview</h3>
                <p class="mt-2 text-sm text-gray-500">A quick glance at how features map to your current pricing tiers.</p>
            </div>
        </div>
        
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
            <div v-for="plan in plans" :key="plan.id" class="rounded-2xl border border-gray-200 p-6 shadow-sm flex flex-col justify-between hover:border-indigo-300 transition-colors">
                <div>
                    <h4 class="text-lg font-semibold leading-8 text-gray-900">{{ plan.name }}</h4>
                    <p class="mt-4 flex items-baseline gap-x-2">
                        <span class="text-3xl font-bold tracking-tight text-gray-900">₱{{ plan.price_monthly }}</span>
                        <span class="text-sm font-semibold leading-6 text-gray-500">/mo</span>
                    </p>
                </div>
                <div class="mt-6">
                    <Link
                        :href="`/admin/plans/${plan.id}/edit`"
                        class="text-indigo-600 hover:text-indigo-500 text-sm font-semibold leading-6 inline-flex items-center gap-1"
                    >
                        Manage Plan Features <span aria-hidden="true">&rarr;</span>
                    </Link>
                </div>
            </div>
        </div>
    </div>
</template>

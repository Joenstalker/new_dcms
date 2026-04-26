<script setup>
const props = defineProps({
    features: {
        type: Object,
        default: () => ({})
    },
    subscriptions: {
        type: Object,
        default: () => ({})
    },
});

// Helper function
const getPercentage = (value, total) => {
    if (!total || total === 0) return 0;
    return Math.round((value / total) * 100);
};
</script>

<template>
    <div>
        <!-- Feature Usage Cards -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-6">
            <div class="bg-base-100 rounded-lg shadow-sm border border-base-300 p-6">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-lg font-semibold text-base-content">SMS Notifications</h3>
                    <span class="p-2 rounded-full bg-info/10 text-info">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z" />
                        </svg>
                    </span>
                </div>
                <p class="text-3xl font-bold text-base-content">{{ features.usage?.sms || 0 }}</p>
                <p class="text-sm text-base-content/60">Active subscriptions</p>
                <div class="mt-4 bg-base-200 rounded-full h-2 overflow-hidden">
                    <div class="bg-info h-2 rounded-full" :style="{ width: getPercentage(features.usage?.sms, subscriptions.total) + '%' }"></div>
                </div>
                <p class="text-xs text-base-content/50 mt-1">{{ getPercentage(features.usage?.sms, subscriptions.total) }}% adoption</p>
            </div>

            <div class="bg-base-100 rounded-lg shadow-sm border border-base-300 p-6">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-lg font-semibold text-base-content">Custom Branding</h3>
                    <span class="p-2 rounded-full bg-secondary/10 text-secondary">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21a4 4 0 01-4-4V5a2 2 0 012-2h4a2 2 0 012 2v12a4 4 0 01-4 4zm0 0h12a2 2 0 002-2v-4a2 2 0 00-2-2h-2.343M11 7.343l1.657-1.657a2 2 0 012.828 0l2.829 2.829a2 2 0 010 2.828l-8.486 8.485M7 17h.01" />
                        </svg>
                    </span>
                </div>
                <p class="text-3xl font-bold text-base-content">{{ features.usage?.branding || 0 }}</p>
                <p class="text-sm text-base-content/60">Active subscriptions</p>
                <div class="mt-4 bg-base-200 rounded-full h-2 overflow-hidden">
                    <div class="bg-secondary h-2 rounded-full" :style="{ width: getPercentage(features.usage?.branding, subscriptions.total) + '%' }"></div>
                </div>
                <p class="text-xs text-base-content/50 mt-1">{{ getPercentage(features.usage?.branding, subscriptions.total) }}% adoption</p>
            </div>

            <div class="bg-base-100 rounded-lg shadow-sm border border-base-300 p-6">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-lg font-semibold text-base-content">Advanced Analytics</h3>
                    <span class="p-2 rounded-full bg-success/10 text-success">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                        </svg>
                    </span>
                </div>
                <p class="text-3xl font-bold text-base-content">{{ features.usage?.analytics || 0 }}</p>
                <p class="text-sm text-base-content/60">Active subscriptions</p>
                <div class="mt-4 bg-base-200 rounded-full h-2 overflow-hidden">
                    <div class="bg-success h-2 rounded-full" :style="{ width: getPercentage(features.usage?.analytics, subscriptions.total) + '%' }"></div>
                </div>
                <p class="text-xs text-base-content/50 mt-1">{{ getPercentage(features.usage?.analytics, subscriptions.total) }}% adoption</p>
            </div>

            <div class="bg-base-100 rounded-lg shadow-sm border border-base-300 p-6">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-lg font-semibold text-base-content">QR Booking</h3>
                    <span class="p-2 rounded-full bg-warning/10 text-warning">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v1m6 11h2m-6 0h-2v4m0-11v3m0 0h.01M12 12h4.01M16 20h4M4 12h4m12 0h.01M5 8h2a1 1 0 001-1V5a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1zm12 0h2a1 1 0 001-1V5a1 1 0 00-1-1h-2a1 1 0 00-1 1v2a1 1 0 001 1zM5 20h2a1 1 0 001-1v-2a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1z" />
                        </svg>
                    </span>
                </div>
                <p class="text-3xl font-bold text-base-content">{{ features.usage?.qr_booking || 0 }}</p>
                <p class="text-sm text-base-content/60">Active subscriptions</p>
                <div class="mt-4 bg-base-200 rounded-full h-2 overflow-hidden">
                    <div class="bg-warning h-2 rounded-full" :style="{ width: getPercentage(features.usage?.qr_booking, subscriptions.total) + '%' }"></div>
                </div>
                <p class="text-xs text-base-content/50 mt-1">{{ getPercentage(features.usage?.qr_booking, subscriptions.total) }}% adoption</p>
            </div>
        </div>

        <!-- Feature by Plan Table -->
        <div class="bg-base-100 rounded-lg shadow-sm border border-base-300 overflow-hidden">
            <div class="px-6 py-4 border-b border-base-300">
                <h3 class="text-lg font-semibold text-base-content">Features by Plan</h3>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-base-300">
                    <thead class="bg-base-200">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-base-content/60 uppercase tracking-wider">Plan</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-base-content/60 uppercase tracking-wider">SMS</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-base-content/60 uppercase tracking-wider">Branding</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-base-content/60 uppercase tracking-wider">Analytics</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-base-content/60 uppercase tracking-wider">QR Booking</th>
                        </tr>
                    </thead>
                    <tbody class="bg-base-100 divide-y divide-base-300">
                        <tr v-for="plan in features.by_plan" :key="plan.name" class="hover:bg-base-200 transition-colors">
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-base-content">{{ plan.name }}</td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span v-if="plan.has_sms" class="text-success text-lg">✓</span>
                                <span v-else class="text-base-content/30 text-lg">✗</span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span v-if="plan.has_branding" class="text-success text-lg">✓</span>
                                <span v-else class="text-base-content/30 text-lg">✗</span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span v-if="plan.has_analytics" class="text-success text-lg">✓</span>
                                <span v-else class="text-base-content/30 text-lg">✗</span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span v-if="plan.has_qr_booking" class="text-success text-lg">✓</span>
                                <span v-else class="text-base-content/30 text-lg">✗</span>
                            </td>
                        </tr>
                        <tr v-if="!features.by_plan?.length">
                            <td colspan="5" class="px-6 py-12 text-center text-base-content/30">
                                No feature data available yet.
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</template>

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
            <div class="bg-white rounded-lg shadow p-6">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-lg font-semibold text-gray-900">SMS Notifications</h3>
                    <span class="p-2 rounded-full bg-blue-100 text-blue-600">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z" />
                        </svg>
                    </span>
                </div>
                <p class="text-3xl font-bold text-gray-900">{{ features.usage?.sms || 0 }}</p>
                <p class="text-sm text-gray-500">Active subscriptions</p>
                <div class="mt-4 bg-gray-200 rounded-full h-2">
                    <div class="bg-blue-600 h-2 rounded-full" :style="{ width: getPercentage(features.usage?.sms, subscriptions.total) + '%' }"></div>
                </div>
                <p class="text-xs text-gray-500 mt-1">{{ getPercentage(features.usage?.sms, subscriptions.total) }}% adoption</p>
            </div>

            <div class="bg-white rounded-lg shadow p-6">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-lg font-semibold text-gray-900">Custom Branding</h3>
                    <span class="p-2 rounded-full bg-purple-100 text-purple-600">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21a4 4 0 01-4-4V5a2 2 0 012-2h4a2 2 0 012 2v12a4 4 0 01-4 4zm0 0h12a2 2 0 002-2v-4a2 2 0 00-2-2h-2.343M11 7.343l1.657-1.657a2 2 0 012.828 0l2.829 2.829a2 2 0 010 2.828l-8.486 8.485M7 17h.01" />
                        </svg>
                    </span>
                </div>
                <p class="text-3xl font-bold text-gray-900">{{ features.usage?.branding || 0 }}</p>
                <p class="text-sm text-gray-500">Active subscriptions</p>
                <div class="mt-4 bg-gray-200 rounded-full h-2">
                    <div class="bg-purple-600 h-2 rounded-full" :style="{ width: getPercentage(features.usage?.branding, subscriptions.total) + '%' }"></div>
                </div>
                <p class="text-xs text-gray-500 mt-1">{{ getPercentage(features.usage?.branding, subscriptions.total) }}% adoption</p>
            </div>

            <div class="bg-white rounded-lg shadow p-6">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-lg font-semibold text-gray-900">Advanced Analytics</h3>
                    <span class="p-2 rounded-full bg-green-100 text-green-600">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                        </svg>
                    </span>
                </div>
                <p class="text-3xl font-bold text-gray-900">{{ features.usage?.analytics || 0 }}</p>
                <p class="text-sm text-gray-500">Active subscriptions</p>
                <div class="mt-4 bg-gray-200 rounded-full h-2">
                    <div class="bg-green-600 h-2 rounded-full" :style="{ width: getPercentage(features.usage?.analytics, subscriptions.total) + '%' }"></div>
                </div>
                <p class="text-xs text-gray-500 mt-1">{{ getPercentage(features.usage?.analytics, subscriptions.total) }}% adoption</p>
            </div>

            <div class="bg-white rounded-lg shadow p-6">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-lg font-semibold text-gray-900">QR Booking</h3>
                    <span class="p-2 rounded-full bg-orange-100 text-orange-600">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v1m6 11h2m-6 0h-2v4m0-11v3m0 0h.01M12 12h4.01M16 20h4M4 12h4m12 0h.01M5 8h2a1 1 0 001-1V5a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1zm12 0h2a1 1 0 001-1V5a1 1 0 00-1-1h-2a1 1 0 00-1 1v2a1 1 0 001 1zM5 20h2a1 1 0 001-1v-2a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1z" />
                        </svg>
                    </span>
                </div>
                <p class="text-3xl font-bold text-gray-900">{{ features.usage?.qr_booking || 0 }}</p>
                <p class="text-sm text-gray-500">Active subscriptions</p>
                <div class="mt-4 bg-gray-200 rounded-full h-2">
                    <div class="bg-orange-600 h-2 rounded-full" :style="{ width: getPercentage(features.usage?.qr_booking, subscriptions.total) + '%' }"></div>
                </div>
                <p class="text-xs text-gray-500 mt-1">{{ getPercentage(features.usage?.qr_booking, subscriptions.total) }}% adoption</p>
            </div>
        </div>

        <!-- Feature by Plan Table -->
        <div class="bg-white rounded-lg shadow overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-200">
                <h3 class="text-lg font-semibold text-gray-900">Features by Plan</h3>
            </div>
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Plan</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">SMS</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Branding</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Analytics</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">QR Booking</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    <tr v-for="plan in features.by_plan" :key="plan.name">
                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">{{ plan.name }}</td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <span v-if="plan.has_sms" class="text-green-600">✓</span>
                            <span v-else class="text-gray-400">✗</span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <span v-if="plan.has_branding" class="text-green-600">✓</span>
                            <span v-else class="text-gray-400">✗</span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <span v-if="plan.has_analytics" class="text-green-600">✓</span>
                            <span v-else class="text-gray-400">✗</span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <span v-if="plan.has_qr_booking" class="text-green-600">✓</span>
                            <span v-else class="text-gray-400">✗</span>
                        </td>
                    </tr>
                    <tr v-if="!features.by_plan?.length">
                        <td colspan="5" class="px-6 py-8 text-center text-gray-500">
                            No feature data available yet.
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</template>

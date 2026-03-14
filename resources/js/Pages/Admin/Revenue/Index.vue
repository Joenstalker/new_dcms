<script setup>
import { Head, Link } from '@inertiajs/vue3';
import { computed } from 'vue';
import AdminLayout from '@/Layouts/AdminLayout.vue';

const props = defineProps({
    stats: Object,
    plan_distribution: Object,
    monthly_revenue: Array,
    status_breakdown: Object,
    recent_subscriptions: Array,
});

// Compute bar chart max for scaling
const maxRevenue = computed(() => {
    if (!props.monthly_revenue?.length) return 1;
    return Math.max(...props.monthly_revenue.map(m => m.revenue), 1);
});

// Plan distribution as array for rendering
const planDistributionArray = computed(() => {
    if (!props.plan_distribution) return [];
    return Object.entries(props.plan_distribution).map(([name, count]) => ({ name, count }));
});

const totalPlans = computed(() => planDistributionArray.value.reduce((sum, p) => sum + p.count, 0));

const planColors = ['bg-teal-500', 'bg-blue-500', 'bg-purple-500', 'bg-amber-500', 'bg-rose-500'];

const formatCurrency = (val) => {
    return '₱' + Number(val).toLocaleString('en-PH', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
};

const statusLabel = {
    active: { label: 'Active', class: 'bg-green-100 text-green-800' },
    past_due: { label: 'Past Due', class: 'bg-red-100 text-red-800' },
    canceled: { label: 'Canceled', class: 'bg-gray-100 text-gray-800' },
    trialing: { label: 'Trialing', class: 'bg-yellow-100 text-yellow-800' },
};
</script>

<template>
    <Head title="Billing & Revenue" />
    <AdminLayout>
        <template #header>
            <h1 class="text-xl font-bold text-gray-900">Billing & Revenue</h1>
        </template>

        <div class="max-w-7xl mx-auto space-y-6">

            <!-- KPI Cards -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-5">
                <div class="bg-white rounded-2xl p-6 border border-gray-100 shadow-sm">
                    <p class="text-sm font-medium text-gray-500">Monthly Recurring Revenue</p>
                    <p class="mt-2 text-3xl font-bold text-gray-900">{{ formatCurrency(stats.mrr) }}</p>
                    <p class="text-xs text-gray-400 mt-1">MRR</p>
                </div>
                <div class="bg-white rounded-2xl p-6 border border-gray-100 shadow-sm">
                    <p class="text-sm font-medium text-gray-500">Annual Recurring Revenue</p>
                    <p class="mt-2 text-3xl font-bold text-gray-900">{{ formatCurrency(stats.arr) }}</p>
                    <p class="text-xs text-gray-400 mt-1">ARR (MRR × 12)</p>
                </div>
                <div class="bg-white rounded-2xl p-6 border border-gray-100 shadow-sm">
                    <p class="text-sm font-medium text-gray-500">Active Subscriptions</p>
                    <p class="mt-2 text-3xl font-bold text-gray-900">{{ stats.active_subscriptions }}</p>
                    <p class="text-xs text-gray-400 mt-1">Paying clinics</p>
                </div>
                <div class="bg-white rounded-2xl p-6 border border-gray-100 shadow-sm">
                    <p class="text-sm font-medium text-gray-500">Churn This Month</p>
                    <p class="mt-2 text-3xl font-bold text-gray-900">{{ stats.churn_this_month }}</p>
                    <p class="text-xs text-gray-400 mt-1">Cancellations</p>
                </div>
            </div>

            <!-- Charts Row -->
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

                <!-- Monthly Revenue Bar Chart -->
                <div class="lg:col-span-2 bg-white rounded-2xl p-6 border border-gray-100 shadow-sm">
                    <h3 class="text-base font-semibold text-gray-900 mb-4">Revenue Trend (Last 12 Months)</h3>
                    <div class="flex items-end gap-1 h-48">
                        <div
                            v-for="month in monthly_revenue"
                            :key="month.month"
                            class="flex-1 flex flex-col items-center gap-1 group"
                        >
                            <div class="relative w-full flex justify-center">
                                <!-- Tooltip -->
                                <div class="absolute bottom-full mb-1 hidden group-hover:block bg-gray-800 text-white text-xs rounded px-2 py-1 whitespace-nowrap z-10">
                                    {{ month.month }}: {{ formatCurrency(month.revenue) }}
                                </div>
                                <div
                                    class="w-full bg-teal-500 rounded-t transition-all duration-300 hover:bg-teal-600"
                                    :style="{ height: Math.max((month.revenue / maxRevenue) * 160, 4) + 'px' }"
                                ></div>
                            </div>
                            <span class="text-xs text-gray-400 rotate-45 origin-left mt-1 hidden sm:block" style="font-size:9px;">
                                {{ month.month.split(' ')[0] }}
                            </span>
                        </div>
                    </div>
                    <div class="mt-2 text-xs text-gray-400 text-center">Hover bars for details</div>
                </div>

                <!-- Plan Distribution -->
                <div class="bg-white rounded-2xl p-6 border border-gray-100 shadow-sm">
                    <h3 class="text-base font-semibold text-gray-900 mb-4">Plan Distribution</h3>
                    <div v-if="planDistributionArray.length" class="space-y-3">
                        <div
                            v-for="(plan, i) in planDistributionArray"
                            :key="plan.name"
                            class="space-y-1"
                        >
                            <div class="flex justify-between text-sm">
                                <span class="font-medium text-gray-700">{{ plan.name }}</span>
                                <span class="text-gray-500">{{ plan.count }} ({{ totalPlans ? Math.round((plan.count / totalPlans) * 100) : 0 }}%)</span>
                            </div>
                            <div class="w-full bg-gray-100 rounded-full h-2">
                                <div
                                    :class="planColors[i % planColors.length]"
                                    class="h-2 rounded-full transition-all duration-500"
                                    :style="{ width: totalPlans ? (plan.count / totalPlans * 100) + '%' : '0%' }"
                                ></div>
                            </div>
                        </div>
                    </div>
                    <div v-else class="flex items-center justify-center h-32 text-gray-400 text-sm">
                        No active subscriptions yet.
                    </div>

                    <!-- Status Breakdown -->
                    <div class="mt-6 pt-4 border-t">
                        <h4 class="text-sm font-semibold text-gray-700 mb-3">Subscription Status</h4>
                        <div class="grid grid-cols-2 gap-2">
                            <div v-for="(count, status) in status_breakdown" :key="status" class="text-center p-2 rounded-lg bg-gray-50">
                                <div class="text-lg font-bold text-gray-900">{{ count }}</div>
                                <div class="text-xs text-gray-500 capitalize">{{ status.replace('_', ' ') }}</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Recent Subscriptions Table -->
            <div class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-100">
                    <h3 class="text-base font-semibold text-gray-900">Recent Subscriptions</h3>
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Clinic</th>
                                <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Plan</th>
                                <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Cycle</th>
                                <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Amount</th>
                                <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Status</th>
                                <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Date</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <tr v-for="sub in recent_subscriptions" :key="sub.id" class="hover:bg-gray-50 transition-colors">
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm font-medium text-gray-900">{{ sub.domain }}</div>
                                    <div class="text-xs text-gray-400">{{ sub.tenant_id?.substring(0, 8) }}...</div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-700 font-medium">{{ sub.plan }}</td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 capitalize">{{ sub.billing_cycle }}</td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-semibold text-gray-900">{{ formatCurrency(sub.amount) }}</td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span
                                        class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium"
                                        :class="statusLabel[sub.stripe_status]?.class || 'bg-gray-100 text-gray-800'"
                                    >
                                        {{ statusLabel[sub.stripe_status]?.label || sub.stripe_status }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ sub.created_at }}</td>
                            </tr>
                            <tr v-if="!recent_subscriptions?.length">
                                <td colspan="6" class="px-6 py-12 text-center text-gray-400 text-sm">No subscriptions yet.</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                <div class="px-6 py-3 border-t border-gray-100 text-right">
                    <Link :href="route('admin.subscriptions.index')" class="text-sm text-teal-600 hover:text-teal-800 font-medium">
                        View all subscriptions →
                    </Link>
                </div>
            </div>

        </div>
    </AdminLayout>
</template>

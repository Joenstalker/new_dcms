<script setup>
import { ref, onMounted, watch, nextTick } from 'vue';
import Chart from 'chart.js/auto';

const props = defineProps({
    subscriptions: {
        type: Object,
        default: () => ({})
    },
});

const mrrChart = ref(null);
const subscriptionPlanChart = ref(null);

// Helper function
const getPercentage = (value, total) => {
    if (!total || total === 0) return 0;
    return Math.round((value / total) * 100);
};

// Initialize charts
const initCharts = () => {
    // MRR Chart
    if (mrrChart.value && props.subscriptions?.mrr) {
        new Chart(mrrChart.value, {
            type: 'bar',
            data: {
                labels: props.subscriptions.mrr.map(m => m.month),
                datasets: [{
                    label: 'MRR ($)',
                    data: props.subscriptions.mrr.map(m => m.mrr || 0),
                    backgroundColor: 'rgb(34, 197, 94)',
                    borderRadius: 4
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: { legend: { display: false } },
                scales: {
                    y: { beginAtZero: true }
                }
            }
        });
    }

    // Subscription Plan Chart
    if (subscriptionPlanChart.value && props.subscriptions?.by_plan) {
        new Chart(subscriptionPlanChart.value, {
            type: 'pie',
            data: {
                labels: props.subscriptions.by_plan.map(p => p.plan_name),
                datasets: [{
                    data: props.subscriptions.by_plan.map(p => p.count),
                    backgroundColor: [
                        'rgb(79, 70, 229)',
                        'rgb(34, 197, 94)',
                        'rgb(249, 115, 22)',
                        'rgb(234, 179, 8)'
                    ]
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: { legend: { position: 'bottom' } }
            }
        });
    }
};

onMounted(() => {
    nextTick(() => {
        initCharts();
    });
});

watch(() => props.subscriptions, () => {
    nextTick(() => {
        initCharts();
    });
}, { deep: true });
</script>

<template>
    <div>
        <!-- Stats Cards -->
        <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-6">
            <div class="bg-base-100 rounded-lg shadow-sm border border-base-300 p-6">
                <div class="flex items-center">
                    <div class="p-3 rounded-full bg-primary/10 text-primary">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z" />
                        </svg>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm font-medium text-base-content/60">Total Subscriptions</p>
                        <p class="text-2xl font-semibold text-base-content">{{ subscriptions.total }}</p>
                    </div>
                </div>
            </div>
            <div class="bg-base-100 rounded-lg shadow-sm border border-base-300 p-6">
                <div class="flex items-center">
                    <div class="p-3 rounded-full bg-success/10 text-success">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm font-medium text-base-content/60">Monthly Recurring Revenue</p>
                        <p class="text-2xl font-semibold text-base-content">${{ subscriptions.total_mrr?.toLocaleString() || 0 }}</p>
                    </div>
                </div>
            </div>
            <div class="bg-base-100 rounded-lg shadow-sm border border-base-300 p-6">
                <div class="flex items-center">
                    <div class="p-3 rounded-full bg-info/10 text-info">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6" />
                        </svg>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm font-medium text-base-content/60">New Subscriptions</p>
                        <p class="text-2xl font-semibold text-base-content">{{ subscriptions.new_subscriptions?.length || 0 }}</p>
                    </div>
                </div>
            </div>
            <div class="bg-base-100 rounded-lg shadow-sm border border-base-300 p-6">
                <div class="flex items-center">
                    <div class="p-3 rounded-full" :class="subscriptions.churn_rate > 10 ? 'bg-error/10 text-error' : 'bg-warning/10 text-warning'">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 17h8m0 0V9m0 8l-8-8-4 4-6-6" />
                        </svg>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm font-medium text-base-content/60">Churn Rate (30d)</p>
                        <p class="text-2xl font-semibold text-base-content">{{ subscriptions.churn_rate }}%</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Charts Row -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
            <!-- MRR Trend -->
            <div class="bg-base-100 rounded-lg shadow-sm border border-base-300 p-6">
                <h3 class="text-lg font-semibold text-base-content mb-4">MRR Trend (Last 12 Months)</h3>
                <div class="h-64">
                    <canvas ref="mrrChart"></canvas>
                </div>
            </div>

            <!-- Subscriptions by Plan -->
            <div class="bg-base-100 rounded-lg shadow-sm border border-base-300 p-6">
                <h3 class="text-lg font-semibold text-base-content mb-4">Subscriptions by Plan</h3>
                <div class="h-64">
                    <canvas ref="subscriptionPlanChart"></canvas>
                </div>
            </div>
        </div>

        <!-- Subscriptions by Status -->
        <div class="bg-base-100 rounded-lg shadow-sm border border-base-300 p-6">
            <h3 class="text-lg font-semibold text-base-content mb-4">Subscriptions by Status</h3>
            <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                <div v-for="status in subscriptions.by_status" :key="status.status" class="text-center p-4 bg-base-200 rounded-lg border border-base-300">
                    <p class="text-3xl font-bold text-base-content">{{ status.count }}</p>
                    <p class="text-sm text-base-content/50 capitalize">{{ status.status }}</p>
                </div>
            </div>
        </div>
    </div>
</template>

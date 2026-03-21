<script setup>
import { ref, onMounted, watch, nextTick } from 'vue';
import Chart from 'chart.js/auto';

const props = defineProps({
    tenants: {
        type: Object,
        default: () => ({})
    },
});

const tenantGrowthChart = ref(null);
const tenantPlanChart = ref(null);

// Initialize charts
const initCharts = () => {
    // Tenant Growth Chart
    if (tenantGrowthChart.value && props.tenants?.growth) {
        new Chart(tenantGrowthChart.value, {
            type: 'line',
            data: {
                labels: props.tenants.growth.map(g => g.month),
                datasets: [{
                    label: 'New Tenants',
                    data: props.tenants.growth.map(g => g.count),
                    borderColor: 'rgb(79, 70, 229)',
                    backgroundColor: 'rgba(79, 70, 229, 0.1)',
                    fill: true,
                    tension: 0.3
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: { legend: { display: false } },
                scales: {
                    y: { beginAtZero: true, ticks: { stepSize: 1 } }
                }
            }
        });
    }

    // Tenant Plan Chart
    if (tenantPlanChart.value && props.tenants?.by_plan) {
        new Chart(tenantPlanChart.value, {
            type: 'doughnut',
            data: {
                labels: props.tenants.by_plan.map(p => p.plan_name || 'No Plan'),
                datasets: [{
                    data: props.tenants.by_plan.map(p => p.count),
                    backgroundColor: [
                        'rgb(79, 70, 229)',
                        'rgb(34, 197, 94)',
                        'rgb(249, 115, 22)',
                        'rgb(234, 179, 8)',
                        'rgb(161, 98, 7)'
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

watch(() => props.tenants, () => {
    nextTick(() => {
        initCharts();
    });
}, { deep: true });
</script>

<template>
    <div>
        <!-- Stats Cards -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-6">
            <div class="bg-base-100 rounded-lg shadow-sm border border-base-300 p-6">
                <div class="flex items-center">
                    <div class="p-3 rounded-full bg-primary/10 text-primary">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                        </svg>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm font-medium text-base-content/60">Total Tenants</p>
                        <p class="text-2xl font-semibold text-base-content">{{ tenants.total }}</p>
                    </div>
                </div>
            </div>
            <div class="bg-base-100 rounded-lg shadow-sm border border-base-300 p-6">
                <div class="flex items-center">
                    <div class="p-3 rounded-full bg-success/10 text-success">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm font-medium text-base-content/60">Active Tenants</p>
                        <p class="text-2xl font-semibold text-base-content">{{ tenants.active }}</p>
                    </div>
                </div>
            </div>
            <div class="bg-base-100 rounded-lg shadow-sm border border-base-300 p-6">
                <div class="flex items-center">
                    <div class="p-3 rounded-full bg-base-200 text-base-content/70">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 <SAMELINE" />
                        </svg>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm font-medium text-base-content/60">Inactive Tenants</p>
                        <p class="text-2xl font-semibold text-base-content">{{ tenants.inactive }}</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Charts Row -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
            <!-- Tenant Growth Chart -->
            <div class="bg-base-100 rounded-lg shadow-sm border border-base-300 p-6">
                <h3 class="text-lg font-semibold text-base-content mb-4">Tenant Growth (Last 12 Months)</h3>
                <div class="h-64">
                    <canvas ref="tenantGrowthChart"></canvas>
                </div>
            </div>

            <!-- Tenants by Plan -->
            <div class="bg-base-100 rounded-lg shadow-sm border border-base-300 p-6">
                <h3 class="text-lg font-semibold text-base-content mb-4">Tenants by Plan</h3>
                <div class="h-64">
                    <canvas ref="tenantPlanChart"></canvas>
                </div>
            </div>
        </div>

        <!-- Recent Tenants Table -->
        <div class="bg-base-100 rounded-lg shadow-sm border border-base-300 overflow-hidden">
            <div class="px-6 py-4 border-b border-base-300">
                <h3 class="text-lg font-semibold text-base-content">Recent Tenants</h3>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-base-300">
                    <thead class="bg-base-200">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-base-content/60 uppercase tracking-wider">Name</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-base-content/60 uppercase tracking-wider">Domain</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-base-content/60 uppercase tracking-wider">Plan</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-base-content/60 uppercase tracking-wider">Status</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-base-content/60 uppercase tracking-wider">Created</th>
                        </tr>
                    </thead>
                    <tbody class="bg-base-100 divide-y divide-base-300">
                        <tr v-for="tenant in tenants.recent" :key="tenant.id" class="hover:bg-base-200 transition-colors">
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-base-content">{{ tenant.name }}</td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-base-content/60">{{ tenant.domain }}</td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-base-content/60">{{ tenant.plan }}</td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span :class="[
                                    'px-2 inline-flex text-xs leading-5 font-semibold rounded-full',
                                    tenant.status === 'active' ? 'bg-success/20 text-success' : 'bg-base-200 text-base-content/70'
                                ]">
                                    {{ tenant.status }}
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-base-content/60">{{ tenant.created_at }}</td>
                        </tr>
                        <tr v-if="!tenants.recent?.length">
                            <td colspan="5" class="px-6 py-12 text-center text-base-content/30">
                                No tenants yet. New tenants will appear here.
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</template>

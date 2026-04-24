<script setup>
import { ref, onMounted, watch, nextTick } from 'vue';
import Chart from 'chart.js/auto';

const props = defineProps({
    support: {
        type: Object,
        default: () => ({})
    },
});

const ticketStatusChart = ref(null);
const ticketPriorityChart = ref(null);
const ticketCategoryChart = ref(null);

// Initialize charts
const initCharts = () => {
    // Ticket Status Chart
    if (ticketStatusChart.value && props.support?.by_status?.length > 0) {
        new Chart(ticketStatusChart.value, {
            type: 'doughnut',
            data: {
                labels: props.support.by_status.map(s => s.status),
                datasets: [{
                    data: props.support.by_status.map(s => s.count),
                    backgroundColor: [
                        'rgb(239, 68, 68)',
                        'rgb(234, 179, 8)',
                        'rgb(59, 130, 246)',
                        'rgb(34, 197, 94)',
                        'rgb(156, 163, 175)'
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

    // Ticket Priority Chart
    if (ticketPriorityChart.value && props.support?.by_priority?.length > 0) {
        new Chart(ticketPriorityChart.value, {
            type: 'bar',
            data: {
                labels: props.support.by_priority.map(p => p.priority),
                datasets: [{
                    label: 'Tickets',
                    data: props.support.by_priority.map(p => p.count),
                    backgroundColor: [
                        'rgb(239, 68, 68)',
                        'rgb(249, 115, 22)',
                        'rgb(234, 179, 8)',
                        'rgb(34, 197, 94)'
                    ],
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

    // Ticket Category Chart
    if (ticketCategoryChart.value && props.support?.by_category?.length > 0) {
        new Chart(ticketCategoryChart.value, {
            type: 'doughnut',
            data: {
                labels: props.support.by_category.map(c => c.category),
                datasets: [{
                    data: props.support.by_category.map(c => c.count),
                    backgroundColor: [
                        'rgb(79, 70, 229)',
                        'rgb(34, 197, 94)',
                        'rgb(249, 115, 22)',
                        'rgb(234, 179, 8)',
                        'rgb(156, 163, 175)'
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

watch(() => props.support, () => {
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
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.228 9c.549-1.165 2.03-2 3.772-2 2.21 0 4 1.343 4 3 0 1.4-1.278 2.575-3.006 2.907-.542.104-.994.54-.994 1.093m0 3h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm font-medium text-base-content/60">Total Tickets</p>
                        <p class="text-2xl font-semibold text-base-content">{{ support.total }}</p>
                    </div>
                </div>
            </div>
            <div class="bg-base-100 rounded-lg shadow-sm border border-base-300 p-6">
                <div class="flex items-center">
                    <div class="p-3 rounded-full bg-warning/10 text-warning">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm font-medium text-base-content/60">Open Tickets</p>
                        <p class="text-2xl font-semibold text-base-content">{{ support.open }}</p>
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
                        <p class="text-sm font-medium text-base-content/60">Resolved</p>
                        <p class="text-2xl font-semibold text-base-content">{{ support.resolved }}</p>
                    </div>
                </div>
            </div>
            <div class="bg-base-100 rounded-lg shadow-sm border border-base-300 p-6">
                <div class="flex items-center">
                    <div class="p-3 rounded-full bg-info/10 text-info">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm font-medium text-base-content/60">Avg Response Time</p>
                        <p class="text-2xl font-semibold text-base-content">{{ support.avg_response_time }}h</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Charts Row -->
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-6">
            <!-- By Status -->
            <div class="bg-base-100 rounded-lg shadow-sm border border-base-300 p-6">
                <h3 class="text-lg font-semibold text-base-content mb-4">Tickets by Status</h3>
                <div class="h-48">
                    <canvas ref="ticketStatusChart"></canvas>
                </div>
            </div>

            <!-- By Priority -->
            <div class="bg-base-100 rounded-lg shadow-sm border border-base-300 p-6">
                <h3 class="text-lg font-semibold text-base-content mb-4">Tickets by Priority</h3>
                <div class="h-48">
                    <canvas ref="ticketPriorityChart"></canvas>
                </div>
            </div>

            <!-- By Category -->
            <div class="bg-base-100 rounded-lg shadow-sm border border-base-300 p-6">
                <h3 class="text-lg font-semibold text-base-content mb-4">Tickets by Category</h3>
                <div class="h-48">
                    <canvas ref="ticketCategoryChart"></canvas>
                </div>
            </div>
        </div>

        <!-- Recent Tickets Table -->
        <div class="bg-base-100 rounded-lg shadow-sm border border-base-300 overflow-hidden">
            <div class="px-6 py-4 border-b border-base-300">
                <h3 class="text-lg font-semibold text-base-content">Recent Tickets</h3>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-base-300">
                    <thead class="bg-base-200">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-base-content/60 uppercase tracking-wider">Subject</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-base-content/60 uppercase tracking-wider">Tenant</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-base-content/60 uppercase tracking-wider">Status</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-base-content/60 uppercase tracking-wider">Priority</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-base-content/60 uppercase tracking-wider">Category</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-base-content/60 uppercase tracking-wider">Created</th>
                        </tr>
                    </thead>
                    <tbody class="bg-base-100 divide-y divide-base-300">
                        <tr v-for="ticket in support.recent" :key="ticket.id" class="hover:bg-base-200 transition-colors">
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-base-content">{{ ticket.subject }}</td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-base-content/60">{{ ticket.tenant_name }}</td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span :class="[
                                    'px-2 inline-flex text-xs leading-5 font-semibold rounded-full',
                                    ticket.status === 'resolved' ? 'bg-success/20 text-success' :
                                    ticket.status === 'open' ? 'bg-warning/20 text-warning' :
                                    'bg-base-200 text-base-content/70'
                                ]">
                                    {{ ticket.status }}
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span :class="[
                                    'px-2 inline-flex text-xs leading-5 font-semibold rounded-full',
                                    ticket.priority === 'urgent' ? 'bg-error/20 text-error' :
                                    ticket.priority === 'high' ? 'bg-warning/20 text-warning' :
                                    ticket.priority === 'medium' ? 'bg-info/20 text-info' :
                                    'bg-base-200 text-base-content/70'
                                ]">
                                    {{ ticket.priority }}
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-base-content/60 capitalize">{{ ticket.category }}</td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-base-content/60">{{ ticket.created_at }}</td>
                        </tr>
                        <tr v-if="support.recent?.length === 0">
                            <td colspan="6" class="px-6 py-12 text-center text-base-content/30">
                                No support tickets yet. Tickets will appear here when tenants submit support requests.
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</template>

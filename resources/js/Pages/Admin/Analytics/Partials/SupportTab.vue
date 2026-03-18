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
            <div class="bg-white rounded-lg shadow p-6">
                <div class="flex items-center">
                    <div class="p-3 rounded-full bg-indigo-100 text-indigo-600">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.228 9c.549-1.165 2.03-2 3.772-2 2.21 0 4 1.343 4 3 0 1.4-1.278 2.575-3.006 2.907-.542.104-.994.54-.994 1.093m0 3h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm font-medium text-gray-500">Total Tickets</p>
                        <p class="text-2xl font-semibold text-gray-900">{{ support.total }}</p>
                    </div>
                </div>
            </div>
            <div class="bg-white rounded-lg shadow p-6">
                <div class="flex items-center">
                    <div class="p-3 rounded-full bg-yellow-100 text-yellow-600">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm font-medium text-gray-500">Open Tickets</p>
                        <p class="text-2xl font-semibold text-gray-900">{{ support.open }}</p>
                    </div>
                </div>
            </div>
            <div class="bg-white rounded-lg shadow p-6">
                <div class="flex items-center">
                    <div class="p-3 rounded-full bg-green-100 text-green-600">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm font-medium text-gray-500">Resolved</p>
                        <p class="text-2xl font-semibold text-gray-900">{{ support.resolved }}</p>
                    </div>
                </div>
            </div>
            <div class="bg-white rounded-lg shadow p-6">
                <div class="flex items-center">
                    <div class="p-3 rounded-full bg-blue-100 text-blue-600">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm font-medium text-gray-500">Avg Response Time</p>
                        <p class="text-2xl font-semibold text-gray-900">{{ support.avg_response_time }}h</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Charts Row -->
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-6">
            <!-- By Status -->
            <div class="bg-white rounded-lg shadow p-6">
                <h3 class="text-lg font-semibold text-gray-900 mb-4">Tickets by Status</h3>
                <div class="h-48">
                    <canvas ref="ticketStatusChart"></canvas>
                </div>
            </div>

            <!-- By Priority -->
            <div class="bg-white rounded-lg shadow p-6">
                <h3 class="text-lg font-semibold text-gray-900 mb-4">Tickets by Priority</h3>
                <div class="h-48">
                    <canvas ref="ticketPriorityChart"></canvas>
                </div>
            </div>

            <!-- By Category -->
            <div class="bg-white rounded-lg shadow p-6">
                <h3 class="text-lg font-semibold text-gray-900 mb-4">Tickets by Category</h3>
                <div class="h-48">
                    <canvas ref="ticketCategoryChart"></canvas>
                </div>
            </div>
        </div>

        <!-- Recent Tickets Table -->
        <div class="bg-white rounded-lg shadow overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-200">
                <h3 class="text-lg font-semibold text-gray-900">Recent Tickets</h3>
            </div>
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Subject</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Tenant</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Priority</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Category</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Created</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    <tr v-for="ticket in support.recent" :key="ticket.id">
                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">{{ ticket.subject }}</td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ ticket.tenant_name }}</td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <span :class="[
                                'px-2 inline-flex text-xs leading-5 font-semibold rounded-full',
                                ticket.status === 'resolved' ? 'bg-green-100 text-green-800' :
                                ticket.status === 'open' ? 'bg-yellow-100 text-yellow-800' :
                                'bg-gray-100 text-gray-800'
                            ]">
                                {{ ticket.status }}
                            </span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <span :class="[
                                'px-2 inline-flex text-xs leading-5 font-semibold rounded-full',
                                ticket.priority === 'urgent' ? 'bg-red-100 text-red-800' :
                                ticket.priority === 'high' ? 'bg-orange-100 text-orange-800' :
                                ticket.priority === 'medium' ? 'bg-blue-100 text-blue-800' :
                                'bg-gray-100 text-gray-800'
                            ]">
                                {{ ticket.priority }}
                            </span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 capitalize">{{ ticket.category }}</td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ ticket.created_at }}</td>
                    </tr>
                    <tr v-if="support.recent?.length === 0">
                        <td colspan="6" class="px-6 py-8 text-center text-gray-500">
                            No support tickets yet. Tickets will appear here when tenants submit support requests.
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</template>

<script setup>
import { router } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { supportState } from '@/States/supportState';
import axios from 'axios';

const props = defineProps({
    tickets: Object,
    currentStatus: String,
    currentCategory: String,
    stats: Object,
});

const statusColors = {
    open: 'badge-info',
    in_progress: 'badge-warning',
    pending: 'badge-ghost',
    resolved: 'badge-success',
    closed: 'badge-neutral',
};

const categoryIcons = {
    billing: '💳',
    technical: '🐛',
    feature_request: '✨',
    account: '👤',
    other: '📌',
};

const priorityColors = {
    low: 'text-success',
    medium: 'text-info',
    high: 'text-warning',
    urgent: 'text-error',
};

const setFilter = (key, value) => {
    const params = {};
    if (key === 'status') params.status = value;
    if (key === 'category') params.category = value;
    router.get(route('admin.support.index'), params, {
        preserveState: true,
        preserveScroll: true,
    });
};

const viewTicket = (ticket) => {
    supportState.viewTicket(ticket.id);
};

const formatTime = (d) => {
    if (!d) return '';
    return new Date(d).toLocaleString();
};
</script>

<template>
    <AdminLayout>
        <template #header>
            <h1 class="text-xl font-bold text-base-content leading-tight line-clamp-1">Support & Tickets</h1>
        </template>

        <div class="max-w-7xl mx-auto space-y-6">
            <!-- Stats -->
            <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                <div class="bg-base-100 rounded-2xl p-5 border border-base-300 shadow-sm">
                    <p class="text-[10px] font-bold uppercase tracking-widest text-base-content/40">Total</p>
                    <p class="text-2xl font-black text-base-content mt-1">{{ stats.total }}</p>
                </div>
                <div class="bg-base-100 rounded-2xl p-5 border border-base-300 shadow-sm">
                    <p class="text-[10px] font-bold uppercase tracking-widest text-info">Open</p>
                    <p class="text-2xl font-black text-info mt-1">{{ stats.open }}</p>
                </div>
                <div class="bg-base-100 rounded-2xl p-5 border border-base-300 shadow-sm">
                    <p class="text-[10px] font-bold uppercase tracking-widest text-warning">In Progress</p>
                    <p class="text-2xl font-black text-warning mt-1">{{ stats.in_progress }}</p>
                </div>
                <div class="bg-base-100 rounded-2xl p-5 border border-base-300 shadow-sm">
                    <p class="text-[10px] font-bold uppercase tracking-widest text-success">Resolved</p>
                    <p class="text-2xl font-black text-success mt-1">{{ stats.resolved }}</p>
                </div>
            </div>

            <!-- Filters -->
            <div class="flex flex-wrap gap-2">
                <button v-for="s in ['all','open','in_progress','pending','resolved','closed']" :key="s"
                    @click="setFilter('status', s)"
                    :class="currentStatus === s ? 'btn-primary text-white' : 'btn-ghost'"
                    class="btn btn-sm rounded-xl font-bold text-xs uppercase tracking-wider"
                >
                    {{ s.replace('_', ' ') }}
                </button>
            </div>

            <!-- Tickets Table -->
            <div class="bg-base-100 rounded-2xl border border-base-300 overflow-hidden shadow-sm">
                <div class="overflow-x-auto">
                    <table class="table table-sm">
                        <thead>
                            <tr class="text-[10px] uppercase tracking-widest text-base-content/40">
                                <th>ID</th>
                                <th>Tenant</th>
                                <th>Subject</th>
                                <th>Category</th>
                                <th>Priority</th>
                                <th>Status</th>
                                <th>Last Update</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="ticket in tickets.data" :key="ticket.id" class="hover">
                                <td class="font-mono text-xs text-base-content/50">#{{ ticket.id }}</td>
                                <td class="text-xs font-semibold">{{ ticket.tenant?.name || `Tenant ${ticket.tenant_id}` }}</td>
                                <td>
                                    <p class="text-sm font-bold truncate max-w-[200px]">{{ ticket.subject }}</p>
                                    <p class="text-xs text-base-content/40 truncate max-w-[200px]">{{ ticket.latest_message?.content || ticket.description }}</p>
                                </td>
                                <td>
                                    <span class="text-xs">{{ categoryIcons[ticket.category] }} {{ ticket.category }}</span>
                                </td>
                                <td>
                                    <span class="text-xs font-bold uppercase" :class="priorityColors[ticket.priority]">{{ ticket.priority }}</span>
                                </td>
                                <td>
                                    <span :class="statusColors[ticket.status]" class="badge badge-sm text-[10px] font-bold uppercase">
                                        {{ ticket.status?.replace('_', ' ') }}
                                    </span>
                                </td>
                                <td class="text-xs text-base-content/40">{{ formatTime(ticket.updated_at) }}</td>
                                <td>
                                    <button @click="viewTicket(ticket)" class="btn btn-ghost btn-xs btn-circle">
                                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="w-4 h-4">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 0 1 0-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178Z" />
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" />
                                        </svg>
                                    </button>
                                </td>
                            </tr>
                            <tr v-if="!tickets.data?.length">
                                <td colspan="8" class="text-center py-12 text-base-content/30 text-sm font-medium">
                                    No tickets found
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>

<style scoped>
</style>

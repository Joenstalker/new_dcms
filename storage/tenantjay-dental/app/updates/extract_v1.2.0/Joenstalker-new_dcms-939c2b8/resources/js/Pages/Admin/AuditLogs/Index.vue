<script setup>
import { Head, Link, router } from '@inertiajs/vue3';
import { ref } from 'vue';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import debounce from 'lodash/debounce';

const props = defineProps({
    logs: Object,
    actionTypes: Array,
    filters: Object,
});

const search = ref(props.filters.search || '');
const action = ref(props.filters.action || '');
const dateFrom = ref(props.filters.dateFrom || '');
const dateTo = ref(props.filters.dateTo || '');

const applyFilters = debounce(() => {
    router.get(
        route('admin.audit-logs.index'),
        {
            search: search.value || undefined,
            action: action.value || undefined,
            date_from: dateFrom.value || undefined,
            date_to: dateTo.value || undefined,
        },
        { preserveState: true, replace: true }
    );
}, 300);

const actionBadgeClass = (action) => {
    if (action.includes('deleted') || action.includes('suspended')) return 'bg-error/20 text-error';
    if (action.includes('created')) return 'bg-success/20 text-success';
    if (action.includes('updated') || action.includes('synced')) return 'bg-info/20 text-info';
    return 'bg-base-200 text-base-content/70';
};

const formatDate = (dateStr) => {
    if (!dateStr) return '—';
    return new Date(dateStr).toLocaleString('en-PH', {
        year: 'numeric', month: 'short', day: 'numeric',
        hour: '2-digit', minute: '2-digit'
    });
};
</script>

<template>
    <Head title="Audit Logs" />
    <AdminLayout>
        <template #header>
            <h1 class="text-xl font-bold text-base-content">Audit Logs</h1>
        </template>

        <div class="max-w-7xl mx-auto space-y-6">

            <!-- Filters -->
            <div class="bg-base-100 p-5 rounded-xl shadow-sm border border-base-300">
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                    <!-- Search -->
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                            <svg class="h-4 w-4 text-base-content/40" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                            </svg>
                        </div>
                        <input
                            v-model="search"
                            @input="applyFilters"
                            type="text"
                            placeholder="Search description, IP..."
                            class="block w-full pl-9 pr-3 py-2 border border-base-300 bg-base-100 text-base-content rounded-lg text-sm focus:outline-none focus:ring-primary focus:border-primary"
                        />
                    </div>

                    <!-- Action filter -->
                    <select
                        v-model="action"
                        @change="applyFilters"
                        class="block w-full py-2 px-3 border border-base-300 bg-base-100 text-base-content rounded-lg text-sm focus:outline-none focus:ring-primary focus:border-primary"
                    >
                        <option value="">All Actions</option>
                        <option v-for="type in actionTypes" :key="type" :value="type">{{ type }}</option>
                    </select>

                    <!-- Date From -->
                    <input
                        v-model="dateFrom"
                        @change="applyFilters"
                        type="date"
                        class="block w-full py-2 px-3 border border-base-300 bg-base-100 text-base-content rounded-lg text-sm focus:outline-none focus:ring-primary focus:border-primary"
                        placeholder="From date"
                    />

                    <!-- Date To -->
                    <input
                        v-model="dateTo"
                        @change="applyFilters"
                        type="date"
                        class="block w-full py-2 px-3 border border-base-300 bg-base-100 text-base-content rounded-lg text-sm focus:outline-none focus:ring-primary focus:border-primary"
                        placeholder="To date"
                    />
                </div>
            </div>

            <!-- Table -->
            <div class="bg-base-100 rounded-xl shadow-sm border border-base-300 overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-base-300">
                        <thead class="bg-base-200">
                            <tr>
                                <th class="px-6 py-4 text-left text-xs font-semibold text-base-content/60 uppercase tracking-wider">Timestamp</th>
                                <th class="px-6 py-4 text-left text-xs font-semibold text-base-content/60 uppercase tracking-wider">Admin</th>
                                <th class="px-6 py-4 text-left text-xs font-semibold text-base-content/60 uppercase tracking-wider">Action</th>
                                <th class="px-6 py-4 text-left text-xs font-semibold text-base-content/60 uppercase tracking-wider">Description</th>
                                <th class="px-6 py-4 text-left text-xs font-semibold text-base-content/60 uppercase tracking-wider">Target</th>
                                <th class="px-6 py-4 text-left text-xs font-semibold text-base-content/60 uppercase tracking-wider">IP</th>
                            </tr>
                        </thead>
                        <tbody class="bg-base-100 divide-y divide-base-300">
                            <tr v-for="log in logs.data" :key="log.id" class="hover:bg-base-200 transition-colors">
                                <td class="px-6 py-4 whitespace-nowrap text-xs text-base-content/50">
                                    {{ formatDate(log.created_at) }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-base-content">
                                    {{ log.admin?.name || 'System' }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span
                                        class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium"
                                        :class="actionBadgeClass(log.action)"
                                    >
                                        {{ log.action }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 text-sm text-base-content max-w-xs truncate">
                                    {{ log.description }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-xs text-base-content/50">
                                    <span v-if="log.target_type">{{ log.target_type }} #{{ log.target_id?.substring(0, 8) }}</span>
                                    <span v-else class="text-base-content/20">—</span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-xs text-base-content/40">
                                    {{ log.ip_address || '—' }}
                                </td>
                            </tr>
                            <tr v-if="!logs.data?.length">
                                <td colspan="6" class="px-6 py-12 text-center text-base-content/30">
                                    <svg class="mx-auto h-10 w-10 text-base-content/20 mb-3" fill="none" viewBox="0 0 24 24" stroke-width="1" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h3.75M9 15h3.75M9 18h3.75m3 .75H18a2.25 2.25 0 0 0 2.25-2.25V6.108c0-1.135-.845-2.098-1.976-2.192a48.424 48.424 0 0 0-1.123-.08m-5.801 0c-.065.21-.1.433-.1.664 0 .414.336.75.75.75h4.5a.75.75 0 0 0 .75-.75 2.25 2.25 0 0 0-.1-.664m-5.8 0A2.251 2.251 0 0 1 13.5 2.25H15c1.012 0 1.867.668 2.15 1.586m-5.8 0c-.376.023-.75.05-1.124.08C9.095 4.01 8.25 4.973 8.25 6.108V8.25m0 0H4.875c-.621 0-1.125.504-1.125 1.125v11.25c0 .621.504 1.125 1.125 1.125h9.75c.621 0 1.125-.504 1.125-1.125V9.375c0-.621-.504-1.125-1.125-1.125H8.25ZM6.75 12h.008v.008H6.75V12Zm0 3h.008v.008H6.75V15Zm0 3h.008v.008H6.75V18Z" />
                                    </svg>
                                    <p class="text-sm font-medium text-base-content/50">No audit logs found</p>
                                    <p class="text-xs text-base-content/30 mt-1">Admin actions will be recorded here.</p>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <div v-if="logs.links && logs.links.length > 3" class="bg-base-100 px-4 py-3 border-t border-base-300 sm:px-6">
                    <div class="flex items-center justify-between">
                        <p class="text-sm text-base-content/70">
                            Showing <span class="font-medium">{{ logs.from || 0 }}</span> to
                            <span class="font-medium">{{ logs.to || 0 }}</span> of
                            <span class="font-medium">{{ logs.total }}</span> entries
                        </p>
                        <nav class="relative z-0 inline-flex rounded-md shadow-sm -space-x-px">
                            <template v-for="(link, i) in logs.links" :key="i">
                                <Link
                                    v-if="link.url"
                                    :href="link.url"
                                    v-html="link.label"
                                    class="relative inline-flex items-center px-4 py-2 border text-sm font-medium"
                                    :class="[
                                        link.active ? 'z-10 bg-primary/10 border-primary text-primary' : 'bg-base-100 border-base-300 text-base-content/50 hover:bg-base-200',
                                        i === 0 ? 'rounded-l-md' : '',
                                        i === logs.links.length - 1 ? 'rounded-r-md' : ''
                                    ]"
                                />
                                <span
                                    v-else
                                    v-html="link.label"
                                    class="relative inline-flex items-center px-4 py-2 border border-base-300 bg-base-100 text-sm font-medium text-base-content/20 cursor-not-allowed"
                                ></span>
                            </template>
                        </nav>
                    </div>
                </div>
            </div>

        </div>
    </AdminLayout>
</template>

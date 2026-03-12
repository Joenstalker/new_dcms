<script setup>
import { Head, Link, router } from '@inertiajs/vue3';
import { ref, watch } from 'vue';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import debounce from 'lodash/debounce';

const props = defineProps({
    tenants: Object,
    filters: Object,
});

const search = ref(props.filters.search || '');
const statusFilter = ref(props.filters.status || '');

const updateSearch = debounce(() => {
    router.get(
        route('admin.tenants.index'),
        { search: search.value, status: statusFilter.value },
        { preserveState: true, replace: true }
    );
}, 300);

watch(statusFilter, () => {
    updateSearch();
});
</script>

<template>
    <Head title="Tenants Management" />
    <AdminLayout>
        <template #header>
            <h1 class="text-xl font-bold text-gray-900">Clinics Management</h1>
        </template>

        <div class="max-w-7xl mx-auto space-y-6">
            <!-- Header and Filters -->
            <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 bg-white p-5 rounded-xl shadow-sm border border-gray-100">
                <div class="flex-1 w-full sm:w-auto flex flex-col sm:flex-row gap-4">
                    <!-- Search -->
                    <div class="relative w-full sm:w-72">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                            <svg class="h-5 w-5 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                            </svg>
                        </div>
                        <input
                            v-model="search"
                            @input="updateSearch"
                            type="text"
                            placeholder="Search by domain..."
                            class="block w-full pl-10 pr-3 py-2 border border-gray-300 rounded-lg leading-5 bg-white placeholder-gray-500 focus:outline-none focus:ring-teal-500 focus:border-teal-500 sm:text-sm transition duration-150 ease-in-out"
                        />
                    </div>

                    <!-- Status Filter -->
                    <select
                        v-model="statusFilter"
                        class="block w-full sm:w-48 pl-3 pr-10 py-2 text-base border-gray-300 focus:outline-none focus:ring-teal-500 focus:border-teal-500 sm:text-sm rounded-lg"
                    >
                        <option value="">All Statuses</option>
                        <option value="active">Active</option>
                        <option value="pending_payment">Pending Payment</option>
                        <option value="suspended">Suspended</option>
                    </select>
                </div>
            </div>

            <!-- Tenants Table -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th scope="col" class="px-6 py-4 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">
                                    Clinic Domain
                                </th>
                                <th scope="col" class="px-6 py-4 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">
                                    Status
                                </th>
                                <th scope="col" class="px-6 py-4 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">
                                    Plan
                                </th>
                                <th scope="col" class="px-6 py-4 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">
                                    Registered On
                                </th>
                                <th scope="col" class="px-6 py-4 text-right text-xs font-semibold text-gray-500 uppercase tracking-wider">
                                    Actions
                                </th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <tr v-for="tenant in tenants.data" :key="tenant.id" class="hover:bg-gray-50 transition-colors">
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="flex items-center">
                                        <div class="flex-shrink-0 h-10 w-10 bg-teal-100 rounded-lg flex items-center justify-center">
                                            <span class="text-teal-700 font-bold tracking-tight">
                                                {{ tenant.id.substring(0, 2).toUpperCase() }}
                                            </span>
                                        </div>
                                        <div class="ml-4">
                                            <div class="text-sm font-medium text-gray-900">{{ tenant.id }}</div>
                                            <a v-if="tenant.domains?.length" :href="'http://' + tenant.domains[0].domain" target="_blank" class="text-xs text-teal-600 hover:underline">
                                                {{ tenant.domains[0].domain }}
                                            </a>
                                            <span v-else class="text-xs text-gray-500">No domain assigned</span>
                                        </div>
                                    </div>
                                </td>
                                
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span v-if="(tenant.subscription_status || 'active') === 'active'" class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                        Active
                                    </span>
                                    <span v-else-if="tenant.subscription_status === 'suspended'" class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800">
                                        Suspended
                                    </span>
                                    <span v-else-if="tenant.subscription_status === 'pending_payment'" class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800">
                                        Pending Payment
                                    </span>
                                    <span v-else class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-800">
                                        {{ tenant.subscription_status }}
                                    </span>
                                </td>

                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    {{ tenant.plan || 'Free / Manual' }}
                                </td>

                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    {{ new Date(tenant.created_at).toLocaleDateString() }}
                                </td>

                                <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                    <Link :href="route('admin.tenants.show', tenant.id)" class="text-teal-600 hover:text-teal-900 bg-teal-50 px-3 py-1.5 rounded-md hover:bg-teal-100 transition-colors">
                                        Manage
                                    </Link>
                                </td>
                            </tr>
                            <tr v-if="tenants.data.length === 0">
                                <td colspan="5" class="px-6 py-12 text-center text-gray-500">
                                    <svg class="mx-auto h-12 w-12 text-gray-400 mb-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                                    </svg>
                                    <p class="text-base font-medium text-gray-900">No clinics found</p>
                                    <p class="text-sm">Try adjusting your search or filters.</p>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <div v-if="tenants.links && tenants.links.length > 3" class="bg-white px-4 py-3 border-t border-gray-200 sm:px-6">
                    <div class="flex items-center justify-between">
                        <div class="hidden sm:flex-1 sm:flex sm:items-center sm:justify-between">
                            <div>
                                <p class="text-sm text-gray-700">
                                    Showing <span class="font-medium">{{ tenants.from || 0 }}</span> to <span class="font-medium">{{ tenants.to || 0 }}</span> of <span class="font-medium">{{ tenants.total }}</span> results
                                </p>
                            </div>
                            <div>
                                <nav class="relative z-0 inline-flex rounded-md shadow-sm -space-x-px" aria-label="Pagination">
                                    <template v-for="(link, i) in tenants.links" :key="i">
                                        <Link
                                            v-if="link.url"
                                            :href="link.url"
                                            v-html="link.label"
                                            class="relative inline-flex items-center px-4 py-2 border text-sm font-medium"
                                            :class="[
                                                link.active ? 'z-10 bg-teal-50 border-teal-500 text-teal-600' : 'bg-white border-gray-300 text-gray-500 hover:bg-gray-50',
                                                i === 0 ? 'rounded-l-md' : '',
                                                i === tenants.links.length - 1 ? 'rounded-r-md' : ''
                                            ]"
                                        />
                                        <span
                                            v-else
                                            v-html="link.label"
                                            class="relative inline-flex items-center px-4 py-2 border border-gray-300 bg-white text-sm font-medium text-gray-300 cursor-not-allowed"
                                            :class="[
                                                i === 0 ? 'rounded-l-md' : '',
                                                i === tenants.links.length - 1 ? 'rounded-r-md' : ''
                                            ]"
                                        ></span>
                                    </template>
                                </nav>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>

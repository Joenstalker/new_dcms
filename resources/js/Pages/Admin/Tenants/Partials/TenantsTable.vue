<script setup>
import { Link } from '@inertiajs/vue3';

defineProps({
    tenants: {
        type: Object,
        required: true,
    },
});

const emit = defineEmits(['manage', 'review']);

const openManageModal = (tenant) => {
    emit('manage', tenant);
};

const openReviewModal = (tenant) => {
    emit('review', tenant);
};
</script>

<template>
    <div class="bg-base-100 rounded-xl shadow-sm border border-base-300 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-base-300">
                <thead class="bg-base-200">
                    <tr>
                        <th scope="col" class="px-6 py-4 text-left text-xs font-semibold text-base-content/70 uppercase tracking-wider">
                            Name
                        </th>
                        <th scope="col" class="px-6 py-4 text-left text-xs font-semibold text-base-content/70 uppercase tracking-wider">
                            Clinic Name
                        </th>
                        <th scope="col" class="px-6 py-4 text-left text-xs font-semibold text-base-content/70 uppercase tracking-wider">
                            Clinic Domain (URL)
                        </th>
                        <th scope="col" class="px-6 py-4 text-left text-xs font-semibold text-base-content/70 uppercase tracking-wider">
                            Status
                        </th>
                        <th scope="col" class="px-6 py-4 text-left text-xs font-semibold text-base-content/70 uppercase tracking-wider">
                            Plan
                        </th>
                        <th scope="col" class="px-6 py-4 text-left text-xs font-semibold text-base-content/70 uppercase tracking-wider">
                            Created
                        </th>
                        <th scope="col" class="px-6 py-4 text-right text-xs font-semibold text-gray-500 uppercase tracking-wider">
                            Action
                        </th>
                    </tr>
                </thead>
                <tbody class="bg-base-100 divide-y divide-base-300">
                    <tr v-for="tenant in tenants.data" :key="tenant.id" class="hover:bg-base-200/50 transition-colors">
                        <!-- Name Column -->
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="text-sm font-medium text-base-content">
                                {{ tenant.owner_name || tenant.name }}
                            </div>
                        </td>
                        
                        <!-- Clinic Name Column -->
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="text-sm text-base-content">{{ tenant.name }}</div>
                        </td>
                        
                        <!-- Clinic Domain (URL) Column -->
                        <td class="px-6 py-4 whitespace-nowrap">
                            <a v-if="tenant.tenant_url" :href="tenant.tenant_url" target="_blank" class="text-sm text-teal-600 hover:underline font-medium">
                                {{ tenant.tenant_url.replace(/(^\w+:|^)\/\//, '') }}
                            </a>
                            <span v-else class="text-sm text-base-content/50">No URL</span>
                        </td>

                        <!-- Status Column -->
                        <td class="px-6 py-4 whitespace-nowrap">
                            <span v-if="tenant.status === 'pending'" class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800">
                                Pending Review
                            </span>
                            <span v-else-if="(tenant.subscription_status || 'active') === 'active'" class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
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

                        <!-- Plan Column -->
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-base-content/70">
                            {{ tenant.plan || 'Free / Manual' }}
                        </td>

                        <!-- Created Column -->
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                            {{ new Date(tenant.created_at).toLocaleDateString() }}
                        </td>

                        <!-- Action Column -->
                        <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                            <button v-if="tenant.status === 'pending'" @click="openReviewModal(tenant)" class="text-yellow-600 hover:text-yellow-900 bg-yellow-50 px-3 py-1.5 rounded-md hover:bg-yellow-100 transition-colors">
                                Review
                            </button>
                            <button v-else @click="openManageModal(tenant)" class="text-teal-600 hover:text-teal-900 bg-teal-50 px-3 py-1.5 rounded-md hover:bg-teal-100 transition-colors">
                                Manage
                            </button>
                        </td>
                    </tr>
                    <tr v-if="tenants.data.length === 0">
                        <td colspan="6" class="px-6 py-12 text-center text-base-content/50">
                            <svg class="mx-auto h-12 w-12 text-base-content/30 mb-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                            </svg>
                            <p class="text-base font-medium text-base-content">No clinics found</p>
                            <p class="text-sm">Try adjusting your search or filters.</p>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        <div v-if="tenants.links && tenants.links.length > 3" class="bg-base-100 px-4 py-3 border-t border-base-300 sm:px-6">
            <div class="flex items-center justify-between">
                <div class="hidden sm:flex-1 sm:flex sm:items-center sm:justify-between">
                    <div>
                        <p class="text-sm text-base-content/70">
                            Showing <span class="font-medium text-base-content">{{ tenants.from || 0 }}</span> to <span class="font-medium text-base-content">{{ tenants.to || 0 }}</span> of <span class="font-medium text-base-content">{{ tenants.total }}</span> results
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
                                        link.active ? 'z-10 bg-primary/10 border-primary text-primary' : 'bg-base-100 border-base-300 text-base-content/70 hover:bg-base-200',
                                        i === 0 ? 'rounded-l-md' : '',
                                        i === tenants.links.length - 1 ? 'rounded-r-md' : ''
                                    ]"
                                />
                                <span
                                    v-else
                                    v-html="link.label"
                                    class="relative inline-flex items-center px-4 py-2 border border-base-300 bg-base-100 text-sm font-medium text-base-content/30 cursor-not-allowed"
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
</template>

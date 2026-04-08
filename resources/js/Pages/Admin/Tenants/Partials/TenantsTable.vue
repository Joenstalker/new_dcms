<script setup>
import { Link } from '@inertiajs/vue3';

defineProps({
    tenants: {
        type: Object,
        required: true,
    },
    previewTenantId: {
        type: String,
        default: 'preview-sandbox',
    },
    primaryColor: {
        type: String,
        default: '#0ea5e9'
    }
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
        <div>
            <table class="min-w-full block lg:table border-collapse">
                <thead class="bg-base-200 hidden lg:table-header-group border-b border-base-300">
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
                        <th scope="col" class="px-6 py-4 text-right text-xs font-semibold text-base-content/70 uppercase tracking-wider">
                            Action
                        </th>
                    </tr>
                </thead>
                <tbody class="bg-base-100 block lg:table-row-group">
                    <tr v-for="tenant in tenants.data" :key="tenant.id" class="block lg:table-row hover:bg-base-200/50 transition-colors mb-4 lg:mb-0 rounded-lg lg:rounded-none overflow-hidden">
                        <!-- Name Column -->
                        <td class="px-4 lg:px-6 py-3 lg:py-4 flex lg:table-cell justify-between items-center text-sm border-b border-base-300">
                            <span class="lg:hidden font-bold text-xs text-base-content/50 uppercase tracking-wide">Owner Name</span>
                            <div class="font-medium text-base-content text-right lg:text-left">
                                {{ tenant.owner_name || tenant.name }}
                                <span
                                    v-if="tenant.id === previewTenantId"
                                    class="ml-2 inline-flex items-center rounded-full bg-amber-100 px-2 py-0.5 text-[10px] font-bold uppercase tracking-wide text-amber-700"
                                >
                                    Preview
                                </span>
                            </div>
                        </td>
                        
                        <!-- Clinic Name Column -->
                        <td class="px-4 lg:px-6 py-3 lg:py-4 flex lg:table-cell justify-between items-center text-sm border-b border-base-300">
                            <span class="lg:hidden font-bold text-xs text-base-content/50 uppercase tracking-wide">Clinic Name</span>
                            <div class="text-base-content text-right lg:text-left">{{ tenant.name }}</div>
                        </td>
                        
                        <!-- Clinic Domain (URL) Column -->
                        <td class="px-4 lg:px-6 py-3 lg:py-4 flex lg:table-cell justify-between items-center text-sm border-b border-base-300">
                            <span class="lg:hidden font-bold text-xs text-base-content/50 uppercase tracking-wide">Domain</span>
                            <a v-if="tenant.tenant_url" :href="tenant.tenant_url" target="_blank" class="text-teal-600 hover:underline font-medium text-right lg:text-left bg-teal-50 lg:bg-transparent px-2 py-0.5 rounded-md lg:px-0 lg:py-0">
                                {{ tenant.tenant_url.replace(/(^\w+:|^)\/\//, '') }}
                            </a>
                            <span v-else class="text-base-content/50 text-right lg:text-left">No URL</span>
                        </td>

                        <!-- Status Column -->
                        <td class="px-4 lg:px-6 py-3 lg:py-4 flex lg:table-cell justify-between items-center text-sm border-b border-base-300">
                            <span class="lg:hidden font-bold text-xs text-base-content/50 uppercase tracking-wide">Status</span>
                            <div class="flex items-center justify-end lg:justify-start bg-base-100 lg:bg-transparent px-2 py-1 rounded-full lg:px-0 lg:py-0">
                                <span class="flex-shrink-0 w-2 h-2 rounded-full mr-2"
                                    :class="{
                                        'bg-success animate-pulse': tenant.status === 'active',
                                        'bg-error': tenant.status === 'suspended',
                                        'bg-warning animate-pulse': tenant.status === 'pending_payment' || tenant.status === 'pending',
                                        'bg-gray-400': !tenant.status || tenant.status === 'cancelled'
                                    }"
                                ></span>
                                <span class="text-xs font-bold capitalize text-base-content">
                                    {{ (tenant.status || 'active').replace('_', ' ') }}
                                </span>
                            </div>
                        </td>

                        <!-- Plan Column -->
                        <td class="px-4 lg:px-6 py-3 lg:py-4 flex lg:table-cell justify-between items-center text-sm border-b border-base-300">
                            <span class="lg:hidden font-bold text-xs text-base-content/50 uppercase tracking-wide">Plan</span>
                            <div class="text-base-content/70 font-semibold text-right lg:text-left">
                                {{ tenant.id === previewTenantId ? 'Preview Sandbox' : (tenant.plan || 'Free / Manual') }}
                            </div>
                        </td>

                        <!-- Created Column -->
                        <td class="px-4 lg:px-6 py-3 lg:py-4 flex lg:table-cell justify-between items-center text-sm border-b border-base-300">
                            <span class="lg:hidden font-bold text-xs text-base-content/50 uppercase tracking-wide">Created</span>
                            <div class="text-base-content/50 text-right lg:text-left font-mono text-xs">
                                {{ new Date(tenant.created_at).toLocaleDateString() }}
                            </div>
                        </td>

                        <!-- Action Column -->
                        <td class="px-4 lg:px-6 py-4 flex lg:table-cell align-middle text-sm font-medium border-b border-base-300 bg-base-200/30 lg:bg-transparent w-full lg:w-auto">
                            <div class="flex justify-center lg:justify-end w-full">
                                <button v-if="tenant.status === 'pending'" @click="openReviewModal(tenant)" class="w-full lg:w-auto justify-center text-yellow-700 hover:text-yellow-900 bg-yellow-100/50 lg:bg-yellow-50 px-4 py-2 lg:px-3 lg:py-1.5 rounded-lg hover:bg-yellow-100 transition-colors outline flex items-center gap-2 outline-1 outline-yellow-400/30 font-bold">
                                    Review Application
                                </button>
                                <button v-else @click="openManageModal(tenant)" class="w-full lg:w-auto justify-center px-4 py-2 lg:px-3 lg:py-1.5 rounded-lg transition-colors font-bold flex items-center gap-2 border lg:border-transparent lg:shadow-sm hover:opacity-80" :style="{ backgroundColor: primaryColor + '15', color: primaryColor }">
                                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" />
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                    </svg>
                                    Manage Clinic
                                </button>
                            </div>
                        </td>
                    </tr>
                    <tr v-if="tenants.data.length === 0" class="block lg:table-row">
                        <td colspan="7" class="px-6 py-12 text-center text-base-content/50 block lg:table-cell">
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
                                    class="relative inline-flex items-center px-4 py-2 border text-sm font-medium transition-all"
                                    :class="[
                                        link.active ? 'z-10 text-white border-transparent' : 'bg-base-100 border-base-300 text-base-content/70 hover:bg-base-200',
                                        i === 0 ? 'rounded-l-md' : '',
                                        i === tenants.links.length - 1 ? 'rounded-r-md' : ''
                                    ]"
                                    :style="link.active ? { backgroundColor: primaryColor, boxShadow: `0 4px 14px 0 ${primaryColor}33` } : {}"
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

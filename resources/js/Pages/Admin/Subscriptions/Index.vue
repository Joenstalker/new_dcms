<script setup>
import { Head, Link, router } from '@inertiajs/vue3';
import { ref } from 'vue';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import debounce from 'lodash/debounce';

const props = defineProps({
    subscriptions: Object,
    filters: Object,
});

const search = ref(props.filters.search || '');

const updateSearch = debounce(() => {
    router.get(
        route('admin.subscriptions.index'),
        { search: search.value },
        { preserveState: true, replace: true }
    );
}, 300);
</script>

<template>
    <Head title="Billing & Subscriptions" />
    <AdminLayout>
        <template #header>
            <div class="flex items-center justify-between w-full">
                <h1 class="text-xl font-bold text-base-content">Billing & Subscriptions</h1>
            </div>
        </template>

        <div class="max-w-7xl mx-auto space-y-6">

            <!-- Tabs -->
            <div class="tabs tabs-boxed inline-flex bg-base-200 border border-base-300">
                <Link
                    href="/admin/subscriptions"
                    class="tab tab-active transition-colors font-medium"
                >
                    Active Subscriptions
                </Link>
                <Link
                    href="/admin/pending-registrations"
                    class="tab transition-colors font-medium hover:text-base-content"
                >
                    Pending Registrations
                </Link>
            </div>
            
            <!-- Search -->
            <div class="bg-base-100 p-5 rounded-xl shadow-sm border border-base-300 flex justify-between">
                <div class="relative w-full sm:w-96">
                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                        <svg class="h-5 w-5 text-base-content/30" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                        </svg>
                    </div>
                    <input
                        v-model="search"
                        @input="updateSearch"
                        type="text"
                        placeholder="Search by clinic ID or domain..."
                        class="block w-full pl-10 pr-3 py-2 border border-base-300 rounded-lg leading-5 bg-base-100 text-base-content placeholder-base-content/50 focus:outline-none focus:ring-primary focus:border-primary sm:text-sm transition shadow-sm"
                    />
                </div>
                
                <div class="hidden sm:block">
                    <!-- Placeholder for export button -->
                    <button disabled class="bg-base-100 border border-base-300 text-base-content/30 px-4 py-2 flex items-center rounded-lg text-sm font-medium cursor-not-allowed hidden">
                        <svg class="h-4 w-4 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" /></svg>
                        Export CSV
                    </button>
                </div>
            </div>

            <!-- Table -->
            <div class="bg-base-100 rounded-xl shadow-sm border border-base-300 overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-base-300">
                        <thead class="bg-base-200">
                            <tr>
                                <th scope="col" class="px-6 py-4 text-left text-xs font-semibold text-base-content/60 uppercase tracking-wider">
                                    Clinic
                                </th>
                                <th scope="col" class="px-6 py-4 text-left text-xs font-semibold text-base-content/60 uppercase tracking-wider">
                                    Plan
                                </th>
                                <th scope="col" class="px-6 py-4 text-left text-xs font-semibold text-base-content/60 uppercase tracking-wider">
                                    Stripe Status
                                </th>
                                <th scope="col" class="px-6 py-4 text-left text-xs font-semibold text-base-content/60 uppercase tracking-wider">
                                    Billing Cycle Ends
                                </th>
                            </tr>
                        </thead>
                        <tbody class="bg-base-100 divide-y divide-base-300">
                            <tr v-for="sub in subscriptions.data" :key="sub.id" class="hover:bg-base-200 transition-colors">
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm font-medium text-base-content">{{ sub.tenant_id }}</div>
                                    <div class="text-xs text-base-content/50" v-if="sub.tenant?.domains?.length">
                                        {{ sub.tenant.domains[0].domain }}
                                    </div>
                                </td>
                                
                                <td class="px-6 py-4 whitespace-nowrap font-medium text-sm text-base-content/80">
                                    {{ sub.plan?.name || 'Unknown' }}
                                </td>

                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span v-if="sub.stripe_status === 'active'" class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-success/20 text-success">
                                        Active
                                    </span>
                                    <span v-else-if="sub.stripe_status === 'past_due' || sub.stripe_status === 'unpaid'" class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-error/20 text-error">
                                        Past Due
                                    </span>
                                    <span v-else-if="sub.stripe_status === 'canceled'" class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-neutral text-neutral-content">
                                        Canceled
                                    </span>
                                    <span v-else class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-warning/20 text-warning">
                                        {{ sub.stripe_status || 'Pending' }}
                                    </span>
                                </td>

                                <td class="px-6 py-4 whitespace-nowrap text-sm text-base-content/50">
                                    {{ sub.ends_at ? new Date(sub.ends_at).toLocaleDateString() : 'Auto-renews' }}
                                </td>
                            </tr>
                            <tr v-if="subscriptions.data.length === 0">
                                <td colspan="4" class="px-6 py-12 text-center text-base-content/30">
                                    <svg class="mx-auto h-12 w-12 text-base-content/20 mb-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z" />
                                    </svg>
                                    <p class="text-base font-medium text-base-content/50">No subscriptions found</p>
                                    <p class="text-sm">When clinics subscribe with Stripe, they will appear here.</p>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <div v-if="subscriptions.links && subscriptions.links.length > 3" class="bg-base-100 px-4 py-3 border-t border-base-300 sm:px-6">
                    <div class="flex items-center justify-between">
                        <div class="hidden sm:flex-1 sm:flex sm:items-center sm:justify-between">
                            <div>
                                <p class="text-sm text-base-content/70">
                                    Showing <span class="font-medium">{{ subscriptions.from || 0 }}</span> to <span class="font-medium">{{ subscriptions.to || 0 }}</span> of <span class="font-medium">{{ subscriptions.total }}</span> results
                                </p>
                            </div>
                            <div>
                                <nav class="relative z-0 inline-flex rounded-md shadow-sm -space-x-px" aria-label="Pagination">
                                    <template v-for="(link, i) in subscriptions.links" :key="i">
                                        <Link
                                            v-if="link.url"
                                            :href="link.url"
                                            v-html="link.label"
                                            class="relative inline-flex items-center px-4 py-2 border text-sm font-medium"
                                            :class="[
                                                link.active ? 'z-10 bg-primary/10 border-primary text-primary' : 'bg-base-100 border-base-300 text-base-content/50 hover:bg-base-200',
                                                i === 0 ? 'rounded-l-md' : '',
                                                i === subscriptions.links.length - 1 ? 'rounded-r-md' : ''
                                            ]"
                                        />
                                        <span
                                            v-else
                                            v-html="link.label"
                                            class="relative inline-flex items-center px-4 py-2 border border-base-300 bg-base-100 text-sm font-medium text-base-content/20 cursor-not-allowed"
                                            :class="[
                                                i === 0 ? 'rounded-l-md' : '',
                                                i === subscriptions.links.length - 1 ? 'rounded-r-md' : ''
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

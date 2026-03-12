<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { computed } from 'vue';
import Swal from 'sweetalert2';

const props = defineProps({
    tenant: Object,
});

const statusForm = useForm({
    status: props.tenant.subscription_status || 'active',
});

const updateStatus = (newStatus) => {
    if (newStatus === 'suspended') {
        Swal.fire({
            title: 'Suspend Clinic?',
            text: 'Are you sure you want to suspend this clinic? They will lose access to the system immediately.',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#94a3b8',
            confirmButtonText: 'Yes, suspend!',
        }).then((result) => {
            if (result.isConfirmed) {
                performStatusUpdate(newStatus);
            }
        });
    } else {
        performStatusUpdate(newStatus);
    }
};

const performStatusUpdate = (newStatus) => {
    statusForm.status = newStatus;
    statusForm.put(route('admin.tenants.updateStatus', props.tenant.id), {
        preserveScroll: true,
    });
};

const currentStatus = computed(() => props.tenant.subscription_status || 'active');
const mainDomain = computed(() => {
    return props.tenant.domains?.length ? props.tenant.domains[0].domain : null;
});
</script>

<template>
    <Head :title="`Manage Clinic: ${tenant.id}`" />
    <AdminLayout>
        <template #header>
            <div class="flex items-center space-x-4">
                <Link :href="route('admin.tenants.index')" class="text-gray-400 hover:text-gray-600 transition-colors">
                    <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                    </svg>
                </Link>
                <h1 class="text-xl font-bold text-gray-900 truncate max-w-xl">
                    Clinic Profile: <span class="text-teal-600">{{ tenant.id }}</span>
                </h1>
            </div>
        </template>

        <div class="max-w-7xl mx-auto space-y-6">
            
            <!-- Alert for Status Changes -->
            <div v-if="$page.props.flash?.success" class="bg-emerald-50 border-l-4 border-emerald-500 p-4 rounded-md shadow-sm">
                <div class="flex">
                    <div class="flex-shrink-0">
                        <svg class="h-5 w-5 text-emerald-400" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                        </svg>
                    </div>
                    <div class="ml-3">
                        <p class="text-sm text-emerald-700 font-medium">
                            {{ $page.props.flash.success }}
                        </p>
                    </div>
                </div>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                <!-- Left Column: Details -->
                <div class="lg:col-span-2 space-y-6">
                    <!-- Overview Card -->
                    <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
                        <div class="px-6 py-5 border-b border-gray-100 bg-gray-50 flex justify-between items-center">
                            <h3 class="text-lg font-semibold text-gray-900">Clinic Overview</h3>
                            
                            <span v-if="currentStatus === 'active'" class="inline-flex items-center px-3 py-1 rounded-full text-xs font-bold bg-green-100 text-green-800 uppercase tracking-wide">
                                Active
                            </span>
                            <span v-else-if="currentStatus === 'suspended'" class="inline-flex items-center px-3 py-1 rounded-full text-xs font-bold bg-red-100 text-red-800 uppercase tracking-wide">
                                Suspended
                            </span>
                            <span v-else-if="currentStatus === 'pending_payment'" class="inline-flex items-center px-3 py-1 rounded-full text-xs font-bold bg-yellow-100 text-yellow-800 uppercase tracking-wide">
                                Pending Payment
                            </span>
                        </div>
                        <div class="p-6">
                            <dl class="grid grid-cols-1 sm:grid-cols-2 gap-x-6 gap-y-8">
                                <div class="sm:col-span-1">
                                    <dt class="text-sm font-medium text-gray-500">Tenant ID</dt>
                                    <dd class="mt-1 text-sm text-gray-900 font-semibold">{{ tenant.id }}</dd>
                                </div>
                                <div class="sm:col-span-1">
                                    <dt class="text-sm font-medium text-gray-500">Domain / URL</dt>
                                    <dd class="mt-1 text-sm text-gray-900">
                                        <a v-if="mainDomain" :href="'http://' + mainDomain" target="_blank" class="text-teal-600 hover:text-teal-900 hover:underline flex items-center">
                                            {{ mainDomain }}
                                            <svg class="h-4 w-4 ml-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14" />
                                            </svg>
                                        </a>
                                        <span v-else class="text-red-500 italic">No domain configured</span>
                                    </dd>
                                </div>
                                <div class="sm:col-span-1">
                                    <dt class="text-sm font-medium text-gray-500">Registration Date</dt>
                                    <dd class="mt-1 text-sm text-gray-900">{{ new Date(tenant.created_at).toLocaleString() }}</dd>
                                </div>
                                <div class="sm:col-span-1">
                                    <dt class="text-sm font-medium text-gray-500">Database Context</dt>
                                    <dd class="mt-1 text-sm text-gray-900 font-mono text-xs bg-gray-100 px-2 py-1 rounded w-max">tenant{{ tenant.id }}</dd>
                                </div>
                            </dl>
                        </div>
                    </div>

                    <!-- Usage Stats (Placeholder for Phase 4) -->
                    <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
                        <div class="px-6 py-5 border-b border-gray-100 bg-gray-50">
                            <h3 class="text-lg font-semibold text-gray-900">Usage Statistics</h3>
                            <p class="text-sm text-gray-500 mt-1">Metrics require querying the tenant's isolated database.</p>
                        </div>
                        <div class="p-8 flex justify-center items-center text-gray-400">
                            <div class="text-center">
                                <svg class="h-10 w-10 mx-auto text-gray-300 mb-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                                </svg>
                                <p class="text-sm">Usage metrics visualization coming in Phase 4</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Right Column: Actions -->
                <div class="space-y-6">
                    <!-- Subscription Card -->
                    <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
                        <div class="px-6 py-5 border-b border-gray-100 bg-gray-50">
                            <h3 class="text-lg font-semibold text-gray-900">Subscription Plan</h3>
                        </div>
                        <div class="p-6">
                            <div class="mb-4">
                                <span class="text-xs font-medium text-gray-500 uppercase tracking-wider">Current Plan</span>
                                <p class="text-2xl font-bold text-teal-600 mt-1">{{ tenant.plan || 'Free / Manual' }}</p>
                            </div>
                            
                            <hr class="my-4 border-gray-100">
                            
                            <div class="space-y-3">
                                <button disabled class="w-full bg-white border border-gray-300 text-gray-700 py-2.5 px-4 rounded-lg font-medium text-sm hover:bg-gray-50 transition-colors cursor-not-allowed opacity-60">
                                    Change Plan (Phase 3)
                                </button>
                                <button disabled class="w-full bg-white border border-gray-300 text-gray-700 py-2.5 px-4 rounded-lg font-medium text-sm hover:bg-gray-50 transition-colors cursor-not-allowed opacity-60">
                                    View Billing History (Phase 3)
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- Administrative Actions -->
                    <div class="bg-white rounded-xl shadow-sm border border-red-100 overflow-hidden">
                        <div class="px-6 py-5 border-b border-red-100 bg-red-50">
                            <h3 class="text-lg font-semibold text-red-800">Danger Zone</h3>
                            <p class="text-xs text-red-600 mt-1">Administrative control panel.</p>
                        </div>
                        <div class="p-6 space-y-4">
                            <!-- Activate Action -->
                            <div v-if="currentStatus !== 'active'">
                                <h4 class="text-sm font-medium text-gray-900 mb-1">Restore Access</h4>
                                <p class="text-xs text-gray-500 mb-3">Restore the clinic's access to the platform.</p>
                                <button 
                                    @click="updateStatus('active')"
                                    :disabled="statusForm.processing"
                                    class="w-full bg-emerald-600 border border-transparent text-white py-2.5 px-4 rounded-lg font-medium text-sm hover:bg-emerald-700 transition-colors disabled:opacity-50"
                                >
                                    Activate Clinic
                                </button>
                            </div>

                            <!-- Suspend Action -->
                            <div v-if="currentStatus === 'active'">
                                <h4 class="text-sm font-medium text-gray-900 mb-1">Suspend Clinic</h4>
                                <p class="text-xs text-gray-500 mb-3">Revokes access immediately. The clinic will not be able to log in or process patients.</p>
                                <button 
                                    @click="updateStatus('suspended')"
                                    :disabled="statusForm.processing"
                                    class="w-full bg-red-600 border border-transparent text-white py-2.5 px-4 rounded-lg font-medium text-sm hover:bg-red-700 transition-colors disabled:opacity-50"
                                >
                                    Suspend Clinic
                                </button>
                            </div>

                            <hr class="my-4 border-gray-100">

                            <!-- Mark as Pending -->
                            <div v-if="currentStatus !== 'pending_payment'">
                                <h4 class="text-sm font-medium text-gray-900 mb-1">Payment Status</h4>
                                <p class="text-xs text-gray-500 mb-3">Mark account as waiting for manual payment validation.</p>
                                <button 
                                    @click="updateStatus('pending_payment')"
                                    :disabled="statusForm.processing"
                                    class="w-full bg-white border border-gray-300 text-gray-700 py-2.5 px-4 rounded-lg font-medium text-sm hover:bg-gray-50 transition-colors disabled:opacity-50"
                                >
                                    Set to Pending Payment
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>

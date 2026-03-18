<script setup>
import { ref, watch, computed } from 'vue';
import Swal from 'sweetalert2';

const props = defineProps({
    show: {
        type: Boolean,
        required: true,
    },
    tenant: {
        type: Object,
        default: null,
    },
    plans: {
        type: Array,
        default: () => [],
    },
});

const emit = defineEmits(['close', 'update', 'delete']);

const form = ref({
    name: '',
    owner_name: '',
    status: 'active',
    plan_id: null,
});

const showMoreDetails = ref(false);

watch(() => props.tenant, (newTenant) => {
    if (newTenant) {
        form.value = {
            name: newTenant.name || '',
            owner_name: newTenant.owner_name || '',
            status: newTenant.subscription_status || 'active',
            plan_id: newTenant.plan_id || null,
        };
    }
}, { immediate: true });

const submit = () => {
    emit('update', form.value);
};

const confirmDelete = () => {
    Swal.fire({
        title: 'Are you sure?',
        text: "This will permanently delete the clinic, all its data, and its database. This action cannot be undone!",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        cancelButtonColor: '#3085d6',
        confirmButtonText: 'Yes, delete it!',
        cancelButtonText: 'Cancel',
        reverseButtons: true
    }).then((result) => {
        if (result.isConfirmed) {
            emit('delete', props.tenant);
        }
    });
};

const getStoragePercentage = (used, max) => {
    if (!max || max === 0) return 0;
    return Math.min(Math.round((used / max) * 100), 100);
};

const getStorageColorClass = (percentage) => {
    if (percentage > 90) return 'bg-red-500';
    if (percentage > 70) return 'bg-yellow-500';
    return 'bg-teal-500';
};
</script>

<template>
    <div v-if="show" class="fixed inset-0 z-50 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
        <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
            <!-- Background overlay -->
            <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" aria-hidden="true" @click="emit('close')"></div>

            <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>

            <!-- Modal panel -->
            <div class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-2xl w-full">
                <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                    <div class="sm:flex sm:items-start">
                        <div class="mt-3 text-center sm:mt-0 sm:ml-4 sm:text-left w-full">
                            <h3 class="text-lg leading-6 font-medium text-gray-900" id="modal-title">
                                Manage Clinic
                            </h3>
                            <div v-if="tenant" class="mt-4 space-y-4">
                                <!-- Clinic Details -->
                                <div class="bg-gray-50 rounded-lg p-4">
                                    <h4 class="text-sm font-semibold text-gray-700 mb-3">Clinic Information</h4>
                                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                        <div>
                                            <label class="block text-xs font-medium text-gray-500 uppercase tracking-wider">Clinic Name</label>
                                            <input v-model="form.name" type="text" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-teal-500 focus:ring-teal-500 sm:text-sm" />
                                        </div>
                                        <div>
                                            <label class="block text-xs font-medium text-gray-500 uppercase tracking-wider">Owner Name</label>
                                            <input v-model="form.owner_name" type="text" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-teal-500 focus:ring-teal-500 sm:text-sm" />
                                        </div>
                                        <div>
                                            <label class="block text-xs font-medium text-gray-500 uppercase tracking-wider">Status</label>
                                            <select v-model="form.status" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-teal-500 focus:ring-teal-500 sm:text-sm">
                                                <option value="active">Active</option>
                                                <option value="suspended">Suspended</option>
                                                <option value="pending_payment">Pending Payment</option>
                                            </select>
                                        </div>
                                        <div>
                                            <label class="block text-xs font-medium text-gray-500 uppercase tracking-wider">Plan</label>
                                            <select v-model="form.plan_id" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-teal-500 focus:ring-teal-500 sm:text-sm">
                                                <option v-for="plan in plans" :key="plan.id" :value="plan.id">{{ plan.name }}</option>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="mt-3">
                                        <span class="text-xs text-gray-500">Database:</span>
                                        <span class="ml-2 font-mono text-xs text-gray-700">{{ tenant.database_name || 'N/A' }}</span>
                                    </div>
                                </div>

                                <!-- Domain Information -->
                                <div class="bg-gray-50 rounded-lg p-4">
                                    <h4 class="text-sm font-semibold text-gray-700 mb-3">Domain Information</h4>
                                    <div class="space-y-4 text-sm">
                                        <div>
                                            <span class="text-gray-500">URL:</span>
                                            <a :href="tenant.tenant_url" target="_blank" class="ml-2 text-teal-600 hover:text-teal-700 font-medium underline inline-flex items-center">
                                                {{ tenant.tenant_url }}
                                                <svg class="w-3 h-3 ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14" />
                                                </svg>
                                            </a>
                                        </div>
                                        <div>
                                            <span class="text-gray-500">Created:</span>
                                            <span class="ml-2 text-gray-700">{{ new Date(tenant.created_at).toLocaleString() }}</span>
                                        </div>
                                    </div>
                                </div>

                                <!-- Subscription & Storage -->
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                    <!-- Subscription Details -->
                                    <div class="bg-gray-50 rounded-lg p-4">
                                        <h4 class="text-sm font-semibold text-gray-700 mb-3">Subscription Details</h4>
                                        <div class="space-y-2 text-sm">
                                            <div class="flex justify-between">
                                                <span class="text-gray-500">Billing Cycle:</span>
                                                <span class="font-medium text-gray-900">{{ tenant.billing_cycle || 'N/A' }}</span>
                                            </div>
                                            <div class="flex justify-between">
                                                <span class="text-gray-500">Amount Paid:</span>
                                                <span class="font-medium text-gray-900">₱{{ tenant.amount_paid || '0.00' }}</span>
                                            </div>
                                            <div class="flex justify-between">
                                                <span class="text-gray-500">Payment Status:</span>
                                                <span class="px-2 py-0.5 rounded-full text-xs font-medium" :class="tenant.payment_status === 'paid' ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-800'">
                                                    {{ tenant.payment_status || 'N/A' }}
                                                </span>
                                            </div>
                                            <div class="mt-3">
                                                <div class="flex justify-between text-xs mb-1">
                                                    <span class="text-gray-500">Subscription Progress</span>
                                                    <span class="text-gray-700">{{ tenant.days_left }} days left</span>
                                                </div>
                                                <div class="w-full bg-gray-200 rounded-full h-1.5 overflow-hidden">
                                                    <div class="bg-teal-500 h-1.5 rounded-full transition-all duration-500" :style="{ width: Math.min(100, (tenant.days_left / (tenant.billing_cycle === 'yearly' ? 365 : 30)) * 100) + '%' }"></div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Storage Details -->
                                    <div class="bg-gray-50 rounded-lg p-4">
                                        <h4 class="text-sm font-semibold text-gray-700 mb-3">Storage Usage</h4>
                                        <div class="space-y-3">
                                            <div class="flex justify-between items-baseline">
                                                <div class="text-2xl font-bold text-gray-900">{{ tenant.storage_used_mb || 0 }}<span class="text-xs font-normal text-gray-500 ml-1">MB</span></div>
                                                <div class="text-xs text-gray-500">of {{ tenant.max_storage_mb || 500 }} MB limit</div>
                                            </div>
                                            
                                            <div class="space-y-1">
                                                <div class="w-full bg-gray-200 rounded-full h-2 overflow-hidden">
                                                    <div 
                                                        class="h-2 rounded-full transition-all duration-500" 
                                                        :class="getStorageColorClass(getStoragePercentage(tenant.storage_used_mb, tenant.max_storage_mb))"
                                                        :style="{ width: getStoragePercentage(tenant.storage_used_mb, tenant.max_storage_mb) + '%' }"
                                                    ></div>
                                                </div>
                                                <div class="flex justify-between text-[10px] text-gray-400 font-medium">
                                                    <span>{{ getStoragePercentage(tenant.storage_used_mb, tenant.max_storage_mb) }}% USED</span>
                                                    <span>{{ Math.max(0, (tenant.max_storage_mb || 500) - (tenant.storage_used_mb || 0)).toFixed(2) }} MB AVAILABLE</span>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="bg-gray-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse space-x-reverse space-x-3 items-center">
                    <button
                        @click="submit"
                        class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-6 py-2 bg-teal-600 text-base font-medium text-white hover:bg-teal-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-teal-500 sm:ml-3 sm:w-auto sm:text-sm transition-colors"
                    >
                        Update Changes
                    </button>
                    <button
                        @click="emit('close')"
                        class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-teal-500 sm:mt-0 sm:w-auto sm:text-sm transition-colors"
                    >
                        Close
                    </button>
                    <button
                        @click="showMoreDetails = true"
                        class="mt-3 w-full inline-flex justify-center items-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-teal-600 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-teal-500 sm:mt-0 sm:w-auto sm:text-sm transition-colors"
                    >
                        <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        View More
                    </button>
                    <div class="flex-grow"></div>
                    <button
                        @click="confirmDelete"
                        class="mt-3 w-full inline-flex justify-center items-center rounded-md border border-red-200 shadow-sm px-4 py-2 bg-red-50 text-base font-medium text-red-600 hover:bg-red-100 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500 sm:mt-0 sm:w-auto sm:text-sm transition-colors"
                    >
                        <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                        </svg>
                        Delete Clinic
                    </button>
                </div>
            </div>
        </div>

        <!-- Additional Details Modal (Read-Only) -->
        <div v-if="showMoreDetails" class="fixed inset-0 z-[60] overflow-y-auto" aria-labelledby="modal-title-more" role="dialog" aria-modal="true">
            <div class="flex items-center justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
                <div class="fixed inset-0 bg-gray-900 bg-opacity-75 transition-opacity" aria-hidden="true" @click="showMoreDetails = false"></div>
                <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>
                <div class="inline-block align-middle bg-white rounded-xl text-left overflow-hidden shadow-2xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg w-full">
                    <div class="bg-white px-6 py-6 font-sans">
                        <div class="flex items-center justify-between mb-6">
                            <h3 class="text-xl font-bold text-gray-900" id="modal-title-more">
                                Additional Clinic Details
                            </h3>
                            <button @click="showMoreDetails = false" class="text-gray-400 hover:text-gray-500 transition-colors">
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                </svg>
                            </button>
                        </div>
                        
                        <div class="space-y-6">
                            <!-- Email Section -->
                            <div class="bg-blue-50 bg-opacity-50 border border-blue-100 rounded-lg p-4">
                                <label class="block text-xs font-semibold text-blue-600 uppercase tracking-wider mb-1">Email Address</label>
                                <div class="text-gray-900 font-medium break-all">{{ tenant.email || 'N/A' }}</div>
                            </div>

                            <!-- Phone Section -->
                            <div class="bg-teal-50 bg-opacity-50 border border-teal-100 rounded-lg p-4">
                                <label class="block text-xs font-semibold text-teal-600 uppercase tracking-wider mb-1">Phone Number</label>
                                <div class="text-gray-900 font-medium">{{ tenant.phone || 'N/A' }}</div>
                            </div>

                            <!-- Address Section -->
                            <div class="bg-purple-50 bg-opacity-50 border border-purple-100 rounded-lg p-4">
                                <label class="block text-xs font-semibold text-purple-600 uppercase tracking-wider mb-1">Full Address</label>
                                <div class="text-gray-900 leading-relaxed">{{ tenant.full_address || 'N/A' }}</div>
                            </div>

                            <!-- Payment Method Section -->
                            <div class="bg-amber-50 bg-opacity-50 border border-amber-100 rounded-lg p-4">
                                <label class="block text-xs font-semibold text-amber-600 uppercase tracking-wider mb-1">Payment Method</label>
                                <div class="flex items-center text-gray-900 font-medium">
                                    <svg v-if="tenant.payment_method === 'card'" class="w-5 h-5 mr-2 text-gray-600" fill="currentColor" viewBox="0 0 20 20">
                                        <path d="M4 4a2 2 0 00-2 2v1h16V6a2 2 0 00-2-2H4z" />
                                        <path fill-rule="evenodd" d="M18 9H2v5a2 2 0 002 2h12a2 2 0 002-2V9zM4 13a1 1 0 011-1h1a1 1 0 110 2H5a1 1 0 01-1-1zm5-1a1 1 0 100 2h1a1 1 0 100-2H9z" clip-rule="evenodd" />
                                    </svg>
                                    <span class="capitalize">{{ tenant.payment_method || 'N/A' }}</span>
                                </div>
                            </div>
                        </div>

                        <div class="mt-8">
                            <button
                                @click="showMoreDetails = false"
                                class="w-full inline-flex justify-center rounded-lg border border-transparent shadow-sm px-4 py-3 bg-gray-900 text-base font-medium text-white hover:bg-gray-800 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-gray-500 transition-colors sm:text-sm"
                            >
                                Done
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

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
    reason: '',
    expiry_date: '',
});

const isProcessing = ref(false);
const showMoreDetails = ref(false);

watch(() => props.tenant, (newTenant) => {
    if (newTenant) {
        form.value = {
            name: newTenant.name || '',
            owner_name: newTenant.owner_name || '',
            status: newTenant.status || 'active',
            plan_id: newTenant.plan_id || null,
            reason: '',
            expiry_date: newTenant.ends_at || '',
        };
    }
}, { immediate: true });

const submit = () => {
    if (!form.value.reason || form.value.reason.length < 5) {
        Swal.fire({
            icon: 'error',
            title: 'Reason Required',
            text: 'Please provide a justification (at least 5 characters) in the Action Reason field before updating.',
            confirmButtonColor: '#0d9488',
        });
        return;
    }
    emit('update', form.value);
};

const confirmSuspend = () => {
    if (!form.value.reason || form.value.reason.length < 5) {
        Swal.fire({
            icon: 'error',
            title: 'Reason Required',
            text: 'Please provide a justification (at least 5 characters) in the Action Reason field before changing the clinic status.',
            confirmButtonColor: '#0d9488',
        });
        return;
    }

    const isSuspended = form.value.status === 'suspended';
    const actionGoal = isSuspended ? 'active' : 'suspended';
    const actionText = isSuspended ? 'reactivate' : 'suspend';
    
    Swal.fire({
        title: `${isSuspended ? 'Reactivate' : 'Suspend'} Clinic?`,
        text: `Are you sure you want to ${actionText} "${props.tenant.name}"?`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: isSuspended ? '#0d9488' : '#d33',
        cancelButtonColor: '#6b7280',
        confirmButtonText: `Yes, ${actionText} it!`,
        cancelButtonText: 'Cancel',
        reverseButtons: true,
        allowOutsideClick: () => !Swal.isLoading()
    }).then((result) => {
        if (result.isConfirmed) {
            // 1. Immediate Visual Update
            const originalStatus = form.value.status;
            form.value.status = actionGoal;
            isProcessing.value = true;
            
            // 2. Show Processing State
            Swal.fire({
                title: 'Processing...',
                html: `${isSuspended ? 'Reactivating' : 'Suspending'} clinic, please wait.`,
                allowOutsideClick: false,
                didOpen: () => {
                    Swal.showLoading();
                }
            });

            // 3. Emit update (Mother component will handle the router.put)
            try {
                emit('update', form.value);
                // Note: The modal usually closes via router.then/onSuccess in Index.vue
                // but we keep the local status update just in case or for the brief moment before close.
            } catch (error) {
                // Revert on error
                form.value.status = originalStatus;
                isProcessing.value = false;
                Swal.fire({
                    icon: 'error',
                    title: 'Action Failed',
                    text: 'An error occurred while updating the clinic status.'
                });
            }
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
            <div class="fixed inset-0 bg-neutral/80 backdrop-blur-sm transition-opacity" aria-hidden="true" @click="emit('close')"></div>

            <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>

            <!-- Modal panel -->
            <div class="inline-block align-bottom bg-base-100 rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-2xl w-full border border-base-300">
                <div class="bg-base-100 px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                    <div class="sm:flex sm:items-start">
                        <div class="mt-3 text-center sm:mt-0 sm:ml-4 sm:text-left w-full">
                            <h3 class="text-lg leading-6 font-medium text-base-content" id="modal-title">
                                Manage Clinic
                            </h3>
                            <div v-if="tenant" class="mt-4 space-y-4">
                                <!-- Clinic Details -->
                                <div class="bg-base-200 rounded-lg p-4">
                                    <h4 class="text-sm font-semibold text-base-content/80 mb-3">Clinic Information</h4>
                                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                        <div>
                                            <label class="block text-xs font-medium text-base-content/60 uppercase tracking-wider">Clinic Name</label>
                                            <input v-model="form.name" type="text" class="mt-1 block w-full rounded-md border-base-300 bg-base-100 text-base-content shadow-sm focus:border-primary focus:ring-primary sm:text-sm" />
                                        </div>
                                        <div>
                                            <label class="block text-xs font-medium text-base-content/60 uppercase tracking-wider">Owner Name</label>
                                            <input v-model="form.owner_name" type="text" class="mt-1 block w-full rounded-md border-base-300 bg-base-100 text-base-content shadow-sm focus:border-primary focus:ring-primary sm:text-sm" />
                                        </div>
                                        <div>
                                            <label class="block text-xs font-medium text-base-content/60 uppercase tracking-wider">Status</label>
                                            <div class="mt-1 h-[38px] flex items-center px-3 rounded-md border border-base-300 bg-base-200/50">
                                                <span class="flex-shrink-0 w-2 h-2 rounded-full mr-2"
                                                    :class="{
                                                        'bg-success': form.status === 'active',
                                                        'bg-error': form.status === 'suspended',
                                                        'bg-warning': form.status === 'pending_payment'
                                                    }"
                                                ></span>
                                                <span class="text-sm font-semibold capitalize text-base-content">
                                                    {{ form.status.replace('_', ' ') }}
                                                </span>
                                            </div>
                                        </div>
                                        <div>
                                            <label class="block text-xs font-medium text-base-content/60 uppercase tracking-wider">Action Reason <span class="text-error">*</span></label>
                                            <input v-model="form.reason" type="text" placeholder="Reason for change..." class="mt-1 block w-full rounded-md border-base-300 bg-base-100 text-base-content shadow-sm focus:border-primary focus:ring-primary sm:text-sm" />
                                        </div>
                                        <div>
                                            <label class="block text-xs font-medium text-base-content/60 uppercase tracking-wider">Plan</label>
                                            <select v-model="form.plan_id" class="mt-1 block w-full rounded-md border-base-300 bg-base-100 text-base-content shadow-sm focus:border-primary focus:ring-primary sm:text-sm">
                                                <option v-for="plan in plans" :key="plan.id" :value="plan.id">{{ plan.name }}</option>
                                            </select>
                                        </div>
                                        <div>
                                            <label class="block text-xs font-medium text-base-content/60 uppercase tracking-wider">Expiry Date</label>
                                            <input v-model="form.expiry_date" type="date" class="mt-1 block w-full rounded-md border-base-300 bg-base-100 text-base-content shadow-sm focus:border-primary focus:ring-primary sm:text-sm" />
                                            <span class="text-[10px] text-base-content/40 italic">Leave empty for 10-year override</span>
                                        </div>
                                    </div>
                                    <div class="mt-3">
                                        <span class="text-xs text-base-content/60">Database:</span>
                                        <span class="ml-2 font-mono text-xs text-base-content/80">{{ tenant.database_name || 'N/A' }}</span>
                                    </div>
                                </div>

                                <!-- Domain Information -->
                                <div class="bg-base-200 rounded-lg p-4">
                                    <h4 class="text-sm font-semibold text-base-content/80 mb-3">Domain Information</h4>
                                    <div class="space-y-4 text-sm">
                                        <div>
                                            <span class="text-base-content/60">URL:</span>
                                            <a :href="tenant.tenant_url" target="_blank" class="ml-2 text-primary hover:text-primary-focus font-medium underline inline-flex items-center">
                                                {{ tenant.tenant_url }}
                                                <svg class="w-3 h-3 ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14" />
                                                </svg>
                                            </a>
                                        </div>
                                        <div>
                                            <span class="text-base-content/60">Created:</span>
                                            <span class="ml-2 text-base-content/80">{{ new Date(tenant.created_at).toLocaleString() }}</span>
                                        </div>
                                    </div>
                                </div>

                                <!-- Subscription & Storage -->
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                    <!-- Subscription Details -->
                                    <div class="bg-base-200 rounded-lg p-4">
                                        <h4 class="text-sm font-semibold text-base-content/80 mb-3">Subscription Details</h4>
                                        <div class="space-y-2 text-sm">
                                            <div class="flex justify-between">
                                                <span class="text-base-content/60">Billing Cycle:</span>
                                                <span class="font-medium text-base-content">{{ tenant.billing_cycle || 'N/A' }}</span>
                                            </div>
                                            <div class="flex justify-between">
                                                <span class="text-base-content/60">Amount Paid:</span>
                                                <span class="font-medium text-base-content">₱{{ tenant.amount_paid || '0.00' }}</span>
                                            </div>
                                            <div class="flex justify-between">
                                                <span class="text-base-content/60">Payment Status:</span>
                                                <span class="px-2 py-0.5 rounded-full text-xs font-medium" :class="tenant.payment_status === 'paid' ? 'bg-success/20 text-success' : 'bg-base-300 text-base-content/60'">
                                                    {{ tenant.payment_status || 'N/A' }}
                                                </span>
                                            </div>
                                            <div class="mt-3">
                                                <div class="flex justify-between text-xs mb-1">
                                                    <span class="text-base-content/60">Subscription Progress</span>
                                                    <span class="text-base-content/80">{{ tenant.days_left }} days left</span>
                                                </div>
                                                <div class="w-full bg-base-300 rounded-full h-1.5 overflow-hidden">
                                                    <div class="bg-primary h-1.5 rounded-full transition-all duration-500" :style="{ width: Math.min(100, (tenant.days_left / (tenant.billing_cycle === 'yearly' ? 365 : 30)) * 100) + '%' }"></div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Storage Details -->
                                    <div class="bg-base-200 rounded-lg p-4">
                                        <h4 class="text-sm font-semibold text-base-content/80 mb-3">Storage Usage</h4>
                                        <div class="space-y-3">
                                            <div class="flex justify-between items-baseline">
                                                <div class="text-2xl font-bold text-base-content">{{ tenant.storage_used_mb || 0 }}<span class="text-xs font-normal text-base-content/50 ml-1">MB</span></div>
                                                <div class="text-xs text-base-content/50">of {{ tenant.max_storage_mb || 500 }} MB limit</div>
                                            </div>
                                            
                                            <div class="space-y-1">
                                                <div class="w-full bg-base-300 rounded-full h-2 overflow-hidden">
                                                    <div 
                                                        class="h-2 rounded-full transition-all duration-500" 
                                                        :class="getStorageColorClass(getStoragePercentage(tenant.storage_used_mb, tenant.max_storage_mb))"
                                                        :style="{ width: getStoragePercentage(tenant.storage_used_mb, tenant.max_storage_mb) + '%' }"
                                                    ></div>
                                                </div>
                                                <div class="flex justify-between text-[10px] text-base-content/40 font-medium">
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
                <div class="bg-base-200 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse space-x-reverse space-x-3 items-center border-t border-base-300">
                    <button
                        @click="submit"
                        class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-6 py-2 bg-primary text-base font-medium text-primary-content hover:bg-primary-focus focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary sm:ml-3 sm:w-auto sm:text-sm transition-colors"
                    >
                        Update Changes
                    </button>
                    <button
                        @click="emit('close')"
                        class="mt-3 w-full inline-flex justify-center rounded-md border border-base-300 shadow-sm px-4 py-2 bg-base-100 text-base font-medium text-base-content hover:bg-base-200 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary sm:mt-0 sm:w-auto sm:text-sm transition-colors"
                    >
                        Close
                    </button>
                    <button
                        @click="showMoreDetails = true"
                        class="mt-3 w-full inline-flex justify-center items-center rounded-md border border-base-300 shadow-sm px-4 py-2 bg-base-100 text-base font-medium text-primary hover:bg-base-200 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary sm:mt-0 sm:w-auto sm:text-sm transition-colors"
                    >
                        <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        View More
                    </button>
                    <div class="flex-grow"></div>
                    <button
                        @click="confirmSuspend"
                        class="mt-3 w-full inline-flex justify-center items-center rounded-md border shadow-sm px-4 py-2 text-base font-medium sm:mt-0 sm:w-auto sm:text-sm transition-colors"
                        :class="form.status === 'suspended' 
                            ? 'border-success/20 bg-success/10 text-success hover:bg-success/20 focus:ring-success' 
                            : 'border-error/20 bg-error/10 text-error hover:bg-error/20 focus:ring-error'"
                    >
                        <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path v-if="form.status !== 'suspended'" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636" />
                            <path v-else stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        {{ form.status === 'suspended' ? 'Reactivate Clinic' : 'Suspend Clinic' }}
                    </button>
                </div>
            </div>
        </div>

        <!-- Additional Details Modal (Read-Only) -->
        <div v-if="showMoreDetails" class="fixed inset-0 z-[60] overflow-y-auto" aria-labelledby="modal-title-more" role="dialog" aria-modal="true">
            <div class="flex items-center justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
                <div class="fixed inset-0 bg-gray-900 bg-opacity-75 transition-opacity" aria-hidden="true" @click="showMoreDetails = false"></div>
                <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>
                <div class="inline-block align-middle bg-base-100 rounded-xl text-left overflow-hidden shadow-2xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg w-full border border-base-300">
                    <div class="bg-base-100 px-6 py-6 font-sans">
                        <div class="flex items-center justify-between mb-6">
                            <h3 class="text-xl font-bold text-base-content" id="modal-title-more">
                                Additional Clinic Details
                            </h3>
                            <button @click="showMoreDetails = false" class="text-base-content/40 hover:text-base-content/60 transition-colors">
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                </svg>
                            </button>
                        </div>
                        
                        <div class="space-y-6">
                            <!-- Email Section -->
                            <div class="bg-blue-500/10 border border-blue-500/20 rounded-lg p-4">
                                <label class="block text-xs font-semibold text-blue-500 uppercase tracking-wider mb-1">Email Address</label>
                                <div class="text-base-content font-medium break-all">{{ tenant.email || 'N/A' }}</div>
                            </div>
 
                            <!-- Phone Section -->
                            <div class="bg-teal-500/10 border border-teal-500/20 rounded-lg p-4">
                                <label class="block text-xs font-semibold text-teal-500 uppercase tracking-wider mb-1">Phone Number</label>
                                <div class="text-base-content font-medium">{{ tenant.phone || 'N/A' }}</div>
                            </div>
 
                            <!-- Address Section -->
                            <div class="bg-purple-500/10 border border-purple-500/20 rounded-lg p-4">
                                <label class="block text-xs font-semibold text-purple-500 uppercase tracking-wider mb-1">Full Address</label>
                                <div class="text-base-content leading-relaxed">{{ tenant.full_address || 'N/A' }}</div>
                            </div>
 
                            <!-- Payment Method Section -->
                            <div class="bg-warning/10 border border-warning/20 rounded-lg p-4">
                                <label class="block text-xs font-semibold text-warning uppercase tracking-wider mb-1">Payment Method</label>
                                <div class="flex items-center text-base-content font-medium">
                                    <svg v-if="tenant.payment_method === 'card'" class="w-5 h-5 mr-2 text-base-content/60" fill="currentColor" viewBox="0 0 20 20">
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
                                class="w-full inline-flex justify-center rounded-lg border border-transparent shadow-sm px-4 py-3 bg-neutral text-base font-medium text-neutral-content hover:bg-neutral-focus focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-neutral transition-colors sm:text-sm"
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

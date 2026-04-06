<script setup>
import { ref, watch, computed } from 'vue';
import Swal from 'sweetalert2';
import axios from 'axios';

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

const activeTab = ref('general');
const isProcessing = ref(false);
const isLoadingUsage = ref(false);

const usageData = ref({
    storage_used_mb: props.tenant?.storage_used_mb || 0,
    max_storage_mb: props.tenant?.max_storage_mb || 500,
    bandwidth_used_mb: props.tenant?.bandwidth_used_mb || 0,
    max_bandwidth_mb: props.tenant?.max_bandwidth_mb || 2048,
});

const fetchUsageStats = async () => {
    if (!props.tenant?.id) return;
    
    try {
        isLoadingUsage.value = true;
        const response = await axios.get(route('admin.tenants.usage', props.tenant.id));
        usageData.value = response.data;
    } catch (error) {
        console.error('Failed to fetch usage stats:', error);
    } finally {
        isLoadingUsage.value = false;
    }
};

watch(activeTab, (newTab) => {
    if (newTab === 'billing') { // 'billing' is the ID for 'Usage & Billing'
        fetchUsageStats();
    }
});

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

        // Also update usage data baseline
        usageData.value = {
            storage_used_mb: newTenant.storage_used_mb || 0,
            max_storage_mb: newTenant.max_storage_mb || 500,
            bandwidth_used_mb: newTenant.bandwidth_used_bytes ? Math.round(newTenant.bandwidth_used_bytes / (1024 * 1024) * 100) / 100 : (newTenant.bandwidth_used_mb || 0),
            max_bandwidth_mb: newTenant.max_bandwidth_mb || 2048,
        };
    }
}, { immediate: true });

const tabs = [
    { id: 'general', label: 'General', icon: 'clipboard-list' },
    { id: 'billing', label: 'Usage & Billing', icon: 'credit-card' },
    { id: 'advanced', label: 'Technical Details', icon: 'cpu-chip' },
    { id: 'contact', label: 'Contact Info', icon: 'phone' },
];

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

    Swal.fire({
        title: 'Confirm Update',
        text: 'Are you sure you want to save these changes? Whatever changes you did may affect or improve the tenant performance.',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#0d9488',
        cancelButtonColor: '#6b7280',
        confirmButtonText: 'Yes, Update Changes',
        cancelButtonText: 'Cancel',
        reverseButtons: true,
        allowOutsideClick: () => !Swal.isLoading()
    }).then((result) => {
        if (result.isConfirmed) {
            isProcessing.value = true;
            
            // Show Processing State
            Swal.fire({
                title: 'Processing...',
                html: 'Updating clinic information, please wait.',
                allowOutsideClick: false,
                didOpen: () => {
                    Swal.showLoading();
                }
            });

            emit('update', form.value);
        }
    });
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

            // 3. Emit update
            emit('update', form.value);
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

const getBandwidthColorClass = (percentage) => {
    if (percentage > 90) return 'bg-red-500';
    if (percentage > 70) return 'bg-orange-500';
    return 'bg-primary';
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
                <div class="bg-base-100 flex flex-col h-[520px]">
                    <!-- Modal Header -->
                    <div class="px-6 pt-5 pb-2 bg-base-100">
                        <h3 class="text-xl font-black text-base-content uppercase tracking-widest flex items-center">
                            <span class="w-1.5 h-6 bg-primary rounded-full mr-3.5"></span>
                            Manage Clinic
                        </h3>
                    </div>

                    <!-- Tab Navigation -->
                    <div class="flex border-b border-base-300 px-6 overflow-x-auto no-scrollbar bg-base-200/30">
                        <button 
                            v-for="tab in tabs" 
                            :key="tab.id"
                            @click="activeTab = tab.id"
                            class="py-4 px-4 text-xs font-bold uppercase tracking-widest transition-all duration-200 border-b-2 whitespace-nowrap"
                            :class="activeTab === tab.id 
                                ? 'border-primary text-primary bg-base-100 shadow-sm' 
                                : 'border-transparent text-base-content/40 hover:text-base-content/70 hover:bg-base-200/50'"
                        >
                            {{ tab.label }}
                        </button>
                    </div>

                    <!-- Tab Content -->
                    <div class="flex-grow overflow-y-auto p-6 bg-base-100">
                        <div v-if="tenant">
                            <!-- Tab 1: General (Editable Fields) -->
                            <div v-show="activeTab === 'general'" class="space-y-6">
                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
                                    <div class="space-y-1.5">
                                        <label class="block text-[10px] font-black text-base-content/50 uppercase tracking-widest">Clinic Name</label>
                                        <input v-model="form.name" type="text" class="block w-full rounded-lg border-base-300 bg-base-100 text-sm focus:border-primary focus:ring-primary shadow-sm" />
                                    </div>
                                    <div class="space-y-1.5">
                                        <label class="block text-[10px] font-black text-base-content/50 uppercase tracking-widest">Owner Name</label>
                                        <input v-model="form.owner_name" type="text" class="block w-full rounded-lg border-base-300 bg-base-100 text-sm focus:border-primary focus:ring-primary shadow-sm" />
                                    </div>
                                    <div class="space-y-1.5">
                                        <label class="block text-[10px] font-black text-base-content/50 uppercase tracking-widest">Action Reason <span class="text-error">*</span></label>
                                        <input v-model="form.reason" type="text" placeholder="Reason for changes..." class="block w-full rounded-lg border-base-300 bg-base-200/50 text-sm focus:border-primary focus:ring-primary shadow-sm" />
                                    </div>
                                    <div class="space-y-1.5">
                                        <label class="block text-[10px] font-black text-base-content/50 uppercase tracking-widest">Status (Display Only)</label>
                                        <div class="flex items-center px-3 py-2 rounded-lg border border-base-300 bg-base-200/30">
                                            <span class="w-2 h-2 rounded-full mr-2" :class="form.status === 'active' ? 'bg-success' : 'bg-error'"></span>
                                            <span class="text-sm font-bold uppercase tracking-wider text-base-content">{{ form.status }}</span>
                                        </div>
                                    </div>
                                    <div class="space-y-1.5">
                                        <label class="block text-[10px] font-black text-base-content/50 uppercase tracking-widest">Assigned Plan</label>
                                        <select v-model="form.plan_id" class="block w-full rounded-lg border-base-300 bg-base-100 text-sm focus:border-primary focus:ring-primary shadow-sm">
                                            <option v-for="plan in plans" :key="plan.id" :value="plan.id">{{ plan.name }}</option>
                                        </select>
                                    </div>
                                    <div class="space-y-1.5">
                                        <label class="block text-[10px] font-black text-base-content/50 uppercase tracking-widest">Override Expiry</label>
                                        <input v-model="form.expiry_date" type="date" class="block w-full rounded-lg border-base-300 bg-base-100 text-sm focus:border-primary focus:ring-primary shadow-sm" />
                                        <p class="text-[9px] text-base-content/40 italic">Leave blank for permanent (10 yr) access</p>
                                    </div>
                                </div>
                            </div>

                            <!-- Tab 2: Billing & Usage -->
                            <div v-show="activeTab === 'billing'" class="space-y-6">
                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
                                    <!-- Subscription Details -->
                                    <div class="bg-base-200/40 rounded-xl p-5 border border-base-300/50">
                                        <h4 class="text-[10px] font-black text-base-content/30 uppercase tracking-[0.2em] mb-4">Subscription Overview</h4>
                                        <div class="space-y-4">
                                            <div class="flex justify-between items-center border-b border-base-300/30 pb-2">
                                                <span class="text-xs text-base-content/60 font-medium">Payment Status:</span>
                                                <span class="px-2 py-0.5 rounded-full text-[10px] font-black uppercase tracking-widest" :class="tenant.payment_status === 'paid' ? 'bg-success/20 text-success' : 'bg-base-300 text-base-content/60'">{{ tenant.payment_status }}</span>
                                            </div>
                                            <div class="flex justify-between items-center border-b border-base-300/30 pb-2">
                                                <span class="text-xs text-base-content/60 font-medium">Cycle:</span>
                                                <span class="text-sm font-bold capitalize text-base-content">{{ tenant.billing_cycle }}</span>
                                            </div>
                                            <div class="flex justify-between items-center">
                                                <span class="text-xs text-base-content/60 font-medium">Amount:</span>
                                                <span class="text-sm font-black text-primary">₱{{ tenant.amount_paid || '0.00' }}</span>
                                            </div>
                                            <div class="pt-2">
                                                <div class="flex justify-between text-[10px] font-black text-base-content/40 uppercase tracking-widest mb-2">
                                                    <span>Days Remaining</span>
                                                    <span>{{ tenant.days_left }} Days</span>
                                                </div>
                                                <div class="w-full bg-base-300 h-1.5 rounded-full overflow-hidden">
                                                    <div class="bg-primary h-full transition-all duration-1000" :style="{ width: Math.min(100, (tenant.days_left / (tenant.billing_cycle === 'yearly' ? 365 : 30)) * 100) + '%' }"></div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Storage Details -->
                                    <div class="bg-base-200/40 rounded-xl p-5 border border-base-300/50 transition-all duration-300" :class="{ 'opacity-50 grayscale-[0.5]': isLoadingUsage }">
                                        <div class="flex justify-between items-center mb-4">
                                            <h4 class="text-[10px] font-black text-base-content/30 uppercase tracking-[0.2em]">Storage Metrics</h4>
                                            <div v-if="isLoadingUsage" class="flex items-center gap-1.5">
                                                <div class="w-1 h-1 rounded-full bg-teal-500 animate-bounce"></div>
                                                <div class="w-1 h-1 rounded-full bg-teal-500 animate-bounce [animation-delay:0.2s]"></div>
                                                <span class="text-[9px] font-black text-teal-600 uppercase tracking-tighter">Syncing</span>
                                            </div>
                                        </div>
                                        <div class="space-y-5">
                                            <div class="flex items-end justify-between">
                                                <div>
                                                    <span class="text-3xl font-black text-base-content">{{ usageData.storage_used_mb || 0 }}</span>
                                                    <span class="text-xs font-bold text-base-content/40 ml-1">MB</span>
                                                </div>
                                                <div class="text-[10px] font-black text-base-content/30 uppercase tracking-widest text-right">
                                                    LIMIT: {{ usageData.max_storage_mb || 500 }} MB
                                                </div>
                                            </div>
                                            <div class="space-y-2">
                                                <div class="w-full bg-base-300 h-3 rounded-full overflow-hidden">
                                                    <div class="h-full transition-all duration-1000 rounded-full" 
                                                        :class="getStorageColorClass(getStoragePercentage(usageData.storage_used_mb, usageData.max_storage_mb))"
                                                        :style="{ width: getStoragePercentage(usageData.storage_used_mb, usageData.max_storage_mb) + '%' }"
                                                    ></div>
                                                </div>
                                                <div class="flex justify-between text-[10px] font-black tracking-tighter">
                                                    <span class="text-base-content/50 uppercase">{{ getStoragePercentage(usageData.storage_used_mb, usageData.max_storage_mb) }}% UTILIZED</span>
                                                    <span class="text-success uppercase">{{ Math.max(0, (usageData.max_storage_mb || 500) - (usageData.storage_used_mb || 0)).toFixed(1) }} MB FREE</span>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Bandwidth Details -->
                                    <div class="bg-base-200/40 rounded-xl p-5 border border-base-300/50 transition-all duration-300" :class="{ 'opacity-50 grayscale-[0.5]': isLoadingUsage }">
                                        <div class="flex justify-between items-center mb-4">
                                            <h4 class="text-[10px] font-black text-base-content/30 uppercase tracking-[0.2em]">Monthly Bandwidth</h4>
                                            <div v-if="isLoadingUsage" class="flex items-center gap-1.5">
                                                <div class="w-1 h-1 rounded-full bg-primary animate-bounce"></div>
                                                <div class="w-1 h-1 rounded-full bg-primary animate-bounce [animation-delay:0.2s]"></div>
                                                <span class="text-[9px] font-black text-primary uppercase tracking-tighter">Syncing</span>
                                            </div>
                                        </div>
                                        <div class="space-y-5">
                                            <div class="flex items-end justify-between">
                                                <div>
                                                    <span class="text-3xl font-black text-base-content">{{ (usageData.bandwidth_used_mb > 1024) ? (usageData.bandwidth_used_mb / 1024).toFixed(2) : (usageData.bandwidth_used_mb || 0) }}</span>
                                                    <span class="text-xs font-bold text-base-content/40 ml-1">{{ (usageData.bandwidth_used_mb > 1024) ? 'GB' : 'MB' }}</span>
                                                </div>
                                                <div class="text-[10px] font-black text-base-content/30 uppercase tracking-widest text-right">
                                                    CAP: {{ (usageData.max_bandwidth_mb / 1024).toFixed(1) }} GB
                                                </div>
                                            </div>
                                            <div class="space-y-2">
                                                <div class="w-full bg-base-300 h-3 rounded-full overflow-hidden">
                                                    <div class="h-full transition-all duration-1000 rounded-full" 
                                                        :class="getBandwidthColorClass(getStoragePercentage(usageData.bandwidth_used_mb, usageData.max_bandwidth_mb))"
                                                        :style="{ width: getStoragePercentage(usageData.bandwidth_used_mb, usageData.max_bandwidth_mb) + '%' }"
                                                    ></div>
                                                </div>
                                                <div class="flex justify-between text-[10px] font-black tracking-tighter">
                                                    <span class="text-base-content/50 uppercase">{{ getStoragePercentage(usageData.bandwidth_used_mb, usageData.max_bandwidth_mb) }}% CONSUMED</span>
                                                    <span class="text-primary uppercase">{{ Math.max(0, ((usageData.max_bandwidth_mb || 2048) - (usageData.bandwidth_used_mb || 0)) / 1024).toFixed(2) }} GB LEFT</span>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Tab 3: Technical Details -->
                            <div v-show="activeTab === 'advanced'" class="space-y-6">
                                <div class="bg-base-200/30 border border-base-300/60 rounded-xl divide-y divide-base-300/50">
                                    <div class="p-4 flex items-center justify-between">
                                        <span class="text-xs font-bold text-base-content/40 uppercase tracking-widest">Public Domain</span>
                                        <a :href="tenant.tenant_url" target="_blank" class="text-sm font-black text-primary hover:underline flex items-center">
                                            {{ tenant.tenant_url }}
                                            <svg class="w-3 h-3 ml-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14" /></svg>
                                        </a>
                                    </div>
                                    <div class="p-4 flex items-center justify-between">
                                        <span class="text-xs font-bold text-base-content/40 uppercase tracking-widest">Database Name</span>
                                        <span class="font-mono text-xs text-base-content/80 bg-base-300/50 px-2 py-1 rounded">{{ tenant.database_name || 'N/A' }}</span>
                                    </div>
                                    <div class="p-4 flex items-center justify-between">
                                        <span class="text-xs font-bold text-base-content/40 uppercase tracking-widest">Provisioned On</span>
                                        <span class="text-sm font-medium text-base-content/80">{{ new Date(tenant.created_at).toLocaleString() }}</span>
                                    </div>
                                </div>
                            </div>

                            <!-- Tab 4: Contact Info -->
                            <div v-show="activeTab === 'contact'" class="space-y-4">
                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                    <div class="bg-primary/5 border border-primary/10 rounded-xl p-4">
                                        <label class="block text-[10px] font-black text-primary uppercase tracking-widest mb-1">Email Connection</label>
                                        <div class="text-sm font-bold text-base-content break-all">{{ tenant.email || 'N/A' }}</div>
                                    </div>
                                    <div class="bg-teal-500/5 border border-teal-500/10 rounded-xl p-4">
                                        <label class="block text-[10px] font-black text-teal-500 uppercase tracking-widest mb-1">Clinic Phone</label>
                                        <div class="text-sm font-bold text-base-content">{{ tenant.phone || 'N/A' }}</div>
                                    </div>
                                </div>
                                <div class="bg-indigo-500/5 border border-indigo-500/10 rounded-xl p-5">
                                    <label class="block text-[10px] font-black text-indigo-500 uppercase tracking-widest mb-2">Physical Location</label>
                                    <div class="text-sm font-medium text-base-content leading-relaxed">{{ tenant.full_address || 'No address provided.' }}</div>
                                </div>
                                <div class="bg-warning/5 border border-warning/10 rounded-xl p-4">
                                    <label class="block text-[10px] font-black text-warning uppercase tracking-widest mb-1">Stored Payment Method</label>
                                    <div class="flex items-center text-sm font-bold text-base-content">
                                         <svg v-if="tenant.payment_method === 'card'" class="w-4 h-4 mr-2" fill="currentColor" viewBox="0 0 20 20">
                                            <path d="M4 4a2 2 0 00-2 2v1h16V6a2 2 0 00-2-2H4z" /><path fill-rule="evenodd" d="M18 9H2v5a2 2 0 002 2h12a2 2 0 002-2V9zM4 13a1 1 0 011-1h1a1 1 0 110 2H5a1 1 0 01-1-1zm5-1a1 1 0 100 2h1a1 1 0 100-2H9z" clip-rule="evenodd" />
                                        </svg>
                                        <span class="capitalize">{{ tenant.payment_method || 'Manual / Cash' }}</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Modal Footer (Fixed) -->
                    <div class="bg-base-200 px-6 py-4 flex flex-col sm:flex-row-reverse items-center justify-between gap-3 border-t border-base-300">
                        <div class="flex flex-row-reverse gap-3 w-full sm:w-auto">
                            <button
                                @click="submit"
                                class="flex-1 sm:flex-none inline-flex justify-center rounded-lg border border-transparent shadow-md px-6 py-2 bg-primary text-sm font-black text-primary-content uppercase tracking-widest hover:brightness-110 transition-all focus:outline-none focus:ring-2 focus:ring-primary shadow-primary/20"
                            >
                                Update Changes
                            </button>
                            <button
                                @click="emit('close')"
                                class="flex-1 sm:flex-none inline-flex justify-center rounded-lg border border-base-300 shadow-sm px-6 py-2 bg-base-100 text-sm font-bold text-base-content/70 hover:bg-base-200 transition-all focus:outline-none"
                            >
                                Close
                            </button>
                        </div>
                        
                        <div class="w-full sm:w-auto">
                            <button
                                @click="confirmSuspend"
                                class="w-full sm:w-auto inline-flex justify-center items-center rounded-lg border shadow-sm px-5 py-2 text-xs font-black uppercase tracking-widest transition-all"
                                :class="form.status === 'suspended' 
                                    ? 'border-success/20 bg-success/10 text-success hover:bg-success/20' 
                                    : 'border-error/20 bg-error/10 text-error hover:bg-error/20'"
                            >
                                <svg class="w-3.5 h-3.5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path v-if="form.status !== 'suspended'" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636" />
                                    <path v-else stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                                {{ form.status === 'suspended' ? 'Reactivate Clinic' : 'Suspend Clinic' }}
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

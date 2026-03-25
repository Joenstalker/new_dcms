<script setup>
import { Head, useForm, router, usePage } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import Modal from '@/Components/Modal.vue';
import PlanForm from './Partials/PlanForm.vue';
import ManagePlanModal from './Partials/ManagePlanModal.vue';
import { ref, computed } from 'vue';
import Swal from 'sweetalert2';

const page = usePage();
const primaryColor = page.props.branding?.primary_color || '#0ea5e9';

const props = defineProps({
    plans: Array,
    allFeatures: Array,
});

const isCreateModalOpen = ref(false);
const isEditModalOpen = ref(false);
const isPushModalOpen = ref(false);
const selectedPlan = ref(null);
const selectedPlanIds = ref([]);

const openCreateModal = () => {
    isCreateModalOpen.value = true;
};

const closeCreateModal = () => {
    isCreateModalOpen.value = false;
};

const openEditModal = (plan) => {
    selectedPlan.value = plan;
    isEditModalOpen.value = true;
};

const closeEditModal = () => {
    isEditModalOpen.value = false;
    selectedPlan.value = null;
};

const handleCreateSubmit = (form) => {
    Swal.fire({
        target: document.querySelector('dialog[open]') || 'body',
        title: 'Creating Plan',
        text: 'Syncing with Stripe, please wait...',
        allowOutsideClick: false,
        didOpen: () => {
            Swal.showLoading();
        }
    });

    form.post(route('admin.plans.store'), {
        onSuccess: () => {
            closeCreateModal();
            // The global watcher in AdminLayout will handle the success toast
            Swal.close();
        },
        onError: () => {
            Swal.close();
        }
    });
};

const handleEditSubmit = (form) => {
    Swal.fire({
        target: document.querySelector('dialog[open]') || 'body',
        title: 'Updating Plan',
        text: 'Syncing price changes with Stripe...',
        allowOutsideClick: false,
        didOpen: () => {
            Swal.showLoading();
        }
    });

    form.put(route('admin.plans.update', selectedPlan.value.id), {
        onSuccess: () => {
            closeEditModal();
            // The global watcher in AdminLayout will handle the success toast
            Swal.close();
        },
        onError: () => {
            Swal.close();
        }
    });
};

const confirmDelete = (plan) => {
    Swal.fire({
        title: 'Delete Plan?',
        text: `Are you sure you want to delete the ${plan.name} plan? This cannot be undone.`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#0d9488', // teal-600
        cancelButtonColor: '#94a3b8', // gray-400
        confirmButtonText: 'Yes, delete it!',
    }).then((result) => {
        if (result.isConfirmed) {
            router.delete(route('admin.plans.destroy', plan.id), {
                onSuccess: () => {
                    Swal.fire({
                        title: 'Deleted!',
                        text: 'Plan has been removed.',
                        icon: 'success',
                        timer: 2000,
                        toast: true,
                        position: 'top-end',
                        showConfirmButton: false,
                    });
                },
                onError: (errors) => {
                    Swal.fire('Error!', errors.message || 'Could not delete plan. It might have active subscribers.', 'error');
                }
            });
        }
    });
};

const forceSync = (plan) => {
    Swal.fire({
        target: document.querySelector('dialog[open]') || 'body',
        title: 'Sync with Stripe?',
        text: `This will refresh the Stripe Product and Price IDs for the ${plan.name} plan.`,
        icon: 'info',
        showCancelButton: true,
        confirmButtonColor: '#0d9488',
        confirmButtonText: 'Yes, sync now',
        showLoaderOnConfirm: true,
        preConfirm: () => {
            return new Promise((resolve) => {
                router.post(route('admin.plans.force-sync', plan.id), {}, {
                    onSuccess: () => {
                        Swal.fire({
                            title: 'Synced!',
                            text: 'Plan successfully synced with Stripe.',
                            icon: 'success',
                            timer: 2000,
                            toast: true,
                            position: 'top-end',
                            showConfirmButton: false,
                        });
                        resolve();
                    },
                    onError: (errors) => {
                        Swal.fire('Error!', errors.message || 'Sync failed.', 'error');
                        resolve();
                    }
                });
            });
        },
        allowOutsideClick: () => !Swal.isLoading()
    });
};

const legacyKeys = [
    'qr_booking', 'sms_notifications', 'custom_branding', 'advanced_analytics', 
    'priority_support', 'multi_branch', 'max_users', 'max_patients', 
    'max_appointments', 'report_level', 'max_storage_mb'
];

const getDynamicFeatures = (plan) => {
    if (!plan.features) return [];
    return plan.features.filter(f => !legacyKeys.includes(f.key));
};

const getStagedFeaturesCount = (plan) => {
    if (!plan.features) return 0;
    return plan.features.filter(f => {
        // Case 1: Never pushed
        if (!f.pivot.pushed_at) return true;
        
        // Case 2: Pushed while non-active, but now active (Needs Repush)
        if (f.implementation_status === 'active' && f.released_at) {
            return new Date(f.released_at) > new Date(f.pivot.pushed_at);
        }
        
        return false;
    }).length;
};

const totalStagedFeatures = computed(() => {
    return props.plans.reduce((acc, plan) => acc + getStagedFeaturesCount(plan), 0);
});

const plansWithStagedFeatures = computed(() => {
    return props.plans.filter(plan => getStagedFeaturesCount(plan) > 0);
});

const openPushModal = () => {
    selectedPlanIds.value = plansWithStagedFeatures.value.map(p => p.id);
    isPushModalOpen.value = true;
};

const handleBatchPush = () => {
    if (selectedPlanIds.value.length === 0) return;

    Swal.fire({
        title: 'Pushing Updates',
        text: `Transmitting features for ${selectedPlanIds.value.length} plans...`,
        allowOutsideClick: false,
        didOpen: () => {
            Swal.showLoading();
        }
    });

    router.post(route('admin.plans.batch-push-updates'), {
        plan_ids: selectedPlanIds.value
    }, {
        onSuccess: () => {
            isPushModalOpen.value = false;
            Swal.fire({
                title: 'Transmitted!',
                text: 'Feature updates have been pushed to all eligible tenants.',
                icon: 'success',
                timer: 3000,
                toast: true,
                position: 'top-end',
                showConfirmButton: false,
            });
        },
        onError: () => {
            Swal.close();
        }
    });
};
</script>

<template>
    <Head title="Subscription Plans" />
    <AdminLayout>
        <template #header>
            <h1 class="text-xl font-bold text-base-content">Subscription Plans</h1>
        </template>

        <div class="max-w-7xl mx-auto space-y-6 pb-12 mt-6">
            
            <div class="flex justify-end items-center space-x-3 mb-2">
                <button v-if="totalStagedFeatures > 0" @click="openPushModal" class="btn btn-sm bg-warning hover:bg-warning/80 text-warning-content border-transparent rounded-lg shadow-sm transition-all flex items-center animate-pulse">
                    <svg class="w-4 h-4 mr-1.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" /></svg>
                    Push {{ totalStagedFeatures }} Features
                </button>

                <button @click="openCreateModal" class="btn btn-sm text-white border-transparent rounded-lg shadow-sm transition-all flex items-center hover:brightness-110" :style="{ backgroundColor: primaryColor }">
                    <svg class="h-4 w-4 mr-1.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4" /></svg>
                    Create Plan
                </button>
            </div>
            
            <div class="grid grid-cols-1 md:grid-cols-3 gap-8 items-start">
                <div v-for="plan in plans" :key="plan.id" class="bg-base-100 rounded-xl overflow-hidden shadow-sm border border-base-300 hover:shadow-md transition-shadow group flex flex-col h-full">
                    <div class="p-6 border-b border-base-300 flex justify-between items-center bg-base-200/50 group-hover:bg-primary/5 transition-colors">
                        <div class="flex items-center">
                            <h3 class="text-lg font-bold uppercase tracking-wide" :style="{ color: primaryColor }">{{ plan.name }}</h3>
                        </div>
                        <div class="flex items-center space-x-2">
                            <button @click="openEditModal(plan)" class="btn btn-xs text-white transition-all flex items-center space-x-1 border-transparent shadow-sm hover:brightness-110" :style="{ backgroundColor: primaryColor }" title="Manage Plan Settings">
                                <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" /><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" /></svg>
                                <span class="uppercase tracking-wider font-bold text-[9px]">Manage</span>
                            </button>
                        </div>
                    </div>
                    
                    <div class="p-6 flex-1 flex flex-col">
                        <div class="flex items-baseline mb-6">
                            <span class="text-3xl font-extrabold text-base-content">₱{{ Number(plan.price_monthly).toLocaleString() }}</span>
                            <span class="text-sm text-base-content/50 ml-1">/ mo</span>
                        </div>
                        
                        <div class="space-y-1 mb-6">
                            <div v-if="plan.stripe_product_id" class="flex items-center space-x-1.5">
                                <span class="flex-shrink-0 w-2 h-2 rounded-full bg-success shadow-sm shadow-success/20"></span>
                                <span class="text-[10px] text-base-content/60 font-medium">Stripe ID: {{ plan.stripe_product_id }}</span>
                            </div>
                            <div v-if="plan.stripe_monthly_price_id" class="text-[9px] text-base-content/40 pl-3">
                                Monthly Price ID: {{ plan.stripe_monthly_price_id }}
                            </div>
                            <div v-if="plan.stripe_yearly_price_id" class="text-[9px] text-base-content/40 pl-3">
                                Yearly Price ID: {{ plan.stripe_yearly_price_id }}
                            </div>
                            <div v-if="!plan.stripe_product_id" class="flex items-center justify-between">
                                <div class="flex items-center space-x-1.5">
                                    <span class="flex-shrink-0 w-2 h-2 rounded-full bg-warning animate-pulse"></span>
                                    <span class="text-[10px] text-warning font-medium italic">Pending Stripe Sync</span>
                                </div>
                                <button @click="forceSync(plan)" class="text-[9px] bg-warning/10 hover:bg-warning/20 text-warning px-2 py-0.5 rounded transition-colors" title="Trigger Manual Sync">
                                    Sync Now
                                </button>
                            </div>
                            <div v-else class="flex justify-end">
                                <button @click="forceSync(plan)" class="text-[9px] text-base-content/40 hover:text-primary transition-colors flex items-center space-x-1" title="Force Refresh Stripe Data">
                                    <svg class="w-2 h-2" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" /></svg>
                                    <span>Sync</span>
                                </button>
                            </div>
                        </div>

                        <ul class="space-y-4 text-sm text-base-content/70 flex-1">
                            <!-- Main Features -->
                            <li class="flex items-center">
                                <svg class="h-5 w-5 text-success mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                                <strong>{{ plan.max_users }}</strong> &nbsp;Users
                            </li>
                            <li class="flex items-center">
                                <svg class="h-5 w-5 text-success mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                                <strong v-if="plan.max_patients === null">Unlimited</strong>
                                <strong v-else>{{ plan.max_patients }}</strong> &nbsp;Patients
                            </li>
                            <li class="flex items-center">
                                <svg class="h-5 w-5 text-success mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                                <strong v-if="plan.max_appointments === null">Unlimited</strong>
                                <strong v-else>{{ plan.max_appointments }}</strong> &nbsp;Appointments
                            </li>
                            <li class="flex items-center">
                                <svg class="h-5 w-5 text-success mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                                <strong>{{ plan.max_storage_mb >= 1024 ? (plan.max_storage_mb / 1024).toFixed(1) + ' GB' : plan.max_storage_mb + ' MB' }}</strong> &nbsp;Storage
                            </li>

                            <!-- Tier Features -->
                            <li class="flex items-center">
                                <svg v-if="plan.has_qr_booking" class="h-5 w-5 text-success mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                                <svg v-else class="h-5 w-5 text-base-content/20 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                                <span :class="{'text-base-content/30 line-through': !plan.has_qr_booking}">QR Online Booking</span>
                            </li>
                            
                            <li class="flex items-center">
                                <svg v-if="plan.has_sms" class="h-5 w-5 text-success mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                                <svg v-else class="h-5 w-5 text-base-content/20 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                                <span :class="{'text-base-content/30 line-through': !plan.has_sms}">SMS Notifications</span>
                            </li>
                            <li class="flex items-center">
                                <svg v-if="plan.has_branding" class="h-5 w-5 text-success mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                                <svg v-else class="h-5 w-5 text-base-content/20 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                                <span :class="{'text-base-content/30 line-through': !plan.has_branding}">Custom Branding</span>
                            </li>
                            <li class="flex items-center">
                                <svg v-if="plan.has_analytics" class="h-5 w-5 text-success mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                                <svg v-else class="h-5 w-5 text-base-content/20 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                                <span :class="{'text-base-content/30 line-through': !plan.has_analytics}">Advanced Analytics</span>
                            </li>
                            <li class="flex items-center">
                                <svg v-if="plan.has_priority_support" class="h-5 w-5 text-success mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                                <svg v-else class="h-5 w-5 text-base-content/20 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                                <span :class="{'text-base-content/30 line-through': !plan.has_priority_support}">Priority Support</span>
                            </li>
                            <li class="flex items-center">
                                <svg v-if="plan.has_multi_branch" class="h-5 w-5 text-success mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                                <svg v-else class="h-5 w-5 text-base-content/20 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                                <span :class="{'text-base-content/30 line-through': !plan.has_multi_branch}">Multi-branch Readiness</span>
                            </li>

                            <!-- Report Level Badge -->
                            <li class="pt-2">
                                <span :class="[
                                    'text-[10px] uppercase font-bold px-2 py-0.5 rounded-full border',
                                    plan.report_level === 'basic' ? 'bg-base-200 text-base-content/50 border-base-300' : 
                                    plan.report_level === 'enhanced' ? 'bg-info/10 text-info border-info/20' : 
                                    'bg-success/10 text-success border-success/20'
                                ]">
                                    {{ plan.report_level }} Reports
                                </span>
                            </li>
                            
                            <!-- Dynamic Pluggable Features -->
                            <li v-for="dynamicFeature in getDynamicFeatures(plan)" :key="dynamicFeature.id" class="flex flex-col border-t border-base-200/60 mt-3 pt-3">
                                <div class="flex items-center justify-between w-full">
                                    <div class="flex items-center">
                                        <svg class="h-4 w-4 mr-2" :style="{ color: primaryColor }" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" /></svg>
                                        <span class="font-medium text-base-content/90">{{ dynamicFeature.name }}</span>
                                    </div>
                                    <span v-if="!dynamicFeature.pivot.pushed_at" class="text-[9px] bg-warning/10 text-warning px-1.5 py-0.5 rounded shadow-sm font-semibold uppercase tracking-wider relative flex items-center">
                                        <span class="absolute -top-0.5 -right-0.5 flex h-2 w-2">
                                          <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-warning opacity-75"></span>
                                          <span class="relative inline-flex rounded-full h-2 w-2 bg-warning"></span>
                                        </span>
                                        Staged
                                    </span>
                                    <span v-else-if="dynamicFeature.implementation_status === 'active' && dynamicFeature.released_at && new Date(dynamicFeature.released_at) > new Date(dynamicFeature.pivot.pushed_at)" 
                                          class="text-[9px] bg-blue-100 text-blue-600 px-1.5 py-0.5 rounded shadow-sm font-semibold uppercase tracking-wider relative flex items-center">
                                        <span class="absolute -top-0.5 -right-0.5 flex h-2 w-2">
                                          <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-blue-400 opacity-75"></span>
                                          <span class="relative inline-flex rounded-full h-2 w-2 bg-blue-500"></span>
                                        </span>
                                        Update Ready
                                    </span>
                                    <span v-else class="text-[9px] bg-success/10 text-success px-1.5 py-0.5 rounded shadow-sm font-semibold uppercase tracking-wider">
                                        Live
                                    </span>
                                </div>
                                <p v-if="dynamicFeature.description" class="text-xs text-base-content/40 pl-6 mt-0.5 leading-tight line-clamp-2" :title="dynamicFeature.description">
                                    {{ dynamicFeature.description }}
                                </p>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>

        <!-- Create Modal -->
        <Modal :show="isCreateModalOpen" @close="closeCreateModal" maxWidth="xl">
            <PlanForm @submit="handleCreateSubmit" @cancel="closeCreateModal" />
        </Modal>

        <!-- Manage Modal (Replaces Edit) -->
        <Modal :show="isEditModalOpen" @close="closeEditModal" maxWidth="2xl">
            <ManagePlanModal 
                v-if="selectedPlan"
                :plan="selectedPlan" 
                :all-features="allFeatures"
                @submit="closeEditModal" 
                @cancel="closeEditModal" 
                @delete="confirmDelete(selectedPlan)"
            />
        </Modal>

        <!-- Push Features Modal -->
        <Modal :show="isPushModalOpen" @close="isPushModalOpen = false" maxWidth="lg">
            <div class="bg-base-100 rounded-xl overflow-hidden shadow-xl">
                <header class="p-6 border-b border-base-300 bg-base-200/50 flex justify-between items-center">
                    <div>
                        <h2 class="text-xl font-bold flex items-center">
                            <svg class="w-6 h-6 mr-2 text-warning" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" /></svg>
                            Push Staged Features
                        </h2>
                        <p class="text-xs text-base-content/50 mt-1">Select plans to transmit feature updates to tenants.</p>
                    </div>
                    <button @click="isPushModalOpen = false" class="btn btn-ghost btn-sm btn-circle">
                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
                    </button>
                </header>

                <div class="p-6 space-y-4">
                    <div class="bg-warning/5 border border-warning/20 p-4 rounded-xl flex items-start">
                        <svg class="w-5 h-5 text-warning mr-3 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                        <p class="text-xs text-warning/80 leading-relaxed font-semibold">
                            Pushing updates notifies all tenants subscribed to the selected plans. Non-subscribed tenants may receive upgrade advertisements.
                        </p>
                    </div>

                    <div class="space-y-2">
                        <label v-for="plan in plansWithStagedFeatures" :key="plan.id" class="flex items-center justify-between p-3 bg-base-200/30 hover:bg-base-200/60 rounded-lg cursor-pointer border border-transparent hover:border-warning/30 transition-all group">
                            <div class="flex items-center">
                                <input type="checkbox" :value="plan.id" v-model="selectedPlanIds" class="checkbox checkbox-warning checkbox-sm mr-3" />
                                <div>
                                    <span class="text-sm font-bold uppercase tracking-tight group-hover:text-warning transition-colors" :style="{ color: primaryColor }">{{ plan.name }}</span>
                                    <p class="text-[10px] text-base-content/40">{{ getStagedFeaturesCount(plan) }} staged features</p>
                                </div>
                            </div>
                        </label>
                    </div>
                </div>

                <footer class="p-6 border-t border-base-300 bg-base-100 flex justify-between items-center">
                    <button @click="isPushModalOpen = false" class="btn btn-ghost btn-sm normal-case">Cancel</button>
                    <button 
                        @click="handleBatchPush" 
                        class="btn btn-warning btn-sm normal-case px-6 text-white font-bold shadow-sm"
                        :disabled="selectedPlanIds.length === 0"
                    >
                        Push to {{ selectedPlanIds.length }} Plans
                    </button>
                </footer>
            </div>
        </Modal>
    </AdminLayout>
</template>

<script setup>
import { Head, useForm, router } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import Modal from '@/Components/Modal.vue';
import PlanForm from './Partials/PlanForm.vue';
import { ref } from 'vue';
import Swal from 'sweetalert2';

const props = defineProps({
    plans: Array,
});

const isCreateModalOpen = ref(false);
const isEditModalOpen = ref(false);
const selectedPlan = ref(null);

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
    return plan.features.filter(f => !f.pivot.pushed_at).length;
};

const pushUpdates = (plan) => {
    const stagedCount = getStagedFeaturesCount(plan);
    Swal.fire({
        title: 'Push Updates?',
        text: `This will instantly push ${stagedCount} staged features to the entire platform. Subscribers will receive an update alert, and non-subscribers will receive an upgrade advertisement.`,
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#0d9488',
        confirmButtonText: 'Yes, push locally',
        showLoaderOnConfirm: true,
        preConfirm: () => {
            return new Promise((resolve) => {
                router.post(route('admin.plans.push-updates', plan.id), {}, {
                    onSuccess: () => {
                        resolve();
                    },
                    onError: (errors) => {
                        Swal.showValidationMessage(errors.message || 'Transmission failed.');
                        resolve();
                    }
                });
            });
        },
        allowOutsideClick: () => !Swal.isLoading()
    }).then((result) => {
        if (result.isConfirmed) {
            Swal.fire('Transmitted!', 'Tenants have been notified of the new updates.', 'success');
        }
    });
};
</script>

<template>
    <Head title="Subscription Plans" />
    <AdminLayout>
        <template #header>
            <div class="flex justify-between items-center w-full">
                <h1 class="text-xl font-bold text-base-content">Subscription Plans</h1>
                <button @click="openCreateModal" class="btn btn-primary btn-sm rounded-lg shadow-sm shadow-primary/10 transition-all flex items-center">
                    <svg class="h-4 w-4 mr-1.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4" /></svg>
                    Create Plan
                </button>
            </div>
        </template>

        <div class="max-w-7xl mx-auto space-y-6 pb-12">
            
            <div class="grid grid-cols-1 md:grid-cols-3 gap-8 items-start">
                <div v-for="plan in plans" :key="plan.id" class="bg-base-100 rounded-xl overflow-hidden shadow-sm border border-base-300 hover:shadow-md transition-shadow group flex flex-col h-full">
                    <div class="p-6 border-b border-base-300 flex justify-between items-center bg-base-200/50 group-hover:bg-primary/5 transition-colors">
                        <div class="flex items-center space-x-3">
                            <h3 class="text-lg font-bold text-primary uppercase tracking-wide">{{ plan.name }}</h3>
                            <button v-if="getStagedFeaturesCount(plan) > 0" @click="pushUpdates(plan)" title="Broadcast Updates" class="flex items-center space-x-1 text-[10px] bg-warning hover:bg-warning/80 text-warning-content px-2 py-0.5 rounded shadow-sm font-bold animate-pulse transition">
                                <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" /></svg>
                                <span>Push {{ getStagedFeaturesCount(plan) }} Features</span>
                            </button>
                        </div>
                        <div class="flex items-center space-x-2">
                            <button @click="openEditModal(plan)" class="text-base-content/40 hover:text-primary transition-colors" title="Edit Plan">
                                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" /></svg>
                            </button>
                            <button @click="confirmDelete(plan)" class="text-base-content/40 hover:text-error transition-colors" title="Delete Plan">
                                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" /></svg>
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
                                        <svg class="h-4 w-4 text-primary mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" /></svg>
                                        <span class="font-medium text-base-content/90">{{ dynamicFeature.name }}</span>
                                    </div>
                                    <span v-if="!dynamicFeature.pivot.pushed_at" class="text-[9px] bg-warning/10 text-warning px-1.5 py-0.5 rounded shadow-sm font-semibold uppercase tracking-wider relative flex items-center">
                                        <span class="absolute -top-0.5 -right-0.5 flex h-2 w-2">
                                          <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-warning opacity-75"></span>
                                          <span class="relative inline-flex rounded-full h-2 w-2 bg-warning"></span>
                                        </span>
                                        Staged
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

        <!-- Edit Modal -->
        <Modal :show="isEditModalOpen" @close="closeEditModal" maxWidth="xl">
            <PlanForm 
                v-if="selectedPlan"
                :plan="selectedPlan" 
                @submit="handleEditSubmit" 
                @cancel="closeEditModal" 
            />
        </Modal>
    </AdminLayout>
</template>

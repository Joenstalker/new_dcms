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
    form.post(route('admin.plans.store'), {
        onSuccess: () => {
            closeCreateModal();
            Swal.fire({
                title: 'Success!',
                text: 'Plan created successfully.',
                icon: 'success',
                timer: 2000,
                toast: true,
                position: 'top-end',
                showConfirmButton: false,
            });
        },
    });
};

const handleEditSubmit = (form) => {
    form.put(route('admin.plans.update', selectedPlan.value.id), {
        onSuccess: () => {
            closeEditModal();
            Swal.fire({
                title: 'Updated!',
                text: 'Plan updated successfully.',
                icon: 'success',
                timer: 2000,
                toast: true,
                position: 'top-end',
                showConfirmButton: false,
            });
        },
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
</script>

<template>
    <Head title="Subscription Plans" />
    <AdminLayout>
        <template #header>
            <div class="flex justify-between items-center w-full">
                <h1 class="text-xl font-bold text-gray-900">Subscription Plans</h1>
                <button @click="openCreateModal" class="bg-teal-600 hover:bg-teal-700 text-white px-4 py-2 rounded-lg text-sm font-bold shadow-sm shadow-teal-900/10 transition-all flex items-center">
                    <svg class="h-4 w-4 mr-1.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4" /></svg>
                    Create Plan
                </button>
            </div>
        </template>

        <div class="max-w-7xl mx-auto space-y-6">
            
            <div class="grid grid-cols-1 md:grid-cols-3 gap-8 items-start">
                <div v-for="plan in plans" :key="plan.id" class="bg-white rounded-xl overflow-hidden shadow-sm border border-gray-200 hover:shadow-md transition-shadow group">
                    <div class="p-6 border-b border-gray-100 flex justify-between items-center bg-gray-50 group-hover:bg-teal-50/30 transition-colors">
                        <h3 class="text-lg font-bold text-teal-700 uppercase tracking-wide">{{ plan.name }}</h3>
                        <div class="flex items-center space-x-2">
                            <button @click="openEditModal(plan)" class="text-gray-400 hover:text-teal-600 transition-colors" title="Edit Plan">
                                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" /></svg>
                            </button>
                            <button @click="confirmDelete(plan)" class="text-gray-400 hover:text-red-600 transition-colors" title="Delete Plan">
                                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" /></svg>
                            </button>
                        </div>
                    </div>
                    
                    <div class="p-6">
                        <div class="flex items-baseline mb-6">
                            <span class="text-3xl font-extrabold text-gray-900">₱{{ Number(plan.price_monthly).toLocaleString() }}</span>
                            <span class="text-sm text-gray-500 ml-1">/ mo</span>
                        </div>
                        
                        <div class="space-y-1 mb-6">
                            <div v-if="plan.stripe_product_id" class="flex items-center space-x-1.5">
                                <span class="flex-shrink-0 w-2 h-2 rounded-full bg-green-500"></span>
                                <span class="text-[10px] text-gray-500 font-medium">Stripe ID: {{ plan.stripe_product_id }}</span>
                            </div>
                            <div v-if="plan.stripe_monthly_price_id" class="text-[9px] text-gray-400 pl-3">
                                Monthly Price ID: {{ plan.stripe_monthly_price_id }}
                            </div>
                            <div v-if="plan.stripe_yearly_price_id" class="text-[9px] text-gray-400 pl-3">
                                Yearly Price ID: {{ plan.stripe_yearly_price_id }}
                            </div>
                            <div v-if="!plan.stripe_product_id" class="flex items-center space-x-1.5">
                                <span class="flex-shrink-0 w-2 h-2 rounded-full bg-amber-400"></span>
                                <span class="text-[10px] text-amber-600 font-medium italic">Pending Stripe Sync</span>
                            </div>
                        </div>

                        <ul class="space-y-4 text-sm text-gray-600">
                            <!-- Main Features -->
                            <li class="flex items-center">
                                <svg class="h-5 w-5 text-teal-500 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                                <strong>{{ plan.max_users }}</strong> &nbsp;Users
                            </li>
                            <li class="flex items-center">
                                <svg class="h-5 w-5 text-teal-500 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                                <strong v-if="plan.max_patients === null">Unlimited</strong>
                                <strong v-else>{{ plan.max_patients }}</strong> &nbsp;Patients
                            </li>
                            <li class="flex items-center">
                                <svg class="h-5 w-5 text-teal-500 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                                <strong v-if="plan.max_appointments === null">Unlimited</strong>
                                <strong v-else>{{ plan.max_appointments }}</strong> &nbsp;Appointments
                            </li>

                            <!-- Tier Features -->
                            <li class="flex items-center">
                                <svg v-if="plan.has_qr_booking" class="h-5 w-5 text-teal-500 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                                <svg v-else class="h-5 w-5 text-gray-300 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                                <span :class="{'text-gray-400 line-through': !plan.has_qr_booking}">QR Online Booking</span>
                            </li>
                            
                            <li class="flex items-center">
                                <svg v-if="plan.has_sms" class="h-5 w-5 text-teal-500 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                                <svg v-else class="h-5 w-5 text-gray-300 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                                <span :class="{'text-gray-400 line-through': !plan.has_sms}">SMS Notifications</span>
                            </li>
                            <li class="flex items-center">
                                <svg v-if="plan.has_branding" class="h-5 w-5 text-teal-500 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                                <svg v-else class="h-5 w-5 text-gray-300 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                                <span :class="{'text-gray-400 line-through': !plan.has_branding}">Custom Branding</span>
                            </li>
                            <li class="flex items-center">
                                <svg v-if="plan.has_analytics" class="h-5 w-5 text-teal-500 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                                <svg v-else class="h-5 w-5 text-gray-300 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                                <span :class="{'text-gray-400 line-through': !plan.has_analytics}">Advanced Analytics</span>
                            </li>
                            <li class="flex items-center">
                                <svg v-if="plan.has_priority_support" class="h-5 w-5 text-teal-500 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                                <svg v-else class="h-5 w-5 text-gray-300 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                                <span :class="{'text-gray-400 line-through': !plan.has_priority_support}">Priority Support</span>
                            </li>
                            <li class="flex items-center">
                                <svg v-if="plan.has_multi_branch" class="h-5 w-5 text-teal-500 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                                <svg v-else class="h-5 w-5 text-gray-300 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                                <span :class="{'text-gray-400 line-through': !plan.has_multi_branch}">Multi-branch Readiness</span>
                            </li>

                            <!-- Report Level Badge -->
                            <li class="pt-2">
                                <span :class="[
                                    'text-[10px] uppercase font-bold px-2 py-0.5 rounded-full border',
                                    plan.report_level === 'basic' ? 'bg-gray-50 text-gray-500 border-gray-200' : 
                                    plan.report_level === 'enhanced' ? 'bg-blue-50 text-blue-600 border-blue-100' : 
                                    'bg-teal-50 text-teal-600 border-teal-100'
                                ]">
                                    {{ plan.report_level }} Reports
                                </span>
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

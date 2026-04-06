<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, useForm, usePage } from '@inertiajs/vue3';
import { ref, computed } from 'vue';
import Swal from 'sweetalert2';

import ServicesTabs from './Partials/ServicesTabs.vue';
import ServicesTable from './Partials/ServicesTable.vue';
import ServiceFormModal from './Partials/ServiceFormModal.vue';

const props = defineProps({
    services: Array
});

const page = usePage();
const user = computed(() => page.props.auth.user);
const roles = computed(() => user.value?.roles || []);
const isOwnerOrDentist = computed(() => roles.value.includes('Owner') || roles.value.includes('Dentist'));

const activeTab = ref('all');
const searchQuery = ref('');

const filteredServices = computed(() => {
    let result = props.services;
    
    // Status filter
    if (activeTab.value !== 'all') {
        result = result.filter(s => s.status === activeTab.value);
    }
    
    // Search filter
    if (searchQuery.value.trim() !== '') {
        const query = searchQuery.value.toLowerCase();
        result = result.filter(s => 
            s.name.toLowerCase().includes(query) || 
            (s.description && s.description.toLowerCase().includes(query))
        );
    }
    
    return result;
});

// Create/Edit Modal State
const isModalOpen = ref(false);
const editingService = ref(null);
const form = useForm({
    name: '',
    description: '',
    price: '',
});

const openModal = (service = null) => {
    editingService.value = service;
    if (service) {
        form.name = service.name;
        form.description = service.description;
        form.price = service.price;
    } else {
        form.reset();
    }
    isModalOpen.value = true;
};

const submit = (payload) => {
    const { form: childForm } = payload;
    
    if (editingService.value) {
        childForm.put(route('services.update', editingService.value.id), {
            onSuccess: () => {
                isModalOpen.value = false;
                Swal.fire('Updated!', 'Service has been updated.', 'success');
            }
        });
    } else {
        childForm.post(route('services.store'), {
            onSuccess: () => {
                isModalOpen.value = false;
                const msg = isOwnerOrDentist.value ? 'Service added successfully!' : 'Service submitted for approval.';
                Swal.fire('Success!', msg, 'success');
            }
        });
    }
};

const deleteService = (id) => {
    Swal.fire({
        title: 'Are you sure?',
        text: "You won't be able to revert this!",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        cancelButtonColor: '#3085d6',
        confirmButtonText: 'Yes, delete it!'
    }).then((result) => {
        if (result.isConfirmed) {
            form.delete(route('services.destroy', id), {
                onSuccess: () => Swal.fire('Deleted!', 'Service has been removed.', 'success')
            });
        }
    });
};

const approveService = (id) => {
    Swal.fire({
        title: 'Approve Service?',
        text: "This service will become visible to the clinic.",
        icon: 'question',
        showCancelButton: true,
        confirmButtonText: 'Yes, Approve'
    }).then((result) => {
        if (result.isConfirmed) {
            form.post(route('services.approve', id), {
                onSuccess: () => Swal.fire('Approved!', 'Service is now active.', 'success')
            });
        }
    });
};

const rejectService = (id) => {
    Swal.fire({
        title: 'Reject Service?',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'Yes, Reject'
    }).then((result) => {
        if (result.isConfirmed) {
            form.post(route('services.reject', id), {
                onSuccess: () => Swal.fire('Rejected', 'Service status updated.', 'error')
            });
        }
    });
};

const closeModal = () => {
    isModalOpen.value = false;
    editingService.value = null;
};
</script>

<template>
    <Head title="Service & Pricing" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
                <h2 class="text-2xl font-bold text-gray-800">Service & Pricing</h2>
            </div>
        </template>

        <div class="py-6">
            <!-- Tabs & Search Navigation -->
            <div class="flex flex-col md:flex-row justify-between items-stretch md:items-center gap-4 mb-6">
                <ServicesTabs v-model:activeTab="activeTab" />
                
                <div class="flex flex-col sm:flex-row gap-3">
                    <div class="relative w-full sm:w-64">
                        <span class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                            <svg class="h-5 w-5 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                            </svg>
                        </span>
                        <input 
                            v-model="searchQuery"
                            type="text" 
                            placeholder="Search services..." 
                            class="input input-bordered w-full pl-10 rounded-xl focus:ring-2 focus:ring-primary shadow-sm"
                        />
                    </div>
                    
                    <button v-if="can('create services') || isOwnerOrDentist" @click="openModal()" class="btn btn-primary shadow-lg border-none px-6 rounded-xl">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                        </svg>
                        New Service
                    </button>
                </div>
            </div>

            <!-- Services Table -->
            <ServicesTable 
                :services="filteredServices"
                :is-owner-or-dentist="isOwnerOrDentist"
                @edit="openModal"
                @delete="deleteService"
                @approve="approveService"
                @reject="rejectService"
            />
        </div>

        <!-- Create/Edit Modal -->
        <ServiceFormModal
            :show="isModalOpen"
            :editing-service="editingService"
            :is-owner-or-dentist="isOwnerOrDentist"
            @close="closeModal"
            @submit="submit"
        />
    </AuthenticatedLayout>
</template>

<style scoped>
.tab-active {
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
}
</style>

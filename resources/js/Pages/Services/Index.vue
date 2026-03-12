<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, useForm, usePage } from '@inertiajs/vue3';
import { ref, computed } from 'vue';
import Swal from 'sweetalert2';

const props = defineProps({
    services: Array
});

const page = usePage();
const user = computed(() => page.props.auth.user);
const roles = computed(() => user.value?.roles || []);
const isOwnerOrDentist = computed(() => roles.value.includes('Owner') || roles.value.includes('Dentist'));

const activeTab = ref('all');
const tabs = [
    { id: 'all', label: 'All Services', icon: 'list' },
    { id: 'approved', label: 'Approved', icon: 'check', color: 'text-success' },
    { id: 'pending', label: 'Pending', icon: 'clock', color: 'text-warning' },
    { id: 'rejected', label: 'Rejected', icon: 'x', color: 'text-error' },
];

const filteredServices = computed(() => {
    if (activeTab.value === 'all') return props.services;
    return props.services.filter(s => s.status === activeTab.value);
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

const submit = () => {
    if (editingService.value) {
        form.put(route('services.update', editingService.value.id), {
            onSuccess: () => {
                isModalOpen.value = false;
                Swal.fire('Updated!', 'Service has been updated.', 'success');
            }
        });
    } else {
        form.post(route('services.store'), {
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

const getStatusBadge = (status) => {
    const classes = {
        approved: 'badge-success text-white',
        pending: 'badge-warning text-white',
        rejected: 'badge-error text-white'
    };
    return classes[status] || 'badge-ghost';
};
</script>

<template>
    <Head title="Service & Pricing" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
                <h2 class="text-2xl font-bold text-gray-800">Service & Pricing</h2>
                <button @click="openModal()" class="btn btn-primary shadow-lg border-none">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                    </svg>
                    New Service
                </button>
            </div>
        </template>

        <div class="py-6">
            <!-- Tabs Navigation -->
            <div class="tabs tabs-boxed mb-8 bg-white p-2 shadow-sm rounded-2xl border border-gray-100 max-w-2xl">
                <a 
                    v-for="tab in tabs" 
                    :key="tab.id"
                    @click="activeTab = tab.id"
                    class="tab tab-lg transition-all duration-300 rounded-xl"
                    :class="[activeTab === tab.id ? 'tab-active bg-primary text-white shadow-md' : 'text-gray-500 hover:bg-gray-50']"
                >
                    <span class="flex items-center gap-2">
                        <span :class="activeTab === tab.id ? 'text-white' : tab.color">{{ tab.label }}</span>
                    </span>
                </a>
            </div>

            <div class="bg-white rounded-3xl shadow-xl overflow-hidden border border-gray-100">
                <div class="overflow-x-auto">
                    <table class="table table-lg w-full">
                        <thead class="bg-slate-50">
                            <tr class="text-slate-500">
                                <th>Service Name</th>
                                <th>Price</th>
                                <th>Status</th>
                                <th>Created By</th>
                                <th class="text-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="service in filteredServices" :key="service.id" class="hover:bg-slate-50/50 transition-colors">
                                <td>
                                    <div class="flex flex-col">
                                        <span class="font-bold text-slate-800">{{ service.name }}</span>
                                        <span class="text-xs text-slate-500 max-w-xs truncate">{{ service.description }}</span>
                                    </div>
                                </td>
                                <td>
                                    <span class="font-mono font-semibold text-primary">${{ Number(service.price).toFixed(2) }}</span>
                                </td>
                                <td>
                                    <div :class="['badge badge-md uppercase font-bold text-[10px] tracking-widest px-3 py-3 border-none', getStatusBadge(service.status)]">
                                        {{ service.status }}
                                    </div>
                                </td>
                                <td>
                                    <div class="flex items-center gap-2">
                                        <div class="avatar placeholder">
                                            <div class="bg-neutral text-neutral-content rounded-full w-8">
                                                <span class="text-xs">{{ service.creator?.name.charAt(0) }}</span>
                                            </div>
                                        </div>
                                        <span class="text-sm font-medium text-slate-600">{{ service.creator?.name }}</span>
                                    </div>
                                </td>
                                <td class="text-right space-x-2">
                                    <!-- Approval Actions -->
                                    <template v-if="isOwnerOrDentist && service.status === 'pending'">
                                        <button @click="approveService(service.id)" class="btn btn-sm btn-success text-white">Approve</button>
                                        <button @click="rejectService(service.id)" class="btn btn-sm btn-outline btn-error">Reject</button>
                                    </template>
                                    
                                    <!-- Regular Actions -->
                                    <button @click="openModal(service)" class="btn btn-sm btn-ghost hover:bg-indigo-50 text-indigo-600">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z" />
                                        </svg>
                                    </button>
                                    <button @click="deleteService(service.id)" class="btn btn-sm btn-ghost hover:bg-red-50 text-red-500">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                        </svg>
                                    </button>
                                </td>
                            </tr>
                            <tr v-if="filteredServices.length === 0">
                                <td colspan="5" class="py-20 text-center">
                                    <div class="flex flex-col items-center gap-2 opacity-40">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-16 w-16" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4" />
                                        </svg>
                                        <p class="text-xl font-bold">No services found in this category.</p>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Create/Edit Modal using daisyUI -->
        <div class="modal" :class="{'modal-open': isModalOpen}">
            <div class="modal-box rounded-3xl max-w-lg">
                <h3 class="font-bold text-2xl mb-6">{{ editingService ? 'Edit Service' : 'New Service' }}</h3>
                <form @submit.prevent="submit" class="space-y-4">
                    <div class="form-control w-full">
                        <label class="label font-semibold text-slate-600">Service Name</label>
                        <input v-model="form.name" type="text" placeholder="e.g. Tooth Extraction" class="input input-bordered w-full rounded-xl focus:ring-2 focus:ring-primary shadow-sm" required />
                        <label v-if="form.errors.name" class="label text-error text-xs">{{ form.errors.name }}</label>
                    </div>

                    <div class="form-control w-full">
                        <label class="label font-semibold text-slate-600">Price ($)</label>
                        <input v-model="form.price" type="number" step="0.01" placeholder="0.00" class="input input-bordered w-full rounded-xl focus:ring-2 focus:ring-primary shadow-sm" required />
                        <label v-if="form.errors.price" class="label text-error text-xs">{{ form.errors.price }}</label>
                    </div>

                    <div class="form-control w-full">
                        <label class="label font-semibold text-slate-600">Description</label>
                        <textarea v-model="form.description" class="textarea textarea-bordered h-24 rounded-xl focus:ring-2 focus:ring-primary shadow-sm" placeholder="Details about the service..."></textarea>
                        <label v-if="form.errors.description" class="label text-error text-xs">{{ form.errors.description }}</label>
                    </div>

                    <div v-if="!isOwnerOrDentist" class="alert alert-info py-2 rounded-xl text-xs flex gap-2">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" class="stroke-current flex-shrink-0 w-4 h-4"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                        <span>Note: Your changes will require approval from the Owner or Dentist.</span>
                    </div>

                    <div class="modal-action mt-8 flex justify-end gap-2">
                        <button type="button" @click="isModalOpen = false" class="btn btn-ghost rounded-xl">Cancel</button>
                        <button type="submit" class="btn btn-primary rounded-xl px-8 shadow-md" :disabled="form.processing">
                            {{ editingService ? 'Save Changes' : 'Create Service' }}
                        </button>
                    </div>
                </form>
            </div>
            <div class="modal-backdrop bg-slate-900/40" @click="isModalOpen = false"></div>
        </div>
    </AuthenticatedLayout>
</template>

<style scoped>
.tab-active {
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
}
</style>

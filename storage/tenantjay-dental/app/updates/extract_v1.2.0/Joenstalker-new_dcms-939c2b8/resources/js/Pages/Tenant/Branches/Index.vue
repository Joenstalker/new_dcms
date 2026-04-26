<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, useForm } from '@inertiajs/vue3';
import { ref } from 'vue';
import BranchFormModal from './BranchFormModal.vue';
import Swal from 'sweetalert2';

const props = defineProps({
    branches: { type: Array, default: () => [] }
});

const isModalOpen = ref(false);
const editingBranch = ref(null);

const form = useForm({
    branch_id: null
});

const openCreateModal = () => {
    editingBranch.value = null;
    isModalOpen.value = true;
};

const openEditModal = (branch) => {
    editingBranch.value = branch;
    isModalOpen.value = true;
};

const deleteBranch = (branch) => {
    if (branch.is_primary) {
        Swal.fire({
            icon: 'error',
            title: 'Cannot Delete Primary Branch',
            text: 'Please set another branch as primary first.'
        });
        return;
    }

    Swal.fire({
        title: 'Delete Branch?',
        text: `Are you sure you want to delete ${branch.name}? This action cannot be undone.`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#ef4444',
        cancelButtonColor: '#6b7280',
        confirmButtonText: 'Yes, delete it!'
    }).then((result) => {
        if (result.isConfirmed) {
            form.delete(route('branches.destroy', branch.id), {
                preserveScroll: true,
                onSuccess: () => {
                    Swal.fire('Deleted!', 'Branch has been deleted.', 'success');
                }
            });
        }
    });
};

const switchBranch = (branchId) => {
    form.branch_id = branchId;
    form.post(route('branches.switch'), {
        preserveScroll: true,
        onSuccess: () => {
            window.location.reload();
        }
    });
};
</script>

<template>
    <Head title="Branch Management" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center justify-between">
                <h2 class="text-xl font-semibold leading-tight text-gray-800">Branch Management</h2>
                <button @click="openCreateModal" class="btn btn-primary btn-sm">
                    <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" /></svg>
                    Add Branch
                </button>
            </div>
        </template>

        <div class="mt-6">
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 relative">
                <div v-for="branch in branches" :key="branch.id" class="bg-base-100 rounded-2xl shadow-sm border border-base-200 overflow-hidden relative flex flex-col">
                    <div v-if="branch.is_primary" class="absolute top-0 right-0 bg-blue-500 text-white text-[10px] font-black uppercase tracking-wider px-3 py-1 rounded-bl-xl z-10">
                        Primary
                    </div>
                    <div v-if="!branch.is_active" class="absolute top-0 right-0 bg-gray-500 text-white text-[10px] font-black uppercase tracking-wider px-3 py-1 rounded-bl-xl z-10 mt-6 lg:mt-0">
                        Inactive
                    </div>
                    
                    <div class="p-6 flex-1">
                        <h3 class="text-lg font-black text-base-content">{{ branch.name }}</h3>
                        <div class="flex items-center text-sm text-base-content/60 mt-2">
                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" /><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" /></svg>
                            {{ branch.address || 'No address provided' }}
                        </div>
                        <div class="flex items-center gap-4 mt-4 text-sm mt-auto">
                            <div class="flex items-center text-base-content/60">
                                <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z" /></svg>
                                {{ branch.phone || 'N/A' }}
                            </div>
                            <div class="flex items-center text-base-content/60 truncate">
                                <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" /></svg>
                                {{ branch.email || 'N/A' }}
                            </div>
                        </div>

                        <div class="grid grid-cols-2 gap-4 mt-6 pt-6 border-t border-base-200">
                            <div class="text-center">
                                <div class="text-2xl font-black text-base-content">{{ branch.users_count || 0 }}</div>
                                <div class="text-[10px] uppercase tracking-wider font-bold text-base-content/40">Staff</div>
                            </div>
                            <div class="text-center border-l border-base-200">
                                <div class="text-2xl font-black text-base-content">{{ branch.patients_count || 0 }}</div>
                                <div class="text-[10px] uppercase tracking-wider font-bold text-base-content/40">Patients</div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="bg-base-50 p-4 border-t border-base-200 flex items-center justify-between">
                        <button @click="switchBranch(branch.id)" class="btn btn-xs btn-outline" :class="{ 'btn-primary': $page.props.current_branch_id === branch.id }" :disabled="$page.props.current_branch_id === branch.id">
                            {{ $page.props.current_branch_id === branch.id ? 'Active' : 'Switch To' }}
                        </button>
                        <div class="flex items-center gap-2">
                            <button @click="openEditModal(branch)" class="btn btn-xs btn-ghost text-blue-500 hover:bg-blue-50">Edit</button>
                            <button @click="deleteBranch(branch)" class="btn btn-xs btn-ghost text-red-500 hover:bg-red-50" :disabled="branch.is_primary">Delete</button>
                        </div>
                    </div>
                </div>

                <div v-if="!branches.length" class="col-span-full py-12 text-center bg-base-100 rounded-2xl border-2 border-dashed border-base-300">
                    <svg class="w-12 h-12 mx-auto text-base-content/20 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" /></svg>
                    <h3 class="text-lg font-black text-base-content">No Branches Found</h3>
                    <p class="text-sm text-base-content/50 mt-1 mb-4">You haven't created any clinic branches yet.</p>
                    <button @click="openCreateModal" class="btn btn-primary btn-sm">Create Your First Branch</button>
                </div>
            </div>
        </div>

        <BranchFormModal 
            :is-open="isModalOpen" 
            :branch="editingBranch" 
            @close="isModalOpen = false" 
        />
    </AuthenticatedLayout>
</template>

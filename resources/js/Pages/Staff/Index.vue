<template>
    <Head title="Staff Management" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex justify-between items-center">
                <h2 class="text-xl font-semibold leading-tight text-gray-800">
                    Staff Management
                </h2>
            </div>
        </template>

        <div class="py-12">
            <div class="mx-auto max-w-7xl sm:px-6 lg:px-8">
                <div v-if="activeTab === 'list'">
                    <!-- Add Staff Button -->
                    <div class="mb-6 flex justify-end">
                        <Link
                            v-if="can('create staff')"
                            :href="route('staff.create')"
                            class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 transition flex items-center shadow-sm"
                        >
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                            </svg>
                            Add Staff Member
                        </Link>
                    </div>

                    <div v-if="$page.props.flash?.success && activeTab === 'list'" class="mb-4 p-4 bg-green-100 text-green-700 rounded-lg shadow-sm border border-green-200">
                        {{ $page.props.flash.success }}
                    </div>

                    <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg border border-gray-100">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Name</th>
                                    <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Email</th>
                                    <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Role</th>
                                    <th class="px-6 py-3 text-right text-xs font-semibold text-gray-500 uppercase tracking-wider">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                <tr v-for="member in staff" :key="member.id" class="hover:bg-gray-50 transition-colors">
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">{{ member.name }}</td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ member.email }}</td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                        <span class="px-2.5 py-0.5 inline-flex text-xs leading-5 font-semibold rounded-full bg-blue-100 text-blue-800">
                                            {{ member.roles?.[0]?.name || 'Staff' }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium space-x-3">
                                        <button @click="showStaff(member)" v-if="can('view staff')" class="text-blue-600 hover:text-blue-900">View</button>
                                        <button @click="editStaff(member)" v-if="can('edit staff')" class="text-indigo-600 hover:text-indigo-900 font-semibold">Edit</button>
                                        <button @click="deleteStaff(member.id)" v-if="can('delete staff')" class="text-red-600 hover:text-red-900">Remove</button>
                                    </td>
                                </tr>
                                <tr v-if="staff.length === 0">
                                    <td colspan="4" class="px-6 py-10 text-center text-gray-500 italic bg-gray-50/50">
                                        No staff members found. Add your first team member to get started!
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <div v-else-if="activeTab === 'permissions'">
                    <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
                        <!-- Staff List Sidebar -->
                        <div class="md:col-span-1 bg-white rounded-xl border border-gray-100 shadow-sm overflow-hidden h-fit">
                            <div class="p-4 border-b border-gray-50 bg-gray-50/50">
                                <h3 class="text-sm font-bold text-gray-900 uppercase tracking-wider">Select Staff</h3>
                            </div>
                            <div class="divide-y divide-gray-50">
                                <button 
                                    v-for="member in staff" 
                                    :key="member.id"
                                    @click="selectStaffForPermissions(member)"
                                    :class="selectedStaffPermissions?.id === member.id ? 'bg-blue-50 text-blue-700' : 'text-gray-600 hover:bg-gray-50'"
                                    class="w-full text-left p-4 transition-colors flex flex-col"
                                >
                                    <span class="font-semibold text-sm">{{ member.name }}</span>
                                    <span class="text-xs opacity-70">{{ member.roles?.[0]?.name }}</span>
                                </button>
                            </div>
                        </div>

                        <!-- Permission Checklist Grid -->
                        <div class="md:col-span-3 space-y-6">
                            <div v-if="selectedStaffPermissions" class="bg-white rounded-2xl border border-gray-100 shadow-sm p-6 animate-fade-in">
                                <div class="flex justify-between items-center mb-8 border-b pb-4">
                                    <div>
                                        <h3 class="text-xl font-bold text-gray-900">Permissions for {{ selectedStaffPermissions.name }}</h3>
                                        <p class="text-sm text-gray-500">Toggle exactly what this staff member can access</p>
                                    </div>
                                    <PrimaryButton 
                                        @click="savePermissionsTab" 
                                        :class="{ 'opacity-25': permissionForm.processing }" 
                                        :disabled="permissionForm.processing"
                                        class="shadow-lg shadow-blue-500/20"
                                    >
                                        Apply Changes
                                    </PrimaryButton>
                                </div>

                                <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                                    <div v-for="(group, feature) in permissionGroups" :key="feature" class="bg-gray-50/50 p-5 rounded-2xl border border-gray-100/50 hover:border-blue-100 transition-colors">
                                        <h4 class="text-xs font-bold text-gray-400 uppercase tracking-widest mb-4 flex items-center">
                                            <span class="w-2 h-2 bg-blue-500 rounded-full mr-2"></span>
                                            {{ feature }}
                                        </h4>
                                        <div class="space-y-3">
                                            <label v-for="permission in group" :key="permission.id" class="flex items-center space-x-3 cursor-pointer group">
                                                <input 
                                                    type="checkbox" 
                                                    :value="permission.name" 
                                                    v-model="permissionForm.permissions"
                                                    class="h-5 w-5 rounded-lg border-gray-300 text-blue-600 focus:ring-blue-500 transition cursor-pointer"
                                                >
                                                <span class="text-sm font-medium text-gray-600 group-hover:text-gray-900 transition">{{ permission.displayName }}</span>
                                            </label>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div v-else class="bg-white rounded-2xl border border-gray-200 border-dashed p-12 text-center flex flex-col items-center justify-center">
                                <div class="w-16 h-16 bg-gray-50 rounded-full flex items-center justify-center mb-4">
                                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-8 h-8 text-gray-400">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 1 1-7.5 0 3.75 3.75 0 0 1 7.5 0ZM4.501 20.118a7.5 7.5 0 0 1 14.998 0A17.933 17.933 0 0 1 12 21.75c-2.676 0-5.216-.584-7.499-1.632Z" />
                                    </svg>
                                </div>
                                <h3 class="text-lg font-bold text-gray-900">No Staff Selected</h3>
                                <p class="text-gray-500 max-w-xs">Select a staff member from the sidebar to manage their granular permissions.</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- View Staff Modal -->
        <Modal :show="showingStaffModal" @close="showingStaffModal = false" maxWidth="md">
            <div class="p-6">
                <div class="flex justify-between items-start mb-6">
                    <h3 class="text-lg font-bold text-gray-900">Staff Details</h3>
                    <button @click="showingStaffModal = false" class="text-gray-400 hover:text-gray-500">
                        <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>
                <div class="space-y-4">
                    <div class="grid grid-cols-3 gap-4">
                        <span class="text-sm font-semibold text-gray-500">Name:</span>
                        <span class="col-span-2 text-sm text-gray-900">{{ selectedStaff?.name }}</span>
                    </div>
                    <div class="grid grid-cols-3 gap-4">
                        <span class="text-sm font-semibold text-gray-500">Email:</span>
                        <span class="col-span-2 text-sm text-gray-900">{{ selectedStaff?.email }}</span>
                    </div>
                    <div class="grid grid-cols-3 gap-4">
                        <span class="text-sm font-semibold text-gray-500">Role:</span>
                        <span class="col-span-2 text-sm">
                            <span class="px-2 py-0.5 rounded-full bg-blue-100 text-blue-800 text-xs font-semibold">
                                {{ selectedStaff?.roles?.[0]?.name || 'Staff' }}
                            </span>
                        </span>
                    </div>
                </div>
                <div class="mt-8 flex justify-end">
                    <SecondaryButton @click="showingStaffModal = false">Close</SecondaryButton>
                </div>
            </div>
        </Modal>

        <!-- Edit Staff Modal -->
        <Modal :show="showingEditModal" @close="showingEditModal = false" maxWidth="md">
            <div class="p-6">
                <div class="flex justify-between items-start mb-6">
                    <h3 class="text-lg font-bold text-gray-900">Edit Staff Member</h3>
                    <button @click="showingEditModal = false" class="text-gray-400 hover:text-gray-500">
                        <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>
                
                <form @submit.prevent="updateStaff">
                    <div>
                        <InputLabel for="edit_name" value="Name" />
                        <TextInput
                            id="edit_name"
                            type="text"
                            class="mt-1 block w-full"
                            v-model="editForm.name"
                            required
                            autofocus
                        />
                        <InputError class="mt-2" :message="editForm.errors.name" />
                    </div>

                    <div class="mt-4">
                        <InputLabel for="edit_email" value="Email" />
                        <TextInput
                            id="edit_email"
                            type="email"
                            class="mt-1 block w-full"
                            v-model="editForm.email"
                            required
                        />
                        <InputError class="mt-2" :message="editForm.errors.email" />
                    </div>

                    <div class="mt-4">
                        <InputLabel for="edit_role" value="Role" />
                        <select 
                            id="edit_role"
                            v-model="editForm.role"
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                            required
                        >
                            <option value="Dentist">Dentist</option>
                            <option value="Assistant">Assistant</option>
                        </select>
                        <InputError class="mt-2" :message="editForm.errors.role" />
                    </div>

                    <div class="mt-8 flex justify-end space-x-3">
                        <SecondaryButton @click="showingEditModal = false">Cancel</SecondaryButton>
                        <PrimaryButton :class="{ 'opacity-25': editForm.processing }" :disabled="editForm.processing">
                            Update Staff
                        </PrimaryButton>
                    </div>
                </form>
            </div>
        </Modal>

    </AuthenticatedLayout>
</template>

<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, router, usePage, useForm } from '@inertiajs/vue3';
import { ref, computed } from 'vue';
import Modal from '@/Components/Modal.vue';
import TextInput from '@/Components/TextInput.vue';
import InputLabel from '@/Components/InputLabel.vue';
import InputError from '@/Components/InputError.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import Swal from 'sweetalert2';

const props = defineProps({
    staff: Array,
    allPermissions: Array,
    initialTab: { type: String, default: 'list' }
});

const page = usePage();

// Tabs State
const activeTab = ref(props.initialTab);
const goToSchedules = () => {
    router.get(route('staff.schedules'));
};

// View Modal State
const showingStaffModal = ref(false);
const selectedStaff = ref(null);

const showStaff = (member) => {
    selectedStaff.value = member;
    showingStaffModal.value = true;
};

// Edit Modal State
const showingEditModal = ref(false);
const editForm = useForm({
    id: null,
    name: '',
    email: '',
    role: '',
});

const editStaff = (member) => {
    editForm.id = member.id;
    editForm.name = member.name;
    editForm.email = member.email;
    editForm.role = member.roles?.[0]?.name || 'Staff';
    showingEditModal.value = true;
};

const selectedStaffPermissions = ref(null);
const permissionForm = useForm({
    permissions: []
});

const selectStaffForPermissions = (member) => {
    selectedStaffPermissions.value = member;
    permissionForm.permissions = member.permissions.map(p => p.name);
};

const savePermissionsTab = () => {
    permissionForm.put(route('staff.update-permissions', selectedStaffPermissions.value.id), {
        onSuccess: () => {
            Swal.fire({
                icon: 'success',
                title: 'Permissions Updated',
                text: `Permissions for ${selectedStaffPermissions.value.name} have been updated.`,
                timer: 2000,
                showConfirmButton: false
            });
        },
        preserveScroll: true
    });
};

// Group permissions for cleaner UI
const permissionGroups = computed(() => {
    const groups = {};
    props.allPermissions.forEach(p => {
        let feature = 'Other';
        let displayName = p.name;

        if (p.name.includes('services')) feature = 'Service & Pricing';
        else if (p.name.includes('patients')) feature = 'Patients';
        else if (p.name.includes('appointments')) feature = 'Appointments';
        else if (p.name.includes('treatments')) feature = 'Treatments';
        else if (p.name.includes('billing')) feature = 'Billing & POS';
        else if (p.name.includes('staff')) feature = 'Staff Management';
        else if (p.name.includes('reports')) feature = 'Reports';
        else if (p.name.includes('settings')) feature = 'Clinic Settings';
        else if (p.name.includes('subscription')) feature = 'Subscription';
        else if (p.name.includes('clinic')) feature = 'Clinic Profile';

        if (!groups[feature]) groups[feature] = [];
        
        // Make display name prettier
        displayName = p.name.split(' ').map(word => word.charAt(0).toUpperCase() + word.slice(1)).join(' ');
        
        groups[feature].push({ ...p, displayName });
    });
    return groups;
});

const updateStaff = () => {
    editForm.put(route('staff.update', editForm.id), {
        onSuccess: () => {
            showingEditModal.value = false;
            Swal.fire({
                icon: 'success',
                title: 'Updated!',
                text: 'Staff member updated successfully.',
                timer: 2000,
                showConfirmButton: false
            });
        },
        onError: () => {
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: 'Something went wrong. Please check the form.',
            });
        },
        preserveScroll: true
    });
};

const deleteStaff = (id) => {
    Swal.fire({
        title: 'Are you sure?',
        text: "You won't be able to revert this! This will permanently delete the staff member from the database.",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#2563eb',
        cancelButtonColor: '#d33',
        confirmButtonText: 'Yes, remove them!',
        cancelButtonText: 'Cancel'
    }).then((result) => {
        if (result.isConfirmed) {
            router.delete(route('staff.destroy', id), {
                onSuccess: () => {
                    Swal.fire({
                        icon: 'success',
                        title: 'Removed!',
                        text: 'Staff member has been permanently removed.',
                        timer: 2000,
                        showConfirmButton: false
                    });
                },
                onError: () => {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: 'Failed to remove staff member.'
                    });
                }
            });
        }
    });
};

// No more local can helper, using global from app.js
</script>

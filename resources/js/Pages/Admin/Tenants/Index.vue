<script setup>
import { Head, router } from '@inertiajs/vue3';
import { ref, watch, computed } from 'vue';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import debounce from 'lodash/debounce';
import TenantsTable from './Partials/TenantsTable.vue';
import CreateTenantModal from './Partials/CreateTenantModal.vue';
import ManageTenantModal from './Partials/ManageTenantModal.vue';
import Swal from 'sweetalert2';

const props = defineProps({
    tenants: Object,
    filters: Object,
    plans: Array,
});

const search = ref(props.filters.search || '');
const statusFilter = ref(props.filters.status || '');

// Modal state
const showCreateModal = ref(false);
const showManageModal = ref(false);
const showReviewModal = ref(false);
const selectedTenant = ref(null);
const newTenantName = ref('');
const newTenantDomain = ref('');
const databasePreview = ref(null);
const isLoadingPreview = ref(false);

// Rejection form
const rejectForm = ref({
    rejection_message: '',
});

// Open manage modal
const openManageModal = (tenant) => {
    selectedTenant.value = tenant;
    showManageModal.value = true;
};

// Open review modal
const openReviewModal = (tenant) => {
    selectedTenant.value = tenant;
    showReviewModal.value = true;
    rejectForm.value.rejection_message = '';
};

// Close review modal
const closeReviewModal = () => {
    showReviewModal.value = false;
    selectedTenant.value = null;
    rejectForm.value.rejection_message = '';
};

// Close manage modal
const closeManageModal = () => {
    showManageModal.value = false;
    selectedTenant.value = null;
};

// Database preview function
const previewDatabaseName = debounce(async () => {
    if (!newTenantDomain.value || newTenantDomain.value.length < 2) {
        databasePreview.value = null;
        return;
    }

    isLoadingPreview.value = true;
    try {
        const response = await fetch(route('api.tenants.preview-database-name'), {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
            },
            body: JSON.stringify({
                domain: newTenantDomain.value,
            }),
        });
        const data = await response.json();
        if (data.success) {
            databasePreview.value = data.data;
        }
    } catch (error) {
        console.error('Failed to preview database name:', error);
    } finally {
        isLoadingPreview.value = false;
    }
}, 500);

const updateSearch = debounce(() => {
    router.get(
        route('admin.tenants.index'),
        { search: search.value, status: statusFilter.value },
        { preserveState: true, replace: true }
    );
}, 300);

watch(statusFilter, () => {
    updateSearch();
});

watch([newTenantDomain], () => {
    previewDatabaseName();
});

const openCreateModal = () => {
    showCreateModal.value = true;
    newTenantName.value = '';
    newTenantDomain.value = '';
    databasePreview.value = null;
};

const closeCreateModal = () => {
    showCreateModal.value = false;
    newTenantName.value = '';
    newTenantDomain.value = '';
    databasePreview.value = null;
};

// Approve pending tenant
const approveTenant = () => {
    Swal.fire({
        title: 'Approve Tenant',
        text: `Are you sure you want to approve "${selectedTenant.value.name}"? The user will be notified and can access their clinic.`,
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#0d9488',
        cancelButtonColor: '#6b7280',
        confirmButtonText: 'Yes, Approve',
        cancelButtonText: 'Cancel',
    }).then((result) => {
        if (result.isConfirmed) {
            axios.post(`/admin/tenants/${selectedTenant.value.id}/approve`)
                .then((response) => {
                    Swal.fire({
                        icon: 'success',
                        title: 'Tenant Approved',
                        text: 'The user has been notified.',
                        confirmButtonColor: '#0d9488',
                    }).then(() => {
                        closeReviewModal();
                        window.location.reload();
                    });
                })
                .catch((error) => {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: error.response?.data?.message || 'Failed to approve tenant.',
                        confirmButtonColor: '#0d9488',
                    });
                });
        }
    });
};

// Reject pending tenant
const submitReject = () => {
    Swal.fire({
        title: 'Reject Tenant',
        text: `Are you sure you want to reject "${selectedTenant.value.name}"? The user will be notified.`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#dc2626',
        cancelButtonColor: '#6b7280',
        confirmButtonText: 'Yes, Reject',
        cancelButtonText: 'Cancel',
    }).then((result) => {
        if (result.isConfirmed) {
            axios.post(`/admin/tenants/${selectedTenant.value.id}/reject`, {
                rejection_message: rejectForm.value.rejection_message,
            })
                .then((response) => {
                    Swal.fire({
                        icon: 'success',
                        title: 'Tenant Rejected',
                        text: 'The user has been notified.',
                        confirmButtonColor: '#0d9488',
                    }).then(() => {
                        closeReviewModal();
                        window.location.reload();
                    });
                })
                .catch((error) => {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: error.response?.data?.message || 'Failed to reject tenant.',
                        confirmButtonColor: '#0d9488',
                    });
                });
        }
    });
};

const createTenant = async () => {
    if (!databasePreview.value) return;

    try {
        const response = await fetch(route('api.tenants.store'), {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
            },
            body: JSON.stringify({
                name: newTenantName.value,
                domain: newTenantDomain.value,
            }),
        });
        const data = await response.json();
        if (data.success) {
            closeCreateModal();
            router.reload({ only: ['tenants'] });
        } else {
            alert(data.message || 'Failed to create tenant');
        }
    } catch (error) {
        console.error('Failed to create tenant:', error);
        alert('Failed to create tenant');
    }
};

const updateTenant = (formData) => {
    router.put(route('admin.tenants.update', selectedTenant.value.id), formData, {
        onSuccess: () => {
            closeManageModal();
        },
    });
};

const deleteTenant = (tenant) => {
    router.delete(route('admin.tenants.destroy', tenant.id), {
        onSuccess: () => {
            closeManageModal();
        },
    });
};

const isFormValid = computed(() => {
    return newTenantName.value.length >= 2 && newTenantDomain.value.length >= 2 && databasePreview.value && !databasePreview.value.already_exists;
});
</script>

<template>
    <Head title="Tenants Management" />
    <AdminLayout>
        <template #header>
            <h1 class="text-xl font-bold text-gray-900">Clinics Management</h1>
        </template>

        <div class="max-w-7xl mx-auto space-y-6">
            <!-- Header and Filters -->
            <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 bg-white p-5 rounded-xl shadow-sm border border-gray-100">
                <div class="flex-1 w-full sm:w-auto flex flex-col sm:flex-row gap-4">
                    <!-- Search -->
                    <div class="relative w-full sm:w-72">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                            <svg class="h-5 w-5 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                            </svg>
                        </div>
                        <input
                            v-model="search"
                            @input="updateSearch"
                            type="text"
                            placeholder="Search by domain..."
                            class="block w-full pl-10 pr-3 py-2 border border-gray-300 rounded-lg leading-5 bg-white placeholder-gray-500 focus:outline-none focus:ring-teal-500 focus:border-teal-500 sm:text-sm transition duration-150 ease-in-out"
                        />
                    </div>

                    <!-- Status Filter -->
                    <select
                        v-model="statusFilter"
                        class="block w-full sm:w-48 pl-3 pr-10 py-2 text-base border-gray-300 focus:outline-none focus:ring-teal-500 focus:border-teal-500 sm:text-sm rounded-lg"
                    >
                        <option value="">All Statuses</option>
                        <option value="active">Active</option>
                        <option value="pending_payment">Pending Payment</option>
                        <option value="suspended">Suspended</option>
                    </select>

                </div>

                <!-- Add Clinic Button -->
                <button
                    @click="openCreateModal"
                    class="inline-flex items-center justify-center px-4 py-2 bg-teal-600 border border-transparent rounded-lg font-semibold text-xs text-white uppercase tracking-wider hover:bg-teal-700 focus:bg-teal-700 active:bg-teal-800 focus:outline-none focus:ring-2 focus:ring-teal-500 focus:ring-offset-2 transition ease-in-out duration-150 whitespace-nowrap"
                >
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                    </svg>
                    Add Clinic
                </button>
            </div>

            <!-- Tenants Table -->
            <TenantsTable 
                :tenants="tenants" 
                @manage="openManageModal"
                @review="openReviewModal"
            />
        </div>

        <!-- Create Tenant Modal -->
        <CreateTenantModal
            :show="showCreateModal"
            :new-tenant-name="newTenantName"
            :new-tenant-domain="newTenantDomain"
            :database-preview="databasePreview"
            :is-loading-preview="isLoadingPreview"
            :is-form-valid="isFormValid"
            @update:newTenantName="newTenantName = $event"
            @update:newTenantDomain="newTenantDomain = $event"
            @close="closeCreateModal"
            @create="createTenant"
        />

        <!-- Manage Tenant Modal -->
        <ManageTenantModal
            :show="showManageModal"
            :tenant="selectedTenant"
            :plans="plans"
            @close="closeManageModal"
            @update="updateTenant"
            @delete="deleteTenant"
        />

        <!-- Review Pending Tenant Modal -->
        <div v-if="showReviewModal" class="fixed inset-0 z-50 overflow-y-auto">
            <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:block sm:p-0">
                <div class="fixed inset-0 transition-opacity bg-gray-500 bg-opacity-75" @click="closeReviewModal" />
                
                <div class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-2xl w-full">
                    <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                        <div class="sm:flex sm:items-start">
                            <div class="mt-3 text-center sm:mt-0 sm:ml-4 sm:text-left w-full">
                                <h3 class="text-lg leading-6 font-medium text-gray-900" id="modal-title">
                                    Review Pending Tenant
                                </h3>
                                
                                <div class="mt-4 space-y-6">
                                    <!-- Clinic Information -->
                                    <div class="bg-gray-50 rounded-lg p-4">
                                        <h4 class="text-sm font-semibold text-gray-900 mb-3">Clinic Information</h4>
                                        <div class="grid grid-cols-2 gap-4 text-sm">
                                            <div>
                                                <span class="text-gray-500">Clinic Name:</span>
                                                <span class="ml-2 font-medium">{{ selectedTenant?.name }}</span>
                                            </div>
                                            <div>
                                                <span class="text-gray-500">Owner Name:</span>
                                                <span class="ml-2 font-medium">{{ selectedTenant?.owner_name }}</span>
                                            </div>
                                            <div>
                                                <span class="text-gray-500">Status:</span>
                                                <span class="ml-2 px-2 py-0.5 text-xs font-semibold rounded-full bg-yellow-100 text-yellow-800">
                                                    Pending Review
                                                </span>
                                            </div>
                                            <div>
                                                <span class="text-gray-500">Database:</span>
                                                <span class="ml-2 font-mono text-xs">{{ selectedTenant?.database_name }}</span>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <!-- Subscription Details -->
                                    <div class="bg-gray-50 rounded-lg p-4">
                                        <h4 class="text-sm font-semibold text-gray-900 mb-3">Subscription Details</h4>
                                        <div class="grid grid-cols-2 gap-4 text-sm">
                                            <div>
                                                <span class="text-gray-500">Plan:</span>
                                                <span class="ml-2 font-medium">{{ selectedTenant?.plan || 'N/A' }}</span>
                                            </div>
                                            <div>
                                                <span class="text-gray-500">Billing Cycle:</span>
                                                <span class="ml-2 font-medium capitalize">{{ selectedTenant?.billing_cycle || 'N/A' }}</span>
                                            </div>
                                            <div>
                                                <span class="text-gray-500">Amount Paid:</span>
                                                <span class="ml-2 font-medium text-green-600">₱{{ Number(selectedTenant?.amount_paid || 0).toLocaleString() }}</span>
                                            </div>
                                            <div>
                                                <span class="text-gray-500">Payment Status:</span>
                                                <span class="ml-2 px-2 py-0.5 text-xs font-semibold rounded-full bg-green-100 text-green-800">
                                                    {{ selectedTenant?.payment_status || 'Paid' }}
                                                </span>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <!-- Rejection Reason -->
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-1">
                                            Rejection Reason (optional)
                                        </label>
                                        <textarea
                                            v-model="rejectForm.rejection_message"
                                            rows="3"
                                            class="w-full border-gray-300 rounded-md shadow-sm focus:ring-teal-500 focus:border-teal-500"
                                            placeholder="Enter reason for rejection..."
                                        ></textarea>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="bg-gray-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                        <button
                            @click="approveTenant"
                            class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-green-600 text-base font-medium text-white hover:bg-green-700 focus:outline-none sm:ml-3 sm:w-auto sm:text-sm"
                        >
                            Approve
                        </button>
                        <button
                            @click="submitReject"
                            class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-red-600 text-base font-medium text-white hover:bg-red-700 focus:outline-none sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm"
                        >
                            Reject
                        </button>
                        <button
                            @click="closeReviewModal"
                            class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 focus:outline-none sm:mt-0 sm:ml-auto sm:w-auto sm:text-sm"
                        >
                            Cancel
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>

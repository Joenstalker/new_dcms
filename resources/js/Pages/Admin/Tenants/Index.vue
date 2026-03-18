<script setup>
import { Head, router } from '@inertiajs/vue3';
import { ref, watch, computed } from 'vue';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import debounce from 'lodash/debounce';
import TenantsTable from './Partials/TenantsTable.vue';
import CreateTenantModal from './Partials/CreateTenantModal.vue';
import ManageTenantModal from './Partials/ManageTenantModal.vue';

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
const selectedTenant = ref(null);
const newTenantName = ref('');
const newTenantDomain = ref('');
const databasePreview = ref(null);
const isLoadingPreview = ref(false);

// Open manage modal
const openManageModal = (tenant) => {
    selectedTenant.value = tenant;
    showManageModal.value = true;
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
            <div class="flex justify-between items-center">
                <h1 class="text-xl font-bold text-gray-900">Clinics Management</h1>
                <button
                    @click="openCreateModal"
                    class="inline-flex items-center px-4 py-2 bg-teal-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-wider hover:bg-teal-700 focus:bg-teal-700 active:bg-teal-800 focus:outline-none focus:ring-2 focus:ring-teal-500 focus:ring-offset-2 transition ease-in-out duration-150"
                >
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                    </svg>
                    Add Clinic
                </button>
            </div>
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
            </div>

            <!-- Tenants Table -->
            <TenantsTable 
                :tenants="tenants" 
                @manage="openManageModal"
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
    </AdminLayout>
</template>

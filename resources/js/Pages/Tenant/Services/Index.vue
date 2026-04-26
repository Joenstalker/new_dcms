<script setup>
import { brandingState } from '@/States/brandingState';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, useForm, usePage } from '@inertiajs/vue3';
import { ref, computed, onMounted, onUnmounted, watch } from 'vue';
import Swal from 'sweetalert2';

import ServicesTable from './Partials/ServicesTable.vue';
import ServiceFormModal from './Partials/ServiceFormModal.vue';
import ServiceDelete from './Partials/ServiceDelete.vue';

const props = defineProps({
    services: Array,
    defaultServiceCatalog: {
        type: Array,
        default: () => [],
    },
});

const page = usePage();
const primaryColor = computed(() => brandingState.primary_color || '#10b981');
const tenantId = computed(() => page.props.tenant?.id || null);
const user = computed(() => page.props.auth.user);
const roles = computed(() => user.value?.roles || []);
const isOwner = computed(() => roles.value.includes('Owner'));
const isDentist = computed(() => roles.value.includes('Dentist'));
const isAssistant = computed(() => roles.value.includes('Assistant'));
const isStaff = computed(() => isDentist.value || isAssistant.value);
const isOwnerOrDentist = computed(() => isOwner.value || isDentist.value);
const canManageServices = computed(() => isOwner.value || isDentist.value || isAssistant.value);

const searchQuery = ref('');
const liveServices = ref([...(props.services || [])]);
const selectedIds = ref([]);
let servicesChannel = null;

const filteredServices = computed(() => {
    let result = liveServices.value;
    
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

const selectAll = computed({
    get: () => filteredServices.value.length > 0 && selectedIds.value.length === filteredServices.value.length,
    set: (value) => {
        if (value) {
            selectedIds.value = filteredServices.value.map(s => s.id);
        } else {
            selectedIds.value = [];
        }
    }
});

const toggleSelection = (id) => {
    const index = selectedIds.value.indexOf(id);
    if (index === -1) {
        selectedIds.value.push(id);
    } else {
        selectedIds.value.splice(index, 1);
    }
};

watch(() => props.services, (nextServices) => {
    liveServices.value = [...(nextServices || [])];
}, { deep: true });

onMounted(() => {
    if (!window.Echo || !tenantId.value) return;

    servicesChannel = window.Echo.private(`tenant.${tenantId.value}.services`)
        .listen('.TenantServiceChanged', (event) => {
            const incoming = event?.service;
            const action = event?.action;

            if (!incoming || !incoming.id) return;

            if (action === 'deleted') {
                liveServices.value = liveServices.value.filter((item) => item.id !== incoming.id);
                selectedIds.value = selectedIds.value.filter(id => id !== incoming.id);
                return;
            }

            const existingIndex = liveServices.value.findIndex((item) => item.id === incoming.id);

            if (existingIndex >= 0) {
                liveServices.value[existingIndex] = {
                    ...liveServices.value[existingIndex],
                    ...incoming,
                };
                return;
            }

            liveServices.value = [incoming, ...liveServices.value];
        });
});

onUnmounted(() => {
    if (window.Echo && tenantId.value) {
        window.Echo.leave(`tenant.${tenantId.value}.services`);
    }

    servicesChannel = null;
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
                Swal.fire('Success!', 'Service added successfully!', 'success');
            }
        });
    }
};

// Delete State
const isDeleteModalOpen = ref(false);
const serviceToDelete = ref(null);

const openDeleteModal = (service) => {
    serviceToDelete.value = service;
    selectedIds.value = [];
    isDeleteModalOpen.value = true;
};

const openBulkDeleteModal = () => {
    serviceToDelete.value = null;
    isDeleteModalOpen.value = true;
};

const handleDeleted = () => {
    selectedIds.value = [];
    isDeleteModalOpen.value = false;
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
            <h2 class="text-xl font-black tracking-tight text-base-content normal-case">
                Service & Pricing
            </h2>
        </template>

        <div class="mt-8 space-y-6">
            <!-- Header Banner -->
            <div class="flex flex-col md:flex-row justify-between items-start md:items-center bg-base-100 p-6 rounded-2xl border border-base-300 shadow-sm gap-4">
                <div>
                    <h3 class="text-sm font-black text-base-content/40 uppercase tracking-[0.2em]">Management Module</h3>
                    <h2 class="text-xl font-black text-base-content uppercase tracking-tight">Service & Pricing</h2>
                    <p class="text-xs text-base-content/50 mt-1">Manage services and pricing offered by your clinic.</p>
                </div>
                <div class="flex flex-col sm:flex-row items-center gap-3 w-full md:w-auto">
                    <!-- Search with Autocomplete -->
                    <div class="relative w-full sm:w-64">
                        <span class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                            <svg class="h-4 w-4 text-base-content/30" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                            </svg>
                        </span>
                        <input 
                            v-model="searchQuery"
                            type="text" 
                            placeholder="Search services..." 
                            class="input input-sm input-bordered w-full pl-10 rounded-xl focus:ring-2 focus:ring-primary shadow-sm text-xs font-bold bg-white"
                        />
                    </div>

                    <div class="flex items-center gap-3 w-full sm:w-auto">
                        <button
                            v-if="selectedIds.length > 0"
                            @click="openBulkDeleteModal"
                            class="btn btn-sm btn-error btn-outline rounded-xl hover:scale-[1.02] transition-all font-black text-[10px] uppercase tracking-widest px-6 w-full sm:w-auto"
                        >
                            Delete Selected ({{ selectedIds.length }})
                        </button>
                        
                        <button 
                            v-if="canManageServices" 
                            @click="openModal()" 
                            class="btn btn-sm rounded-xl border-0 text-white shadow-lg hover:scale-[1.02] transition-all font-black text-[10px] uppercase tracking-widest px-6 w-full sm:w-auto"
                            :style="{ backgroundColor: primaryColor, boxShadow: `0 10px 15px -3px ${primaryColor}33` }"
                        >
                            + New Service
                        </button>
                    </div>
                </div>
            </div>

            <div class="bg-base-100 rounded-2xl border border-base-300 overflow-hidden shadow-sm">
                <ServicesTable 
                    :services="filteredServices"
                    :can-manage="canManageServices"
                    :selected-ids="selectedIds"
                    v-model:selectAll="selectAll"
                    @edit="openModal"
                    @delete="openDeleteModal"
                    @toggle-selection="toggleSelection"
                />
            </div>
        </div>

        <!-- Create/Edit Modal -->
        <ServiceFormModal
            :show="isModalOpen"
            :editing-service="editingService"
            :is-owner-or-dentist="isOwnerOrDentist"
            :default-service-catalog="defaultServiceCatalog"
            @close="closeModal"
            @submit="submit"
        />

        <!-- Delete Modal -->
        <ServiceDelete
            :show="isDeleteModalOpen"
            :service="serviceToDelete"
            :selected-ids="selectedIds"
            @close="isDeleteModalOpen = false"
            @deleted="handleDeleted"
        />
    </AuthenticatedLayout>
</template>

<style scoped>
.tab-active {
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
}
</style>

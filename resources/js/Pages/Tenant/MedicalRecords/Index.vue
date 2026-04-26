<script setup>
import { brandingState } from '@/States/brandingState';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, usePage } from '@inertiajs/vue3';
import { computed, onMounted, onUnmounted, ref, watch } from 'vue';
import MedicalCreate from './MedicalCreate.vue';
import MedicalEdit from './MedicalEdit.vue';
import MedicalDelete from './MedicalDelete.vue';

const props = defineProps({
    medicalRecords: {
        type: Array,
        default: () => [],
    },
    defaultMedicalChecklist: {
        type: Array,
        default: () => [],
    },
});

const page = usePage();
const primaryColor = computed(() => brandingState.primary_color);
const permissions = computed(() => page.props.auth.user?.permissions || []);
const tenantId = computed(() => page.props.tenant?.id || null);
const liveMedicalRecords = ref([...(props.medicalRecords || [])]);
let medicalRecordsChannel = null;

watch(() => props.medicalRecords, (nextRecords) => {
    liveMedicalRecords.value = [...(nextRecords || [])];
}, { deep: true });

onMounted(() => {
    if (!window.Echo || !tenantId.value) {
        return;
    }

    medicalRecordsChannel = window.Echo.private(`tenant.${tenantId.value}.medical-records`)
        .listen('.TenantMedicalRecordChanged', (event) => {
            const incoming = event?.medicalRecord;
            const action = event?.action;

            if (!incoming || !incoming.id) {
                return;
            }

            if (action === 'deleted') {
                liveMedicalRecords.value = liveMedicalRecords.value.filter((item) => item.id !== incoming.id);

                if (activeRecord.value?.id === incoming.id) {
                    activeRecord.value = null;
                    showEditModal.value = false;
                    showDeleteModal.value = false;
                }

                return;
            }

            const existingIndex = liveMedicalRecords.value.findIndex((item) => item.id === incoming.id);
            if (existingIndex >= 0) {
                liveMedicalRecords.value[existingIndex] = {
                    ...liveMedicalRecords.value[existingIndex],
                    ...incoming,
                };

                if (activeRecord.value?.id === incoming.id) {
                    activeRecord.value = {
                        ...activeRecord.value,
                        ...incoming,
                    };
                }
            } else {
                liveMedicalRecords.value = [...liveMedicalRecords.value, incoming];
            }

            liveMedicalRecords.value = [...liveMedicalRecords.value].sort((a, b) => {
                const an = String(a?.name || '').toLowerCase();
                const bn = String(b?.name || '').toLowerCase();
                return an.localeCompare(bn);
            });
        });
});

onUnmounted(() => {
    if (window.Echo && tenantId.value) {
        window.Echo.leave(`tenant.${tenantId.value}.medical-records`);
    }

    medicalRecordsChannel = null;
});

const canCreate = computed(() => permissions.value.includes('create medical records'));
const canEdit = computed(() => permissions.value.includes('edit medical records'));
const canDelete = computed(() => permissions.value.includes('delete medical records'));

const showCreateModal = ref(false);
const showEditModal = ref(false);
const showDeleteModal = ref(false);
const activeRecord = ref(null);
const selectedIds = ref([]);

const selectAll = computed({
    get: () => liveMedicalRecords.value.length > 0 && selectedIds.value.length === liveMedicalRecords.value.length,
    set: (value) => {
        if (value) {
            selectedIds.value = liveMedicalRecords.value.map(record => record.id);
        } else {
            selectedIds.value = [];
        }
    }
});

const toggleSelection = (id) => {
    const index = selectedIds.value.indexOf(id);
    if (index > -1) {
        selectedIds.value.splice(index, 1);
    } else {
        selectedIds.value.push(id);
    }
};

const openEditModal = (record) => {
    activeRecord.value = record;
    showEditModal.value = true;
};

const openDeleteModal = (record) => {
    activeRecord.value = record;
    selectedIds.value = []; // Clear bulk selection if deleting single
    showDeleteModal.value = true;
};

const openBulkDeleteModal = () => {
    activeRecord.value = null;
    showDeleteModal.value = true;
};

const handleBulkDeleted = () => {
    selectedIds.value = [];
};
</script>

<template>
    <Head title="Medical Records" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="text-xl font-black tracking-tight text-base-content normal-case">
                Medical Records
            </h2>
        </template>

        <div class="mt-8 space-y-6">
            <div class="flex justify-between items-center bg-base-100 p-6 rounded-2xl border border-base-300 shadow-sm">
                <div>
                    <h3 class="text-sm font-black text-base-content/40 uppercase tracking-[0.2em]">Clinical Module</h3>
                    <h2 class="text-xl font-black text-base-content uppercase tracking-tight">Medical Records</h2>
                    <p class="text-xs text-base-content/50 mt-1">Manage default medical conditions used during patient intake and booking.</p>
                </div>
                <div class="flex items-center gap-3">
                    <button
                        v-if="canDelete && selectedIds.length > 0"
                        @click="openBulkDeleteModal"
                        class="btn btn-error btn-outline rounded-xl hover:scale-[1.02] transition-all font-black text-xs uppercase tracking-widest px-6"
                    >
                        Delete Selected ({{ selectedIds.length }})
                    </button>
                    <button
                        v-if="canCreate"
                        @click="showCreateModal = true"
                        class="btn rounded-xl border-0 text-white shadow-lg shadow-primary/20 hover:scale-[1.02] hover:shadow-xl transition-all font-black text-xs uppercase tracking-widest px-6"
                        :style="{ backgroundColor: primaryColor }"
                    >
                        + Add Medical Item
                    </button>
                </div>
            </div>

            <div class="bg-base-100 rounded-2xl border border-base-300 overflow-hidden shadow-sm">
                <table class="table w-full">
                    <thead>
                        <tr class="bg-base-200/50">
                            <th class="px-6 py-4 text-center w-10">
                                <div
                                    class="w-5 h-5 rounded border border-base-300 flex items-center justify-center cursor-pointer transition-all"
                                    :class="selectAll ? 'bg-primary border-primary' : 'bg-base-100'"
                                    @click="selectAll = !selectAll"
                                >
                                    <svg v-if="selectAll" xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5 text-white" viewBox="0 0 20 20" fill="currentColor">
                                        <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd" />
                                    </svg>
                                </div>
                            </th>
                            <th class="text-[10px] font-black uppercase tracking-widest text-base-content/40 px-6 py-4 text-center">ID</th>
                            <th class="text-[10px] font-black uppercase tracking-widest text-base-content/40 px-6 py-4">Medical Item</th>
                            <th class="text-[10px] font-black uppercase tracking-widest text-base-content/40 px-6 py-4">Description</th>
                            <th class="text-[10px] font-black uppercase tracking-widest text-base-content/40 px-6 py-4 text-center">Status</th>
                            <th class="text-[10px] font-black uppercase tracking-widest text-base-content/40 px-6 py-4 text-center">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-base-200">
                        <tr v-for="record in liveMedicalRecords" :key="record.id" class="hover:bg-base-200/30 transition-colors" :class="{ 'bg-primary/5': selectedIds.includes(record.id) }">
                            <td class="px-6 py-4 text-center">
                                <div
                                    class="w-5 h-5 rounded border border-base-300 flex items-center justify-center cursor-pointer transition-all mx-auto"
                                    :class="selectedIds.includes(record.id) ? 'bg-primary border-primary' : 'bg-base-100'"
                                    @click="toggleSelection(record.id)"
                                >
                                    <svg v-if="selectedIds.includes(record.id)" xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5 text-white" viewBox="0 0 20 20" fill="currentColor">
                                        <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd" />
                                    </svg>
                                </div>
                            </td>
                            <td class="px-6 py-4 text-center text-[10px] font-black text-base-content/30 tracking-widest">MR-{{ record.id }}</td>
                            <td class="px-6 py-4">
                                <p class="text-sm font-black text-base-content">{{ record.name }}</p>
                            </td>
                            <td class="px-6 py-4">
                                <p class="text-xs text-base-content/70 max-w-md">{{ record.description || 'No description provided.' }}</p>
                            </td>
                            <td class="px-6 py-4 text-center">
                                <span
                                    class="px-3 py-1 rounded-full text-[10px] font-black uppercase tracking-wider"
                                    :class="record.is_active ? 'bg-green-100 text-green-700' : 'bg-gray-200 text-gray-600'"
                                >
                                    {{ record.is_active ? 'Active' : 'Inactive' }}
                                </span>
                            </td>
                            <td class="px-6 py-4 text-center">
                                <div class="flex items-center justify-center gap-2">
                                    <button
                                        v-if="canEdit"
                                        @click="openEditModal(record)"
                                        class="btn btn-sm btn-square btn-ghost hover:bg-info/10 hover:text-info transition-colors"
                                        title="Edit"
                                    >
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                        </svg>
                                    </button>
                                </div>
                            </td>
                        </tr>
                        <tr v-if="liveMedicalRecords.length === 0">
                            <td colspan="6" class="px-6 py-16 text-center">
                                <h3 class="text-sm font-black text-base-content/40 uppercase tracking-widest">No Medical Records Found</h3>
                                <p class="text-xs text-base-content/30 mt-1">Create checklist items to use in patient booking and intake.</p>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <MedicalCreate
            :show="showCreateModal"
            :default-medical-checklist="defaultMedicalChecklist"
            @close="showCreateModal = false"
        />

        <MedicalEdit
            :show="showEditModal"
            :medical-record="activeRecord"
            @close="showEditModal = false"
        />

        <MedicalDelete
            :show="showDeleteModal"
            :medical-record="activeRecord"
            :selected-ids="selectedIds"
            @close="showDeleteModal = false"
            @deleted="handleBulkDeleted"
        />
    </AuthenticatedLayout>
</template>

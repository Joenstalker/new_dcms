<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, usePage } from '@inertiajs/vue3';
import { ref, computed } from 'vue';
import CreateTreatment from './CreateTreatment.vue';
import EditTreatment from './EditTreatment.vue';
import ShowTreatment from './ShowTreatment.vue';
import DeleteTreatment from './DeleteTreatment.vue';

const props = defineProps({
    treatments: Array,
    patients: Array,
});

const page = usePage();
const branding = computed(() => page.props.branding || {});
const primaryColor = computed(() => page.props.tenant?.branding_color || branding.value.primary_color || '#0ea5e9');

const permissions = computed(() => page.props.auth.user?.permissions || []);
const canCreate = computed(() => permissions.value.includes('create treatments'));
const canEdit = computed(() => permissions.value.includes('edit treatments'));
const canDelete = computed(() => permissions.value.includes('delete treatments'));

// Modal states
const showCreateModal = ref(false);
const showEditModal = ref(false);
const showViewModal = ref(false);
const showDeleteModal = ref(false);
const activeTreatment = ref(null);

const openViewModal = (treatment) => {
    activeTreatment.value = treatment;
    showViewModal.value = true;
};

const openEditModal = (treatment) => {
    activeTreatment.value = treatment;
    showEditModal.value = true;
};

const openDeleteModal = (treatment) => {
    activeTreatment.value = treatment;
    showDeleteModal.value = true;
};
</script>

<template>
    <Head title="Service & Pricing" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="text-xl font-black tracking-tight text-base-content uppercase">
                Service & Pricing Management
            </h2>
        </template>

        <div class="mt-8 space-y-6">
            <!-- Top Actions -->
            <div class="flex justify-between items-center bg-base-100 p-6 rounded-2xl border border-base-300 shadow-sm">
                <div>
                    <h3 class="text-sm font-black text-base-content/40 uppercase tracking-[0.2em]">Clinical Module</h3>
                    <h2 class="text-xl font-black text-base-content uppercase tracking-tight">Treatment Records</h2>
                </div>
                <button 
                    v-if="canCreate"
                    @click="showCreateModal = true"
                    class="btn rounded-xl border-0 text-white shadow-lg shadow-primary/20 hover:scale-[1.02] hover:shadow-xl transition-all font-black text-xs uppercase tracking-widest px-6"
                    :style="{ backgroundColor: primaryColor }"
                >
                    + Record Treatment
                </button>
            </div>

            <!-- Table -->
            <div class="bg-base-100 rounded-2xl border border-base-300 overflow-hidden shadow-sm">
                <table class="table w-full">
                    <thead>
                        <tr class="bg-base-200/50">
                            <th class="text-[10px] font-black uppercase tracking-widest text-base-content/40 px-6 py-4 text-center">ID</th>
                            <th class="text-[10px] font-black uppercase tracking-widest text-base-content/40 px-6 py-4">Patient</th>
                            <th class="text-[10px] font-black uppercase tracking-widest text-base-content/40 px-6 py-4">Treatment Details</th>
                            <th class="text-[10px] font-black uppercase tracking-widest text-base-content/40 px-6 py-4 text-center">Date</th>
                            <th class="text-[10px] font-black uppercase tracking-widest text-base-content/40 px-6 py-4 text-right">Cost</th>
                            <th class="text-[10px] font-black uppercase tracking-widest text-base-content/40 px-6 py-4 text-center">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-base-200">
                        <tr v-for="treatment in treatments" :key="treatment.id" class="hover:bg-base-200/30 transition-colors group">
                            <td class="px-6 py-4 text-center">
                                <span class="text-[10px] font-black text-base-content/30 tracking-widest">TR-{{ treatment.id }}</span>
                            </td>
                            <td class="px-6 py-4">
                                <div class="flex items-center gap-3">
                                    <div class="w-10 h-10 rounded-full bg-base-200 flex items-center justify-center text-xs font-black text-base-content/40 uppercase">
                                        {{ treatment.patient?.first_name?.charAt(0) }}{{ treatment.patient?.last_name?.charAt(0) }}
                                    </div>
                                    <div>
                                        <p class="text-sm font-black text-base-content">
                                            {{ treatment.patient?.first_name }} {{ treatment.patient?.last_name }}
                                        </p>
                                        <p class="text-[10px] font-bold text-base-content/30 uppercase tracking-widest">
                                            ID-{{ treatment.patient_id }}
                                        </p>
                                    </div>
                                </div>
                            </td>
                            <td class="px-6 py-4">
                                <p class="text-sm font-bold text-base-content">{{ treatment.diagnosis }}</p>
                                <p class="text-[10px] font-medium text-base-content/50 uppercase tracking-wide truncate max-w-xs">{{ treatment.procedure }}</p>
                            </td>
                            <td class="px-6 py-4 text-center">
                                <span class="text-xs font-bold text-base-content/60">
                                    {{ new Date(treatment.created_at).toLocaleDateString() }}
                                </span>
                            </td>
                            <td class="px-6 py-4 text-right">
                                <span class="text-sm font-black text-primary">
                                    ${{ Number(treatment.cost).toFixed(2) }}
                                </span>
                            </td>
                            <td class="px-6 py-4 text-center">
                                <div class="flex items-center justify-center gap-2">
                                    <button 
                                        @click="openViewModal(treatment)"
                                        class="btn btn-sm btn-square btn-ghost hover:bg-primary/10 hover:text-primary transition-colors tooltip tooltip-top"
                                        data-tip="View Details"
                                    >
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                        </svg>
                                    </button>
                                    <button 
                                        v-if="canEdit"
                                        @click="openEditModal(treatment)"
                                        class="btn btn-sm btn-square btn-ghost hover:bg-info/10 hover:text-info transition-colors tooltip tooltip-top"
                                        data-tip="Edit Record"
                                    >
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                        </svg>
                                    </button>
                                    <button 
                                        v-if="canDelete"
                                        @click="openDeleteModal(treatment)"
                                        class="btn btn-sm btn-square btn-ghost hover:bg-error/10 hover:text-error transition-colors tooltip tooltip-top"
                                        data-tip="Delete Record"
                                    >
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                        </svg>
                                    </button>
                                </div>
                            </td>
                        </tr>
                        <tr v-if="treatments.length === 0">
                            <td colspan="6" class="px-6 py-16 text-center">
                                <div class="w-16 h-16 mx-auto mb-4 rounded-2xl bg-base-200 flex items-center justify-center">
                                    <svg class="w-8 h-8 text-base-content/20" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                                    </svg>
                                </div>
                                <h3 class="text-sm font-black text-base-content/40 uppercase tracking-widest">No Treatments Found</h3>
                                <p class="text-xs text-base-content/30 mt-1">Record a new treatment to see it here.</p>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Modals -->
        <CreateTreatment 
            :show="showCreateModal" 
            :patients="patients" 
            @close="showCreateModal = false" 
        />

        <EditTreatment 
            :show="showEditModal" 
            :treatment="activeTreatment" 
            :patients="patients" 
            @close="showEditModal = false" 
        />

        <ShowTreatment 
            :show="showViewModal" 
            :treatment="activeTreatment" 
            @close="showViewModal = false" 
        />

        <DeleteTreatment 
            :show="showDeleteModal" 
            :treatment="activeTreatment" 
            @close="showDeleteModal = false" 
        />
    </AuthenticatedLayout>
</template>

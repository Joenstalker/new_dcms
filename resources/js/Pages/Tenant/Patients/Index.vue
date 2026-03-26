<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, router, usePage } from '@inertiajs/vue3';
import { ref, watch, computed } from 'vue';
import debounce from 'lodash/debounce';
import PatientShowModal from './PatientShowModal.vue';
import AddPatientModal from './AddPatientModal.vue';
import Swal from 'sweetalert2';

const props = defineProps({
    patients: Array,
    filters: Object,
});

const branding = computed(() => usePage().props.branding || {});
const primaryColor = computed(() => branding.value.primary_color || '#0ea5e9');

const search = ref(props.filters?.search || '');

// Debounced search watcher
watch(search, debounce(function (value) {
    router.get('/patients', { search: value }, {
        preserveState: true,
        replace: true
    });
}, 300));

const formatDate = (dateString) => {
    if (!dateString) return 'N/A';
    return new Date(dateString).toLocaleDateString('en-US', {
        year: 'numeric', month: 'short', day: 'numeric'
    });
};

// Toast notification for flash messages
watch(() => usePage().props.flash?.success, (successMsg) => {
    if (successMsg) {
        Swal.fire({
            toast: true,
            position: 'top-end',
            icon: 'success',
            title: successMsg,
            showConfirmButton: false,
            timer: 3000,
            timerProgressBar: true,
            customClass: {
                popup: 'colored-toast'
            }
        });
    }
}, { immediate: true });

// Manage Modal State
const showModal = ref(false);
const activePatientId = ref(null);

const openManageModal = (patientId) => {
    activePatientId.value = patientId;
    showModal.value = true;
};

const openEditModal = (patient) => {
    showModal.value = false;
    editPatientData.value = patient;
    showAddModal.value = true;
};

// Add Patient Modal State
const showAddModal = ref(false);
const editPatientData = ref(null);

const openAddModal = () => {
    editPatientData.value = null;
    showAddModal.value = true;
};

const tenantLimits = computed(() => usePage().props.tenant_plan?.limits || {});
const tenantUsage = computed(() => usePage().props.tenant_plan?.current_usage || {});

const limitReached = computed(() => {
    const max = tenantLimits.value.max_patients;
    const current = tenantUsage.value.patients || props.patients?.length || 0;
    return max !== undefined && max !== null && max !== -1 && current >= max;
});

const checkLimitAndOpenAddModal = () => {
    const maxPatients = tenantLimits.value.max_patients;
    const currentPatients = tenantUsage.value.patients || props.patients?.length || 0;
    
    if (maxPatients !== undefined && maxPatients !== null && currentPatients >= maxPatients) {
        Swal.fire({
            title: 'Patient Limit Reached',
            text: `Your plan allows up to ${maxPatients} patients. You currently have ${currentPatients}. Please upgrade your subscription to add more.`,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: primaryColor.value,
            cancelButtonColor: '#94a3b8',
            confirmButtonText: 'Upgrade Plan',
            cancelButtonText: 'Cancel'
        }).then((result) => {
            if (result.isConfirmed) {
                router.get(route('settings.features'));
            }
        });
        return;
    }
    openAddModal();
};
</script>

<template>
    <Head title="Patients" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="text-xl font-black tracking-tight text-base-content">Patient Records</h2>
        </template>

        <div class="mt-8 space-y-6">
            <!-- Limit Reached Banner -->
            <div v-if="limitReached" class="mb-6 animate-in fade-in slide-in-from-top-4 duration-500">
                <div class="bg-warning/10 border border-warning/20 rounded-2xl p-4 flex items-center justify-between">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-xl bg-warning/20 flex items-center justify-center text-warning">
                            <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                            </svg>
                        </div>
                        <div>
                            <h4 class="text-sm font-black text-base-content tracking-tight">Patient Limit Reached</h4>
                            <p class="text-xs text-base-content/60 font-medium">You have reached the maximum number of patients allowed for your current plan ({{ tenantLimits.max_patients }}). Upgrade to add more.</p>
                        </div>
                    </div>
                    <Link
                        :href="route('billing.portal')"
                        class="btn btn-sm btn-warning font-black text-white shadow-sm normal-case"
                    >
                        Upgrade Now
                    </Link>
                </div>
            </div>

            <!-- Top Actions -->
            <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 bg-base-100 p-6 rounded-2xl border border-base-300">
                <div class="flex-1 w-full sm:max-w-md relative">
                    <svg class="w-5 h-5 absolute left-4 top-1/2 -translate-y-1/2 text-base-content/30" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                    </svg>
                    <input 
                        v-model="search" 
                        type="text" 
                        placeholder="Search by ID or Patient Name..." 
                        class="input w-full pl-11 rounded-xl bg-base-200/50 border-transparent focus:border-primary focus:bg-base-100 text-sm font-medium transition-all"
                    >
                </div>
                <button 
                    @click="checkLimitAndOpenAddModal"
                    class="btn rounded-xl border-0 text-white shadow-lg shadow-primary/20 hover:scale-[1.02] hover:shadow-xl transition-all w-full sm:w-auto text-xs font-black uppercase tracking-widest"
                    :style="{ backgroundColor: primaryColor }"
                >
                    + Add Patient
                </button>
            </div>

            <!-- Patient List Table -->
            <div class="bg-base-100 rounded-2xl border border-base-300 overflow-hidden shadow-sm">
                <table class="table w-full">
                    <thead>
                        <tr class="bg-base-200/50">
                            <th class="text-[10px] font-black uppercase tracking-widest text-base-content/40 px-6 py-4">Patient Name</th>
                            <th class="text-[10px] font-black uppercase tracking-widest text-base-content/40 px-6 py-4">Last Visit</th>
                            <th class="text-[10px] font-black uppercase tracking-widest text-base-content/40 px-6 py-4 text-right">Balance</th>
                            <th class="text-[10px] font-black uppercase tracking-widest text-base-content/40 px-6 py-4 text-center">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-base-200">
                        <tr v-for="patient in patients" :key="patient.id" class="hover:bg-base-200/30 transition-colors group">
                            <!-- Patient Details -->
                            <td class="px-6 py-4">
                                <div class="flex items-center gap-4">
                                    <div class="h-12 w-12 rounded-full overflow-hidden bg-base-200 ring-2 ring-base-100 shrink-0 shadow-sm">
                                        <img v-if="patient.photo_url" :src="patient.photo_url" class="h-full w-full object-cover">
                                        <div v-else class="h-full w-full flex items-center justify-center text-base-content/20 bg-base-300">
                                            <svg class="w-6 h-6" fill="currentColor" viewBox="0 0 24 24"><path d="M12 12c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm0 2c-2.67 0-8 1.34-8 4v2h16v-2c0-2.66-5.33-4-8-4z"/></svg>
                                        </div>
                                    </div>
                                    <div>
                                        <p class="text-[10px] font-black uppercase tracking-[0.2em] text-base-content/30 mb-0.5">
                                            ID-{{ patient.id }}
                                        </p>
                                        <p class="text-sm font-black text-base-content">
                                            {{ patient.first_name }} {{ patient.last_name }}
                                        </p>
                                    </div>
                                </div>
                            </td>

                            <!-- Last Visit -->
                            <td class="px-6 py-4 align-middle">
                                <span class="text-xs font-semibold text-base-content/60 bg-base-200/50 px-3 py-1.5 rounded-lg border border-base-200">
                                    {{ patient.last_visit_time ? formatDate(patient.last_visit_time) : 'No recorded visits' }}
                                </span>
                            </td>

                            <!-- Balance -->
                            <td class="px-6 py-4 align-middle text-right">
                                <p class="text-sm font-black" :class="patient.balance > 0 ? 'text-error' : 'text-base-content/50'">
                                    ₱{{ Number(patient.balance || 0).toFixed(2) }}
                                </p>
                            </td>

                            <!-- Actions -->
                            <td class="px-6 py-4 align-middle text-center">
                                <button 
                                    @click="openManageModal(patient.id)"
                                    class="btn btn-sm text-[10px] font-black uppercase tracking-widest text-white shadow-sm hover:scale-[1.02] transition-transform rounded-xl border-0"
                                    :style="{ backgroundColor: primaryColor }"
                                >
                                    Manage
                                </button>
                            </td>
                        </tr>
                        <tr v-if="patients.length === 0">
                            <td colspan="4" class="px-6 py-16 text-center">
                                <div class="w-16 h-16 mx-auto mb-4 rounded-2xl bg-base-200 flex items-center justify-center">
                                    <svg class="w-8 h-8 text-base-content/20" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                                    </svg>
                                </div>
                                <h3 class="text-sm font-black text-base-content/40 uppercase tracking-widest">No Patients Found</h3>
                                <p class="text-xs text-base-content/30 mt-1">Try adjusting your search criteria.</p>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Render the Manage Modal -->
        <PatientShowModal 
            :show="showModal" 
            :patient-id="activePatientId" 
            @close="showModal = false" 
            @edit="openEditModal"
        />

        <!-- Render the Add Patient Modal -->
        <AddPatientModal 
            :show="showAddModal" 
            :patient="editPatientData"
            @close="showAddModal = false" 
        />
    </AuthenticatedLayout>
</template>

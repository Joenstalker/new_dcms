<script setup>
import { brandingState } from '@/States/brandingState';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, router, usePage, Link } from '@inertiajs/vue3';
import { ref, watch, computed, onMounted, onUnmounted } from 'vue';
import PatientShowModal from './PatientShowModal.vue';
import AddPatientModal from './AddPatientModal.vue';
import Swal from 'sweetalert2';

const props = defineProps({
    patients: Object,
    filters: Object,
    total_patients: Number,
    filtered_total: Number,
    available_years: Array,
    available_tags: Array,
});

const primaryColor = computed(() => brandingState.primary_color);
const tenantId = computed(() => usePage().props.tenant?.id || null);
const livePatients = ref([...(props.patients?.data || [])]);
let patientsChannel = null;

watch(() => props.patients, (nextPatients) => {
    livePatients.value = [...(nextPatients?.data || [])];
}, { deep: true });

const search = ref(props.filters?.search || '');
const selectedType = ref(props.filters?.type || '');
const selectedYear = ref(props.filters?.year || '');
const selectedTag = ref(props.filters?.tag || '');
const selectedSort = ref(props.filters?.sort || 'latest');

const hasActiveFilters = computed(() => {
    return Boolean(
        (search.value || '').trim() ||
        selectedType.value ||
        selectedYear.value ||
        selectedTag.value ||
        selectedSort.value !== 'latest'
    );
});

const applyFilters = () => {
    router.get(route('patients.index'), {
        search: (search.value || '').trim() || undefined,
        type: selectedType.value || undefined,
        year: selectedYear.value || undefined,
        tag: selectedTag.value || undefined,
        sort: selectedSort.value || 'latest',
        page: 1,
    }, {
        preserveState: true,
        replace: true,
    });
};

const clearFilters = () => {
    search.value = '';
    selectedType.value = '';
    selectedYear.value = '';
    selectedTag.value = '';
    selectedSort.value = 'latest';

    router.get(route('patients.index'), {}, {
        preserveState: true,
        replace: true,
    });
};

const formatDate = (dateString) => {
    if (!dateString) return 'N/A';
    return new Date(dateString).toLocaleDateString('en-US', {
        year: 'numeric', month: 'short', day: 'numeric'
    });
};

const compactAddress = (address, maxLength = 58) => {
    const text = String(address || '').trim();
    if (!text) return 'N/A';
    return text.length > maxLength ? `${text.slice(0, maxLength)}...` : text;
};

onMounted(() => {
    if (!window.Echo || !tenantId.value) return;

    patientsChannel = window.Echo.private(`tenant.${tenantId.value}.patients`)
        .listen('.TenantPatientChanged', (event) => {
            const incoming = event?.patient;
            const action = event?.action;

            if (!incoming || !incoming.id) return;

            if (action === 'deleted') {
                livePatients.value = livePatients.value.filter((item) => item.id !== incoming.id);
                return;
            }

            const existingIndex = livePatients.value.findIndex((item) => item.id === incoming.id);

            if (existingIndex >= 0) {
                livePatients.value[existingIndex] = {
                    ...livePatients.value[existingIndex],
                    ...incoming,
                };
                return;
            }

            const isFirstPage = (props.patients?.current_page || 1) === 1;
            if (isFirstPage && !hasActiveFilters.value) {
                livePatients.value = [incoming, ...livePatients.value].slice(0, props.patients?.per_page || 20);
            }
        });
});

onUnmounted(() => {
    if (window.Echo && tenantId.value) {
        window.Echo.leave(`tenant.${tenantId.value}.patients`);
    }

    patientsChannel = null;
});

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
const failedPatientPhotoKeys = ref({});

const patientPhotoKey = (patient) => `patient-${patient?.id ?? 'unknown'}`;
const isPatientPhotoFailed = (patient) => Boolean(failedPatientPhotoKeys.value[patientPhotoKey(patient)]);
const markPatientPhotoFailed = (patient) => {
    failedPatientPhotoKeys.value = {
        ...failedPatientPhotoKeys.value,
        [patientPhotoKey(patient)]: true,
    };
};

const resolvePatientPhotoUrl = (patient) => {
    const directUrl = String(patient?.photo_url || '').trim();
    if (directUrl) {
        return directUrl;
    }

    const path = String(patient?.photo_path || '').trim();
    if (!path) {
        return null;
    }

    if (path.startsWith('data:image')) {
        return path;
    }

    if (path.startsWith('http://') || path.startsWith('https://') || path.startsWith('/')) {
        return path;
    }

    return `/tenant-storage/${path.replace(/^\/+/, '')}`;
};

const openAddModal = () => {
    editPatientData.value = null;
    showAddModal.value = true;
};

const tenantLimits = computed(() => usePage().props.tenant_plan?.limits || {});
const tenantUsage = computed(() => usePage().props.tenant_plan?.current_usage || {});

const paginationLinks = computed(() => props.patients?.links || []);

const totalPatients = computed(() => Number(props.total_patients || 0));
const filteredTotal = computed(() => Number(props.filtered_total || livePatients.value.length || 0));

const limitReached = computed(() => {
    const max = tenantLimits.value.max_patients;
    const current = tenantUsage.value.patients || livePatients.value.length || 0;
    return max !== undefined && max !== null && max !== -1 && current >= max;
});

const checkLimitAndOpenAddModal = () => {
    const maxPatients = tenantLimits.value.max_patients;
    const currentPatients = tenantUsage.value.patients || livePatients.value.length || 0;
    
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

        <div class="mt-4 space-y-4">
            <!-- Limit Reached Banner -->
            <div v-if="limitReached" class="mb-4 animate-in fade-in slide-in-from-top-4 duration-500">
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

            <!-- Top Actions + Count -->
            <div class="flex flex-col lg:flex-row justify-between items-start lg:items-center gap-4 bg-base-100/95 backdrop-blur-md p-4 rounded-2xl border border-base-300 shadow-sm">
                <div>
                    <h3 class="text-[10px] font-black text-base-content/40 uppercase tracking-[0.2em]">Patient Registry</h3>
                    <p class="text-xs font-bold text-base-content/70 mt-0.5">
                        Showing {{ filteredTotal }} of {{ totalPatients }} patients
                    </p>
                </div>
                <div class="flex items-center gap-3 w-full lg:w-auto">
                    <button
                        v-if="can('create patients')"
                        @click="checkLimitAndOpenAddModal"
                        class="btn btn-sm h-10 rounded-xl border-0 text-white shadow-lg shadow-primary/20 hover:scale-[1.02] hover:shadow-xl transition-all w-full lg:w-auto text-[10px] font-black uppercase tracking-widest"
                        :style="{ backgroundColor: primaryColor }"
                    >
                        + Add Patient
                    </button>
                </div>
            </div>

            <!-- Filter Strip -->
            <div class="bg-base-100/95 backdrop-blur-md p-4 rounded-2xl border border-base-300 shadow-sm">
                <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-6 gap-2">
                    <div class="xl:col-span-2 relative">
                        <svg class="w-4 h-4 absolute left-4 top-1/2 -translate-y-1/2 text-base-content/30" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                        </svg>
                        <input
                            v-model="search"
                            @keyup.enter="applyFilters"
                            type="text"
                            placeholder="Filter by ID, name, mobile, email"
                            class="input input-sm h-10 w-full pl-10 rounded-xl bg-base-200/50 border-transparent focus:border-primary focus:bg-base-100 text-xs font-medium transition-all"
                        >
                    </div>

                    <select v-model="selectedType" class="select select-sm h-10 rounded-xl bg-base-200/50 border-transparent focus:border-primary text-xs font-bold">
                        <option value="">Patient Type</option>
                        <option value="pedia">Pedia</option>
                        <option value="adult">Adult</option>
                    </select>

                    <select v-model="selectedYear" class="select select-sm h-10 rounded-xl bg-base-200/50 border-transparent focus:border-primary text-xs font-bold">
                        <option value="">Visit Year</option>
                        <option v-for="yearOption in (available_years || [])" :key="`year-${yearOption}`" :value="String(yearOption)">
                            {{ yearOption }}
                        </option>
                    </select>

                    <select v-model="selectedTag" class="select select-sm h-10 rounded-xl bg-base-200/50 border-transparent focus:border-primary text-xs font-bold">
                        <option value="">Tag</option>
                        <option v-for="tagOption in (available_tags || [])" :key="`tag-${tagOption}`" :value="tagOption">
                            {{ tagOption }}
                        </option>
                    </select>

                    <select v-model="selectedSort" class="select select-sm h-10 rounded-xl bg-base-200/50 border-transparent focus:border-primary text-xs font-bold">
                        <option value="latest">Sort: Latest</option>
                        <option value="name_asc">Sort: Name A-Z</option>
                        <option value="name_desc">Sort: Name Z-A</option>
                        <option value="first_visit_desc">Sort: First Visit</option>
                        <option value="last_recall_desc">Sort: Last Recall</option>
                        <option value="balance_desc">Sort: Highest Balance</option>
                    </select>
                </div>

                <div class="flex flex-wrap justify-end gap-2 mt-3">
                    <button
                        @click="clearFilters"
                        class="btn btn-xs h-8 px-4 btn-ghost rounded-lg text-[10px] font-black uppercase tracking-widest"
                    >
                        Clear
                    </button>
                    <button
                        @click="applyFilters"
                        class="btn btn-xs h-8 px-6 rounded-lg border-0 text-white text-[10px] font-black uppercase tracking-widest"
                        :style="{ backgroundColor: primaryColor }"
                    >
                        Go
                    </button>
                </div>
            </div>

            <!-- Patient List (Responsive / No horizontal scroll) -->
            <div class="bg-base-100/95 backdrop-blur-md rounded-2xl border border-base-300 shadow-md overflow-hidden">
                <!-- Mobile / Small Tablet Cards -->
                <div class="lg:hidden divide-y divide-base-200">
                    <div
                        v-for="patient in livePatients"
                        :key="`card-${patient.id}`"
                        class="p-4 space-y-3"
                    >
                        <div class="flex items-start justify-between gap-3">
                            <div class="flex items-center gap-3 min-w-0">
                                <div class="h-11 w-11 rounded-full overflow-hidden bg-base-200 ring-2 ring-base-100 shrink-0 shadow-sm">
                                    <img
                                            v-if="resolvePatientPhotoUrl(patient) && !isPatientPhotoFailed(patient)"
                                            :src="resolvePatientPhotoUrl(patient)"
                                        class="h-full w-full object-cover"
                                        @error="markPatientPhotoFailed(patient)"
                                    >
                                    <div v-else class="h-full w-full flex items-center justify-center text-base-content/20 bg-base-300">
                                        <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24"><path d="M12 12c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm0 2c-2.67 0-8 1.34-8 4v2h16v-2c0-2.66-5.33-4-8-4z"/></svg>
                                    </div>
                                </div>
                                <div class="min-w-0">
                                    <p class="text-sm font-black text-base-content truncate">{{ patient.first_name }} {{ patient.last_name }}</p>
                                    <p class="text-xs font-black tracking-widest uppercase text-base-content/40">ID {{ patient.id }}</p>
                                    <div class="flex items-center gap-1 mt-1">
                                        <span v-if="patient.patient_type" class="badge badge-xs font-black uppercase tracking-widest" :class="patient.patient_type === 'pedia' ? 'badge-info' : 'badge-neutral'">
                                            {{ patient.patient_type }}
                                        </span>
                                    </div>
                                </div>
                            </div>
                            <button
                                @click="openManageModal(patient.id)"
                                class="btn btn-sm h-10 min-h-10 px-3 text-xs font-black uppercase tracking-[0.12em] text-white shadow-sm rounded-lg border-0 shrink-0"
                                :style="{ backgroundColor: primaryColor }"
                            >
                                Manage
                            </button>
                        </div>

                        <div class="grid grid-cols-2 gap-2 text-xs">
                            <div class="bg-base-200/40 rounded-lg px-2 py-1.5">
                                <p class="text-xs font-black uppercase tracking-widest text-base-content/40">Mobile</p>
                                <p class="font-bold text-base-content/70">{{ patient.phone || 'N/A' }}</p>
                            </div>
                            <div class="bg-base-200/40 rounded-lg px-2 py-1.5">
                                <p class="text-xs font-black uppercase tracking-widest text-base-content/40">Balance</p>
                                <p class="font-black" :class="patient.balance > 0 ? 'text-error' : 'text-base-content/60'">₱{{ Number(patient.balance || 0).toFixed(2) }}</p>
                            </div>
                            <div class="bg-base-200/40 rounded-lg px-2 py-1.5 col-span-2">
                                <p class="text-xs font-black uppercase tracking-widest text-base-content/40">Address</p>
                                <p class="font-semibold text-base-content/70 truncate">{{ compactAddress(patient.address, 48) }}</p>
                            </div>
                            <div class="bg-base-200/40 rounded-lg px-2 py-1.5">
                                <p class="text-xs font-black uppercase tracking-widest text-base-content/40">First Visit</p>
                                <p class="font-semibold text-base-content/70">{{ patient.first_visit_at ? formatDate(patient.first_visit_at) : (patient.last_visit_time ? formatDate(patient.last_visit_time) : 'N/A') }}</p>
                            </div>
                            <div class="bg-base-200/40 rounded-lg px-2 py-1.5">
                                <p class="text-xs font-black uppercase tracking-widest text-base-content/40">Last Recall</p>
                                <p class="font-semibold text-base-content/70">{{ patient.last_recall_at ? formatDate(patient.last_recall_at) : 'N/A' }}</p>
                            </div>
                        </div>
                    </div>

                    <div v-if="livePatients.length === 0" class="px-6 py-16 text-center">
                        <div class="w-16 h-16 mx-auto mb-4 rounded-2xl bg-base-200 flex items-center justify-center">
                            <svg class="w-8 h-8 text-base-content/20" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                            </svg>
                        </div>
                        <h3 class="text-sm font-black text-base-content/40 uppercase tracking-widest">No Patients Found</h3>
                        <p class="text-xs text-base-content/30 mt-1">Try adjusting your search criteria.</p>
                    </div>
                </div>

                <!-- Large Screens Table -->
                <table class="hidden lg:table table-fixed w-full">
                    <thead>
                        <tr class="bg-base-200/50">
                            <th class="w-[10%] text-xs font-black uppercase tracking-widest text-base-content/40 px-3 xl:px-5 py-4">ID</th>
                            <th class="w-[21%] text-xs font-black uppercase tracking-widest text-base-content/40 px-3 xl:px-5 py-4">Patient Name</th>
                            <th class="w-[19%] text-xs font-black uppercase tracking-widest text-base-content/40 px-3 xl:px-5 py-4">Address</th>
                            <th class="w-[12%] text-xs font-black uppercase tracking-widest text-base-content/40 px-3 xl:px-5 py-4">Mobile</th>
                            <th class="w-[10%] text-xs font-black uppercase tracking-widest text-base-content/40 px-3 xl:px-5 py-4">First Visit</th>
                            <th class="hidden xl:table-cell w-[10%] text-xs font-black uppercase tracking-widest text-base-content/40 px-3 xl:px-5 py-4">Last Recall</th>
                            <th class="w-[8%] text-xs font-black uppercase tracking-widest text-base-content/40 px-3 xl:px-5 py-4 text-right">Balance</th>
                            <th class="w-[10%] text-xs font-black uppercase tracking-widest text-base-content/40 px-3 xl:px-5 py-4 text-center whitespace-nowrap sticky right-0 z-20 bg-base-200/95 backdrop-blur-sm border-l border-base-200">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-base-200">
                        <tr v-for="patient in livePatients" :key="patient.id" class="hover:bg-base-200/30 transition-colors group">
                            <td class="px-3 xl:px-5 py-5 align-middle text-xs font-black tracking-widest text-base-content/50">
                                {{ patient.id }}
                            </td>

                            <!-- Patient Details -->
                            <td class="px-3 xl:px-5 py-5">
                                <div class="flex items-center gap-4">
                                    <div class="h-12 w-12 rounded-full overflow-hidden bg-base-200 ring-2 ring-base-100 shrink-0 shadow-sm">
                                        <img
                                            v-if="resolvePatientPhotoUrl(patient) && !isPatientPhotoFailed(patient)"
                                            :src="resolvePatientPhotoUrl(patient)"
                                            class="h-full w-full object-cover"
                                            @error="markPatientPhotoFailed(patient)"
                                        >
                                        <div v-else class="h-full w-full flex items-center justify-center text-base-content/20 bg-base-300">
                                            <svg class="w-6 h-6" fill="currentColor" viewBox="0 0 24 24"><path d="M12 12c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm0 2c-2.67 0-8 1.34-8 4v2h16v-2c0-2.66-5.33-4-8-4z"/></svg>
                                        </div>
                                    </div>
                                    <div class="min-w-0">
                                        <p class="text-sm font-black text-base-content truncate">
                                            {{ patient.first_name }} {{ patient.last_name }}
                                        </p>
                                        <div class="flex items-center gap-1 mt-1">
                                            <span v-if="patient.patient_type" class="badge badge-xs font-black uppercase tracking-widest" :class="patient.patient_type === 'pedia' ? 'badge-info' : 'badge-neutral'">
                                                {{ patient.patient_type }}
                                            </span>
                                            <span
                                                v-for="tagValue in (patient.tags || []).slice(0, 2)"
                                                :key="`p-tag-${patient.id}-${tagValue}`"
                                                class="badge badge-xs badge-outline text-xs font-black"
                                            >
                                                {{ tagValue }}
                                            </span>
                                        </div>
                                    </div>
                                </div>
                            </td>

                            <td class="px-3 xl:px-5 py-5 align-middle overflow-hidden">
                                <p class="text-xs font-semibold text-base-content/70 whitespace-normal leading-snug break-all [overflow-wrap:anywhere] max-w-full">{{ compactAddress(patient.address, 58) }}</p>
                            </td>

                            <td class="px-3 xl:px-5 py-5 align-middle overflow-hidden">
                                <span class="text-xs font-semibold text-base-content/70 block whitespace-normal break-all [overflow-wrap:anywhere] leading-snug max-w-full">{{ patient.phone || 'N/A' }}</span>
                            </td>

                            <td class="px-3 xl:px-5 py-5 align-middle">
                                <span class="text-xs font-semibold text-base-content/70 bg-base-200/50 px-2 xl:px-3 py-1.5 rounded-lg border border-base-200 inline-block whitespace-normal">
                                    {{ patient.first_visit_at ? formatDate(patient.first_visit_at) : (patient.last_visit_time ? formatDate(patient.last_visit_time) : 'N/A') }}
                                </span>
                            </td>

                            <td class="hidden xl:table-cell px-3 xl:px-5 py-5 align-middle">
                                <span class="text-xs font-semibold text-base-content/70 bg-base-200/50 px-3 py-1.5 rounded-lg border border-base-200 whitespace-normal">
                                    {{ patient.last_recall_at ? formatDate(patient.last_recall_at) : 'N/A' }}
                                </span>
                            </td>

                            <!-- Balance -->
                            <td class="px-3 xl:px-5 py-5 align-middle text-right">
                                <p class="text-sm font-black" :class="patient.balance > 0 ? 'text-error' : 'text-base-content/50'">
                                    ₱{{ Number(patient.balance || 0).toFixed(2) }}
                                </p>
                            </td>

                            <!-- Actions -->
                            <td class="px-3 xl:px-5 py-5 align-middle text-center whitespace-nowrap sticky right-0 z-10 bg-base-100/95 backdrop-blur-md border-l border-base-200">
                                <button 
                                    @click="openManageModal(patient.id)"
                                    class="btn btn-xs h-8 px-3 rounded-lg border-0 text-white text-[10px] font-black uppercase tracking-widest shadow-sm hover:scale-[1.02] transition-transform"
                                    :style="{ backgroundColor: primaryColor }"
                                >
                                    Manage
                                </button>
                            </td>
                        </tr>
                        <tr v-if="livePatients.length === 0">
                            <td colspan="8" class="px-6 py-16 text-center">
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

                <div v-if="paginationLinks.length > 3" class="px-6 py-4 border-t border-base-200 bg-base-100 flex flex-wrap gap-2 justify-center md:justify-between items-center">
                    <div class="text-xs font-bold text-base-content/50">
                        Page {{ props.patients?.current_page || 1 }} of {{ props.patients?.last_page || 1 }}
                    </div>
                    <div class="join">
                        <Link
                            v-for="(link, i) in paginationLinks"
                            :key="`patient-page-${i}`"
                            :href="link.url || '#'"
                            class="join-item btn btn-sm"
                            :class="[
                                link.active ? 'btn-primary text-white border-0' : 'btn-ghost',
                                !link.url ? 'btn-disabled opacity-40 pointer-events-none' : ''
                            ]"
                            preserve-state
                            preserve-scroll
                            v-html="link.label"
                        />
                    </div>
                </div>
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

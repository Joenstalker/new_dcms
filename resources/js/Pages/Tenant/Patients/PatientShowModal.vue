<script setup>
import { brandingState } from '@/States/brandingState';
import { ref, watch, computed } from 'vue';
import { usePage, router, Link } from '@inertiajs/vue3';
import Swal from 'sweetalert2';

const props = defineProps({
    show: Boolean,
    patientId: [Number, String],
});

const emit = defineEmits(['close', 'edit']);

const primaryColor = computed(() => brandingState.primary_color);

const loading = ref(false);
const patient = ref(null);
const activeTab = ref('medical_history');
const page = usePage();
const progressNotesFeature = computed(() => page.props.subscription?.features?.progress_notes || null);
const progressNotesStatus = computed(
    () => progressNotesFeature.value?.implementation_status
        || page.props.tenant_plan?.global_feature_statuses?.progress_notes
        || 'coming_soon'
);
const progressNotesStatusLabel = computed(() => String(progressNotesStatus.value).replace(/_/g, ' ').toUpperCase());
const progressNotesUpdateStatus = computed(() => progressNotesFeature.value?.update_status || null);
const progressNotesIsApplied = computed(() => progressNotesFeature.value?.is_applied === true);
const progressNotesIsEnabledForPlan = computed(() => progressNotesFeature.value?.is_enabled !== false);
const requiresTenantUpdate = computed(() => {
    if (progressNotesUpdateStatus.value === 'pending') {
        return true;
    }

    if (progressNotesUpdateStatus.value === 'applied') {
        return false;
    }

    // If no tenant update row exists, allow by default once central status is active.
    return false;
});

const canUseProgressNotes = computed(() => {
    return progressNotesStatus.value === 'active'
        && progressNotesIsEnabledForPlan.value
        && !requiresTenantUpdate.value
        && (progressNotesIsApplied.value || progressNotesUpdateStatus.value !== 'pending');
});

const goToFeatureUpdates = () => {
    router.get(route('settings.updates'));
};

const tabs = [
    { id: 'medical_history', label: 'Medical History' },
    { id: 'progress_notes', label: 'Progress Notes' },
    { id: 'photos', label: 'Photos' },
    { id: 'dental_chart', label: 'Dental Chart' },
    { id: 'forms', label: 'Forms' },
    { id: 'appointments', label: 'Appointments' },
    { id: 'billing', label: 'Balance' },
];

watch(() => props.show, async (newVal) => {
    if (newVal && props.patientId) {
        // Fetch patient details when modal opens
        loading.value = true;
        try {
            const response = await fetch(`/patients/${props.patientId}`, {
                headers: { 'Accept': 'application/json' }
            });

            // Handle non-OK responses first
            if (!response.ok) {
                const text = await response.text();
                console.error(`Failed to load patient: server responded ${response.status}`);
                // If the response is HTML (error page) avoid using it as JSON or image src
                // Provide a user-friendly fallback here
                patient.value = null;
                return;
            }

            const contentType = response.headers.get('content-type') || '';
            if (!contentType.includes('application/json')) {
                // Unexpected content-type (HTML or plain text) — don't attempt to parse as JSON
                console.error('Failed to load patient: unexpected content-type', contentType);
                patient.value = null;
                return;
            }

            const data = await response.json();

            // Ensure photo_url is a safe value (avoid HTML error pages being used as image src)
            if (data && data.photo_url && typeof data.photo_url === 'string') {
                const url = data.photo_url.trim();
                if (url.includes('?error=') || url.startsWith(window.location.origin + '/?error=')) {
                    data.photo_url = null;
                }
            }

            patient.value = data;
            activeTab.value = 'medical_history';
        } catch (error) {
            console.error("Failed to load patient:", error);
            patient.value = null;
        } finally {
            loading.value = false;
        }
    } else {
        patient.value = null;
    }
});

const close = () => {
    emit('close');
};

const formatDate = (dateString) => {
    if (!dateString) return 'N/A';
    return new Date(dateString).toLocaleDateString('en-US', {
        year: 'numeric', month: 'short', day: 'numeric'
    });
};

const deletePatient = () => {
    Swal.fire({
        title: 'Delete Patient?',
        text: "This action cannot be undone.",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#ef4444',
        cancelButtonColor: '#6b7280',
        confirmButtonText: 'Yes, delete it!'
    }).then((result) => {
        if (result.isConfirmed) {
            router.delete(`/patients/${patient.value.id}`, {
                onSuccess: () => {
                    close();
                    Swal.fire({
                        title: 'Deleted!',
                        text: 'Patient record has been removed.',
                        icon: 'success',
                        timer: 2000,
                        showConfirmButton: false
                    });
                },
            });
        }
    });
};
</script>

<script>
// Import partials inside a normal script block or directly in setup
import MedicalHistory from './partials/MedicalHistory.vue';
import PatientAppointments from './partials/PatientAppointments.vue';
import PatientBilling from './partials/PatientBilling.vue';
import ProgressNotes from './partials/ProgressNotes.vue';
import PlaceholderTab from './partials/PlaceholderTab.vue';
</script>

<template>
    <dialog class="modal" :class="{ 'modal-open': show }">
        <div class="modal-box w-[96vw] max-w-[1500px] h-[92vh] flex flex-col p-0 overflow-hidden bg-base-200">
            <!-- Header -->
            <div class="flex items-center justify-between p-4 bg-base-100 border-b border-base-300">
                <h3 class="text-lg font-black text-base-content uppercase tracking-widest">Manage Patient</h3>
                <button @click="close" class="btn btn-sm btn-circle btn-ghost">✕</button>
            </div>

            <!-- Content -->
            <div class="flex-1 flex overflow-hidden">
                <template v-if="loading">
                    <div class="flex-1 flex items-center justify-center">
                        <span class="loading loading-spinner loading-lg" :style="{ color: primaryColor }"></span>
                    </div>
                </template>
                <template v-else-if="patient">
                    <!-- Left Sidebar (Profile Widget) -->
                    <div class="w-80 bg-base-100 border-r border-base-300 overflow-y-auto p-6 flex flex-col shrink-0 custom-scrollbar">
                        <!-- Profile Card -->
                        <div class="bg-base-100 rounded-3xl border border-base-200 p-6 flex flex-col items-center text-center shadow-sm relative">
                            <div class="w-28 h-28 rounded-full overflow-hidden bg-base-200 mb-4 ring-4 ring-base-100 ring-offset-2 ring-offset-base-300">
                                <img v-if="patient.photo_url" :src="patient.photo_url" alt="Profile" class="w-full h-full object-cover" />
                                <div v-else class="w-full h-full flex items-center justify-center text-base-content/20 bg-base-300">
                                    <svg class="w-12 h-12" fill="currentColor" viewBox="0 0 24 24"><path d="M12 12c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm0 2c-2.67 0-8 1.34-8 4v2h16v-2c0-2.66-5.33-4-8-4z"/></svg>
                                </div>
                            </div>
                            
                            <h2 class="text-base font-black text-base-content uppercase tracking-tight leading-tight">
                                {{ patient.last_name }}, {{ patient.first_name }}
                            </h2>
                            <p class="text-xs font-bold text-base-content/40 mt-1 uppercase tracking-widest">ID: {{ patient.id }}</p>
                            
                            <div class="mt-4 text-xs font-medium text-base-content/60">
                                <p>Last Visit: {{ patient.last_visit_time ? formatDate(patient.last_visit_time) : 'Never' }}</p>
                            </div>

                            <div class="mt-6 flex gap-2 w-full">
                                <button v-if="can('delete patients')" @click="deletePatient" class="flex-1 btn btn-sm btn-error btn-outline text-xs">Delete</button>
                                <button v-if="can('edit patients')" @click="$emit('edit', patient)" class="flex-1 btn btn-sm btn-outline text-xs" :style="{ borderColor: primaryColor, color: primaryColor }">Edit</button>
                                <a v-if="can('view patients')" :href="`/patients/${patient.id}/pdf`" target="_blank" class="btn btn-sm btn-outline btn-info text-xs" title="Download Printable Record">
                                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/></svg>
                                </a>
                            </div>
                        </div>

                        <!-- Details List -->
                        <div class="mt-6 space-y-4 px-2">
                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <p class="text-[10px] font-black text-base-content/40 uppercase tracking-widest">Age</p>
                                    <p class="text-sm font-bold text-base-content mt-0.5">
                                        {{ patient.date_of_birth ? Math.floor((new Date() - new Date(patient.date_of_birth)) / 31557600000) + ' yrs' : 'N/A' }}
                                    </p>
                                </div>
                                <div>
                                    <p class="text-[10px] font-black text-base-content/40 uppercase tracking-widest">Birthday</p>
                                    <p class="text-sm font-bold text-base-content mt-0.5">{{ formatDate(patient.date_of_birth) }}</p>
                                </div>
                                <div>
                                    <p class="text-[10px] font-black text-base-content/40 uppercase tracking-widest">Gender</p>
                                    <p class="text-sm font-bold text-base-content mt-0.5 capitalize">{{ patient.gender || 'N/A' }}</p>
                                </div>
                                <div>
                                    <p class="text-[10px] font-black text-base-content/40 uppercase tracking-widest">Mobile</p>
                                    <p class="text-sm font-bold text-base-content mt-0.5">{{ patient.phone || 'N/A' }}</p>
                                </div>
                            </div>
                            
                            <div class="border-t border-base-200 pt-4 space-y-4">
                                <div>
                                    <p class="text-[10px] font-black text-base-content/40 uppercase tracking-widest">Email Address</p>
                                    <p class="text-sm font-bold text-base-content mt-0.5 break-words">{{ patient.email || 'N/A' }}</p>
                                </div>
                                <div>
                                    <p class="text-[10px] font-black text-base-content/40 uppercase tracking-widest">Address</p>
                                    <p class="text-sm font-bold text-base-content mt-0.5">{{ patient.address || 'N/A' }}</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Right Main Content (Internal Tabs) -->
                    <div class="flex-1 flex flex-col bg-base-200">
                        <!-- Navigation Tabs -->
                        <div class="bg-base-100 px-6 pt-4 border-b border-base-300 overflow-x-auto flex-shrink-0 flex items-center justify-between">
                            <div class="flex space-x-6">
                                <button
                                    v-for="tab in tabs"
                                    :key="tab.id"
                                    @click="activeTab = tab.id"
                                    class="py-3 px-1 border-b-2 text-xs font-black uppercase tracking-widest whitespace-nowrap transition-colors"
                                    :class="activeTab === tab.id ? 'border-primary text-base-content' : 'border-transparent text-base-content/40 hover:text-base-content/70'"
                                    :style="activeTab === tab.id ? { borderColor: primaryColor, color: primaryColor } : {}"
                                >
                                    {{ tab.label }}
                                </button>
                            </div>
                        </div>

                        <!-- Tab Content Scrollable Area -->
                        <div class="flex-1 overflow-y-auto p-6 bg-base-100 custom-scrollbar">
                            
                            <!-- Medical History Content -->
                            <MedicalHistory v-if="activeTab === 'medical_history'" :patient="patient" />

                            <!-- Progress Notes Content -->
                            <ProgressNotes
                                v-else-if="activeTab === 'progress_notes' && canUseProgressNotes"
                                :patient="patient"
                            />
                            <div
                                v-else-if="activeTab === 'progress_notes' && !canUseProgressNotes"
                                class="h-full flex flex-col items-center justify-center p-12 text-center text-base-content/45"
                            >
                                <svg class="w-16 h-16 mb-4 opacity-20" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" />
                                </svg>
                                <template v-if="requiresTenantUpdate && progressNotesStatus === 'active'">
                                    <p class="text-sm font-black uppercase tracking-widest">Progress Notes Is Ready To Use</p>
                                    <p class="text-xs mt-2 max-w-md">
                                        Central admin has published this feature, but your clinic must apply updates first before usage.
                                    </p>
                                    <button
                                        type="button"
                                        @click="goToFeatureUpdates"
                                        class="btn btn-sm mt-4 rounded-xl border-0 text-white font-black uppercase tracking-widest"
                                        :style="{ backgroundColor: primaryColor }"
                                    >
                                        Go To Settings Updates
                                    </button>
                                </template>
                                <template v-else>
                                    <p class="text-sm font-black uppercase tracking-widest">Progress Notes Unavailable</p>
                                    <p class="text-xs mt-2 max-w-md">
                                        This tab follows the central admin Implementation Phase Status.
                                        Current status: {{ progressNotesStatusLabel }}.
                                    </p>
                                </template>
                            </div>

                            <!-- Appointments Content -->
                            <PatientAppointments v-else-if="activeTab === 'appointments'" :patient="patient" />

                            <!-- Billing & Balance Content -->
                            <PatientBilling v-else-if="activeTab === 'billing'" :patient="patient" />
                            
                            <!-- Placeholders for other tabs -->
                            <PlaceholderTab v-else :patient="patient" :tab-name="activeTab.replace('_', ' ')" />

                        </div>
                    </div>
                </template>
            </div>
        </div>
        <form method="dialog" class="modal-backdrop">
            <button @click="close">close</button>
        </form>
    </dialog>
</template>

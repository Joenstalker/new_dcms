<script setup>
import { brandingState } from '@/States/brandingState';
import { computed, ref, onMounted, onUnmounted, watch } from 'vue';
import { usePage } from '@inertiajs/vue3';
import CreateTreatment from '../../Treatments/CreateTreatment.vue';
import EditTreatment from '../../Treatments/EditTreatment.vue';
import DeleteTreatment from '../../Treatments/DeleteTreatment.vue';

const props = defineProps({
    patient: { type: Object, required: true },
});

const page = usePage();
const tenantId = computed(() => page.props.tenant?.id || null);
const primaryColor = computed(() => brandingState.primary_color || '#2563eb');
const permissions = computed(() => page.props.auth?.user?.permissions || []);
const roleNames = computed(() => {
    const roles = page.props.auth?.user?.roles || [];

    return roles.map((role) => {
        if (typeof role === 'string') return role;
        return role?.name;
    }).filter(Boolean);
});
const isOwner = computed(() => roleNames.value.includes('Owner'));
const canCreate = computed(() => isOwner.value || permissions.value.includes('create progress notes'));
const canEdit = computed(() => isOwner.value || permissions.value.includes('edit progress notes'));
const canDelete = computed(() => isOwner.value || permissions.value.includes('delete progress notes'));
const expandedRows = ref([]);
const showCreateModal = ref(false);
const showEditModal = ref(false);
const showDeleteModal = ref(false);
const activeTreatment = ref(null);
const services = ref([]);
const dentists = ref([]);
const localTreatments = ref([]);
let treatmentsChannel = null;

const syncLocalTreatments = (treatments) => {
    localTreatments.value = Array.isArray(treatments)
        ? treatments.map((item) => ({ ...item }))
        : [];
};

const applyTreatmentEvent = (eventPayload) => {
    const incoming = eventPayload?.treatment;
    const action = eventPayload?.action;
    if (!incoming?.id || !action) return;

    if (action === 'deleted') {
        localTreatments.value = localTreatments.value.filter((item) => item.id !== incoming.id);
        return;
    }

    const currentPatientId = Number(props.patient?.id || 0);
    const incomingPatientId = Number(incoming.patient_id || incoming.patient?.id || 0);

    if (!currentPatientId || !incomingPatientId || incomingPatientId !== currentPatientId) {
        return;
    }

    const existingIndex = localTreatments.value.findIndex((item) => item.id === incoming.id);

    if (existingIndex >= 0) {
        localTreatments.value[existingIndex] = {
            ...localTreatments.value[existingIndex],
            ...incoming,
        };
        return;
    }

    localTreatments.value = [incoming, ...localTreatments.value];
};

const setupTreatmentsRealtime = () => {
    if (!window.Echo || !tenantId.value) return;

    treatmentsChannel = window.Echo.private(`tenant.${tenantId.value}.treatments`)
        .listen('.TenantTreatmentChanged', applyTreatmentEvent);
};

watch(
    () => props.patient?.treatments,
    (nextTreatments) => {
        syncLocalTreatments(nextTreatments);
    },
    { immediate: true, deep: true },
);

const notes = computed(() => {
    const list = [...localTreatments.value];

    return list.sort((a, b) => {
        const aTime = new Date(a?.created_at || 0).getTime();
        const bTime = new Date(b?.created_at || 0).getTime();
        return bTime - aTime;
    });
});

const currency = new Intl.NumberFormat('en-PH', {
    minimumFractionDigits: 0,
    maximumFractionDigits: 2,
});

const formatDateTime = (value) => {
    if (!value) return 'N/A';

    const d = new Date(value);
    if (Number.isNaN(d.getTime())) return 'N/A';

    return d.toLocaleString('en-US', {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
    });
};

const formatDate = (value) => {
    if (!value) return 'N/A';

    const d = new Date(value);
    if (Number.isNaN(d.getTime())) return 'N/A';

    return d.toLocaleDateString('en-US', {
        month: '2-digit',
        day: '2-digit',
        year: '2-digit',
    });
};

const formatMoney = (value) => {
    const num = Number(value || 0);
    return currency.format(num);
};

const rowAmount = (note) => Number(note?.cost || 0);
const rowPaid = (note) => Number(note?.paid_amount ?? note?.paid ?? 0);
const rowBalance = (note) => {
    if (note?.balance !== undefined && note?.balance !== null && note?.balance !== '') {
        return Number(note.balance || 0);
    }

    return Math.max(rowAmount(note) - rowPaid(note), 0);
};

const totalAmount = computed(() => notes.value.reduce((sum, note) => sum + rowAmount(note), 0));
const totalPaid = computed(() => notes.value.reduce((sum, note) => sum + rowPaid(note), 0));
const totalBalance = computed(() => notes.value.reduce((sum, note) => sum + rowBalance(note), 0));

const toggleRow = (noteId) => {
    if (expandedRows.value.includes(noteId)) {
        expandedRows.value = expandedRows.value.filter((id) => id !== noteId);
        return;
    }

    expandedRows.value.push(noteId);
};

const isExpanded = (noteId) => expandedRows.value.includes(noteId);

const localPatients = computed(() => {
    if (!props.patient) return [];

    return [{
        id: props.patient.id,
        first_name: props.patient.first_name,
        last_name: props.patient.last_name,
    }];
});

const openEditModal = (note) => {
    activeTreatment.value = note;
    showEditModal.value = true;
};

const openDeleteModal = (note) => {
    activeTreatment.value = note;
    showDeleteModal.value = true;
};

onMounted(async () => {
    setupTreatmentsRealtime();

    try {
        const response = await fetch('/treatments/options', {
            headers: { Accept: 'application/json' },
        });

        if (!response.ok) return;
        const payload = await response.json();
        services.value = Array.isArray(payload?.services) ? payload.services : [];
        dentists.value = Array.isArray(payload?.dentists) ? payload.dentists : [];
    } catch (e) {
        // Gracefully fallback: create modal can still open but dropdowns may be empty.
    }
});

onUnmounted(() => {
    if (window.Echo && tenantId.value) {
        window.Echo.leave(`tenant.${tenantId.value}.treatments`);
    }

    treatmentsChannel = null;
});
</script>

<template>
    <div class="space-y-5">
        <div class="flex items-center justify-between">
            <div>
                <h4 class="text-sm font-black uppercase tracking-widest text-base-content">Progress Notes</h4>
                <p class="text-xs text-base-content/50">Patient-linked clinical notes and procedures</p>
            </div>
            <span class="badge badge-sm badge-outline font-black">{{ notes.length }} notes</span>
        </div>

        <div v-if="notes.length === 0" class="rounded-2xl border border-base-300 bg-base-100 p-10 text-center text-base-content/50">
            <p class="text-sm font-black uppercase tracking-widest">No progress notes yet</p>
            <p class="mt-1 text-xs">Once procedures are recorded, notes will appear here.</p>
            <div class="mt-5" v-if="canCreate">
                <button
                    type="button"
                    @click="showCreateModal = true"
                    class="btn btn-sm rounded-xl border-0 text-white font-black text-[10px] uppercase tracking-widest"
                    :style="{ backgroundColor: primaryColor }"
                >
                    + Add Progress Note
                </button>
            </div>
        </div>

        <div v-else class="rounded-2xl border border-base-300 bg-base-100 overflow-hidden">
            <div class="overflow-x-auto">
                <table class="table w-full">
                    <thead>
                        <tr class="bg-base-200/60">
                            <th class="text-[10px] font-black uppercase tracking-widest text-base-content/45">Date</th>
                            <th class="text-[10px] font-black uppercase tracking-widest text-base-content/45">Procedure</th>
                            <th class="text-[10px] font-black uppercase tracking-widest text-base-content/45">Dentist</th>
                            <th class="text-[10px] font-black uppercase tracking-widest text-base-content/45 text-right">Amount</th>
                            <th class="text-[10px] font-black uppercase tracking-widest text-base-content/45 text-right">Paid</th>
                            <th class="text-[10px] font-black uppercase tracking-widest text-base-content/45 text-right">Balance</th>
                            <th class="text-[10px] font-black uppercase tracking-widest text-base-content/45 text-center">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <template v-for="note in notes" :key="`note-${note.id}`">
                            <tr class="hover:bg-base-200/30 transition-colors align-top">
                                <td class="text-sm font-bold text-base-content/80 whitespace-nowrap">{{ formatDate(note.created_at) }}</td>
                                <td class="min-w-[280px]">
                                    <p class="text-sm font-black text-base-content leading-tight">
                                        {{ note.diagnosis || 'No diagnosis provided' }}
                                    </p>
                                    <p class="mt-1 text-xs font-medium text-base-content/60 whitespace-pre-line">
                                        {{ note.procedure || 'N/A' }}
                                    </p>
                                </td>
                                <td class="text-sm font-bold text-base-content/80 whitespace-nowrap">
                                    {{ note.dentist?.name || 'N/A' }}
                                </td>
                                <td class="text-sm font-black text-base-content text-right whitespace-nowrap">
                                    {{ formatMoney(rowAmount(note)) }}
                                </td>
                                <td class="text-sm font-black text-base-content/85 text-right whitespace-nowrap">
                                    {{ formatMoney(rowPaid(note)) }}
                                </td>
                                <td class="text-sm font-black text-right whitespace-nowrap" :class="rowBalance(note) > 0 ? 'text-warning' : 'text-base-content/70'">
                                    {{ formatMoney(rowBalance(note)) }}
                                </td>
                                <td>
                                    <div class="flex items-center justify-center gap-1">
                                        <button
                                            type="button"
                                            class="btn btn-xs btn-square btn-ghost"
                                            @click="toggleRow(note.id)"
                                            :title="isExpanded(note.id) ? 'Hide note details' : 'View note details'"
                                        >
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                            </svg>
                                        </button>
                                        <button
                                            v-if="canEdit"
                                            type="button"
                                            @click="openEditModal(note)"
                                            class="btn btn-xs btn-square btn-ghost text-info"
                                            title="Edit Progress Note"
                                        >
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                            </svg>
                                        </button>
                                        <button
                                            v-if="canDelete"
                                            type="button"
                                            @click="openDeleteModal(note)"
                                            class="btn btn-xs btn-square btn-ghost text-error"
                                            title="Delete Progress Note"
                                        >
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                            </svg>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                            <tr v-if="isExpanded(note.id)" class="bg-base-200/35">
                                <td colspan="7" class="py-3">
                                    <div class="rounded-xl border border-base-300 bg-base-100 p-3">
                                        <p class="text-[10px] font-black uppercase tracking-widest text-base-content/45">Clinical Notes</p>
                                        <p class="mt-1 text-xs text-base-content/80 whitespace-pre-wrap">{{ note.notes || 'No additional clinical notes.' }}</p>
                                        <p class="mt-2 text-[10px] font-bold uppercase tracking-wider text-base-content/45">Recorded: {{ formatDateTime(note.created_at) }}</p>
                                    </div>
                                </td>
                            </tr>
                        </template>
                    </tbody>
                    <tfoot>
                        <tr class="bg-base-200/60">
                            <td colspan="3" class="text-right text-sm font-black uppercase tracking-widest text-base-content/60">Totals</td>
                            <td class="text-right text-sm font-black text-base-content">{{ formatMoney(totalAmount) }}</td>
                            <td class="text-right text-sm font-black text-base-content">{{ formatMoney(totalPaid) }}</td>
                            <td class="text-right text-sm font-black text-base-content">{{ formatMoney(totalBalance) }}</td>
                            <td class="text-center">
                                <button
                                    v-if="canCreate"
                                    type="button"
                                    @click="showCreateModal = true"
                                    class="btn btn-xs btn-square border-0 text-white"
                                    :style="{ backgroundColor: primaryColor }"
                                    title="Add Progress Note"
                                >
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                                    </svg>
                                </button>
                            </td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>

        <CreateTreatment
            :show="showCreateModal"
            :patients="localPatients"
            :services="services"
            :dentists="dentists"
            :initial-patient-id="Number(props.patient?.id || 0)"
            :render-in-place="true"
            @close="showCreateModal = false"
        />

        <EditTreatment
            :show="showEditModal"
            :treatment="activeTreatment"
            :patients="localPatients"
            :render-in-place="true"
            @close="showEditModal = false"
        />

        <DeleteTreatment
            :show="showDeleteModal"
            :treatment="activeTreatment"
            :render-in-place="true"
            @close="showDeleteModal = false"
        />
    </div>
</template>

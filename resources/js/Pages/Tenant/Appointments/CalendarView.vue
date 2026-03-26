<script setup>
import { ref, computed } from 'vue';
import FullCalendar from '@fullcalendar/vue3';
import dayGridPlugin from '@fullcalendar/daygrid';
import timeGridPlugin from '@fullcalendar/timegrid';
import interactionPlugin from '@fullcalendar/interaction';
import { router, usePage } from '@inertiajs/vue3';
import ShowAppointments from './ShowAppointments.vue';
import EditAppointments from './EditAppointments.vue';
import DeleteAppointments from './DeleteAppointments.vue';

const props = defineProps({
    appointments: Array,
    dentists: Array,
    selectedAssociates: Array,
    selectedTypes: Array
});

const permissions = computed(() => usePage().props.auth.user.permissions);
const canEdit = computed(() => permissions.value.includes('edit appointments'));
const canDelete = computed(() => permissions.value.includes('delete appointments'));

// Calendar Overlay State
const showDayOverlay = ref(false);
const selectedDayData = ref(null);
const selectedDateString = ref('');

// Item Detail States
const showDetailModal = ref(false);
const showEditModal = ref(false);
const showDeleteModal = ref(false);
const selectedAppointment = ref(null);

const openDetail = (apt) => {
    selectedAppointment.value = apt;
    showDetailModal.value = true;
};

const openEdit = (apt) => {
    selectedAppointment.value = apt;
    showEditModal.value = true;
};

const openDelete = (apt) => {
    selectedAppointment.value = apt;
    showDeleteModal.value = true;
};

const filteredAppointments = computed(() => {
    return props.appointments.filter(apt => {
        const matchesAssociate = !apt.dentist_id || props.selectedAssociates.includes(apt.dentist_id);
        const matchesType = props.selectedTypes.includes(apt.type || 'appointment');
        return matchesAssociate && matchesType;
    });
});

const calendarOptions = computed(() => ({
    plugins: [dayGridPlugin, timeGridPlugin, interactionPlugin],
    initialView: 'dayGridMonth',
    headerToolbar: {
        left: 'prev,today,next',
        center: 'title',
        right: 'dayGridMonth,timeGridWeek,timeGridDay'
    },
    dayMaxEvents: true,
    height: 'auto',
    events: filteredAppointments.value.map(apt => ({
        id: apt.id,
        title: apt.patient ? `${apt.patient.first_name} ${apt.patient.last_name}` : `${apt.guest_first_name} ${apt.guest_last_name}`,
        start: apt.appointment_date,
        extendedProps: apt,
        color: apt.dentist?.calendar_color || '#3b82f6'
    })),
    eventClick: (info) => {
        openDetail(info.event.extendedProps);
    },
    dateClick: (info) => {
        const dateStr = info.dateStr;
        const dayAppointments = filteredAppointments.value.filter(apt => 
            new Date(apt.appointment_date).toISOString().split('T')[0] === dateStr
        );
        
        if (dayAppointments.length > 0) {
            selectedDayData.value = dayAppointments;
            selectedDateString.value = new Date(dateStr).toLocaleDateString('en-US', { month: 'long', day: 'numeric', year: 'numeric' });
            showDayOverlay.value = true;
        }
    }
}));

const approveBooking = (id) => {
    if (confirm('Approve this online booking and register the patient?')) {
        router.post(`/appointments/${id}/approve`, {}, {
            onSuccess: () => {
                showDayOverlay.value = false;
            }
        });
    }
};

const getDayEventIcons = (date) => {
    const dateStr = date.toISOString().split('T')[0];
    const dayApts = filteredAppointments.value.filter(apt => 
        new Date(apt.appointment_date).toISOString().split('T')[0] === dateStr
    );
    return dayApts.map(apt => ({
        type: apt.type || 'appointment',
        color: apt.dentist?.calendar_color || '#3b82f6'
    }));
};
</script>

<template>
    <div class="bg-white p-6 rounded-lg shadow-sm border border-gray-100 relative h-full">
        <FullCalendar 
            :options="calendarOptions"
        >
            <template #dayCellContent="arg">
                <div class="flex flex-col h-full">
                    <div class="flex justify-between items-start mb-1 px-1 pt-1">
                        <span class="text-xs font-medium text-gray-500">{{ arg.dayNumberText }}</span>
                        <div v-if="getDayEventIcons(arg.date).length > 0" class="bg-red-500 text-white text-[10px] w-4 h-4 rounded-full flex items-center justify-center font-bold">
                            {{ getDayEventIcons(arg.date).length }}
                        </div>
                    </div>
                    <div class="flex flex-wrap gap-0.5 px-1 mt-auto pb-1">
                        <span 
                            v-for="(icon, idx) in getDayEventIcons(arg.date).slice(0, 10)" 
                            :key="idx"
                            class="text-[10px]"
                            :style="{ color: icon.color }"
                        >
                            {{ icon.type === 'appointment' || icon.type === 'online_booking' ? '🦷' : '👤' }}
                        </span>
                    </div>
                </div>
            </template>
        </FullCalendar>

        <!-- Day Detail Overlay -->
        <div v-if="showDayOverlay" class="fixed inset-0 bg-black/60 z-[100] flex items-center justify-center backdrop-blur-sm p-4 animate-in fade-in duration-200">
            <div class="bg-gray-800 w-full max-w-2xl rounded-xl shadow-2xl overflow-hidden text-white border border-gray-700">
                <div class="p-4 bg-gray-900 flex justify-between items-center border-b border-gray-700">
                    <h3 class="text-lg font-bold flex items-center">
                        <span class="mr-2">📅</span>
                        {{ selectedDateString }}
                    </h3>
                    <button @click="showDayOverlay = false" class="text-gray-400 hover:text-white text-2xl font-bold leading-none">&times;</button>
                </div>
                <div class="max-h-[70vh] overflow-y-auto p-2">
                    <div v-for="apt in selectedDayData" :key="apt.id" class="flex items-center p-3 rounded-lg hover:bg-gray-700/50 transition border-b border-gray-700/50 last:border-0 group">
                        <div class="mr-4">
                            <span class="text-xl" :style="{ color: apt.dentist?.calendar_color || '#3b82f6' }">🦷</span>
                        </div>
                        <div class="flex-1 grid grid-cols-1 md:grid-cols-4 gap-2 items-center cursor-pointer" @click="openDetail(apt)">
                            <div class="text-sm font-mono text-blue-400">
                                {{ new Date(apt.appointment_date).toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' }) }}
                            </div>
                            <div class="font-bold flex flex-col">
                                {{ apt.patient ? `${apt.patient.first_name} ${apt.patient.last_name}` : `${apt.guest_first_name} ${apt.guest_last_name}` }}
                                <span v-if="!apt.patient && apt.photo_path" class="text-[10px] text-green-400 font-normal">📸 Photo available</span>
                            </div>
                            <div class="text-sm text-gray-300 italic">
                                {{ apt.service || 'General' }}
                            </div>
                            <div class="text-sm text-gray-400">
                                [{{ apt.dentist ? apt.dentist.name : 'Unassigned' }}]
                            </div>
                        </div>
                        <div class="ml-4 flex gap-2">
                            <button 
                                v-if="canEdit"
                                @click.stop="openEdit(apt)"
                                class="p-2 hover:bg-gray-600 rounded-lg text-blue-400 transition"
                                title="Edit"
                            > 
                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" /></svg>
                            </button>
                            <button 
                                v-if="canDelete"
                                @click.stop="openDelete(apt)"
                                class="p-2 hover:bg-gray-600 rounded-lg text-red-400 transition"
                                title="Delete"
                            > 
                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" /></svg>
                            </button>
                            <button 
                                v-if="!apt.patient && (apt.type === 'online_booking' || !apt.patient_id)"
                                @click.stop="approveBooking(apt.id)"
                                class="bg-blue-600 hover:bg-blue-700 text-white text-[10px] px-3 py-1 rounded-full shadow-lg font-black uppercase tracking-widest"
                            > Approve </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Granular Component Modals -->
        <ShowAppointments 
            :appointment="selectedAppointment"
            :show="showDetailModal"
            @close="showDetailModal = false"
        />

        <EditAppointments 
            :appointment="selectedAppointment"
            :show="showEditModal"
            @close="showEditModal = false"
        />

        <DeleteAppointments 
            :appointment-id="selectedAppointment?.id"
            :show="showDeleteModal"
            @close="showDeleteModal = false"
        />
    </div>
</template>

<style scoped>
/* Scoped overrides for the nested component */
:deep(.fc-theme-standard td), :deep(.fc-theme-standard th) {
    border-color: #f3f4f6;
}
:deep(.fc-day-today) {
    background-color: #eff6ff !important;
}
:deep(.fc-col-header-cell) {
    background-color: #f9fafb;
    padding: 10px 0 !important;
}
:deep(.fc-toolbar-title) {
    font-size: 1.25rem !important;
    font-weight: 700 !important;
    color: #111827;
}
:deep(.fc-button-primary) {
    background-color: #ffffff !important;
    border-color: #d1d5db !important;
    color: #374151 !important;
    text-transform: capitalize !important;
    font-weight: 600 !important;
}
:deep(.fc-button-active) {
    background-color: #3b82f6 !important;
    border-color: #3b82f6 !important;
    color: white !important;
}
:deep(.fc-daygrid-day-number) {
    display: none;
}
:deep(.fc-daygrid-day-frame) {
    min-height: 100px;
}
</style>

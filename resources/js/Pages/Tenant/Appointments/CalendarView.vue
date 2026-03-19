<script setup>
import { ref, computed } from 'vue';
import FullCalendar from '@fullcalendar/vue3';
import dayGridPlugin from '@fullcalendar/daygrid';
import timeGridPlugin from '@fullcalendar/timegrid';
import interactionPlugin from '@fullcalendar/interaction';
import { router } from '@inertiajs/vue3';

const props = defineProps({
    appointments: Array,
    dentists: Array,
    selectedAssociates: Array,
    selectedTypes: Array
});

// Calendar Overlay State
const showDayOverlay = ref(false);
const selectedDayData = ref(null);
const selectedDateString = ref('');

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
        <div v-if="showDayOverlay" class="absolute inset-0 bg-black/60 z-[100] flex items-center justify-center backdrop-blur-sm p-4 animate-in fade-in duration-200">
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
                        <div class="flex-1 grid grid-cols-1 md:grid-cols-4 gap-2 items-center">
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
                        <div class="ml-4 opacity-0 group-hover:opacity-100 transition">
                            <button 
                                v-if="!apt.patient && (apt.type === 'online_booking' || !apt.patient_id)"
                                @click="approveBooking(apt.id)"
                                class="bg-blue-600 hover:bg-blue-700 text-white text-xs px-3 py-1 rounded-full shadow-lg"
                            > Approve </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
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

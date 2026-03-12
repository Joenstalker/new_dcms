<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head } from '@inertiajs/vue3';
import FullCalendar from '@fullcalendar/vue3';
import dayGridPlugin from '@fullcalendar/daygrid';
import timeGridPlugin from '@fullcalendar/timegrid';
import interactionPlugin from '@fullcalendar/interaction';
import googleCalendarPlugin from '@fullcalendar/google-calendar';
import { ref, onMounted } from 'vue';

const props = defineProps({
    staff: Array,
    api_key: String
});

const calendarOptions = ref({
    plugins: [dayGridPlugin, timeGridPlugin, interactionPlugin, googleCalendarPlugin],
    initialView: 'timeGridWeek',
    headerToolbar: {
        left: 'prev,next today',
        center: 'title',
        right: 'dayGridMonth,timeGridWeek,timeGridDay'
    },
    editable: true,
    selectable: true,
    selectMirror: true,
    dayMaxEvents: true,
    weekends: true,
    height: 'auto',
    googleCalendarApiKey: props.api_key,
    events: {
        googleCalendarId: 'primary' // This can be changed to a specific clinic calendar ID
    },
    eventClick: (info) => {
        // Handle event click
        console.log('Event clicked:', info.event);
    },
    select: (selectInfo) => {
        // Handle date selection
        console.log('Selection:', selectInfo);
    }
});

// If we wanted to add a specific Google Calendar ID, we could do:
// events: {
//   googleCalendarId: 'primary' // or a specific calendar ID
// }

</script>

<template>
    <Head title="Staff Schedules" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="text-xl font-semibold leading-tight text-gray-800">
                Staff Schedules & Calendar
            </h2>
        </template>

        <div class="py-12">
            <div class="mx-auto max-w-7xl sm:px-6 lg:px-8">
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                    <div class="mb-6 flex justify-between items-center">
                        <h3 class="text-lg font-medium text-gray-900">Clinic Schedule</h3>
                        <div class="flex space-x-2">
                             <select class="rounded-md border-gray-300 shadow-sm text-sm">
                                <option value="">All Staff</option>
                                <option v-for="member in staff" :key="member.id" :value="member.id">
                                    {{ member.name }}
                                </option>
                            </select>
                            <button class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 transition text-sm">
                                Add Shift
                            </button>
                        </div>
                    </div>

                    <div class="calendar-container">
                        <FullCalendar :options="calendarOptions" />
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>

<style scoped>
.calendar-container {
    background: white;
    padding: 20px;
    border-radius: 8px;
}

:deep(.fc-header-toolbar) {
    margin-bottom: 1.5rem !important;
}

:deep(.fc-button-primary) {
    background-color: #2563eb !important;
    border-color: #2563eb !important;
}

:deep(.fc-button-primary:hover) {
    background-color: #1d4ed8 !important;
    border-color: #1d4ed8 !important;
}

:deep(.fc-button-active) {
    background-color: #1e40af !important;
    border-color: #1e40af !important;
}
</style>

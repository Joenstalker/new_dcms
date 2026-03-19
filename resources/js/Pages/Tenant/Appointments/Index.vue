<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link } from '@inertiajs/vue3';
import { ref } from 'vue';
import CalendarView from './CalendarView.vue';
import ListView from './ListView.vue';
import NewAppointmentModal from './NewAppointmentModal.vue';

const props = defineProps({
    appointments: {
        type: Array,
        default: () => []
    },
    dentists: {
        type: Array, 
        default: () => []
    },
    patients: Array,
});

// View state
const activeView = ref('calendar'); // 'calendar' or 'list'

// Shared Filters
const selectedAssociates = ref(props.dentists.map(d => d.id));
const selectedTypes = ref(['appointment', 'recall', 'birthday', 'event', 'online_booking']);

// Modal State
const showNewAppointmentModal = ref(false);

const toggleAssociate = (id) => {
    const index = selectedAssociates.value.indexOf(id);
    if (index > -1) selectedAssociates.value.splice(index, 1);
    else selectedAssociates.value.push(id);
};

const toggleType = (type) => {
    const index = selectedTypes.value.indexOf(type);
    if (index > -1) selectedTypes.value.splice(index, 1);
    else selectedTypes.value.push(type);
};
</script>

<template>
    <Head title="Appointments" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
                <div>
                    <h2 class="text-3xl font-bold text-gray-800 flex items-center gap-2">
                        Clinic Appointments <span class="text-sm font-medium text-blue-500 bg-blue-50 px-2 py-0.5 rounded">Manage</span>
                    </h2>
                    <p class="text-sm text-gray-500">View and schedule clinic appointments.</p>
                </div>
                
                <div class="flex items-center gap-3">
                    <div class="bg-white p-1 rounded-lg shadow-sm border border-gray-100 flex">
                        <button 
                            @click="activeView = 'calendar'"
                            :class="['px-4 py-1.5 text-sm font-bold rounded-md transition-all', activeView === 'calendar' ? 'bg-[#007b7b] text-white shadow-md' : 'text-gray-500 hover:bg-gray-50']"
                        >
                            Calendar
                        </button>
                        <button 
                            @click="activeView = 'list'"
                            :class="['px-4 py-1.5 text-sm font-bold rounded-md transition-all', activeView === 'list' ? 'bg-[#007b7b] text-white shadow-md' : 'text-gray-500 hover:bg-gray-50']"
                        >
                            List View
                        </button>
                    </div>
                </div>

                <div class="flex items-center gap-2">
                    <button class="bg-[#00ced1] text-white px-4 py-2 rounded-lg text-sm font-bold shadow-sm hover:opacity-90 transition flex items-center gap-2">
                        <span>📅</span> Events/Schedules
                    </button>
                    <button 
                        @click="showNewAppointmentModal = true"
                        class="bg-[#00ced1] text-white px-4 py-2 rounded-lg text-sm font-bold shadow-sm hover:opacity-90 transition flex items-center gap-2"
                    >
                        <span class="text-xl">+</span> New Appointment
                    </button>
                </div>
            </div>
        </template>

        <div class="flex flex-col lg:flex-row gap-8 mt-8 min-h-[700px]">
            <!-- Sidebar Filters (Shared) -->
            <div class="w-full lg:w-64 space-y-8 bg-white p-6 rounded-2xl shadow-sm border border-gray-100 self-start">
                <!-- Associates Section -->
                <div>
                    <div class="flex justify-between items-center mb-4">
                        <h3 class="text-sm font-bold text-gray-800 uppercase tracking-wider flex items-center">
                            Associates <span class="ml-2 text-gray-400">︿</span>
                        </h3>
                        <!-- Optional mini toggle from image -->
                        <div class="w-8 h-4 bg-teal-600 rounded-full relative p-0.5 cursor-pointer">
                            <div class="w-3 h-3 bg-white rounded-full translate-x-4 transition"></div>
                        </div>
                    </div>
                    <div class="space-y-3">
                        <label v-for="dentist in dentists" :key="dentist.id" class="flex items-center space-x-3 cursor-pointer group">
                            <input 
                                type="checkbox" 
                                :checked="selectedAssociates.includes(dentist.id)"
                                @change="toggleAssociate(dentist.id)"
                                class="rounded border-gray-300 text-purple-600 focus:ring-purple-500 w-4 h-4"
                            >
                            <span class="text-sm font-bold transition" :style="{ color: dentist.calendar_color || '#3b82f6' }">
                                {{ dentist.name }}
                            </span>
                        </label>
                    </div>
                </div>

                <!-- Type Section -->
                <div>
                     <h3 class="text-sm font-bold text-gray-800 uppercase tracking-wider mb-4 flex items-center">
                        Type <span class="ml-2 text-gray-400">︿</span>
                    </h3>
                    <div class="space-y-3">
                        <label v-for="type in [
                            { id: 'appointment', label: 'Appointments', icon: '🦷' },
                            { id: 'recall', label: 'Recalls', icon: '🕒' },
                            { id: 'birthday', label: 'Birthdays', icon: '🎂' },
                            { id: 'event', label: 'Events/Schedules', icon: '📅' },
                            { id: 'online_booking', label: 'Online Bookings', icon: '🖱️' }
                        ]" :key="type.id" class="flex items-center space-x-3 cursor-pointer group">
                            <input 
                                type="checkbox" 
                                :checked="selectedTypes.includes(type.id)"
                                @change="toggleType(type.id)"
                                class="rounded border-gray-300 text-teal-600 focus:ring-teal-500 w-4 h-4"
                            >
                            <span class="text-sm">{{ type.icon }}</span>
                            <span class="text-sm font-medium text-gray-500 group-hover:text-teal-600 transition">{{ type.label }}</span>
                        </label>
                    </div>
                </div>
            </div>

            <!-- View Content -->
            <div class="flex-1">
                <CalendarView 
                    v-if="activeView === 'calendar'"
                    :appointments="appointments"
                    :dentists="dentists"
                    :selectedAssociates="selectedAssociates"
                    :selectedTypes="selectedTypes"
                />
                <ListView 
                    v-else
                    :appointments="appointments"
                    :selectedAssociates="selectedAssociates"
                    :selectedTypes="selectedTypes"
                />
            </div>
        </div>

        <NewAppointmentModal 
            :show="showNewAppointmentModal"
            :patients="patients"
            :dentists="dentists"
            @close="showNewAppointmentModal = false"
        />
    </AuthenticatedLayout>
</template>

<style>
/* FullCalendar Overrides to match the dark detail view and premium feel */
.fc-theme-standard td, .fc-theme-standard th {
    border-color: #f3f4f6;
}
.fc-day-today {
    background-color: #eff6ff !important;
}
.fc-col-header-cell {
    background-color: #f9fafb;
    padding: 10px 0 !important;
}
.fc-toolbar-title {
    font-size: 1.25rem !important;
    font-weight: 700 !important;
    color: #111827;
}
.fc-button-primary {
    background-color: #ffffff !important;
    border-color: #d1d5db !important;
    color: #374151 !important;
    text-transform: capitalize !important;
    font-weight: 600 !important;
    box-shadow: 0 1px 2px 0 rgba(0, 0, 0, 0.05) !important;
}
.fc-button-active {
    background-color: #3b82f6 !important;
    border-color: #3b82f6 !important;
    color: white !important;
}
.fc-event {
    cursor: pointer;
    border-radius: 4px;
    border: none;
    padding: 1px 4px;
}
.fc-daygrid-day-number {
    display: none; /* We handle this in dayCellContent */
}
.fc-daygrid-day-frame {
    min-height: 100px;
}
</style>

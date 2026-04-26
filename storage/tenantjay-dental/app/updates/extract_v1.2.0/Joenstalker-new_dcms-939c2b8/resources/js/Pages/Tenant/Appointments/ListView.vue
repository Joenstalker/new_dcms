<script setup>
import { ref, computed } from 'vue';
import { usePage } from '@inertiajs/vue3';
import ShowAppointments from './ShowAppointments.vue';
import EditAppointments from './EditAppointments.vue';
import DeleteAppointments from './DeleteAppointments.vue';

const props = defineProps({
    appointments: {
        type: Array,
        default: () => []
    },
    selectedAssociates: Array,
    selectedTypes: Array
});

const permissions = computed(() => usePage().props.auth.user.permissions);
const canEdit = computed(() => permissions.value.includes('edit appointments'));
const canDelete = computed(() => permissions.value.includes('delete appointments'));

const searchQuery = ref('');
const dateFilter = ref('this-month'); // Default as per image style

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
        const matchesSearch = !searchQuery.value || 
            (apt.patient?.first_name + ' ' + apt.patient?.last_name).toLowerCase().includes(searchQuery.value.toLowerCase()) ||
            (apt.guest_first_name + ' ' + apt.guest_last_name).toLowerCase().includes(searchQuery.value.toLowerCase());
        
        // Basic date filtering logic (simplified for now)
        const aptDate = new Date(apt.appointment_date);
        const now = new Date();
        let matchesDate = true;
        
        if (dateFilter.value === 'today') {
            matchesDate = aptDate.toDateString() === now.toDateString();
        } else if (dateFilter.value === 'this-week') {
            const startOfWeek = new Date(now.setDate(now.getDate() - now.getDay()));
            const endOfWeek = new Date(now.setDate(now.getDate() - now.getDay() + 6));
            matchesDate = aptDate >= startOfWeek && aptDate <= endOfWeek;
        }
        
        return matchesAssociate && matchesType && matchesSearch && matchesDate;
    });
});

const setDateFilter = (filter) => {
    dateFilter.value = filter;
};
</script>

<template>
    <div class="space-y-6">
        <!-- Date Range Filter Header -->
        <div class="bg-[#007b7b] text-white p-6 rounded-xl shadow-lg flex items-center space-x-4">
            <div class="p-3 bg-white/20 rounded-lg">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z" />
                </svg>
            </div>
            <div>
                <h3 class="text-xl font-bold">Date Range Filter</h3>
                <p class="text-blue-100 text-sm">Select a time period to view appointments</p>
            </div>
        </div>

        <!-- Quick Filters -->
        <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-6 gap-3">
            <button 
                v-for="filter in [
                    { id: 'today', label: 'Today', sub: 'Current day', icon: '📅', color: 'bg-red-500' },
                    { id: 'this-week', label: 'This Week', sub: 'Mon - Sun', icon: '🗓️', color: 'bg-teal-500' },
                    { id: 'this-month', label: 'This Month', sub: 'Current month', icon: '🗓️', color: 'bg-indigo-500' },
                    { id: 'last-week', label: 'Last Week', sub: 'Previous week', icon: '⬅️', color: 'bg-gray-400' },
                    { id: 'last-month', label: 'Last Month', sub: 'Previous month', icon: '⬅️', color: 'bg-gray-400' },
                    { id: 'next-month', label: 'Next Month', sub: 'Upcoming month', icon: '➡️', color: 'bg-gray-400' }
                ]" 
                :key="filter.id"
                @click="setDateFilter(filter.id)"
                :class="[
                    'p-4 rounded-xl border-2 transition-all flex flex-col items-center justify-center text-center group',
                    dateFilter === filter.id ? 'border-blue-500 bg-blue-50 shadow-md transform -translate-y-1' : 'border-gray-100 bg-white hover:border-blue-200'
                ]"
            >
                <div :class="['w-10 h-10 rounded-lg flex items-center justify-center text-white mb-2 shadow-sm', filter.color]">
                    {{ filter.icon }}
                </div>
                <span :class="['text-sm font-bold', dateFilter === filter.id ? 'text-blue-700' : 'text-gray-700']">{{ filter.label }}</span>
                <span class="text-[10px] text-gray-400">{{ filter.sub }}</span>
            </button>
        </div>

        <!-- Table Controls -->
        <div class="bg-white p-6 rounded-xl shadow-sm border border-gray-100">
            <div class="flex flex-col md:flex-row justify-between items-center mb-6 gap-4">
                <h4 class="text-lg font-bold text-gray-800 flex items-center">
                    List View - March 19, 2026 to March 19, 2026
                </h4>
                
                <div class="flex items-center gap-3 w-full md:w-auto">
                    <div class="relative flex-1 md:w-64">
                        <input 
                            v-model="searchQuery"
                            type="text" 
                            placeholder="Search here..." 
                            class="w-full pl-10 pr-4 py-2 bg-gray-50 border-gray-200 rounded-full text-sm focus:ring-blue-500 focus:border-blue-500"
                        >
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 absolute left-3 top-2.5 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                        </svg>
                    </div>
                    <button class="p-2 bg-blue-600 text-white rounded-full shadow-lg hover:bg-blue-700 transition">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4h13M3 8h9m-9 4h6m4 0l4-4m0 0l4 4m-4-4v12" />
                        </svg>
                    </button>
                    <div class="flex items-center bg-gray-50 px-4 py-2 rounded-full border border-gray-200 text-sm font-medium text-gray-600">
                        <span class="mr-2">📄</span>
                        {{ filteredAppointments.length }} Records
                    </div>
                </div>
            </div>

            <!-- Table -->
            <div class="overflow-x-auto min-h-[400px]">
                <table class="min-w-full">
                    <thead>
                        <tr class="bg-gray-50 text-gray-500 text-xs uppercase tracking-wider text-left border-b border-gray-100">
                            <th class="px-6 py-4 font-bold">Date & Time</th>
                            <th class="px-6 py-4 font-bold">Type</th>
                            <th class="px-6 py-4 font-bold">Patient</th>
                            <th class="px-6 py-4 font-bold">Associate</th>
                            <th class="px-6 py-4 font-bold">Reason</th>
                            <th class="px-6 py-4 font-bold text-center">Status</th>
                            <th class="px-6 py-4"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-50">
                        <tr v-for="apt in filteredAppointments" :key="apt.id" class="hover:bg-gray-50 transition group cursor-pointer" @click="openDetail(apt)">
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm font-bold text-gray-800">{{ new Date(apt.appointment_date).toLocaleDateString([], { month: 'short', day: 'numeric', year: 'numeric' }) }}</div>
                                <div class="text-xs text-gray-400">{{ new Date(apt.appointment_date).toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' }) }}</div>
                            </td>
                            <td class="px-6 py-4">
                                <span class="text-lg" :title="apt.type">{{ apt.type === 'appointment' ? '🦷' : '📅' }}</span>
                            </td>
                            <td class="px-6 py-4">
                                <div v-if="apt.patient" class="text-sm font-bold text-teal-600 hover:underline">
                                    {{ apt.patient.first_name }} {{ apt.patient.last_name }}
                                </div>
                                <div v-else class="text-sm font-bold text-gray-800">
                                    {{ apt.guest_first_name }} {{ apt.guest_last_name }}
                                    <span class="text-[10px] bg-gray-100 px-1 rounded ml-1 font-normal text-gray-500">Guest</span>
                                </div>
                            </td>
                            <td class="px-6 py-4">
                                <div class="text-xs text-gray-600 font-medium">{{ apt.dentist?.name || 'Unassigned' }}</div>
                            </td>
                            <td class="px-6 py-4">
                                <div class="text-xs text-gray-500 italic max-w-xs truncate">{{ apt.notes || 'No reason provided' }}</div>
                            </td>
                            <td class="px-6 py-4 text-center">
                                <span :class="[
                                    'px-3 py-1 rounded-full text-[10px] font-bold uppercase tracking-widest',
                                    apt.status === 'completed' ? 'bg-gray-200 text-gray-600' : 'bg-green-100 text-green-700'
                                ]">
                                    {{ apt.status === 'completed' ? 'Finished' : apt.status }}
                                </span>
                            </td>
                            <td class="px-6 py-4 text-right">
                                <div class="dropdown dropdown-left">
                                    <label tabindex="0" class="btn btn-ghost btn-xs text-gray-300 hover:text-gray-600 transition" @click.stop>
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                                            <path d="M10 6a2 2 0 110-4 2 2 0 010 4zM10 12a2 2 0 110-4 2 2 0 010 4zM10 18a2 2 0 110-4 2 2 0 010 4z" />
                                        </svg>
                                    </label>
                                    <ul tabindex="0" class="dropdown-content z-[10] menu p-2 shadow-xl bg-base-100 rounded-box w-40 border border-base-300 text-[10px] font-black uppercase tracking-widest">
                                        <li><a @click="openDetail(apt)">View Details</a></li>
                                        <li v-if="canEdit"><a @click="openEdit(apt)">Edit</a></li>
                                        <li v-if="canDelete"><a @click="openDelete(apt)" class="text-red-500">Delete</a></li>
                                    </ul>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
                <div v-if="filteredAppointments.length === 0" class="p-12 text-center text-gray-400">
                    <div class="text-4xl mb-2">🔍</div>
                    <p>No appointments found matching your filters.</p>
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

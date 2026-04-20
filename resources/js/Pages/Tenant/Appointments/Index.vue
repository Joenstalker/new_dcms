<script setup>
import { brandingState } from '@/States/brandingState';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head } from '@inertiajs/vue3';
import { ref, computed, onMounted, onUnmounted } from 'vue';
import { usePage } from '@inertiajs/vue3';
import CalendarView from './CalendarView.vue';
import ListView from './ListView.vue';
import BookingQueue from './BookingQueue.vue';
import WalkIn from './WalkIn.vue';
import NewAppointmentModal from './NewAppointmentModal.vue';
import CreateAppointments from './CreateAppointments.vue';
import Swal from 'sweetalert2';

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

const primaryColor = computed(() => brandingState.primary_color);
const page = usePage();
const tenantId = computed(() => page.props.tenant?.id || null);
const liveAppointments = ref([...(props.appointments || [])]);
let appointmentsChannel = null;

// Active tab from URL query
const activeTab = ref('calendar');

onMounted(() => {
    const params = new URLSearchParams(window.location.search);
    activeTab.value = params.get('tab') || 'calendar';

    if (!window.Echo || !tenantId.value) return;

    appointmentsChannel = window.Echo.private(`tenant.${tenantId.value}.appointments`)
        .listen('.OnlineBookingCreated', (event) => {
            const incoming = event?.appointment;
            if (!incoming || !incoming.id) return;

            const existingIndex = liveAppointments.value.findIndex((item) => item.id === incoming.id);
            if (existingIndex >= 0) {
                liveAppointments.value[existingIndex] = {
                    ...liveAppointments.value[existingIndex],
                    ...incoming,
                };
                return;
            }

            liveAppointments.value = [incoming, ...liveAppointments.value];

            const patientName = [incoming.guest_first_name, incoming.guest_last_name]
                .filter(Boolean)
                .join(' ')
                .trim() || 'New patient';

            Swal.fire({
                toast: true,
                position: 'top-end',
                icon: 'info',
                title: `New online booking: ${patientName}`,
                text: incoming.service ? `Service: ${incoming.service}` : 'A new booking was added to the queue.',
                showConfirmButton: false,
                timer: 3500,
                timerProgressBar: true,
            });
        })
        .listen('.TenantAppointmentChanged', (event) => {
            const incoming = event?.appointment;
            const action = event?.action;

            if (!incoming || !incoming.id) return;

            if (action === 'deleted') {
                liveAppointments.value = liveAppointments.value.filter((item) => item.id !== incoming.id);
                return;
            }

            const existingIndex = liveAppointments.value.findIndex((item) => item.id === incoming.id);

            if (existingIndex >= 0) {
                liveAppointments.value[existingIndex] = {
                    ...liveAppointments.value[existingIndex],
                    ...incoming,
                };
                return;
            }

            liveAppointments.value = [incoming, ...liveAppointments.value];
        });
});

onUnmounted(() => {
    if (window.Echo && tenantId.value) {
        window.Echo.leave(`tenant.${tenantId.value}.appointments`);
    }
    appointmentsChannel = null;
});

// Calendar sub-view state (calendar vs list)
const calendarSubView = ref('calendar');

// Shared Filters
const selectedAssociates = ref(props.dentists.map(d => d.id));
const selectedTypes = ref(['appointment', 'recall', 'birthday', 'event', 'online_booking']);

const permissions = computed(() => page.props.auth.user.permissions);
const canCreate = computed(() => permissions.value.includes('create appointments'));
const canView = computed(() => permissions.value.includes('view appointments'));

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
            <h2 class="text-xl font-black tracking-tight text-base-content">Appointments</h2>
        </template>

        <!-- Zero-Trust Access Board -->
        <div v-if="!canView" class="py-12">
            <div class="mx-auto max-w-7xl sm:px-6 lg:px-8">
                <div class="bg-base-100 p-12 rounded-3xl border border-dashed border-base-300 text-center shadow-2xl">
                    <div class="w-20 h-20 bg-amber-100 text-amber-600 rounded-2xl flex items-center justify-center mx-auto mb-6 shadow-lg shadow-amber-200/50">
                        <svg class="w-10 h-10" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                        </svg>
                    </div>
                    <h3 class="text-2xl font-black text-base-content uppercase tracking-tight">Access Restricted</h3>
                    <p class="text-xs font-bold text-base-content/40 mt-3 uppercase tracking-[0.2em] leading-relaxed max-w-md mx-auto">
                        You do not have the required permissions to view scheduling data. Please contact your clinic administrator.
                    </p>
                </div>
            </div>
        </div>

        <div v-else class="pb-12">
            <!-- Navigation -->
            <div class="mb-8 flex items-center space-x-1 bg-base-300/30 p-1.5 rounded-2xl w-fit border border-base-300">
                <button 
                    @click="activeTab = 'calendar'"
                    :class="['px-6 py-2.5 rounded-xl text-[10px] font-black uppercase tracking-[0.2em] transition-all duration-300', 
                        activeTab === 'calendar' ? 'bg-base-content text-base-100 shadow-xl' : 'text-base-content/50 hover:text-base-content']"
                >
                    Overview
                </button>
                <button 
                    @click="activeTab = 'queue'"
                    :class="['px-6 py-2.5 rounded-xl text-[10px] font-black uppercase tracking-[0.2em] transition-all duration-300', 
                        activeTab === 'queue' ? 'bg-base-content text-base-100 shadow-xl' : 'text-base-content/50 hover:text-base-content']"
                >
                    Booking Queue
                </button>
                <button 
                    v-if="canCreate"
                    @click="activeTab = 'walkin'"
                    :class="['px-6 py-2.5 rounded-xl text-[10px] font-black uppercase tracking-[0.2em] transition-all duration-300', 
                        activeTab === 'walkin' ? 'bg-base-content text-base-100 shadow-xl' : 'text-base-content/50 hover:text-base-content']"
                >
                    Walk-in
                </button>
            </div>

            <!-- Calendar View Tab -->
            <div v-if="activeTab === 'calendar'">
                <!-- Action Bar (below tab nav) -->
                <div class="flex items-center justify-between mb-6">
                    <div class="bg-base-200 p-1 rounded-xl flex">
                        <button 
                            @click="calendarSubView = 'calendar'"
                            :class="['px-3 py-1.5 text-[10px] font-black uppercase tracking-widest rounded-lg transition-all duration-300', 
                                calendarSubView === 'calendar' ? 'shadow-md text-white' : 'text-base-content/50 hover:text-base-content']"
                            :style="calendarSubView === 'calendar' ? { backgroundColor: primaryColor } : {}"
                        >
                            Calendar
                        </button>
                        <button 
                            @click="calendarSubView = 'list'"
                            :class="['px-3 py-1.5 text-[10px] font-black uppercase tracking-widest rounded-lg transition-all duration-300', 
                                calendarSubView === 'list' ? 'shadow-md text-white' : 'text-base-content/50 hover:text-base-content']"
                            :style="calendarSubView === 'list' ? { backgroundColor: primaryColor } : {}"
                        >
                            List
                        </button>
                    </div>

                    <button 
                        v-if="canCreate"
                        @click="showNewAppointmentModal = true"
                        class="text-white px-4 py-2 rounded-xl text-xs font-black uppercase tracking-wider shadow-lg hover:shadow-xl hover:scale-[1.02] transition-all duration-300 flex items-center gap-2"
                        :style="{ backgroundColor: primaryColor }"
                    >
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                        </svg>
                        New Appointment
                    </button>
                </div>

                <div class="flex flex-col lg:flex-row gap-6 min-h-[600px]">
                    <!-- Sidebar Filters -->
                    <div class="w-full lg:w-60 space-y-6 bg-base-100 p-5 rounded-2xl shadow-sm border border-base-300 self-start">
                        <!-- Associates Section -->
                        <div>
                            <h3 class="text-[10px] font-black text-base-content/30 uppercase tracking-[0.2em] mb-3">Associates</h3>
                            <div class="space-y-2.5">
                                <label v-for="dentist in dentists" :key="dentist.id" class="flex items-center space-x-3 cursor-pointer group">
                                    <input 
                                        type="checkbox" 
                                        :checked="selectedAssociates.includes(dentist.id)"
                                        @change="toggleAssociate(dentist.id)"
                                        class="checkbox checkbox-xs rounded"
                                        :style="{ borderColor: dentist.calendar_color || primaryColor }"
                                    >
                                    <span class="text-xs font-bold transition" :style="{ color: dentist.calendar_color || primaryColor }">
                                        {{ dentist.name }}
                                    </span>
                                </label>
                            </div>
                        </div>

                        <!-- Divider -->
                        <div class="border-t border-base-300"></div>

                        <!-- Type Section -->
                        <div>
                            <h3 class="text-[10px] font-black text-base-content/30 uppercase tracking-[0.2em] mb-3">Type</h3>
                            <div class="space-y-2.5">
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
                                        class="checkbox checkbox-xs rounded"
                                    >
                                    <span class="text-xs">{{ type.icon }}</span>
                                    <span class="text-xs font-medium text-base-content/50 group-hover:text-base-content transition">{{ type.label }}</span>
                                </label>
                            </div>
                        </div>
                    </div>

                    <!-- View Content -->
                    <div class="flex-1">
                        <CalendarView 
                            v-if="calendarSubView === 'calendar'"
                            :appointments="liveAppointments"
                            :dentists="dentists"
                            :selectedAssociates="selectedAssociates"
                            :selectedTypes="selectedTypes"
                        />
                        <ListView 
                            v-else
                            :appointments="liveAppointments"
                            :selectedAssociates="selectedAssociates"
                            :selectedTypes="selectedTypes"
                        />
                    </div>
                </div>
            </div>

            <!-- Booking Queue Tab -->
            <div v-else-if="activeTab === 'queue'">
                <BookingQueue 
                    :appointments="liveAppointments"
                    :dentists="dentists"
                />
            </div>

            <!-- Walk-in Tab -->
            <div v-else-if="activeTab === 'walkin'">
                <CreateAppointments 
                    :isWalkIn="true"
                    :dentists="dentists"
                    :patients="patients || []"
                />
            </div>

            <!-- Create Modal Component -->
            <CreateAppointments 
                :showModal="showNewAppointmentModal"
                :patients="patients"
                :dentists="dentists"
                @close="showNewAppointmentModal = false"
            />
        </div>
    </AuthenticatedLayout>
</template>

<style>
/* FullCalendar theme-aware overrides */
.fc-theme-standard td, .fc-theme-standard th {
    border-color: oklch(var(--bc) / 0.1);
}
.fc-day-today {
    background-color: oklch(var(--p) / 0.05) !important;
}
.fc-col-header-cell {
    background-color: oklch(var(--b2));
    padding: 10px 0 !important;
}
.fc-toolbar-title {
    font-size: 1.25rem !important;
    font-weight: 800 !important;
    color: oklch(var(--bc));
}
.fc-button-primary {
    background-color: oklch(var(--b1)) !important;
    border-color: oklch(var(--bc) / 0.15) !important;
    color: oklch(var(--bc)) !important;
    text-transform: capitalize !important;
    font-weight: 700 !important;
    box-shadow: 0 1px 2px 0 rgba(0, 0, 0, 0.05) !important;
}
.fc-button-active {
    background-color: oklch(var(--p)) !important;
    border-color: oklch(var(--p)) !important;
    color: oklch(var(--pc)) !important;
}
.fc-event {
    cursor: pointer;
    border-radius: 6px;
    border: none;
    padding: 1px 4px;
}
.fc-daygrid-day-frame {
    min-height: 100px;
}
</style>

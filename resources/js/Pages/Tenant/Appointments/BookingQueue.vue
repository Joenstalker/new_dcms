<script setup>
import { brandingState } from '@/States/brandingState';
import { computed, ref } from 'vue';
import { usePage, router } from '@inertiajs/vue3';
import Swal from 'sweetalert2';

const props = defineProps({
    appointments: { type: Array, default: () => [] },
    dentists: { type: Array, default: () => [] },
});

const primaryColor = computed(() => brandingState.primary_color);
const permissions = computed(() => usePage().props.auth.user.permissions);
const canEdit = computed(() => permissions.value.includes('edit appointments'));

// Filter only pending / queued appointments
const queuedAppointments = computed(() => 
    props.appointments.filter(a => ['pending', 'confirmed', 'queued', 'scheduled'].includes(a.status))
);

const formatTime = (dateString) => {
    if (!dateString) return '--:--';
    const date = new Date(dateString);
    return date.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
};

const selectedAppointment = ref(null);
const showDetailModal = ref(false);

const viewDetails = (appointment) => {
    selectedAppointment.value = appointment;
    showDetailModal.value = true;
};

const handleApprove = (id) => {
    Swal.fire({
        title: 'Approve Booking?',
        text: 'The patient will receive an acknowledgement email.',
        icon: 'question',
        showCancelButton: true,
        confirmButtonText: 'Yes, Approve',
        confirmButtonColor: primaryColor.value,
    }).then((result) => {
        if (result.isConfirmed) {
            router.post(route('appointments.approve', id));
        }
    });
};

const handleReject = (id) => {
    Swal.fire({
        title: 'Reject Booking?',
        text: 'The patient will receive a notification email.',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'Yes, Reject',
        confirmButtonColor: '#ef4444',
    }).then((result) => {
        if (result.isConfirmed) {
            router.post(route('appointments.reject', id));
        }
    });
};
</script>

<template>
    <div class="space-y-6">
        <!-- Queue Header -->
        <div class="flex items-center justify-between">
            <div>
                <h3 class="text-lg font-black text-base-content">Today's Queue</h3>
                <p class="text-xs text-base-content/40 mt-1">Patients waiting for their appointment</p>
            </div>
            <div class="badge badge-lg font-black" :style="{ backgroundColor: primaryColor, color: '#fff' }">
                {{ queuedAppointments.length }} in queue
            </div>
        </div>

        <!-- Queue List -->
        <div v-if="queuedAppointments.length > 0" class="space-y-3">
            <div 
                v-for="(appointment, index) in queuedAppointments" 
                :key="appointment.id"
                class="bg-base-100 rounded-2xl border border-base-300 p-5 flex items-center gap-5 hover:shadow-md transition-all duration-300 group"
            >
                <!-- Queue Number -->
                <div 
                    class="w-12 h-12 rounded-2xl flex items-center justify-center text-white font-black text-lg shrink-0 shadow-lg"
                    :style="{ backgroundColor: primaryColor }"
                >
                    {{ index + 1 }}
                </div>

                <!-- Patient Info -->
                <div class="flex-1 min-w-0 pointer-events-auto" @click="viewDetails(appointment)">
                    <p class="font-black text-sm text-base-content truncate cursor-pointer hover:underline">
                        <template v-if="appointment.patient">
                            {{ appointment.patient.first_name }} {{ appointment.patient.last_name }}
                        </template>
                        <template v-else>
                            {{ appointment.guest_first_name }} {{ appointment.guest_last_name }}
                        </template>
                    </p>
                    <p class="text-[10px] uppercase tracking-widest text-base-content/30 font-bold mt-0.5">
                        {{ appointment.service || 'General Checkup' }}
                    </p>
                </div>

                <!-- Time -->
                <div class="text-right shrink-0">
                    <p class="text-xs font-black text-base-content/60">
                        {{ formatTime(appointment.appointment_date) }}
                    </p>
                    <p class="text-[10px] text-base-content/20 uppercase tracking-widest font-bold">
                        {{ appointment.status || 'Pending' }}
                    </p>
                </div>

                <!-- Actions -->
                <div v-if="canEdit" class="flex gap-2 shrink-0 opacity-0 group-hover:opacity-100 transition-opacity">
                    <template v-if="appointment.status === 'pending'">
                        <button @click="handleApprove(appointment.id)" class="btn btn-sm btn-success text-white w-10 h-10 rounded-xl shadow-lg hover:scale-105 transition-transform" title="Approve">
                            <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5" />
                            </svg>
                        </button>
                        <button @click="handleReject(appointment.id)" class="btn btn-sm btn-error btn-outline w-10 h-10 rounded-xl shadow-lg hover:scale-105 transition-transform" title="Reject">
                            <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </template>
                    <template v-else>
                        <div class="badge badge-success text-[10px] uppercase font-black tracking-widest p-3 rounded-lg text-white">Accepted</div>
                        <button class="btn btn-sm btn-ghost btn-circle text-success" title="Check In">
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5" />
                            </svg>
                        </button>
                    </template>
                </div>
            </div>
        </div>

        <!-- Guest Detail Modal -->
        <input type="checkbox" id="detail-modal" class="modal-toggle" v-model="showDetailModal" />
        <div class="modal">
            <div class="modal-box rounded-3xl p-8 max-w-lg">
                <div class="flex items-center gap-4 mb-8">
                    <div class="w-14 h-14 rounded-2xl bg-base-200 flex items-center justify-center text-2xl">
                        👤
                    </div>
                    <div>
                        <h3 class="text-xl font-black text-base-content">
                            {{ selectedAppointment?.patient ? (selectedAppointment.patient.first_name + ' ' + selectedAppointment.patient.last_name) : (selectedAppointment?.guest_first_name + ' ' + selectedAppointment?.guest_last_name) }}
                        </h3>
                        <p class="text-[10px] uppercase font-black tracking-widest text-base-content/40">
                             Booking Details
                        </p>
                    </div>
                </div>

                <div class="space-y-6">
                    <div class="grid grid-cols-2 gap-6">
                        <div>
                            <p class="text-[10px] uppercase font-black tracking-widest text-base-content/30 mb-1">Phone</p>
                            <p class="text-xs font-bold">{{ selectedAppointment?.patient?.phone || selectedAppointment?.guest_phone || 'N/A' }}</p>
                        </div>
                        <div>
                            <p class="text-[10px] uppercase font-black tracking-widest text-base-content/30 mb-1">Email</p>
                            <p class="text-xs font-bold">{{ selectedAppointment?.patient?.email || selectedAppointment?.guest_email || 'N/A' }}</p>
                        </div>
                    </div>

                    <div>
                        <p class="text-[10px] uppercase font-black tracking-widest text-base-content/30 mb-1">Medical History</p>
                        <p class="text-xs font-bold leading-relaxed">
                            {{ selectedAppointment?.guest_medical_history?.length ? selectedAppointment.guest_medical_history.join(', ') : 'None reported' }}
                        </p>
                    </div>

                    <div>
                        <p class="text-[10px] uppercase font-black tracking-widest text-base-content/30 mb-1">Notes</p>
                        <p class="text-xs font-bold leading-relaxed italic text-base-content/60">
                            "{{ selectedAppointment?.notes || 'No notes provided' }}"
                        </p>
                    </div>
                </div>

                <div class="modal-action mt-10">
                    <label for="detail-modal" class="btn btn-ghost rounded-xl px-8">Close</label>
                    <button 
                        v-if="selectedAppointment?.status === 'pending'"
                        @click="handleApprove(selectedAppointment.id); showDetailModal = false" 
                        class="btn rounded-xl px-8 text-white border-none shadow-lg"
                        :style="{ backgroundColor: primaryColor }"
                    >
                        Approve Booking
                    </button>
                </div>
            </div>
            <label class="modal-backdrop" for="detail-modal">Close</label>
        </div>
    </div>
</template>

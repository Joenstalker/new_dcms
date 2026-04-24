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

// Active queue: keep only not-yet-accepted bookings and order FIFO.
const queuedAppointments = computed(() => 
    props.appointments
        .filter((a) => ['pending', 'confirmed', 'queued'].includes(String(a.status || '').toLowerCase()))
        .sort((a, b) => {
            const left = new Date(a.created_at || a.appointment_date || 0).getTime();
            const right = new Date(b.created_at || b.appointment_date || 0).getTime();

            if (left === right) {
                return Number(a.id || 0) - Number(b.id || 0);
            }

            return left - right;
        })
);

const formatTime = (dateString) => {
    if (!dateString) return '--:--';
    const date = new Date(dateString);
    return date.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
};

const resolvePhotoUrl = (path) => {
    if (!path) return null;
    if (String(path).startsWith('data:image')) return path;
    return `/tenant-storage/${String(path).replace(/^\/+/, '')}`;
};

const getAppointmentAvatar = (appointment) => {
    if (appointment?.patient?.photo_path) {
        return resolvePhotoUrl(appointment.patient.photo_path);
    }

    if (appointment?.patient?.photo_url) {
        return appointment.patient.photo_url;
    }

    if (appointment?.photo_path) {
        return resolvePhotoUrl(appointment.photo_path);
    }

    return null;
};

const isExistingPatientBooking = (appointment) => {
    return Boolean(appointment?.patient_id || appointment?.patient?.id);
};

const failedAvatarKeys = ref({});

const appointmentAvatarKey = (appointment) => `appointment-${appointment?.id ?? 'unknown'}`;
const selectedAvatarKey = computed(() => `selected-${selectedAppointment.value?.id ?? 'none'}`);

const isAvatarFailed = (key) => Boolean(failedAvatarKeys.value[key]);

const markAvatarFailed = (key) => {
    failedAvatarKeys.value = {
        ...failedAvatarKeys.value,
        [key]: true,
    };
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
        text: 'If an email is on file, the patient will receive an acknowledgement email.',
        icon: 'question',
        showCancelButton: true,
        confirmButtonText: 'Yes, Approve',
        confirmButtonColor: primaryColor.value,
    }).then((result) => {
        if (result.isConfirmed) {
            router.post(route('appointments.approve', id, false), {}, {
                preserveScroll: true,
                onError: () => {
                    Swal.fire({
                        icon: 'error',
                        title: 'Approval failed',
                        text: 'The booking could not be approved. Please refresh and try again.',
                    });
                },
            });
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
            router.post(route('appointments.reject', id, false), {}, {
                preserveScroll: true,
                onError: () => {
                    Swal.fire({
                        icon: 'error',
                        title: 'Rejection failed',
                        text: 'The booking could not be rejected. Please refresh and try again.',
                    });
                },
            });
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
                    class="h-12 min-w-12 px-3 rounded-xl border border-base-300 bg-base-200/70 flex items-center justify-center text-base-content font-black text-sm tracking-wide shrink-0"
                >
                    #{{ index + 1 }}
                </div>

                <!-- Patient Info -->
                <div class="flex-1 min-w-0 pointer-events-auto" @click="viewDetails(appointment)">
                    <div class="flex items-center gap-3">
                        <div class="w-11 h-11 rounded-full overflow-hidden bg-base-200 ring-2 ring-base-100 shrink-0 shadow-sm">
                            <img
                                v-if="getAppointmentAvatar(appointment) && !isAvatarFailed(appointmentAvatarKey(appointment))"
                                :src="getAppointmentAvatar(appointment)"
                                alt="Patient profile"
                                class="h-full w-full object-cover"
                                @error="markAvatarFailed(appointmentAvatarKey(appointment))"
                            >
                            <div v-else class="h-full w-full flex items-center justify-center text-base-content/25 bg-base-300">
                                <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24"><path d="M12 12c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm0 2c-2.67 0-8 1.34-8 4v2h16v-2c0-2.66-5.33-4-8-4z"/></svg>
                            </div>
                        </div>
                        <div class="min-w-0">
                            <p class="font-black text-sm text-base-content truncate cursor-pointer hover:underline">
                                <template v-if="appointment.patient">
                                    {{ appointment.patient.first_name }} {{ appointment.patient.last_name }}
                                </template>
                                <template v-else>
                                    {{ appointment.guest_first_name }} {{ appointment.guest_last_name }}
                                </template>
                            </p>
                            <div class="mt-1">
                                <span
                                    class="badge badge-xs font-black uppercase tracking-widest"
                                    :class="isExistingPatientBooking(appointment) ? 'badge-info' : 'badge-warning'"
                                >
                                    {{ isExistingPatientBooking(appointment) ? 'Existing' : 'New' }}
                                </span>
                            </div>
                            <p class="text-[10px] uppercase tracking-widest text-base-content/30 font-bold mt-0.5">
                                {{ appointment.service || 'General Checkup' }}
                            </p>
                        </div>
                    </div>
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
                <div v-if="canEdit" class="flex gap-2 shrink-0">
                    <template v-if="appointment.status === 'pending'">
                        <button @click="handleApprove(appointment.id)" class="btn btn-xs sm:btn-sm btn-success text-white w-8 h-8 sm:w-10 sm:h-10 rounded-lg sm:rounded-xl shadow-md sm:shadow-lg hover:scale-105 transition-transform" title="Approve">
                            <svg class="w-4 h-4 sm:w-6 sm:h-6" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5" />
                            </svg>
                        </button>
                        <button @click="handleReject(appointment.id)" class="btn btn-xs sm:btn-sm btn-error btn-outline w-8 h-8 sm:w-10 sm:h-10 rounded-lg sm:rounded-xl shadow-md sm:shadow-lg hover:scale-105 transition-transform" title="Reject">
                            <svg class="w-4 h-4 sm:w-6 sm:h-6" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </template>
                    <template v-else>
                        <div class="badge badge-success text-[9px] sm:text-[10px] uppercase font-black tracking-widest px-2 py-1 sm:p-3 rounded-lg text-white">Accepted</div>
                        <button class="btn btn-xs sm:btn-sm btn-ghost btn-circle text-success w-8 h-8 sm:w-10 sm:h-10" title="Check In">
                            <svg class="w-3.5 h-3.5 sm:w-4 sm:h-4" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor">
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
                    <div class="w-14 h-14 rounded-2xl overflow-hidden bg-base-200 flex items-center justify-center text-2xl">
                        <img
                            v-if="selectedAppointment && getAppointmentAvatar(selectedAppointment) && !isAvatarFailed(selectedAvatarKey)"
                            :src="getAppointmentAvatar(selectedAppointment)"
                            alt="Patient profile"
                            class="h-full w-full object-cover"
                            @error="markAvatarFailed(selectedAvatarKey)"
                        >
                        <span v-else>👤</span>
                    </div>
                    <div>
                        <h3 class="text-xl font-black text-base-content">
                            {{ selectedAppointment?.patient ? (selectedAppointment.patient.first_name + ' ' + selectedAppointment.patient.last_name) : (selectedAppointment?.guest_first_name + ' ' + selectedAppointment?.guest_last_name) }}
                        </h3>
                        <p class="text-[10px] uppercase font-black tracking-widest text-base-content/40">
                             Booking Details
                        </p>
                        <div class="mt-1">
                            <span
                                class="badge badge-xs font-black uppercase tracking-widest"
                                :class="isExistingPatientBooking(selectedAppointment) ? 'badge-info' : 'badge-warning'"
                            >
                                {{ isExistingPatientBooking(selectedAppointment) ? 'Existing' : 'New' }} Patient
                            </span>
                        </div>
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

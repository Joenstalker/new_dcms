<script setup>
import { computed } from 'vue';
import { usePage } from '@inertiajs/vue3';

const props = defineProps({
    appointments: { type: Array, default: () => [] },
    dentists: { type: Array, default: () => [] },
});

const branding = computed(() => usePage().props.branding || {});
const primaryColor = computed(() => branding.value.primary_color || '#0ea5e9');
const permissions = computed(() => usePage().props.auth.user.permissions);
const canEdit = computed(() => permissions.value.includes('edit appointments'));

// Filter only pending / queued appointments
const queuedAppointments = computed(() => 
    props.appointments.filter(a => ['pending', 'confirmed', 'queued'].includes(a.status))
);
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
                <div class="flex-1 min-w-0">
                    <p class="font-black text-sm text-base-content truncate">
                        {{ appointment.patient?.name || 'Unknown Patient' }}
                    </p>
                    <p class="text-[10px] uppercase tracking-widest text-base-content/30 font-bold mt-0.5">
                        {{ appointment.service?.name || 'General Checkup' }}
                    </p>
                </div>

                <!-- Time -->
                <div class="text-right shrink-0">
                    <p class="text-xs font-black text-base-content/60">
                        {{ appointment.scheduled_time || '--:--' }}
                    </p>
                    <p class="text-[10px] text-base-content/20 uppercase tracking-widest font-bold">
                        {{ appointment.status || 'Pending' }}
                    </p>
                </div>

                <!-- Actions -->
                <div v-if="canEdit" class="flex gap-2 shrink-0 opacity-0 group-hover:opacity-100 transition-opacity">
                    <button class="btn btn-sm btn-ghost btn-circle text-success" title="Check In">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5" />
                        </svg>
                    </button>
                    <button class="btn btn-sm btn-ghost btn-circle text-error" title="Cancel">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>
            </div>
        </div>

        <!-- Empty State -->
        <div v-else class="bg-base-100 rounded-2xl border border-base-300 p-16 text-center">
            <div class="w-16 h-16 mx-auto mb-4 rounded-2xl bg-base-200 flex items-center justify-center">
                <svg class="w-8 h-8 text-base-content/20" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 12h16.5m-16.5 3.75h16.5M3.75 19.5h16.5M5.625 4.5h12.75a1.875 1.875 0 0 1 0 3.75H5.625a1.875 1.875 0 0 1 0-3.75Z" />
                </svg>
            </div>
            <h3 class="text-sm font-black text-base-content/40 uppercase tracking-widest">No patients in queue</h3>
            <p class="text-xs text-base-content/20 mt-2">Patients will appear here when they check in for their appointments.</p>
        </div>
    </div>
</template>

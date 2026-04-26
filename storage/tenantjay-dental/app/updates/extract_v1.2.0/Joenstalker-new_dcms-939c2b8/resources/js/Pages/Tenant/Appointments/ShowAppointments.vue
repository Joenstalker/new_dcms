<script setup>
import { computed } from 'vue';
import { usePage } from '@inertiajs/vue3';

const props = defineProps({
    appointment: { type: Object, default: null },
    show: { type: Boolean, default: false }
});

const emit = defineEmits(['close']);

const permissions = computed(() => usePage().props.auth.user.permissions);
const canView = computed(() => permissions.value.includes('view appointments'));
</script>

<template>
    <div v-if="canView && show">
        <div class="fixed inset-0 z-[150] flex items-center justify-center p-4 bg-black/40 backdrop-blur-sm animate-in fade-in duration-200" role="dialog" aria-modal="true" aria-labelledby="appointment-details-title">
            <div class="bg-base-100 w-full max-w-lg rounded-2xl shadow-2xl overflow-hidden border border-base-300">
                <div class="p-4 bg-base-900 text-white flex justify-between items-center">
                    <h3 id="appointment-details-title" class="text-sm font-black uppercase tracking-[0.16em] flex items-center">
                        <span class="mr-2.5">📅</span>
                        Appointment Details
                    </h3>
                    <button @click="$emit('close')" class="p-2 text-white/50 hover:text-white text-2xl font-bold leading-none transition-colors">&times;</button>
                </div>
                <div class="p-8">
                    <div class="flex items-center gap-5 mb-8">
                        <div class="w-14 h-14 rounded-2xl bg-base-200 flex items-center justify-center text-2xl shadow-inner border border-base-300">
                            🦷
                        </div>
                        <div>
                            <h4 class="text-lg font-black text-base-content leading-tight uppercase tracking-tight">
                                {{ appointment?.patient ? `${appointment.patient.first_name} ${appointment.patient.last_name}` : `${appointment?.guest_first_name} ${appointment?.guest_last_name}` }}
                            </h4>
                            <p class="text-xs font-black text-base-content/30 uppercase tracking-[0.16em] mt-1">Status: {{ appointment?.status }}</p>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div class="bg-base-200/50 p-4 rounded-xl border border-base-300">
                            <span class="text-xs font-black text-base-content/40 uppercase tracking-[0.16em] block mb-1">Date & Time</span>
                            <span class="text-sm font-black text-base-content uppercase tracking-[0.1em]">{{ new Date(appointment?.appointment_date).toLocaleString() }}</span>
                        </div>
                        <div class="bg-base-200/50 p-4 rounded-xl border border-base-300">
                            <span class="text-xs font-black text-base-content/40 uppercase tracking-[0.16em] block mb-1">Service</span>
                            <span class="text-sm font-black text-base-content uppercase tracking-[0.1em]">{{ appointment?.service || 'General' }}</span>
                        </div>
                        <div class="bg-base-200/50 p-4 rounded-xl border border-base-300 col-span-2">
                            <span class="text-xs font-black text-base-content/40 uppercase tracking-[0.16em] block mb-1">Clinic Associate</span>
                            <span class="text-sm font-black text-base-content uppercase tracking-[0.1em]">{{ appointment?.dentist?.name || 'Unassigned' }}</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

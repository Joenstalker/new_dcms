<script setup>
import { brandingState } from '@/States/brandingState';
import { ref, computed } from 'vue';

const props = defineProps({
    patient: { type: Object, required: true }
});

const primaryColor = computed(() => brandingState.primary_color);

const formatDate = (dateString) => {
    if (!dateString) return 'N/A';
    const date = new Date(dateString);
    return date.toLocaleDateString('en-US', {
        year: 'numeric', month: 'short', day: 'numeric',
        hour: '2-digit', minute: '2-digit'
    });
};

const getStatusClass = (status) => {
    switch (status) {
        case 'scheduled': return 'badge-info';
        case 'completed': return 'badge-success';
        case 'cancelled': return 'badge-error';
        case 'pending': return 'badge-warning';
        default: return 'badge-ghost';
    }
};
</script>

<template>
    <div class="space-y-4">
        <div class="flex items-center justify-between border-b border-base-200 pb-2">
            <h4 class="text-sm font-black text-base-content/40 uppercase tracking-widest">Appointment History</h4>
        </div>
        
        <div v-if="patient.appointments && patient.appointments.length > 0" class="space-y-3">
            <div v-for="appointment in patient.appointments" :key="appointment.id" class="p-4 bg-base-100 rounded-2xl border border-base-200 hover:border-primary/20 transition-all flex justify-between items-center shadow-sm">
                <div class="flex gap-4">
                    <div class="w-10 h-10 rounded-xl bg-base-200 flex items-center justify-center shrink-0">
                        <svg class="w-5 h-5 text-base-content/30" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" /></svg>
                    </div>
                    <div>
                        <p class="text-sm font-black text-base-content uppercase leading-none">{{ appointment.service || 'General Consultation' }}</p>
                        <p class="text-[10px] font-bold text-base-content/40 mt-1 uppercase tracking-widst">{{ formatDate(appointment.appointment_date) }}</p>
                    </div>
                </div>
                <div class="badge badge-sm font-black text-[9px] uppercase tracking-widest px-2 h-5 py-0 border-0" :class="getStatusClass(appointment.status)">
                    {{ appointment.status }}
                </div>
            </div>
        </div>
        <div v-else class="text-center py-16">
            <div class="w-16 h-16 bg-base-200 rounded-3xl mx-auto flex items-center justify-center mb-4">
                <svg class="w-8 h-8 text-base-content/10" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" /></svg>
            </div>
            <p class="text-xs font-bold text-base-content/30 uppercase tracking-widest">No appointments found.</p>
        </div>
    </div>
</template>

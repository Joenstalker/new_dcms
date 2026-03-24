<script setup>
import { computed } from 'vue';
import { usePage } from '@inertiajs/vue3';

defineProps({
    patient: { type: Object, required: true }
});

const branding = computed(() => usePage().props.branding || {});
const primaryColor = computed(() => branding.value.primary_color || '#0ea5e9');

const formatDate = (dateString) => {
    if (!dateString) return 'N/A';
    return new Date(dateString).toLocaleDateString('en-US', {
        year: 'numeric', month: 'short', day: 'numeric'
    });
};
</script>

<template>
    <div class="space-y-4">
        <h4 class="text-sm font-black text-base-content/40 uppercase tracking-widest border-b border-base-200 pb-2">Treatment Records</h4>
        <div v-if="patient.treatments && patient.treatments.length > 0" class="space-y-3">
            <div v-for="treatment in patient.treatments" :key="treatment.id" class="p-4 bg-base-200/50 rounded-xl border border-base-200 flex justify-between items-center">
                <div>
                    <p class="font-bold">{{ treatment.treatment_type }}</p>
                    <p class="text-xs text-base-content/60 mt-1">{{ formatDate(treatment.treatment_date) }}</p>
                </div>
                <p class="font-black" :style="{ color: primaryColor }">₱{{ Number(treatment.cost || 0).toFixed(2) }}</p>
            </div>
        </div>
        <div v-else class="text-center py-12 text-base-content/40 text-sm font-medium">
            No treatments recorded yet.
        </div>
    </div>
</template>

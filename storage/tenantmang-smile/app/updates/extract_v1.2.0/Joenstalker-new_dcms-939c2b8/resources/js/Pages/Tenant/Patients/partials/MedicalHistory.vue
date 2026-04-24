<script setup>
defineProps({
    patient: { type: Object, required: true }
});
</script>

<template>
    <div class="space-y-6 max-w-3xl">
        <h4 class="text-sm font-black text-base-content/40 uppercase tracking-widest border-b border-base-200 pb-2">Medical History Overview</h4>
        <div class="bg-base-200/50 p-6 rounded-2xl text-sm leading-relaxed whitespace-pre-wrap">
            {{ patient.medical_history || 'No medical history recorded.' }}
        </div>
        
        <h4 class="text-sm font-black text-base-content/40 uppercase tracking-widest border-b border-base-200 pb-2 mt-8">Operation History</h4>
        <div class="bg-base-200/50 p-6 rounded-2xl text-sm leading-relaxed whitespace-pre-wrap">
            {{ patient.operation_history || 'No operation history recorded.' }}
        </div>
    </div>
</template>

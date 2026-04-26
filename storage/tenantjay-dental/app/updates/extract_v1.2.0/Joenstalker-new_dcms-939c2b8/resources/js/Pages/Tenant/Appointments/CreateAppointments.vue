<script setup>
import { computed } from 'vue';
import { usePage } from '@inertiajs/vue3';
import NewAppointmentModal from './NewAppointmentModal.vue';
import WalkIn from './WalkIn.vue';

const props = defineProps({
    showModal: { type: Boolean, default: false },
    isWalkIn: { type: Boolean, default: false },
    patients: Array,
    dentists: Array
});

const emit = defineEmits(['close']);

const permissions = computed(() => usePage().props.auth.user.permissions);
const canCreate = computed(() => permissions.value.includes('create appointments'));
</script>

<template>
    <div v-if="canCreate">
        <NewAppointmentModal 
            v-if="showModal"
            :show="showModal"
            :patients="patients"
            :dentists="dentists"
            @close="$emit('close')"
        />
        
        <WalkIn 
            v-if="isWalkIn"
            :patients="patients"
            :dentists="dentists"
        />
    </div>
    <div v-else-if="showModal || isWalkIn" class="p-8 text-center bg-base-200/50 rounded-2xl border border-dashed border-base-300">
        <div class="w-16 h-16 bg-red-100 text-red-600 rounded-2xl flex items-center justify-center mx-auto mb-4 shadow-sm">
            <svg class="w-8 h-8" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 15v2m0-6V9m4.938-4.938L18.878 6.122m-12.756 0l1.94-1.94m-2.122 13.844l1.94 1.94m12.756 0l-1.94-1.94M12 2a10 10 0 110 20 10 10 0 010-20z" />
            </svg>
        </div>
        <h3 class="text-sm font-black uppercase tracking-widest text-base-content">Permission Required</h3>
        <p class="text-[10px] font-bold text-base-content/40 mt-1 uppercase tracking-widest">You do not have permission to create appointments.</p>
    </div>
</template>

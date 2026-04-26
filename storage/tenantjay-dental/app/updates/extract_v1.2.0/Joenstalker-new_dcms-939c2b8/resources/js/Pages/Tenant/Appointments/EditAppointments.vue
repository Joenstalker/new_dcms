<script setup>
import { computed } from 'vue';
import { usePage } from '@inertiajs/vue3';

const props = defineProps({
    appointment: { type: Object, default: null },
    show: { type: Boolean, default: false }
});

const emit = defineEmits(['close', 'success']);

const permissions = computed(() => usePage().props.auth.user.permissions);
const canEdit = computed(() => permissions.value.includes('edit appointments'));
</script>

<template>
    <div v-if="canEdit && show">
        <div class="fixed inset-0 z-[200] flex items-center justify-center p-4 bg-black/60 backdrop-blur-sm animate-in fade-in duration-200">
            <div class="bg-base-100 w-full max-w-xl rounded-2xl shadow-2xl overflow-hidden border border-base-300">
                <div class="p-4 bg-base-200 flex justify-between items-center border-b border-base-300">
                    <h3 class="text-sm font-black uppercase tracking-widest text-base-content flex items-center">
                        <span class="mr-2">🔧</span>
                        Edit Appointment #{{ appointment?.id }}
                    </h3>
                    <button @click="$emit('close')" class="text-base-content/50 hover:text-base-content text-2xl font-bold leading-none">&times;</button>
                </div>
                <div class="p-6">
                    <!-- Placeholder for Edit Form logic -->
                    <div class="p-8 text-center text-base-content/40 italic text-xs uppercase tracking-widest font-black">
                        Appointment editing form goes here...
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div v-else-if="show" class="fixed inset-0 z-[200] flex items-center justify-center p-4 bg-black/60 backdrop-blur-sm">
        <div class="bg-base-100 p-8 rounded-2xl shadow-2xl border border-base-300 text-center">
            <div class="w-16 h-16 bg-amber-100 text-amber-600 rounded-2xl flex items-center justify-center mx-auto mb-4 shadow-sm">
                <svg class="w-8 h-8" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                </svg>
            </div>
            <h3 class="text-sm font-black uppercase tracking-widest text-base-content">Permission Required</h3>
            <p class="text-[10px] font-bold text-base-content/40 mt-1 uppercase tracking-widest">You do not have permission to edit appointments.</p>
            <button @click="$emit('close')" class="btn btn-sm btn-ghost mt-6 text-[10px] font-black uppercase tracking-widest">Close</button>
        </div>
    </div>
</template>

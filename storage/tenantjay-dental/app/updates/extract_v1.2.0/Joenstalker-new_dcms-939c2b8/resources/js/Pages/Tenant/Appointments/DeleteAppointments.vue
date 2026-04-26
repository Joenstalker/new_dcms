<script setup>
import { computed } from 'vue';
import { usePage, router } from '@inertiajs/vue3';

const props = defineProps({
    appointmentId: { type: [Number, String], required: true },
    show: { type: Boolean, default: false }
});

const emit = defineEmits(['close', 'deleted']);

const permissions = computed(() => usePage().props.auth.user.permissions);
const canDelete = computed(() => permissions.value.includes('delete appointments'));

const handleDelete = () => {
    router.delete(`/appointments/${props.appointmentId}`, {
        onSuccess: () => {
            emit('deleted');
            emit('close');
        }
    });
};
</script>

<template>
    <div v-if="canDelete && show">
        <div class="fixed inset-0 z-[200] flex items-center justify-center p-4 bg-black/60 backdrop-blur-sm animate-in fade-in duration-200">
            <div class="bg-base-100 p-8 rounded-2xl shadow-2xl border border-red-200 text-center max-w-sm">
                <div class="w-16 h-16 bg-red-100 text-red-600 rounded-2xl flex items-center justify-center mx-auto mb-4 shadow-sm">
                    <svg class="w-8 h-8" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                    </svg>
                </div>
                <h3 class="text-sm font-black uppercase tracking-widest text-base-content">Delete Appointment?</h3>
                <p class="text-[10px] font-bold text-base-content/40 mt-3 uppercase tracking-widest leading-relaxed">This action is permanent and will notify the patient if notifications are enabled.</p>
                
                <div class="flex gap-4 mt-8">
                    <button @click="$emit('close')" class="flex-1 btn btn-sm btn-ghost rounded-xl text-[10px] uppercase font-black tracking-widest">Cancel</button>
                    <button @click="handleDelete" class="flex-1 btn btn-sm btn-error rounded-xl text-[10px] uppercase font-black tracking-widest text-white">Delete</button>
                </div>
            </div>
        </div>
    </div>
    <div v-else-if="show" class="fixed inset-0 z-[200] flex items-center justify-center p-4 bg-black/60 backdrop-blur-sm">
        <div class="bg-base-100 p-8 rounded-2xl shadow-2xl border border-base-300 text-center">
            <div class="w-16 h-16 bg-red-100 text-red-600 rounded-2xl flex items-center justify-center mx-auto mb-4 shadow-sm">
                <svg class="w-8 h-8" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 2a10 10 0 110 20 10 10 0 010-20zm0 13v2m0-6V9m4.938-4.938L18.878 6.122m-12.756 0l1.94-1.94m-2.122 13.844l1.94 1.94m12.756 0l-1.94-1.94M12 2a10 10 0 110 20 10 10 0 010-20z" />
                </svg>
            </div>
            <h3 class="text-sm font-black uppercase tracking-widest text-base-content">Permission Required</h3>
            <p class="text-[10px] font-bold text-base-content/40 mt-1 uppercase tracking-widest">You do not have permission to delete appointments.</p>
            <button @click="$emit('close')" class="btn btn-sm btn-ghost mt-6 text-[10px] font-black uppercase tracking-widest text-base-content/50 hover:text-base-content transition">Close</button>
        </div>
    </div>
</template>

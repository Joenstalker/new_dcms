<script setup>
import { useForm } from '@inertiajs/vue3';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import Swal from 'sweetalert2';

const props = defineProps({
    calendarColor: String,
    hasPermission: Boolean
});

const form = useForm({
    calendar_color: props.calendarColor || '#3b82f6',
});

const presetColors = [
    '#3b82f6', '#ef4444', '#10b981', '#f59e0b', 
    '#8b5cf6', '#ec4899', '#06b6d4', '#64748b'
];

const saveColor = () => {
    form.put(route('staff-settings.calendar-color'), {
        preserveScroll: true,
        onSuccess: () => {
            Swal.mixin({
                toast: true, position: 'top-end', showConfirmButton: false, timer: 3000, timerProgressBar: true
            }).fire({ icon: 'success', title: 'Color updated successfully' });
        },
    });
};
</script>

<template>
    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
        <div class="p-6 border-b border-gray-50 bg-gray-50/30 flex justify-between items-center">
            <div>
                <h3 class="text-lg font-bold text-gray-900 flex items-center gap-2">
                    <svg class="w-5 h-5 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M7 21a4 4 0 01-4-4V5a2 2 0 012-2h4a2 2 0 012 2v12a4 4 0 01-4 4zm0 0h12a2 2 0 002-2v-4a2 2 0 00-2-2h-2.343M11 7.343l1.657-1.657a2 2 0 012.828 0l2.829 2.829a2 2 0 010 2.828l-8.486 8.485M7 17h.01" />
                    </svg>
                    Calendar Color
                </h3>
                <p class="text-xs text-gray-500 mt-1">Choose the color representing you on the clinic calendar.</p>
            </div>
        </div>

        <div v-if="hasPermission" class="p-6">
            <div class="flex flex-col sm:flex-row sm:items-center gap-6">
                <div class="flex-1 space-y-4">
                    <div class="flex items-center gap-4">
                        <input 
                            type="color" 
                            v-model="form.calendar_color" 
                            class="h-12 w-12 rounded cursor-pointer border-0 p-0"
                        >
                        <div class="flex-1">
                            <input 
                                type="text" 
                                v-model="form.calendar_color" 
                                class="input input-bordered w-full max-w-xs font-mono"
                                placeholder="#000000"
                            >
                        </div>
                    </div>

                    <div class="flex flex-wrap gap-2 pt-2 border-t">
                        <button 
                            v-for="color in presetColors" 
                            :key="color"
                            type="button"
                            @click="form.calendar_color = color"
                            class="w-8 h-8 rounded-full border-2 transition-transform hover:scale-110 focus:outline-none"
                            :class="form.calendar_color === color ? 'border-gray-900 shadow-md scale-110' : 'border-transparent'"
                            :style="{ backgroundColor: color }"
                            :aria-label="`Select color ${color}`"
                        ></button>
                    </div>
                </div>

                <!-- Preview -->
                <div class="w-full sm:w-64 p-4 rounded-xl border border-gray-100 bg-gray-50 text-center">
                    <p class="text-[10px] uppercase font-bold text-gray-400 mb-2">Calendar Preview</p>
                    <div 
                        class="text-left px-3 py-2 rounded-lg border-l-4 text-xs font-bold"
                        :style="{ 
                            backgroundColor: form.calendar_color + '15', 
                            borderLeftColor: form.calendar_color,
                            color: form.calendar_color
                        }"
                    >
                        10:00 AM • Patient Visit
                    </div>
                </div>
            </div>
            
            <div class="mt-6 flex justify-end">
                <PrimaryButton @click="saveColor" :disabled="form.processing">
                    Save Color
                </PrimaryButton>
            </div>
        </div>

        <!-- Locked State -->
        <div v-else class="p-8 text-center bg-gray-50">
            <svg class="w-10 h-10 mx-auto text-gray-300 mb-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M16.5 10.5V6.75a4.5 4.5 0 10-9 0v3.75m-.75 11.25h10.5a2.25 2.25 0 002.25-2.25v-6.75a2.25 2.25 0 00-2.25-2.25H6.75a2.25 2.25 0 00-2.25 2.25v6.75a2.25 2.25 0 002.25 2.25z" />
            </svg>
            <h4 class="text-sm font-bold text-gray-600 uppercase tracking-widest">Access Restricted</h4>
            <p class="text-xs text-gray-500 mt-1 max-w-sm mx-auto">You do not have permission to change your calendar color. Contact your clinic owner.</p>
        </div>
    </div>
</template>

<script setup>
import { useForm } from '@inertiajs/vue3';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import Swal from 'sweetalert2';

const props = defineProps({
    hours: Object,
    hasPermission: Boolean
});

const form = useForm({
    working_hours: props.hours,
});

const daysOfWeek = [
    { key: 'monday', label: 'Monday' },
    { key: 'tuesday', label: 'Tuesday' },
    { key: 'wednesday', label: 'Wednesday' },
    { key: 'thursday', label: 'Thursday' },
    { key: 'friday', label: 'Friday' },
    { key: 'saturday', label: 'Saturday' },
    { key: 'sunday', label: 'Sunday' }
];

const saveWorkingHours = () => {
    form.put(route('staff-settings.working-hours'), {
        preserveScroll: true,
        onSuccess: () => {
            Swal.mixin({
                toast: true, position: 'top-end', showConfirmButton: false, timer: 3000, timerProgressBar: true
            }).fire({ icon: 'success', title: 'Working hours updated successfully' });
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
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    Working Hours
                </h3>
                <p class="text-xs text-gray-500 mt-1">Set your weekly availability for appointments.</p>
            </div>
        </div>

        <div v-if="hasPermission" class="p-6">
            <div class="space-y-4">
                <div 
                    v-for="day in daysOfWeek" 
                    :key="day.key" 
                    class="flex flex-col sm:flex-row sm:items-center justify-between p-4 rounded-xl border transition-colors"
                    :class="form.working_hours[day.key].enabled ? 'bg-white border-blue-100 shadow-sm' : 'bg-gray-50 border-gray-100'"
                >
                    <div class="flex items-center gap-4 mb-3 sm:mb-0">
                        <input 
                            type="checkbox" 
                            v-model="form.working_hours[day.key].enabled"
                            class="checkbox checkbox-primary checkbox-sm rounded-md"
                        />
                        <span class="font-bold w-24" :class="form.working_hours[day.key].enabled ? 'text-gray-900' : 'text-gray-400'">
                            {{ day.label }}
                        </span>
                    </div>

                    <div v-if="form.working_hours[day.key].enabled" class="flex items-center gap-2 ml-8 sm:ml-0">
                        <input 
                            type="time" 
                            v-model="form.working_hours[day.key].start"
                            class="input input-bordered input-sm font-mono"
                        />
                        <span class="text-gray-400 text-sm">to</span>
                        <input 
                            type="time" 
                            v-model="form.working_hours[day.key].end"
                            class="input input-bordered input-sm font-mono"
                        />
                    </div>
                    <div v-else class="text-xs font-bold text-gray-400 uppercase tracking-widest pl-8 sm:pl-0 sm:pr-12">
                        Unavailable
                    </div>
                </div>
            </div>
            
            <div class="mt-6 flex justify-end">
                <PrimaryButton @click="saveWorkingHours" :disabled="form.processing">
                    Save Schedule
                </PrimaryButton>
            </div>
        </div>

        <!-- Locked State -->
        <div v-else class="p-8 text-center bg-gray-50">
            <svg class="w-10 h-10 mx-auto text-gray-300 mb-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M16.5 10.5V6.75a4.5 4.5 0 10-9 0v3.75m-.75 11.25h10.5a2.25 2.25 0 002.25-2.25v-6.75a2.25 2.25 0 00-2.25-2.25H6.75a2.25 2.25 0 00-2.25 2.25v6.75a2.25 2.25 0 002.25 2.25z" />
            </svg>
            <h4 class="text-sm font-bold text-gray-600 uppercase tracking-widest">Access Restricted</h4>
            <p class="text-xs text-gray-500 mt-1 max-w-sm mx-auto">You do not have permission to modify your schedule.</p>
        </div>
    </div>
</template>

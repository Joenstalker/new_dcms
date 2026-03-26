<script setup>
import { useForm } from '@inertiajs/vue3';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import Swal from 'sweetalert2';

const props = defineProps({
    preferences: Object,
    hasPermission: Boolean
});

const form = useForm({
    notification_preferences: props.preferences,
});

const notificationTypes = {
    new_appointment: 'New Appointment Assigned',
    appointment_cancelled: 'Appointment Cancelled',
    appointment_rescheduled: 'Appointment Rescheduled',
    treatment_reminder: 'Treatment Reminder',
    system_announcements: 'System Announcements'
};

const saveNotifications = () => {
    form.put(route('staff-settings.notifications'), {
        preserveScroll: true,
        onSuccess: () => {
            Swal.mixin({
                toast: true, position: 'top-end', showConfirmButton: false, timer: 3000, timerProgressBar: true
            }).fire({ icon: 'success', title: 'Notifications updated successfully' });
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
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" />
                    </svg>
                    Notification Preferences
                </h3>
                <p class="text-xs text-gray-500 mt-1">Control how and when you receive system alerts.</p>
            </div>
        </div>

        <div v-if="hasPermission" class="p-6">
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="border-b border-gray-100">
                            <th class="pb-3 text-xs font-bold text-gray-500 uppercase tracking-wider">Alert Type</th>
                            <th class="pb-3 text-xs font-bold text-gray-500 uppercase tracking-wider text-center w-24">Email</th>
                            <th class="pb-3 text-xs font-bold text-gray-500 uppercase tracking-wider text-center w-24">In-App</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-50">
                        <tr v-for="(label, key) in notificationTypes" :key="key" class="hover:bg-gray-50/50 transition-colors">
                            <td class="py-4 text-sm font-medium text-gray-700">{{ label }}</td>
                            <td class="py-4 text-center">
                                <input 
                                    type="checkbox" 
                                    v-model="form.notification_preferences[key].email"
                                    class="toggle toggle-primary toggle-sm"
                                />
                            </td>
                            <td class="py-4 text-center">
                                <input 
                                    type="checkbox" 
                                    v-model="form.notification_preferences[key].in_app"
                                    class="toggle toggle-primary toggle-sm"
                                />
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
            <div class="mt-6 flex justify-end">
                <PrimaryButton @click="saveNotifications" :disabled="form.processing">
                    Save Preferences
                </PrimaryButton>
            </div>
        </div>

        <!-- Locked State -->
        <div v-else class="p-8 text-center bg-gray-50">
            <svg class="w-10 h-10 mx-auto text-gray-300 mb-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M16.5 10.5V6.75a4.5 4.5 0 10-9 0v3.75m-.75 11.25h10.5a2.25 2.25 0 002.25-2.25v-6.75a2.25 2.25 0 00-2.25-2.25H6.75a2.25 2.25 0 00-2.25 2.25v6.75a2.25 2.25 0 002.25 2.25z" />
            </svg>
            <h4 class="text-sm font-bold text-gray-600 uppercase tracking-widest">Access Restricted</h4>
            <p class="text-xs text-gray-500 mt-1 max-w-sm mx-auto">You do not have permission to modify your notification settings.</p>
        </div>
    </div>
</template>

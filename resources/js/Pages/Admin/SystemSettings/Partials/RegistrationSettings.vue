<script setup>
import { useForm } from '@inertiajs/vue3';

const props = defineProps({
    form: {
        type: Object,
        required: true,
    },
});

const emit = defineEmits(['save']);

const descriptions = {
    pending_timeout_default_hours: 'Hours before a pending registration automatically expires',
    pending_reminder_global_enabled: 'Send reminder emails to clinics with pending registrations',
    pending_reminder_hours_before: 'Hours before expiry to send the reminder email',
    pending_auto_approve_enabled: 'Automatically approve pending registrations after a certain time',
    pending_auto_approve_hours: 'Hours after registration to trigger automatic approval',
};

const saveGroup = () => {
    emit('save', 'registrations');
};
</script>

<template>
    <div class="bg-base-100 shadow-sm rounded-lg border border-base-300 overflow-hidden">
        <div class="p-6 border-b border-base-300">
            <h2 class="text-lg font-bold text-base-content">Registration Configuration</h2>
            <p class="mt-1 text-sm text-base-content/50">Manage settings for new clinic registrations, timeouts, and reminders.</p>
        </div>
        
        <div class="p-6 space-y-6">
            <!-- Pending Timeout Default Hours -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4 items-center">
                <div class="md:col-span-2">
                    <label class="block text-sm font-bold text-base-content/70">Registration Timeout</label>
                    <p class="text-xs text-base-content/50 mt-1">{{ descriptions.pending_timeout_default_hours }}</p>
                </div>
                <div class="flex items-center gap-2">
                    <input
                        v-model="form.pending_timeout_default_hours"
                        type="number"
                        min="1"
                        class="block w-full rounded-md border-base-300 bg-base-100 text-base-content shadow-sm focus:border-primary focus:ring-primary sm:text-sm px-3 py-2 border"
                    />
                    <span class="text-sm text-base-content/50 whitespace-nowrap">hours</span>
                </div>
            </div>

            <!-- Reminder Enabled -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4 items-center">
                <div class="md:col-span-2">
                    <label class="block text-sm font-bold text-base-content/70">Enable Reminders</label>
                    <p class="text-xs text-base-content/50 mt-1">{{ descriptions.pending_reminder_global_enabled }}</p>
                </div>
                <div class="flex items-center">
                    <input 
                        type="checkbox" 
                        class="toggle toggle-primary"
                        :checked="form.pending_reminder_global_enabled === 'true' || form.pending_reminder_global_enabled === true"
                        @change="form.pending_reminder_global_enabled = $event.target.checked ? 'true' : 'false'"
                    />
                </div>
            </div>

            <!-- Reminder Hours Before -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4 items-center" v-if="form.pending_reminder_global_enabled === 'true' || form.pending_reminder_global_enabled === true">
                <div class="md:col-span-2">
                    <label class="block text-sm font-bold text-base-content/70">Reminder Timing</label>
                    <p class="text-xs text-base-content/50 mt-1">{{ descriptions.pending_reminder_hours_before }}</p>
                </div>
                <div class="flex items-center gap-2">
                    <input
                        v-model="form.pending_reminder_hours_before"
                        type="number"
                        min="1"
                        class="block w-full rounded-md border-base-300 bg-base-100 text-base-content shadow-sm focus:border-primary focus:ring-primary sm:text-sm px-3 py-2 border"
                    />
                    <span class="text-sm text-base-content/50 whitespace-nowrap">hours</span>
                </div>
            </div>

            <!-- Auto-Approve Enabled -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4 items-center">
                <div class="md:col-span-2">
                    <label class="block text-sm font-bold text-base-content/70">Enable Auto-Approve</label>
                    <p class="text-xs text-base-content/50 mt-1">{{ descriptions.pending_auto_approve_enabled }}</p>
                </div>
                <div class="flex items-center">
                    <input 
                        type="checkbox" 
                        class="toggle toggle-primary" 
                        :checked="form.pending_auto_approve_enabled === 'true' || form.pending_auto_approve_enabled === true"
                        @change="form.pending_auto_approve_enabled = $event.target.checked ? 'true' : 'false'"
                    />
                </div>
            </div>

            <!-- Auto-Approve Hours -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4 items-center" v-if="form.pending_auto_approve_enabled === 'true' || form.pending_auto_approve_enabled === true">
                <div class="md:col-span-2">
                    <label class="block text-sm font-bold text-base-content/70">Auto-Approve Timing</label>
                    <p class="text-xs text-base-content/50 mt-1">{{ descriptions.pending_auto_approve_hours }}</p>
                </div>
                <div class="flex items-center gap-2">
                    <input
                        v-model="form.pending_auto_approve_hours"
                        type="number"
                        min="1"
                        class="block w-full rounded-md border-base-300 bg-base-100 text-base-content shadow-sm focus:border-primary focus:ring-primary sm:text-sm px-3 py-2 border"
                    />
                    <span class="text-sm text-base-content/50 whitespace-nowrap">hours</span>
                </div>
            </div>
        </div>

        <div class="px-6 py-4 bg-base-200/50 border-t border-base-300 flex justify-end">
            <button
                @click="saveGroup"
                class="btn btn-primary btn-sm"
            >
                Save Settings
            </button>
        </div>
    </div>
</template>

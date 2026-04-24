<script setup>
import { useForm, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

const props = defineProps({
    form: {
        type: Object,
        required: true,
    },
});

const emit = defineEmits(['save']);

const descriptions = {
    pending_timeout_default_minutes: 'Minutes before a pending registration automatically expires',
    pending_refund_timer_enabled: 'Enable automatic refunds for expired registrations',
    pending_refund_timer_minutes: 'Minutes before an unapproved registration is automatically refunded',
    pending_reminder_global_enabled: 'Send reminder emails to clinics with pending registrations',
    pending_reminder_minutes_before: 'Minutes before expiry to send characters text reminder',
    pending_auto_approve_enabled: 'Automatically approve pending registrations when the timeout is reached',
    pending_auto_approve_minutes: 'Minutes after which auto-approve triggers if enabled',
};

const saveGroup = () => {
    emit('save', 'registrations');
};

const branding = computed(() => usePage().props.branding || {});
const primaryColor = computed(() => branding.value.primary_color || '#0ea5e9');
</script>

<template>
    <div class="bg-base-100 shadow-sm rounded-lg border border-base-300 overflow-hidden">
        <div class="p-6 border-b border-base-300">
            <h2 class="text-lg font-bold text-base-content">Registration Configuration</h2>
            <p class="mt-1 text-sm text-base-content/50">Manage settings for new clinic registrations, timeouts, and reminders.</p>
        </div>
        
        <div class="p-6 space-y-6">
            <!-- Pending Timeout Default Minutes -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4 items-center">
                <div class="md:col-span-2">
                    <label class="block text-sm font-bold text-base-content/70">Registration Timeout</label>
                    <p class="text-xs text-base-content/50 mt-1">{{ descriptions.pending_timeout_default_minutes }}</p>
                </div>
                <div class="flex items-center gap-2">
                    <input
                        v-model="form.pending_timeout_default_minutes"
                        type="number"
                        min="1"
                        class="block w-full rounded-md border-base-300 bg-base-100 text-base-content shadow-sm focus:border-primary focus:ring-primary sm:text-sm px-3 py-2 border"
                    />
                    <span class="text-sm text-base-content/50 whitespace-nowrap">minutes</span>
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
                        class="toggle border-2 border-base-300 shadow-sm"
                        :style="form.pending_reminder_global_enabled ? { backgroundColor: primaryColor, borderColor: primaryColor } : {}"
                        v-model="form.pending_reminder_global_enabled"
                    />
                </div>
            </div>

            <!-- Reminder Minutes Before -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4 items-center" v-if="form.pending_reminder_global_enabled === 'true' || form.pending_reminder_global_enabled === true">
                <div class="md:col-span-2">
                    <label class="block text-sm font-bold text-base-content/70">Reminder Timing</label>
                    <p class="text-xs text-base-content/50 mt-1">{{ descriptions.pending_reminder_minutes_before }}</p>
                </div>
                <div class="flex items-center gap-2">
                    <input
                        v-model="form.pending_reminder_minutes_before"
                        type="number"
                        min="1"
                        class="block w-full rounded-md border-base-300 bg-base-100 text-base-content shadow-sm focus:border-primary focus:ring-primary sm:text-sm px-3 py-2 border"
                    />
                    <span class="text-sm text-base-content/50 whitespace-nowrap">minutes</span>
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
                        class="toggle border-2 border-base-300 shadow-sm"
                        :style="form.pending_auto_approve_enabled ? { backgroundColor: primaryColor, borderColor: primaryColor } : {}"
                        v-model="form.pending_auto_approve_enabled"
                    />
                </div>
            </div>

            <!-- Auto-Approve Minutes -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4 items-center" v-if="form.pending_auto_approve_enabled === 'true' || form.pending_auto_approve_enabled === true">
                <div class="md:col-span-2">
                    <label class="block text-sm font-bold text-base-content/70">Auto-Approve Timing</label>
                    <p class="text-xs text-base-content/50 mt-1">{{ descriptions.pending_auto_approve_minutes }}</p>
                </div>
                <div class="flex items-center gap-2">
                    <input
                        v-model="form.pending_auto_approve_minutes"
                        type="number"
                        min="1"
                        class="block w-full rounded-md border-base-300 bg-base-100 text-base-content shadow-sm focus:border-primary focus:ring-primary sm:text-sm px-3 py-2 border"
                    />
                    <span class="text-sm text-base-content/50 whitespace-nowrap">minutes</span>
                </div>
            </div>

            <!-- Auto-Refund Enabled -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4 items-center">
                <div class="md:col-span-2">
                    <label class="block text-sm font-bold text-base-content/70">Enable Auto-Refund</label>
                    <p class="text-xs text-base-content/50 mt-1">{{ descriptions.pending_refund_timer_enabled }}</p>
                </div>
                <div class="flex items-center">
                    <input 
                        type="checkbox" 
                        class="toggle border-2 border-base-300 shadow-sm"
                        :style="form.pending_refund_timer_enabled ? { backgroundColor: primaryColor, borderColor: primaryColor } : {}"
                        v-model="form.pending_refund_timer_enabled"
                    />
                </div>
            </div>

            <!-- Auto-Refund Minutes -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4 items-center" v-if="form.pending_refund_timer_enabled === 'true' || form.pending_refund_timer_enabled === true">
                <div class="md:col-span-2">
                    <label class="block text-sm font-bold text-base-content/70">Auto-Refund Timing</label>
                    <p class="text-xs text-base-content/50 mt-1">{{ descriptions.pending_refund_timer_minutes }}</p>
                </div>
                <div class="flex items-center gap-2">
                    <input
                        v-model="form.pending_refund_timer_minutes"
                        type="number"
                        min="1"
                        class="block w-full rounded-md border-base-300 bg-base-100 text-base-content shadow-sm focus:border-primary focus:ring-primary sm:text-sm px-3 py-2 border"
                    />
                    <span class="text-sm text-base-content/50 whitespace-nowrap">minutes</span>
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

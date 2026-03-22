<script setup>
import { usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
const props = defineProps({
    form: {
        type: Object,
        required: true,
    },
});

const emit = defineEmits(['save']);

const securityDescriptions = {
    two_factor_auth_enabled: 'Require two-factor authentication for admin login (Coming Soon)',
    ip_whitelist_enabled: 'Restrict admin access to specific IP addresses (Coming Soon)',
    audit_logging_enabled: 'Log all admin actions for audit purposes',
    data_encryption_enabled: 'Encrypt sensitive patient data in the database',
    concurrent_sessions_enabled: 'Allow users to be logged in from multiple devices (Coming Soon)',
};

const saveGroup = () => {
    emit('save', 'security');
};

const branding = computed(() => usePage().props.branding || {});
const primaryColor = computed(() => branding.value.primary_color || '#0ea5e9');
</script>

<template>
    <div class="bg-base-100 shadow-sm rounded-lg border border-base-300 overflow-hidden">
        <div class="p-6 border-b border-base-300">
            <h2 class="text-lg font-bold text-base-content">Security Configuration</h2>
            <p class="mt-1 text-sm text-base-content/50">Manage security settings for the admin portal.</p>
        </div>
        
        <div class="p-6 space-y-6">
            <!-- Two Factor Auth - Coming Soon -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4 items-center opacity-60">
                <div class="md:col-span-2">
                    <label class="text-sm font-bold text-base-content/70 flex items-center gap-2">
                        Two-Factor Authentication
                        <span class="badge badge-warning badge-sm">
                            Coming Soon
                        </span>
                    </label>
                    <p class="text-xs text-base-content/50 mt-1">{{ securityDescriptions.two_factor_auth_enabled }}</p>
                </div>
                <div class="flex items-center">
                    <input 
                        type="checkbox" 
                        class="toggle border-2 border-base-300 shadow-sm opacity-30" 
                        disabled
                    />
                </div>
            </div>

            <!-- IP Whitelist - Coming Soon -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4 items-center opacity-60">
                <div class="md:col-span-2">
                    <label class="text-sm font-bold text-base-content/70 flex items-center gap-2">
                        IP Whitelist
                        <span class="badge badge-warning badge-sm">
                            Coming Soon
                        </span>
                    </label>
                    <p class="text-xs text-base-content/50 mt-1">{{ securityDescriptions.ip_whitelist_enabled }}</p>
                </div>
                <div class="flex items-center">
                    <input 
                        type="checkbox" 
                        class="toggle border-2 border-base-300 shadow-sm opacity-30" 
                        disabled
                    />
                </div>
            </div>

            <!-- Audit Logging -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4 items-center">
                <div class="md:col-span-2">
                    <label class="block text-sm font-bold text-base-content/70">Audit Logging</label>
                    <p class="text-xs text-base-content/50 mt-1">{{ securityDescriptions.audit_logging_enabled }}</p>
                </div>
                <div class="flex items-center">
                    <input 
                        type="checkbox" 
                        class="toggle border-2 border-base-300 shadow-sm"
                        :style="form.audit_logging_enabled ? { backgroundColor: primaryColor, borderColor: primaryColor } : {}"
                        v-model="form.audit_logging_enabled"
                    />
                </div>
            </div>

            <!-- Data Encryption -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4 items-center">
                <div class="md:col-span-2">
                    <label class="block text-sm font-bold text-base-content/70">Data Encryption</label>
                    <p class="text-xs text-base-content/50 mt-1">{{ securityDescriptions.data_encryption_enabled }}</p>
                </div>
                <div class="flex items-center">
                    <input 
                        type="checkbox" 
                        class="toggle border-2 border-base-300 shadow-sm"
                        :style="form.data_encryption_enabled ? { backgroundColor: primaryColor, borderColor: primaryColor } : {}"
                        v-model="form.data_encryption_enabled"
                    />
                </div>
            </div>

            <!-- Concurrent Sessions - Coming Soon -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4 items-center opacity-60">
                <div class="md:col-span-2">
                    <label class="text-sm font-bold text-base-content/70 flex items-center gap-2">
                        Concurrent Sessions
                        <span class="badge badge-warning badge-sm">
                            Coming Soon
                        </span>
                    </label>
                    <p class="text-xs text-base-content/50 mt-1">{{ securityDescriptions.concurrent_sessions_enabled }}</p>
                </div>
                <div class="flex items-center">
                    <input 
                        type="checkbox" 
                        class="toggle border-2 border-base-300 shadow-sm opacity-30" 
                        disabled
                    />
                </div>
            </div>
        </div>

        <div class="px-6 py-4 bg-base-200/50 border-t border-base-300 flex justify-end">
            <button
                @click="saveGroup"
                class="btn btn-primary btn-sm"
            >
                Save Security Settings
            </button>
        </div>
    </div>
</template>

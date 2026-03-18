<script setup>
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
</script>

<template>
    <div class="bg-white shadow-sm rounded-lg border border-gray-200">
        <div class="p-6 border-b border-gray-200">
            <h2 class="text-lg font-semibold text-gray-900">Security Configuration</h2>
            <p class="mt-1 text-sm text-gray-500">Manage security settings for the admin portal.</p>
        </div>
        
        <div class="p-6 space-y-6">
            <!-- Two Factor Auth - Coming Soon -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4 items-center opacity-60">
                <div class="md:col-span-2">
                    <label class="block text-sm font-medium text-gray-700 flex items-center gap-2">
                        Two-Factor Authentication
                        <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-amber-100 text-amber-800">
                            Coming Soon
                        </span>
                    </label>
                    <p class="text-xs text-gray-500 mt-1">{{ securityDescriptions.two_factor_auth_enabled }}</p>
                </div>
                <div class="flex items-center">
                    <button
                        disabled
                        class="opacity-50 cursor-not-allowed relative inline-flex h-6 w-11 flex-shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200 ease-in-out focus:outline-none bg-gray-200"
                    >
                        <span class="translate-x-0 pointer-events-none inline-block h-5 w-5 transform rounded-full bg-white shadow ring-0 transition duration-200 ease-in-out" />
                    </button>
                </div>
            </div>

            <!-- IP Whitelist - Coming Soon -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4 items-center opacity-60">
                <div class="md:col-span-2">
                    <label class="block text-sm font-medium text-gray-700 flex items-center gap-2">
                        IP Whitelist
                        <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-amber-100 text-amber-800">
                            Coming Soon
                        </span>
                    </label>
                    <p class="text-xs text-gray-500 mt-1">{{ securityDescriptions.ip_whitelist_enabled }}</p>
                </div>
                <div class="flex items-center">
                    <button
                        disabled
                        class="opacity-50 cursor-not-allowed relative inline-flex h-6 w-11 flex-shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200 ease-in-out focus:outline-none bg-gray-200"
                    >
                        <span class="translate-x-0 pointer-events-none inline-block h-5 w-5 transform rounded-full bg-white shadow ring-0 transition duration-200 ease-in-out" />
                    </button>
                </div>
            </div>

            <!-- Audit Logging -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4 items-center">
                <div class="md:col-span-2">
                    <label class="block text-sm font-medium text-gray-700">Audit Logging</label>
                    <p class="text-xs text-gray-500 mt-1">{{ securityDescriptions.audit_logging_enabled }}</p>
                </div>
                <div class="flex items-center">
                    <button
                        @click="form.audit_logging_enabled = !form.audit_logging_enabled"
                        :class="[
                            form.audit_logging_enabled ? 'bg-teal-600' : 'bg-gray-200',
                            'relative inline-flex h-6 w-11 flex-shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200 ease-in-out focus:outline-none focus:ring-2 focus:ring-teal-500 focus:ring-offset-2'
                        ]"
                    >
                        <span
                            :class="[
                                form.audit_logging_enabled ? 'translate-x-5' : 'translate-x-0',
                                'pointer-events-none inline-block h-5 w-5 transform rounded-full bg-white shadow ring-0 transition duration-200 ease-in-out'
                            ]"
                        />
                    </button>
                </div>
            </div>

            <!-- Data Encryption -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4 items-center">
                <div class="md:col-span-2">
                    <label class="block text-sm font-medium text-gray-700">Data Encryption</label>
                    <p class="text-xs text-gray-500 mt-1">{{ securityDescriptions.data_encryption_enabled }}</p>
                </div>
                <div class="flex items-center">
                    <button
                        @click="form.data_encryption_enabled = !form.data_encryption_enabled"
                        :class="[
                            form.data_encryption_enabled ? 'bg-teal-600' : 'bg-gray-200',
                            'relative inline-flex h-6 w-11 flex-shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200 ease-in-out focus:outline-none focus:ring-2 focus:ring-teal-500 focus:ring-offset-2'
                        ]"
                    >
                        <span
                            :class="[
                                form.data_encryption_enabled ? 'translate-x-5' : 'translate-x-0',
                                'pointer-events-none inline-block h-5 w-5 transform rounded-full bg-white shadow ring-0 transition duration-200 ease-in-out'
                            ]"
                        />
                    </button>
                </div>
            </div>

            <!-- Concurrent Sessions - Coming Soon -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4 items-center opacity-60">
                <div class="md:col-span-2">
                    <label class="block text-sm font-medium text-gray-700 flex items-center gap-2">
                        Concurrent Sessions
                        <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-amber-100 text-amber-800">
                            Coming Soon
                        </span>
                    </label>
                    <p class="text-xs text-gray-500 mt-1">{{ securityDescriptions.concurrent_sessions_enabled }}</p>
                </div>
                <div class="flex items-center">
                    <button
                        disabled
                        class="opacity-50 cursor-not-allowed relative inline-flex h-6 w-11 flex-shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200 ease-in-out focus:outline-none bg-gray-200"
                    >
                        <span class="translate-x-0 pointer-events-none inline-block h-5 w-5 transform rounded-full bg-white shadow ring-0 transition duration-200 ease-in-out" />
                    </button>
                </div>
            </div>
        </div>

        <div class="px-6 py-4 bg-gray-50 border-t border-gray-200 flex justify-end">
            <button
                @click="saveGroup"
                class="bg-teal-600 hover:bg-teal-700 text-white px-4 py-2 rounded-lg text-sm font-medium shadow-sm transition-colors"
            >
                Save Security Settings
            </button>
        </div>
    </div>
</template>

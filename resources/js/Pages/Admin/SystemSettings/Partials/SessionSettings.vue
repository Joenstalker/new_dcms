<script setup>
import { useForm } from '@inertiajs/vue3';

const props = defineProps({
    form: {
        type: Object,
        required: true,
    },
});

const emit = defineEmits(['save']);

const sessionDescriptions = {
    session_lifetime: 'Minutes before a session expires due to inactivity',
    session_expire_on_close: 'End session when user closes the browser',
    session_encrypt: 'Encrypt session data for additional security',
    remember_me_duration: 'Duration in minutes for "Remember Me" functionality (default: 14 days)',
    max_login_attempts: 'Maximum failed login attempts before account lockout',
    lockout_duration: 'Minutes to lock out user after exceeding max attempts',
    password_reset_expiry: 'Minutes before password reset token expires',
};

const saveGroup = () => {
    emit('save', 'session');
};
</script>

<template>
    <div class="bg-white shadow-sm rounded-lg border border-gray-200">
        <div class="p-6 border-b border-gray-200">
            <h2 class="text-lg font-semibold text-gray-900">Session Configuration</h2>
            <p class="mt-1 text-sm text-gray-500">Manage session behavior and security settings for the admin portal.</p>
        </div>
        
        <div class="p-6 space-y-6">
            <!-- Session Lifetime -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4 items-center">
                <div class="md:col-span-2">
                    <label class="block text-sm font-medium text-gray-700">Session Lifetime</label>
                    <p class="text-xs text-gray-500 mt-1">{{ sessionDescriptions.session_lifetime }}</p>
                </div>
                <div class="flex items-center gap-2">
                    <input
                        v-model="form.session_lifetime"
                        type="number"
                        min="5"
                        max="1440"
                        class="block w-full rounded-md border-gray-300 shadow-sm focus:border-teal-500 focus:ring-teal-500 sm:text-sm px-3 py-2 border"
                    />
                    <span class="text-sm text-gray-500 whitespace-nowrap">minutes</span>
                </div>
            </div>

            <!-- Session Expire on Close -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4 items-center">
                <div class="md:col-span-2">
                    <label class="block text-sm font-medium text-gray-700">Expire on Close</label>
                    <p class="text-xs text-gray-500 mt-1">{{ sessionDescriptions.session_expire_on_close }}</p>
                </div>
                <div class="flex items-center">
                    <button
                        @click="form.session_expire_on_close = !form.session_expire_on_close"
                        :class="[
                            form.session_expire_on_close ? 'bg-teal-600' : 'bg-gray-200',
                            'relative inline-flex h-6 w-11 flex-shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200 ease-in-out focus:outline-none focus:ring-2 focus:ring-teal-500 focus:ring-offset-2'
                        ]"
                    >
                        <span
                            :class="[
                                form.session_expire_on_close ? 'translate-x-5' : 'translate-x-0',
                                'pointer-events-none inline-block h-5 w-5 transform rounded-full bg-white shadow ring-0 transition duration-200 ease-in-out'
                            ]"
                        />
                    </button>
                </div>
            </div>

            <!-- Session Encryption -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4 items-center">
                <div class="md:col-span-2">
                    <label class="block text-sm font-medium text-gray-700">Session Encryption</label>
                    <p class="text-xs text-gray-500 mt-1">{{ sessionDescriptions.session_encrypt }}</p>
                </div>
                <div class="flex items-center">
                    <button
                        @click="form.session_encrypt = !form.session_encrypt"
                        :class="[
                            form.session_encrypt ? 'bg-teal-600' : 'bg-gray-200',
                            'relative inline-flex h-6 w-11 flex-shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200 ease-in-out focus:outline-none focus:ring-2 focus:ring-teal-500 focus:ring-offset-2'
                        ]"
                    >
                        <span
                            :class="[
                                form.session_encrypt ? 'translate-x-5' : 'translate-x-0',
                                'pointer-events-none inline-block h-5 w-5 transform rounded-full bg-white shadow ring-0 transition duration-200 ease-in-out'
                            ]"
                        />
                    </button>
                </div>
            </div>

            <!-- Remember Me Duration -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4 items-center">
                <div class="md:col-span-2">
                    <label class="block text-sm font-medium text-gray-700">Remember Me Duration</label>
                    <p class="text-xs text-gray-500 mt-1">{{ sessionDescriptions.remember_me_duration }}</p>
                </div>
                <div class="flex items-center gap-2">
                    <input
                        v-model="form.remember_me_duration"
                        type="number"
                        min="60"
                        max="525600"
                        class="block w-full rounded-md border-gray-300 shadow-sm focus:border-teal-500 focus:ring-teal-500 sm:text-sm px-3 py-2 border"
                    />
                    <span class="text-sm text-gray-500 whitespace-nowrap">minutes</span>
                </div>
            </div>

            <!-- Max Login Attempts -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4 items-center">
                <div class="md:col-span-2">
                    <label class="block text-sm font-medium text-gray-700">Max Login Attempts</label>
                    <p class="text-xs text-gray-500 mt-1">{{ sessionDescriptions.max_login_attempts }}</p>
                </div>
                <div class="flex items-center gap-2">
                    <input
                        v-model="form.max_login_attempts"
                        type="number"
                        min="3"
                        max="10"
                        class="block w-full rounded-md border-gray-300 shadow-sm focus:border-teal-500 focus:ring-teal-500 sm:text-sm px-3 py-2 border"
                    />
                    <span class="text-sm text-gray-500 whitespace-nowrap">attempts</span>
                </div>
            </div>

            <!-- Lockout Duration -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4 items-center">
                <div class="md:col-span-2">
                    <label class="block text-sm font-medium text-gray-700">Lockout Duration</label>
                    <p class="text-xs text-gray-500 mt-1">{{ sessionDescriptions.lockout_duration }}</p>
                </div>
                <div class="flex items-center gap-2">
                    <input
                        v-model="form.lockout_duration"
                        type="number"
                        min="1"
                        max="1440"
                        class="block w-full rounded-md border-gray-300 shadow-sm focus:border-teal-500 focus:ring-teal-500 sm:text-sm px-3 py-2 border"
                    />
                    <span class="text-sm text-gray-500 whitespace-nowrap">minutes</span>
                </div>
            </div>

            <!-- Password Reset Expiry -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4 items-center">
                <div class="md:col-span-2">
                    <label class="block text-sm font-medium text-gray-700">Password Reset Expiry</label>
                    <p class="text-xs text-gray-500 mt-1">{{ sessionDescriptions.password_reset_expiry }}</p>
                </div>
                <div class="flex items-center gap-2">
                    <input
                        v-model="form.password_reset_expiry"
                        type="number"
                        min="5"
                        max="1440"
                        class="block w-full rounded-md border-gray-300 shadow-sm focus:border-teal-500 focus:ring-teal-500 sm:text-sm px-3 py-2 border"
                    />
                    <span class="text-sm text-gray-500 whitespace-nowrap">minutes</span>
                </div>
            </div>
        </div>

        <div class="px-6 py-4 bg-gray-50 border-t border-gray-200 flex justify-end">
            <button
                @click="saveGroup"
                class="bg-teal-600 hover:bg-teal-700 text-white px-4 py-2 rounded-lg text-sm font-medium shadow-sm transition-colors"
            >
                Save Session Settings
            </button>
        </div>
    </div>
</template>

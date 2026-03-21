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
    <div class="bg-base-100 shadow-sm rounded-lg border border-base-300 overflow-hidden">
        <div class="p-6 border-b border-base-300">
            <h2 class="text-lg font-bold text-base-content">Session Configuration</h2>
            <p class="mt-1 text-sm text-base-content/50">Manage session behavior and security settings for the admin portal.</p>
        </div>
        
        <div class="p-6 space-y-6">
            <!-- Session Lifetime -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4 items-center">
                <div class="md:col-span-2">
                    <label class="block text-sm font-bold text-base-content/70">Session Lifetime</label>
                    <p class="text-xs text-base-content/50 mt-1">{{ sessionDescriptions.session_lifetime }}</p>
                </div>
                <div class="flex items-center gap-2">
                    <input
                        v-model="form.session_lifetime"
                        type="number"
                        min="5"
                        max="1440"
                        class="block w-full rounded-md border-base-300 bg-base-100 text-base-content shadow-sm focus:border-primary focus:ring-primary sm:text-sm px-3 py-2 border"
                    />
                    <span class="text-sm text-base-content/50 whitespace-nowrap">minutes</span>
                </div>
            </div>

            <!-- Session Expire on Close -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4 items-center">
                <div class="md:col-span-2">
                    <label class="block text-sm font-bold text-base-content/70">Expire on Close</label>
                    <p class="text-xs text-base-content/50 mt-1">{{ sessionDescriptions.session_expire_on_close }}</p>
                </div>
                <div class="flex items-center">
                    <input 
                        type="checkbox" 
                        class="toggle toggle-primary" 
                        v-model="form.session_expire_on_close"
                    />
                </div>
            </div>

            <!-- Session Encryption -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4 items-center">
                <div class="md:col-span-2">
                    <label class="block text-sm font-bold text-base-content/70">Session Encryption</label>
                    <p class="text-xs text-base-content/50 mt-1">{{ sessionDescriptions.session_encrypt }}</p>
                </div>
                <div class="flex items-center">
                    <input 
                        type="checkbox" 
                        class="toggle toggle-primary" 
                        v-model="form.session_encrypt"
                    />
                </div>
            </div>

            <!-- Remember Me Duration -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4 items-center">
                <div class="md:col-span-2">
                    <label class="block text-sm font-bold text-base-content/70">Remember Me Duration</label>
                    <p class="text-xs text-base-content/50 mt-1">{{ sessionDescriptions.remember_me_duration }}</p>
                </div>
                <div class="flex items-center gap-2">
                    <input
                        v-model="form.remember_me_duration"
                        type="number"
                        min="60"
                        max="525600"
                        class="block w-full rounded-md border-base-300 bg-base-100 text-base-content shadow-sm focus:border-primary focus:ring-primary sm:text-sm px-3 py-2 border"
                    />
                    <span class="text-sm text-base-content/50 whitespace-nowrap">minutes</span>
                </div>
            </div>

            <!-- Max Login Attempts -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4 items-center">
                <div class="md:col-span-2">
                    <label class="block text-sm font-bold text-base-content/70">Max Login Attempts</label>
                    <p class="text-xs text-base-content/50 mt-1">{{ sessionDescriptions.max_login_attempts }}</p>
                </div>
                <div class="flex items-center gap-2">
                    <input
                        v-model="form.max_login_attempts"
                        type="number"
                        min="3"
                        max="10"
                        class="block w-full rounded-md border-base-300 bg-base-100 text-base-content shadow-sm focus:border-primary focus:ring-primary sm:text-sm px-3 py-2 border"
                    />
                    <span class="text-sm text-base-content/50 whitespace-nowrap">attempts</span>
                </div>
            </div>

            <!-- Lockout Duration -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4 items-center">
                <div class="md:col-span-2">
                    <label class="block text-sm font-bold text-base-content/70">Lockout Duration</label>
                    <p class="text-xs text-base-content/50 mt-1">{{ sessionDescriptions.lockout_duration }}</p>
                </div>
                <div class="flex items-center gap-2">
                    <input
                        v-model="form.lockout_duration"
                        type="number"
                        min="1"
                        max="1440"
                        class="block w-full rounded-md border-base-300 bg-base-100 text-base-content shadow-sm focus:border-primary focus:ring-primary sm:text-sm px-3 py-2 border"
                    />
                    <span class="text-sm text-base-content/50 whitespace-nowrap">minutes</span>
                </div>
            </div>

            <!-- Password Reset Expiry -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4 items-center">
                <div class="md:col-span-2">
                    <label class="block text-sm font-bold text-base-content/70">Password Reset Expiry</label>
                    <p class="text-xs text-base-content/50 mt-1">{{ sessionDescriptions.password_reset_expiry }}</p>
                </div>
                <div class="flex items-center gap-2">
                    <input
                        v-model="form.password_reset_expiry"
                        type="number"
                        min="5"
                        max="1440"
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
                Save Session Settings
            </button>
        </div>
    </div>
</template>

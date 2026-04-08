<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, useForm } from '@inertiajs/vue3';
import { ref } from 'vue';

const props = defineProps({
    login_lock: {
        type: Object,
        default: () => ({ max_attempts: 5, lockout_minutes: 15 }),
    },
});

const form = useForm({
    login_max_attempts: props.login_lock?.max_attempts ?? 5,
    login_lockout_minutes: props.login_lock?.lockout_minutes ?? 15,
});

const selectedConfig = ref('lock_login');

const save = () => {
    form.post(route('settings.login-lock.update'), {
        preserveScroll: true,
    });
};
</script>

<template>
    <Head title="Security Settings" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="text-xl font-semibold leading-tight text-gray-800">
                Security Settings
            </h2>
        </template>

        <div class="mt-6 space-y-6">
            <div v-if="$page.props.flash && $page.props.flash.success" class="mb-2 bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded-xl shadow-sm flex items-center gap-3">
                <span class="text-xl">✅</span>
                {{ $page.props.flash.success }}
            </div>

            <div class="rounded-2xl border border-gray-200 bg-white p-6 shadow-sm">
                <label class="block text-sm font-semibold text-gray-700">Configuration Type</label>
                <select
                    v-model="selectedConfig"
                    class="mt-2 w-full max-w-sm rounded-xl border border-gray-300 bg-white px-3 py-2.5 text-sm text-gray-800 focus:border-blue-400 focus:ring-blue-400"
                >
                    <option value="lock_login">Lock Login</option>
                </select>
            </div>

            <div v-if="selectedConfig === 'lock_login'" class="rounded-2xl border border-blue-200 bg-blue-50/60 p-6 shadow-sm">
                <div class="flex items-start justify-between gap-4 flex-wrap">
                    <div>
                        <h3 class="text-lg font-bold text-blue-900">Tenant Login Protection</h3>
                        <p class="mt-1 text-sm text-blue-700">
                            Configure brute-force protection for your tenant login modal. Additional security controls can be added here later.
                        </p>
                    </div>
                </div>

                <div class="mt-5 grid grid-cols-1 md:grid-cols-2 gap-5">
                    <div>
                        <label class="block text-sm font-semibold text-blue-900">Max Failed Attempts</label>
                        <input
                            v-model.number="form.login_max_attempts"
                            type="number"
                            min="1"
                            max="100"
                            class="mt-2 w-full rounded-xl border border-blue-200 bg-white px-3 py-2.5 text-sm text-gray-800 focus:border-blue-400 focus:ring-blue-400"
                        />
                        <p v-if="form.errors.login_max_attempts" class="mt-1 text-xs text-red-600">
                            {{ form.errors.login_max_attempts }}
                        </p>
                    </div>

                    <div>
                        <label class="block text-sm font-semibold text-blue-900">Lockout Duration (minutes)</label>
                        <input
                            v-model.number="form.login_lockout_minutes"
                            type="number"
                            min="1"
                            max="1440"
                            class="mt-2 w-full rounded-xl border border-blue-200 bg-white px-3 py-2.5 text-sm text-gray-800 focus:border-blue-400 focus:ring-blue-400"
                        />
                        <p v-if="form.errors.login_lockout_minutes" class="mt-1 text-xs text-red-600">
                            {{ form.errors.login_lockout_minutes }}
                        </p>
                    </div>
                </div>

                <div class="mt-5 flex justify-end">
                    <button
                        type="button"
                        @click="save"
                        :disabled="form.processing"
                        class="inline-flex items-center justify-center rounded-xl bg-blue-600 px-5 py-2.5 text-sm font-bold text-white shadow hover:bg-blue-700 disabled:cursor-not-allowed disabled:opacity-60"
                    >
                        {{ form.processing ? 'Saving...' : 'Save Lock Settings' }}
                    </button>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>

<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, useForm, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

const props = defineProps({
    tenant: Object,
    qr_code: String,
    booking_url: String
});

const subscription = computed(() => usePage().props.subscription || null);

const form = useForm({
    clinic_name: props.tenant?.clinic_name || '',
    email: props.tenant?.email || '',
    phone: props.tenant?.phone || '',
    address: props.tenant?.address || '',
    branding_color: props.tenant?.branding_color || '#2563eb', // Default blue-600
});

const submit = () => {
    form.post('/settings', { preserveScroll: true });
};
</script>

<template>
    <Head title="Clinic Setup" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="text-xl font-semibold leading-tight text-gray-800">
                Clinic Configuration
            </h2>
        </template>

        <div class="p-6 bg-white rounded-lg shadow-md max-w-4xl mx-auto space-y-6 mt-6">
            <h3 class="text-lg font-medium text-gray-800 border-b pb-4">Clinic Setup & Branding</h3>

            <div v-if="$page.props.flash && $page.props.flash.success" class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded">
                {{ $page.props.flash.success }}
            </div>

            <form @submit.prevent="submit" class="space-y-6">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <!-- General Settings -->
                    <div class="space-y-4">
                        <h4 class="text-md font-medium text-gray-900 border-b pb-2">General Information</h4>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Clinic Name</label>
                            <input type="text" v-model="form.clinic_name" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700">Contact Email</label>
                            <input type="email" v-model="form.email" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700">Phone Number</label>
                            <input type="text" v-model="form.phone" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700">Business Address</label>
                            <textarea v-model="form.address" rows="3" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"></textarea>
                        </div>
                    </div>

                    <!-- Branding Settings -->
                    <div class="space-y-4">
                        <h4 class="text-md font-medium text-gray-900 border-b pb-2">Custom Branding & QR Booking</h4>
                        
                        <!-- QR Code Section -->
                        <div class="bg-gray-50 p-4 rounded-lg border border-gray-200 flex flex-col items-center">
                            <div v-html="qr_code" class="bg-white p-2 rounded shadow-sm"></div>
                            <p class="mt-3 text-sm font-semibold text-gray-700">Clinic Booking QR Code</p>
                            <p class="text-xs text-center text-gray-500 mt-1">Scan this to book an appointment online.</p>
                            <a :href="booking_url" target="_blank" class="mt-2 text-xs text-blue-600 hover:underline">
                                {{ booking_url }}
                            </a>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mt-4">Primary Brand Color</label>
                            <div class="mt-1 flex items-center space-x-3">
                                <input type="color" v-model="form.branding_color" class="h-10 w-10 border border-gray-300 rounded-md cursor-pointer p-0 shadow-sm">
                                <input type="text" v-model="form.branding_color" class="block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500" placeholder="#000000">
                            </div>
                            <p class="text-xs text-gray-500 mt-1">This color will be applied to your public QR booking page.</p>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700">Clinic Logo</label>
                            <div class="mt-1 flex justify-center px-6 pt-5 pb-6 border-2 border-gray-300 border-dashed rounded-md">
                                <div class="space-y-1 text-center">
                                    <svg class="mx-auto h-12 w-12 text-gray-400" stroke="currentColor" fill="none" viewBox="0 0 48 48"><path d="M28 8H12a4 4 0 00-4 4v20m32-12v8m0 0v8a4 4 0 01-4 4H12a4 4 0 01-4-4v-4m32-4l-3.172-3.172a4 4 0 00-5.656 0L28 28M8 32l9.172-9.172a4 4 0 015.656 0L28 28m0 0l4 4m4-24h8m-4-4v8m-12 4h.02" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" /></svg>
                                    <div class="flex text-sm text-gray-600 justify-center">
                                        <label class="relative cursor-pointer bg-white rounded-md font-medium text-blue-600 hover:text-blue-500 focus-within:outline-none focus-within:ring-2 focus-within:ring-offset-2 focus-within:ring-blue-500">
                                            <span>Upload a file</span>
                                            <input type="file" class="sr-only">
                                        </label>
                                    </div>
                                    <p class="text-xs text-gray-500">PNG, JPG, GIF up to 2MB</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="flex justify-end pt-4 border-t">
                    <button type="submit" :disabled="form.processing" class="px-6 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 disabled:opacity-50 transition-colors">
                        Save Configuration
                    </button>
                </div>
            </form>

            <!-- Subscription & Billing Section -->
            <div v-if="subscription" class="mt-8 border-t pt-6">
                <h3 class="text-lg font-medium text-gray-800 mb-4">Subscription & Billing</h3>
                <div class="bg-gray-50 rounded-lg border border-gray-200 p-5 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                    <div class="space-y-1">
                        <div class="flex items-center gap-2">
                            <span class="text-sm font-semibold text-gray-700">Current Plan:</span>
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                                {{ subscription.plan_name }}
                            </span>
                            <span
                                class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium"
                                :class="{
                                    'bg-green-100 text-green-800': subscription.stripe_status === 'active',
                                    'bg-yellow-100 text-yellow-800': subscription.stripe_status === 'trialing',
                                    'bg-red-100 text-red-800': subscription.stripe_status === 'past_due' || subscription.stripe_status === 'unpaid',
                                    'bg-gray-100 text-gray-800': subscription.stripe_status === 'canceled',
                                }"
                            >
                                {{ subscription.stripe_status }}
                            </span>
                        </div>
                        <p class="text-xs text-gray-500">
                            Billing cycle: <span class="font-medium capitalize">{{ subscription.billing_cycle }}</span>
                        </p>
                        <div class="flex flex-wrap gap-2 mt-2">
                            <span v-if="subscription.max_patients" class="text-xs text-gray-600">
                                Up to <strong>{{ subscription.max_patients }}</strong> patients
                            </span>
                            <span v-else class="text-xs text-gray-600">Unlimited patients</span>
                            <span class="text-gray-300">·</span>
                            <span class="text-xs text-gray-600">
                                Up to <strong>{{ subscription.max_users }}</strong> staff users
                            </span>
                            <span v-if="subscription.has_sms" class="text-xs text-green-600 font-medium">· SMS ✓</span>
                            <span v-if="subscription.has_analytics" class="text-xs text-green-600 font-medium">· Analytics ✓</span>
                            <span v-if="subscription.has_multi_branch" class="text-xs text-green-600 font-medium">· Multi-branch ✓</span>
                        </div>
                    </div>
                    <a
                        :href="route('billing.portal')"
                        class="inline-flex items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 transition-colors whitespace-nowrap"
                    >
                        <svg class="w-4 h-4 mr-2" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 8.25h19.5M2.25 9h19.5m-16.5 5.25h6m-6 2.25h3m-3.75 3h15a2.25 2.25 0 0 0 2.25-2.25V6.75A2.25 2.25 0 0 0 19.5 4.5h-15a2.25 2.25 0 0 0-2.25 2.25v10.5A2.25 2.25 0 0 0 4.5 19.5Z" />
                        </svg>
                        Manage Billing
                    </a>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>

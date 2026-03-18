<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, useForm, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

import GeneralSettings from './Partials/GeneralSettings.vue';
import BrandingSettings from './Partials/BrandingSettings.vue';
import SubscriptionBilling from './Partials/SubscriptionBilling.vue';

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
                    <GeneralSettings :form="form" />

                    <!-- Branding Settings -->
                    <BrandingSettings 
                        :form="form" 
                        :qr-code="qr_code"
                        :booking-url="booking_url"
                    />
                </div>

                <div class="flex justify-end pt-4 border-t">
                    <button type="submit" :disabled="form.processing" class="px-6 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 disabled:opacity-50 transition-colors">
                        Save Configuration
                    </button>
                </div>
            </form>

            <!-- Subscription & Billing Section -->
            <SubscriptionBilling :subscription="subscription" />
        </div>
    </AuthenticatedLayout>
</template>

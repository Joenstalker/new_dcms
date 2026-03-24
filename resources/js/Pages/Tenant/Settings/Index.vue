<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, useForm, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

import ClinicBranding from './Partials/ClinicBranding.vue';
import PortalCustomization from './Partials/PortalCustomization.vue';
import OperatingHours from './Partials/OperatingHours.vue';
import QRCodeSetup from './Partials/QRCodeSetup.vue';
import FeatureSettings from './Partials/FeatureSettings.vue';
import LandingPageCustomizer from './Partials/LandingPageCustomizer.vue';

const props = defineProps({
    tenant: Object,
    qr_code: String,
    is_premium: {
        type: Boolean,
        default: false
    },
    staff: Array,
    booking_url: String
});

const currentTab = computed(() => {
    if (typeof window === 'undefined') return 'branding';
    const urlParams = new URLSearchParams(window.location.search);
    return urlParams.get('tab') || 'branding';
});

const form = useForm({
    clinic_name: props.tenant?.name || '',
    email: props.tenant?.email || '',
    phone: props.tenant?.phone || '',
    address: props.tenant?.street || '',
    branding_color: props.tenant?.branding_color || '#2563eb',
    font_family: props.tenant?.font_family || {
        header: 'font-sans',
        sidebar: 'font-sans',
        names: 'font-sans',
        general: 'font-sans'
    },
    hero_title: props.tenant?.hero_title || '',
    hero_subtitle: props.tenant?.hero_subtitle || '',
    about_us_description: props.tenant?.about_us_description || '',
    enabled_features: props.tenant?.enabled_features || [],
    landing_page_config: props.tenant?.landing_page_config || {},
    portal_config: props.tenant?.portal_config || {
        apply_to: 'all', // all, specific
        selected_staff: []
    },
    logo: null,
    logo_login: null,
    logo_booking: null,
});

const submit = () => {
    form.post('/settings', { 
        preserveScroll: true,
        forceFormData: true,
    });
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

        <div class="mt-6">
            <div v-if="$page.props.flash && $page.props.flash.success" class="mb-6 bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded-xl shadow-sm flex items-center gap-3">
                <span class="text-xl">✅</span>
                {{ $page.props.flash.success }}
            </div>

            <form @submit.prevent="submit" class="space-y-6">
                <!-- Branding Tab -->
                <div v-if="currentTab === 'branding'">
                    <ClinicBranding :form="form" :tenant="tenant" :is_premium="is_premium" />
                </div>

                <!-- Portal Customization Tab -->
                <div v-if="currentTab === 'portal'">
                    <PortalCustomization :form="form" :staff="staff" :is_premium="is_premium" />
                </div>

                <!-- Operating Hours Tab -->
                <div v-if="currentTab === 'hours'">
                    <OperatingHours :form="form" />
                </div>

                <!-- QR Code Tab -->
                <div v-if="currentTab === 'qr'">
                    <QRCodeSetup 
                        :qr-code="qr_code"
                        :booking-url="booking_url"
                    />
                </div>

                <!-- Feature Management Tab -->
                <div v-if="currentTab === 'features'">
                    <FeatureSettings :form="form" />
                </div>

                <!-- Landing Designer Tab -->
                <div v-if="currentTab === 'designer'">
                    <LandingPageCustomizer :form="form" :tenant="tenant" :is_premium="is_premium" />
                </div>

                <div v-if="currentTab !== 'qr' && currentTab !== 'updates'" class="flex justify-end pt-4">
                    <button 
                        type="submit" 
                        :disabled="form.processing" 
                        class="px-8 py-4 bg-primary text-white font-black uppercase tracking-widest text-[10px] rounded-2xl shadow-lg hover:shadow-primary/20 transition-all active:scale-95 disabled:opacity-50"
                        :style="{ backgroundColor: form.branding_color }"
                    >
                        {{ form.processing ? 'Saving...' : 'Save Configuration' }}
                    </button>
                </div>
            </form>
        </div>
    </AuthenticatedLayout>
</template>

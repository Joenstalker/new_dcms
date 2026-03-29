<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, useForm, usePage } from '@inertiajs/vue3';
import { computed, watch, inject } from 'vue';
import Swal from 'sweetalert2';

import ClinicBranding from './Partials/ClinicBranding.vue';
import PortalCustomization from './Partials/PortalCustomization.vue';
import LandingPageCustomizer from './Partials/LandingPageCustomizer.vue';
import OperatingHours from './Partials/OperatingHours.vue';
import QRCodeSetup from './Partials/QRCodeSetup.vue';
import FeatureSettings from './Partials/FeatureSettings.vue';

const props = defineProps({
    tenant: Object,
    is_premium: {
        type: Boolean,
        default: false
    },
    staff: Array,
    qr_code: String,
    booking_url: String,
    features: Object,
    has_pending_updates: Boolean,
});

const page = usePage();
const subscription = computed(() => page.props.subscription || {});
const hasBranding = computed(() => !!subscription.value.has_branding);

const currentTab = computed(() => {
    if (typeof window === 'undefined') return 'branding';
    const urlParams = new URLSearchParams(window.location.search);
    return urlParams.get('tab') || 'branding';
});

const initialFontFamily = (typeof props.tenant?.font_family === 'object' && props.tenant?.font_family !== null && !Array.isArray(props.tenant?.font_family))
    ? props.tenant.font_family
    : { header: 'font-sans', sidebar: 'font-sans', names: 'font-sans', general: 'font-sans' };

const initialEnabledFeatures = Array.isArray(props.tenant?.enabled_features)
    ? props.tenant.enabled_features
    : (props.tenant?.enabled_features ? JSON.parse(props.tenant?.enabled_features) : []);

const form = useForm({
    clinic_name: props.tenant?.name || '',
    email: props.tenant?.email || '',
    phone: props.tenant?.phone || '',
    address: props.tenant?.street || '',
    branding_color: props.tenant?.branding_color || page.props.branding?.primary_color || '#0ea5e9',
    font_family: initialFontFamily,
    hero_title: props.tenant?.hero_title || '',
    hero_subtitle: props.tenant?.hero_subtitle || '',
    about_us_description: props.tenant?.about_us_description || '',
    enabled_features: initialEnabledFeatures,
    landing_page_config: props.tenant?.landing_page_config || {},
    portal_config: props.tenant?.portal_config || {
        apply_to: 'all',
        selected_staff: []
    },
    operating_hours: props.tenant?.operating_hours || {},
    online_booking_enabled: props.tenant?.online_booking_enabled ?? true,
});

// 3. Import global Branding State to drive Live Preview
import { brandingState } from '@/States/brandingState';

// 4. Watch for real-time changes to push to the Layout (Interactive Mode)
watch(() => form.branding_color, (newColor) => {
    if (newColor) {
        brandingState.setPrimaryColor(newColor);
    }
}, { immediate: true });

const submit = () => {
    // Transform arrays into JSON strings to protect them during multipart/form-data submission
    // This prevents Laravel validation errors like "must be an array" on multipart requests
    form.post('/settings', { 
        preserveScroll: true,
        forceFormData: true,
        onSuccess: () => {
            Swal.fire({
                toast: true,
                position: 'top-end',
                icon: 'success',
                title: 'Configuration saved successfully!',
                showConfirmButton: false,
                timer: 3000,
                timerProgressBar: true,
                customClass: {
                    popup: 'colored-toast'
                }
            });
        },
        onError: (errors) => {
            const errorList = Object.values(errors).map(msg => `<li class="text-left text-xs mb-1">${msg}</li>`).join('');
            Swal.fire({
                title: 'Validation Error',
                html: `<ul class="list-disc pl-4 mt-2 text-error font-bold">${errorList}</ul>`,
                icon: 'error',
                confirmButtonColor: brandingState.primary_color,
                confirmButtonText: 'Check Form',
            });
        }
    });
};

// Also catch session flash errors natively
watch(() => usePage().props.flash, (flash) => {
    if (flash && flash.error) {
        Swal.fire({
            toast: true,
            position: 'top-end',
            icon: 'error',
            title: flash.error,
            showConfirmButton: false,
            timer: 4000,
            timerProgressBar: true,
        });
    }
}, { immediate: true, deep: true });
</script>

<template>
    <Head title="Custom Branding" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="text-xl font-semibold leading-tight text-gray-800">
                Custom Branding & Design
            </h2>
        </template>

        <div class="mt-6">
            <form @submit.prevent="submit" class="space-y-6">
                <!-- Tabs Navigation -->
                <div class="flex items-center gap-2 mb-6 border-b border-gray-100 pb-4 overflow-x-auto no-scrollbar">
                    <button 
                        type="button"
                        @click="$inertia.visit(route('settings.branding', { tab: 'branding' }))"
                        class="px-4 py-2 rounded-xl text-sm font-bold transition-all whitespace-nowrap"
                        :class="currentTab === 'branding' ? 'bg-primary/10 text-primary shadow-sm shadow-primary/10' : 'text-gray-400 hover:text-gray-600 hover:bg-gray-50'"
                    >
                        🎨 Clinic & Colors
                    </button>
                    <button 
                        type="button"
                        @click="$inertia.visit(route('settings.branding', { tab: 'hours' }))"
                        class="px-4 py-2 rounded-xl text-sm font-bold transition-all whitespace-nowrap"
                        :class="currentTab === 'hours' ? 'bg-primary/10 text-primary shadow-sm shadow-primary/10' : 'text-gray-400 hover:text-gray-600 hover:bg-gray-50'"
                    >
                        🕐 Operating Hours
                    </button>
                    <button 
                        type="button"
                        @click="$inertia.visit(route('settings.branding', { tab: 'qr' }))"
                        class="px-4 py-2 rounded-xl text-sm font-bold transition-all whitespace-nowrap"
                        :class="currentTab === 'qr' ? 'bg-primary/10 text-primary shadow-sm shadow-primary/10' : 'text-gray-400 hover:text-gray-600 hover:bg-gray-50'"
                    >
                        📱 QR Booking
                    </button>
                    <button 
                        type="button"
                        @click="$inertia.visit(route('settings.branding', { tab: 'portal' }))"
                        class="px-4 py-2 rounded-xl text-sm font-bold transition-all whitespace-nowrap"
                        :class="currentTab === 'portal' ? 'bg-primary/10 text-primary shadow-sm shadow-primary/10' : 'text-gray-400 hover:text-gray-600 hover:bg-gray-50'"
                    >
                        🖌️ Portal Design
                    </button>
                    <button 
                        type="button"
                        @click="$inertia.visit(route('settings.branding', { tab: 'designer' }))"
                        class="px-4 py-2 rounded-xl text-sm font-bold transition-all whitespace-nowrap"
                        :class="currentTab === 'designer' ? 'bg-primary/10 text-primary shadow-sm shadow-primary/10' : 'text-gray-400 hover:text-gray-600 hover:bg-gray-50'"
                    >
                        🚀 Landing Designer
                    </button>
                    <button 
                        type="button"
                        @click="$inertia.visit(route('settings.branding', { tab: 'features' }))"
                        class="px-4 py-2 rounded-xl text-sm font-bold transition-all whitespace-nowrap"
                        :class="currentTab === 'features' ? 'bg-primary/10 text-primary shadow-sm shadow-primary/10' : 'text-gray-400 hover:text-gray-600 hover:bg-gray-50'"
                    >
                        ⚙️ Features
                    </button>
                </div>

                <!-- Clinic Details & Colors Tab -->
                <div v-if="currentTab === 'branding'">
                    <ClinicBranding :form="form" :tenant="tenant" :is_premium="is_premium" />
                </div>

                <!-- Operating Hours Tab -->
                <div v-if="currentTab === 'hours'">
                    <OperatingHours :form="form" />
                </div>

                <!-- QR Code Booking Tab -->
                <div v-if="currentTab === 'qr'">
                    <QRCodeSetup 
                        :qr-code="qr_code"
                        :booking-url="booking_url"
                        :form="form"
                    />
                </div>

                <!-- Portal Customization Tab -->
                <div v-if="currentTab === 'portal'">
                    <div v-if="hasBranding">
                        <PortalCustomization :form="form" :staff="staff" :is_premium="is_premium" />
                    </div>
                    <div v-else class="flex flex-col items-center justify-center py-16 px-8 bg-base-200/50 rounded-3xl border-2 border-dashed border-base-300">
                        <div class="w-20 h-20 rounded-3xl bg-gradient-to-br from-amber-400 to-orange-500 flex items-center justify-center mb-6 shadow-lg">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-10 w-10 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M7 21a4 4 0 01-4-4V5a2 2 0 012-2h4a2 2 0 012 2v12a4 4 0 01-4 4zm0 0h12a2 2 0 002-2v-4a2 2 0 00-2-2h-2.343M11 7.343l1.657-1.657a2 2 0 012.828 0l2.829 2.829a2 2 0 010 2.828l-8.486 8.485M7 17h.01" />
                            </svg>
                        </div>
                        <h3 class="text-xl font-black text-base-content mb-2">Portal Customization</h3>
                        <p class="text-sm text-base-content/50 text-center max-w-md mb-6">
                            Customize the look and feel of your staff and patient portals.
                            Upgrade to the <strong>Pro</strong> plan to unlock these settings.
                        </p>
                        <a :href="route('settings.features')" class="btn btn-sm bg-gradient-to-r from-amber-400 to-orange-500 text-white border-none shadow-lg hover:shadow-xl transition-all">
                            Upgrade Plan
                        </a>
                    </div>
                </div>

                <!-- Landing Designer Tab -->
                <div v-if="currentTab === 'designer'">
                    <div v-if="hasBranding">
                        <LandingPageCustomizer :form="form" :tenant="tenant" :is_premium="is_premium" />
                    </div>
                    <div v-else class="flex flex-col items-center justify-center py-16 px-8 bg-base-200/50 rounded-3xl border-2 border-dashed border-base-300">
                        <div class="w-20 h-20 rounded-3xl bg-gradient-to-br from-amber-400 to-orange-500 flex items-center justify-center mb-6 shadow-lg">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-10 w-10 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M9.53 16.122a3 3 0 00-5.78 1.128 2.25 2.25 0 01-2.4 2.245 4.5 4.5 0 008.4-2.245c0-.399-.078-.78-.22-1.128zm0 0a15.998 15.998 0 003.388-1.62m-5.043-.025a15.994 15.994 0 011.622-3.395m3.42 3.42a15.995 15.995 0 004.764-4.648l3.876-5.814a1.151 1.151 0 00-1.597-1.597L14.146 6.32a15.996 15.996 0 00-4.649 4.763m3.42 3.42a6.776 6.776 0 00-3.42-3.42" />
                            </svg>
                        </div>
                        <h3 class="text-xl font-black text-base-content mb-2">Landing Page Designer</h3>
                        <p class="text-sm text-base-content/50 text-center max-w-md mb-6">
                            Design a custom landing page for your clinic.
                            Upgrade to the <strong>Pro</strong> plan to unlock the designer.
                        </p>
                        <a :href="route('settings.features')" class="btn btn-sm bg-gradient-to-r from-amber-400 to-orange-500 text-white border-none shadow-lg hover:shadow-xl transition-all">
                            Upgrade Plan
                        </a>
                    </div>
                </div>

                <!-- Feature Management Tab -->
                <div v-if="currentTab === 'features'">
                    <FeatureSettings :form="form" />
                </div>

                <!-- Save Button (always shown) -->
                <div class="flex justify-end pt-4">
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

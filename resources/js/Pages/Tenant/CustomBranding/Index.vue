<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, useForm, usePage } from '@inertiajs/vue3';
import { computed, watch, inject, ref, onMounted, onUnmounted, nextTick } from 'vue';
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

const initialEnabledFeatures = computed(() => {
    const raw = props.tenant?.enabled_features;
    if (Array.isArray(raw)) return raw;
    if (typeof raw === 'string' && raw.trim() !== '') {
        try {
            return JSON.parse(raw);
        } catch (e) {
            console.error('Failed to parse enabled_features:', e);
            return [];
        }
    }
    return [];
}).value;

const normalizeBoolean = (value, fallback = true) => {
    if (value === null || value === undefined) return fallback;
    if (typeof value === 'boolean') return value;
    if (typeof value === 'number') return value === 1;
    if (typeof value === 'string') {
        const normalized = value.trim().toLowerCase();
        if (['1', 'true', 'yes', 'on'].includes(normalized)) return true;
        if (['0', 'false', 'no', 'off', ''].includes(normalized)) return false;
    }
    return Boolean(value);
};

const form = useForm({
    clinic_name: props.tenant?.clinic_name || props.tenant?.name || '',
    email: props.tenant?.clinic_email || props.tenant?.email || '',
    phone: props.tenant?.clinic_phone || props.tenant?.phone || '',
    address: props.tenant?.clinic_address || props.tenant?.street || '',
    branding_color: props.tenant?.branding_color || page.props.branding?.primary_color || '#0ea5e9',
    sidebar_position: props.tenant?.sidebar_position || page.props.branding?.sidebar_position || 'left',
    support_chat_bottom_offset: Number(props.tenant?.support_chat_bottom_offset ?? page.props.branding?.support_chat_bottom_offset ?? 56),
    support_chat_right_offset: Number(props.tenant?.support_chat_right_offset ?? page.props.branding?.support_chat_right_offset ?? 24),
    portal_background_overlay_opacity: Number(props.tenant?.portal_background_overlay_opacity ?? 0),
    ui_sidebar_text_color: props.tenant?.ui_sidebar_text_color || '',
    ui_sidebar_text_size: Number(props.tenant?.ui_sidebar_text_size ?? 12),
    ui_header_title_color: props.tenant?.ui_header_title_color || '',
    ui_header_title_size: Number(props.tenant?.ui_header_title_size ?? 20),
    ui_footer_text_color: props.tenant?.ui_footer_text_color || '',
    ui_footer_text_size: Number(props.tenant?.ui_footer_text_size ?? 10),
    ui_main_text_color: props.tenant?.ui_main_text_color || '',
    ui_main_text_size: Number(props.tenant?.ui_main_text_size ?? 14),
    ui_card_background_color: props.tenant?.ui_card_background_color || '#ffffff',
    ui_card_border_color: props.tenant?.ui_card_border_color || '',
    ui_card_text_color: props.tenant?.ui_card_text_color || '',
    font_family: initialFontFamily,
    hero_title: props.tenant?.hero_title || '',
    hero_subtitle: props.tenant?.hero_subtitle || '',
    about_us_description: props.tenant?.about_us_description || '',
    enabled_features: initialEnabledFeatures,
    landing_page_config: props.tenant?.landing_page_config || {
        background_color: '#ffffff',
        text_primary: '#111827',
        text_secondary: '#4b5563',
    },
    portal_config: props.tenant?.portal_config || {
        apply_to: 'all',
        selected_staff: []
    },
    operating_hours: props.tenant?.operating_hours || {},
    online_booking_enabled: normalizeBoolean(props.tenant?.online_booking_enabled, true),
});

// 3. Import global Branding State to drive Live Preview
import { brandingState } from '@/States/brandingState';

// 4. Watch for real-time changes to push to the Layout (Interactive Mode)
watch(() => form.branding_color, (newColor) => {
    if (newColor) {
        brandingState.setPrimaryColor(newColor);
    }
}, { immediate: true });

watch(() => form.portal_background_overlay_opacity, (newValue) => {
    brandingState.setPortalBackgroundOverlayOpacity(newValue);
}, { immediate: true });

watch(() => [
    form.ui_sidebar_text_color,
    form.ui_sidebar_text_size,
    form.ui_header_title_color,
    form.ui_header_title_size,
    form.ui_footer_text_color,
    form.ui_footer_text_size,
    form.ui_main_text_color,
    form.ui_main_text_size,
    form.ui_card_background_color,
    form.ui_card_border_color,
    form.ui_card_text_color,
], () => {
    brandingState.setUiTokens({
        ui_sidebar_text_color: form.ui_sidebar_text_color || null,
        ui_sidebar_text_size: form.ui_sidebar_text_size,
        ui_header_title_color: form.ui_header_title_color || null,
        ui_header_title_size: form.ui_header_title_size,
        ui_footer_text_color: form.ui_footer_text_color || null,
        ui_footer_text_size: form.ui_footer_text_size,
        ui_main_text_color: form.ui_main_text_color || null,
        ui_main_text_size: form.ui_main_text_size,
        ui_card_background_color: form.ui_card_background_color || '#ffffff',
        ui_card_border_color: form.ui_card_border_color || null,
        ui_card_text_color: form.ui_card_text_color || null,
    });
}, { immediate: true });

const isSaving = ref(false);
const hasUnsavedChanges = ref(false);
const isApplyingRealtimeUpdate = ref(false);
let brandingChannel = null;
let saveTimeout = null;

const autoSave = () => {
    isSaving.value = true;
    hasUnsavedChanges.value = false;

    form
        .transform((data) => ({
            ...data,
            online_booking_enabled: normalizeBoolean(data.online_booking_enabled, true),
        }))
        .post('/settings', {
        preserveScroll: true,
        preserveState: true,
        forceFormData: true,
        onSuccess: () => {
            isSaving.value = false;
        },
        onError: (errors) => {
            isSaving.value = false;
            hasUnsavedChanges.value = true;
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

watch(() => form.data(), () => {
    if (isApplyingRealtimeUpdate.value) return;

    hasUnsavedChanges.value = true;
    if (saveTimeout) clearTimeout(saveTimeout);
    saveTimeout = setTimeout(() => {
        autoSave();
    }, 1500);
}, { deep: true });

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

const applyRealtimeBranding = async (event) => {
    isApplyingRealtimeUpdate.value = true;

    if (Object.prototype.hasOwnProperty.call(event, 'clinic_name')) form.clinic_name = event.clinic_name || '';
    if (Object.prototype.hasOwnProperty.call(event, 'email')) form.email = event.email || '';
    if (Object.prototype.hasOwnProperty.call(event, 'phone')) form.phone = event.phone || '';
    if (Object.prototype.hasOwnProperty.call(event, 'address')) form.address = event.address || '';
    if (Object.prototype.hasOwnProperty.call(event, 'branding_color')) form.branding_color = event.branding_color || '#0ea5e9';
    if (Object.prototype.hasOwnProperty.call(event, 'font_family') && event.font_family && typeof event.font_family === 'object') form.font_family = event.font_family;
    if (Object.prototype.hasOwnProperty.call(event, 'enabled_features') && Array.isArray(event.enabled_features)) form.enabled_features = [...event.enabled_features];
    if (Object.prototype.hasOwnProperty.call(event, 'landing_page_config') && event.landing_page_config && typeof event.landing_page_config === 'object') form.landing_page_config = { ...event.landing_page_config };
    if (Object.prototype.hasOwnProperty.call(event, 'portal_config') && event.portal_config && typeof event.portal_config === 'object') form.portal_config = { ...event.portal_config };
    if (Object.prototype.hasOwnProperty.call(event, 'operating_hours') && event.operating_hours && typeof event.operating_hours === 'object') form.operating_hours = { ...event.operating_hours };
    if (Object.prototype.hasOwnProperty.call(event, 'online_booking_enabled')) form.online_booking_enabled = normalizeBoolean(event.online_booking_enabled, true);
    if (Object.prototype.hasOwnProperty.call(event, 'sidebar_position')) form.sidebar_position = event.sidebar_position || 'left';
    if (Object.prototype.hasOwnProperty.call(event, 'support_chat_bottom_offset')) form.support_chat_bottom_offset = Number(event.support_chat_bottom_offset ?? 56);
    if (Object.prototype.hasOwnProperty.call(event, 'support_chat_right_offset')) form.support_chat_right_offset = Number(event.support_chat_right_offset ?? 24);
    if (Object.prototype.hasOwnProperty.call(event, 'portal_background_overlay_opacity')) form.portal_background_overlay_opacity = Number(event.portal_background_overlay_opacity ?? 0);
    if (Object.prototype.hasOwnProperty.call(event, 'ui_sidebar_text_color')) form.ui_sidebar_text_color = event.ui_sidebar_text_color || '';
    if (Object.prototype.hasOwnProperty.call(event, 'ui_sidebar_text_size')) form.ui_sidebar_text_size = Number(event.ui_sidebar_text_size ?? 12);
    if (Object.prototype.hasOwnProperty.call(event, 'ui_header_title_color')) form.ui_header_title_color = event.ui_header_title_color || '';
    if (Object.prototype.hasOwnProperty.call(event, 'ui_header_title_size')) form.ui_header_title_size = Number(event.ui_header_title_size ?? 20);
    if (Object.prototype.hasOwnProperty.call(event, 'ui_footer_text_color')) form.ui_footer_text_color = event.ui_footer_text_color || '';
    if (Object.prototype.hasOwnProperty.call(event, 'ui_footer_text_size')) form.ui_footer_text_size = Number(event.ui_footer_text_size ?? 10);
    if (Object.prototype.hasOwnProperty.call(event, 'ui_main_text_color')) form.ui_main_text_color = event.ui_main_text_color || '';
    if (Object.prototype.hasOwnProperty.call(event, 'ui_main_text_size')) form.ui_main_text_size = Number(event.ui_main_text_size ?? 14);
    if (Object.prototype.hasOwnProperty.call(event, 'ui_card_background_color')) form.ui_card_background_color = event.ui_card_background_color || '#ffffff';
    if (Object.prototype.hasOwnProperty.call(event, 'ui_card_border_color')) form.ui_card_border_color = event.ui_card_border_color || '';
    if (Object.prototype.hasOwnProperty.call(event, 'ui_card_text_color')) form.ui_card_text_color = event.ui_card_text_color || '';

    if (Object.prototype.hasOwnProperty.call(event, 'primary_color') && event.primary_color) {
        brandingState.setPrimaryColor(event.primary_color);
    }

    if (Object.prototype.hasOwnProperty.call(event, 'portal_background_image')) {
        brandingState.setPortalBackgroundImage(event.portal_background_image || null);
    }

    await nextTick();
    isApplyingRealtimeUpdate.value = false;
};

onMounted(() => {
    const tenantId = props.tenant?.id;
    if (!window.Echo || !tenantId) return;

    brandingChannel = window.Echo.channel(`tenant.${tenantId}.branding`)
        .listen('.TenantBrandingUpdated', async (event) => {
            await applyRealtimeBranding(event || {});
        });
});

onUnmounted(() => {
    const tenantId = props.tenant?.id;
    if (window.Echo && tenantId) {
        window.Echo.leave(`tenant.${tenantId}.branding`);
    }
    brandingChannel = null;
});
</script>

<template>
    <Head title="Custom Branding" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="text-xl font-semibold leading-tight text-base-content">
                Custom Branding & Design
            </h2>
        </template>

        <div class="mt-6">
            <form @submit.prevent class="space-y-6">
                <!-- Tabs Navigation -->
                <div class="tenant-subnav-panel flex items-center gap-2 mb-6 p-1 overflow-x-auto no-scrollbar">
                    <button 
                        type="button"
                        @click="$inertia.visit(route('settings.branding', { tab: 'branding' }))"
                        class="tenant-subnav-pill px-4 py-2 rounded-full text-sm font-semibold transition-all whitespace-nowrap border"
                        :class="currentTab === 'branding' ? 'tenant-subnav-pill-active' : ''"
                    >
                        Clinic & Colors
                    </button>
                    <button 
                        type="button"
                        @click="$inertia.visit(route('settings.branding', { tab: 'hours' }))"
                        class="tenant-subnav-pill px-4 py-2 rounded-full text-sm font-semibold transition-all whitespace-nowrap border"
                        :class="currentTab === 'hours' ? 'tenant-subnav-pill-active' : ''"
                    >
                        Operating Hours
                    </button>
                    <button 
                        type="button"
                        @click="$inertia.visit(route('settings.branding', { tab: 'qr' }))"
                        class="tenant-subnav-pill px-4 py-2 rounded-full text-sm font-semibold transition-all whitespace-nowrap border"
                        :class="currentTab === 'qr' ? 'tenant-subnav-pill-active' : ''"
                    >
                        QR Booking
                    </button>
                    <button 
                        type="button"
                        @click="$inertia.visit(route('settings.branding', { tab: 'portal' }))"
                        class="tenant-subnav-pill px-4 py-2 rounded-full text-sm font-semibold transition-all whitespace-nowrap border"
                        :class="currentTab === 'portal' ? 'tenant-subnav-pill-active' : ''"
                    >
                        Portal Design
                    </button>
                    <button 
                        type="button"
                        @click="$inertia.visit(route('settings.branding', { tab: 'designer' }))"
                        class="tenant-subnav-pill px-4 py-2 rounded-full text-sm font-semibold transition-all whitespace-nowrap border"
                        :class="currentTab === 'designer' ? 'tenant-subnav-pill-active' : ''"
                    >
                        Landing Designer
                    </button>
                    <button 
                        type="button"
                        @click="$inertia.visit(route('settings.branding', { tab: 'features' }))"
                        class="tenant-subnav-pill px-4 py-2 rounded-full text-sm font-semibold transition-all whitespace-nowrap border"
                        :class="currentTab === 'features' ? 'tenant-subnav-pill-active' : ''"
                    >
                        Features
                    </button>
                </div>

                <!-- Clinic Details & Colors Tab -->
                <div v-if="currentTab === 'branding'">
                    <ClinicBranding :form="form" :tenant="tenant" :is_premium="true" />
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
                    <PortalCustomization :form="form" :staff="staff" :is_premium="true" />
                </div>

                <!-- Landing Designer Tab -->
                <div v-if="currentTab === 'designer'">
                    <LandingPageCustomizer :form="form" :tenant="tenant" :is_premium="true" />
                </div>

                <!-- Feature Management Tab -->
                <div v-if="currentTab === 'features'">
                    <FeatureSettings :form="form" />
                </div>

                

            </form>
        </div>
    </AuthenticatedLayout>
</template>

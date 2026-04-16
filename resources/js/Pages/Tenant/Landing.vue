<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3';
import { computed, ref, onMounted, onUnmounted, watch } from 'vue';
import BookingModal from '@/Pages/Tenant/Booking/Partials/BookingModal.vue';
import LoginModal from '@/Pages/Tenant/Auth/Partials/LoginModal.vue';

const props = defineProps({
    services: Array,
    dentists: Array,
    teamMembers: {
        type: Array,
        default: () => [],
    },
    medicalRecords: {
        type: Array,
        default: () => [],
    },
    recaptchaSiteKey: String,
    online_booking_enabled: {
        type: Boolean,
        default: true
    },
    operating_hours: {
        type: Object,
        default: () => ({})
    },
});

import { usePage } from '@inertiajs/vue3';
const page = usePage();
const tenant = computed(() => page.props.tenant);

const showBookingModal = ref(false);
const showLoginModal = ref(false);
const showBookingUnavailableModal = ref(false);
const onlineBookingEnabled = ref(props.online_booking_enabled ?? true);
const liveOperatingHours = ref({ ...(props.operating_hours || {}) });
const liveLandingPageConfig = ref({ ...(tenant.value?.landing_page_config || {}) });
const liveHeroTitle = ref(tenant.value?.hero_title || `Welcome to ${tenant.value?.name}`);
const liveHeroSubtitle = ref(tenant.value?.hero_subtitle || 'Providing high-quality dental care with a gentle touch and modern technology.');
const liveAboutDescription = ref(tenant.value?.about_us_description || `${tenant.value?.name} is dedicated to providing the best dental care in the region. Our team of experienced professionals is here to ensure your smile remains healthy and beautiful.`);
let bookingChannel = null;
let brandingChannel = null;

const form = useForm({
    name: '',
    email: '',
    phone: '',
    subject: '',
    message: '',
});

const submit = () => {
    form.post(route('tenant.concern.store'), {
        preserveScroll: true,
        onSuccess: () => form.reset(),
    });
};

const brandingColor = computed(() => tenant.value?.branding_color || '#3b82f6'); // Default blue-500

// Typography: Use tenant's granular font_family if available
const fonts = computed(() => {
    const defaults = {
        header: 'font-sans',
        sidebar: 'font-sans',
        names: 'font-sans',
        general: 'font-sans'
    };
    
    const tenantFonts = tenant.value?.font_family;
    
    // Handle old string format or missing fonts
    if (typeof tenantFonts === 'string') {
        return { ...defaults, general: tenantFonts };
    }
    
    return { ...defaults, ...tenantFonts };
});

const backgroundColor = computed(() => {
    return landingConfig.value.background_color || '#ffffff';
});

const heroTitle = computed(() => liveHeroTitle.value);
const heroSubtitle = computed(() => liveHeroSubtitle.value);
const aboutDescription = computed(() => liveAboutDescription.value);
const heroBadgeText = computed(() => {
    const value = landingConfig.value.sections?.hero?.badge_text;
    return typeof value === 'string' && value.trim() !== '' ? value : 'Expert Dental Care';
});
const heroCtaText = computed(() => {
    const value = landingConfig.value.sections?.hero?.cta_text;
    return typeof value === 'string' && value.trim() !== '' ? value : 'Schedule Your Visit';
});

const formattedAddress = computed(() => {
    const parts = [tenant.value?.street, tenant.value?.barangay, tenant.value?.city, tenant.value?.province];
    return parts.filter(p => p).join(', ');
});

const landingConfig = computed(() => {
    const config = liveLandingPageConfig.value || {};
    const sectionDefaults = {
        hero: { active: true, background_type: 'color', background_color: '#f9fafb', background_image: null },
        content: {
            active: true,
            image: null,
            title: 'Committed to Excellence in Dental Care',
            subtitle: 'Our clinic is dedicated to providing the best dental care in the region. Our team of experienced professionals is here to ensure your smile remains healthy and beautiful.',
            highlights: ['Modern Technology', 'Sterilized Environment', 'Compassionate Experts'],
            background_type: 'color',
            background_color: '#f9fafb',
            background_image: null,
        },
        services: {
            active: true,
            image: null,
            title: 'Our Specialized Services',
            subtitle: 'We offer a wide range of dental treatments to keep your clinic healthy and your smile glowing.',
            background_type: 'color',
            background_color: '#ffffff',
            background_image: null,
        },
        team: {
            active: true,
            image: null,
            title: 'Meet Our Specialist Team',
            subtitle: 'Expert dentists dedicated to provide world-class dental treatments with care.',
            background_type: 'color',
            background_color: '#ffffff',
            background_image: null,
        },
        contact: {
            active: true,
            image: null,
            title: "Have a Concern? We're Here to Help.",
            subtitle: "Whether you're looking for an appointment or have a general inquiry, feel free to send us a message. Our team will respond as quickly as possible.",
            background_type: 'color',
            background_color: '#ffffff',
            background_image: null,
        },
    };

    const incomingSections = (config.sections && typeof config.sections === 'object') ? config.sections : {};
    const mergedSections = {
        hero: { ...sectionDefaults.hero, ...(incomingSections.hero || {}) },
        content: { ...sectionDefaults.content, ...(incomingSections.content || {}) },
        services: { ...sectionDefaults.services, ...(incomingSections.services || {}) },
        team: { ...sectionDefaults.team, ...(incomingSections.team || {}) },
        contact: { ...sectionDefaults.contact, ...(incomingSections.contact || {}) },
    };

    return {
        background_color: config.background_color || '#ffffff',
        sections: mergedSections,
        text_primary: config.text_primary || '#111827',
        text_secondary: config.text_secondary || '#4b5563',
        operating_hours_style: {
            section_background: config.operating_hours_style?.section_background || '#111827',
            section_title_color: config.operating_hours_style?.section_title_color || '#ffffff',
            section_border_color: config.operating_hours_style?.section_border_color || '#1f2937',
            card_open_background: config.operating_hours_style?.card_open_background || '#1f2937',
            card_closed_background: config.operating_hours_style?.card_closed_background || '#111827',
            card_open_day_color: config.operating_hours_style?.card_open_day_color || '#ffffff',
            card_closed_day_color: config.operating_hours_style?.card_closed_day_color || '#fca5a5',
            card_time_color: config.operating_hours_style?.card_time_color || '#9ca3af',
            closed_label_color: config.operating_hours_style?.closed_label_color || '#fda4af',
            copyright_color: config.operating_hours_style?.copyright_color || '#6b7280',
        },
    };
});

const autoTeamMembersFromServer = computed(() => {
    if (Array.isArray(props.teamMembers) && props.teamMembers.length > 0) {
        const autoOnly = props.teamMembers.filter((member) => member?.source === 'staff');
        if (autoOnly.length > 0) return autoOnly;
    }

    return (props.dentists || []).map((dentist) => ({
        id: `staff-fallback-${dentist.id}`,
        source: 'staff',
        name: dentist.name,
        role: 'Professional Dentist',
        bio: '',
        image_url: '',
    }));
});

const manualTeamMembersFromConfig = computed(() => {
    const cards = liveLandingPageConfig.value?.team?.manual_cards;
    if (!Array.isArray(cards)) return [];

    return cards
        .filter((card) => card && typeof card === 'object')
        .map((card, index) => ({
            id: card.id || `manual-${index}`,
            source: 'manual',
            name: String(card.name || '').trim(),
            role: String(card.role || '').trim(),
            bio: String(card.bio || '').trim(),
            image_url: String(card.image_url || '').trim(),
        }))
        .filter((card) => card.name !== '');
});

const resolvedTeamMembers = computed(() => {
    const mode = liveLandingPageConfig.value?.team?.source_mode || 'auto_staff';

    if (mode === 'manual') {
        return manualTeamMembersFromConfig.value;
    }

    if (mode === 'hybrid') {
        return [...autoTeamMembersFromServer.value, ...manualTeamMembersFromConfig.value];
    }

    return autoTeamMembersFromServer.value;
});

watch(
    () => tenant.value,
    (currentTenant) => {
        if (!currentTenant) return;
        liveLandingPageConfig.value = { ...(currentTenant.landing_page_config || {}) };
        liveHeroTitle.value = currentTenant.hero_title || `Welcome to ${currentTenant.name}`;
        liveHeroSubtitle.value = currentTenant.hero_subtitle || 'Providing high-quality dental care with a gentle touch and modern technology.';
        liveAboutDescription.value = currentTenant.about_us_description || `${currentTenant.name} is dedicated to providing the best dental care in the region. Our team of experienced professionals is here to ensure your smile remains healthy and beautiful.`;
    },
    { deep: true }
);

const isSectionActive = (name) => landingConfig.value.sections[name]?.active;
const getSectionTitle = (name, fallback) => {
    const value = landingConfig.value.sections?.[name]?.title;
    return typeof value === 'string' && value.trim() !== '' ? value : fallback;
};

const getSectionSubtitle = (name, fallback) => {
    const value = landingConfig.value.sections?.[name]?.subtitle;
    return typeof value === 'string' && value.trim() !== '' ? value : fallback;
};

const contentHighlights = computed(() => {
    const raw = landingConfig.value.sections?.content?.highlights;
    if (!Array.isArray(raw)) {
        return ['Modern Technology', 'Sterilized Environment', 'Compassionate Experts'];
    }

    return raw
        .map((item) => String(item || '').trim())
        .filter((item) => item !== '')
        .slice(0, 5);
});

const getSectionImage = (name) => {
    const img = landingConfig.value.sections[name]?.image;
    
    // Fallback to high-quality dental/medical defaults
    if (!img) {
        const defaults = {
            content: '/images/dentist-model.png',
            services: '/images/dental-smile-for-landingpage.png',
            team: '/images/dentist-model.png',
            contact: '/images/OneTop.png'
        };
        return defaults[name];
    }

    if (img && typeof img === 'string' && (img.startsWith('data:image') || img.startsWith('http') || img.startsWith('/'))) {
        return img;
    }
    return img ? '/tenant-storage/' + img : null;
};

const getSectionBackgroundStyle = (name, fallbackColor = '#ffffff') => {
    const section = landingConfig.value.sections?.[name] || {};
    const backgroundType = section.background_type === 'image' ? 'image' : 'color';
    const backgroundImage = section.background_image;

    if (backgroundType === 'image' && backgroundImage) {
        return {
            backgroundImage: `url(${backgroundImage})`,
            backgroundSize: 'cover',
            backgroundPosition: 'center',
            backgroundRepeat: 'no-repeat',
        };
    }

    return {
        backgroundColor: section.background_color || fallbackColor,
    };
};

const bookingEnabled = computed(() => onlineBookingEnabled.value);

const openBookingFlow = () => {
    if (bookingEnabled.value) {
        showBookingModal.value = true;
        return;
    }

    showBookingUnavailableModal.value = true;
};

const closeBookingUnavailableModal = () => {
    showBookingUnavailableModal.value = false;
};

const goToSection = (sectionId) => {
    closeBookingUnavailableModal();
    const section = document.getElementById(sectionId);
    if (section) {
        section.scrollIntoView({ behavior: 'smooth', block: 'start' });
    }
};

const applyTenantPatch = (patch = {}) => {
    if (!tenant.value || typeof tenant.value !== 'object') return;

    Object.keys(patch).forEach((key) => {
        if (Object.prototype.hasOwnProperty.call(patch, key)) {
            tenant.value[key] = patch[key];
        }
    });
};

onMounted(() => {
    if (!window.Echo || !tenant.value?.id) return;

    bookingChannel = window.Echo.channel(`tenant.${tenant.value.id}.booking`)
        .listen('.OnlineBookingStatusUpdated', (event) => {
            onlineBookingEnabled.value = Boolean(event.online_booking_enabled);

            if (!onlineBookingEnabled.value && showBookingModal.value) {
                showBookingModal.value = false;
                showBookingUnavailableModal.value = true;
            }
        });

    brandingChannel = window.Echo.channel(`tenant.${tenant.value.id}.branding`)
        .listen('.TenantBrandingUpdated', (event) => {
            if (Object.prototype.hasOwnProperty.call(event, 'online_booking_enabled')) {
                onlineBookingEnabled.value = Boolean(event.online_booking_enabled);
            }

            applyTenantPatch({
                ...(Object.prototype.hasOwnProperty.call(event, 'clinic_name') ? { name: event.clinic_name || tenant.value?.name } : {}),
                ...(Object.prototype.hasOwnProperty.call(event, 'email') ? { email: event.email || '' } : {}),
                ...(Object.prototype.hasOwnProperty.call(event, 'phone') ? { phone: event.phone || '' } : {}),
                ...(Object.prototype.hasOwnProperty.call(event, 'address') ? { street: event.address || '' } : {}),
                ...(Object.prototype.hasOwnProperty.call(event, 'branding_color') ? { branding_color: event.branding_color || '#3b82f6' } : {}),
                ...(Object.prototype.hasOwnProperty.call(event, 'font_family') ? { font_family: event.font_family || tenant.value?.font_family } : {}),
                ...(Object.prototype.hasOwnProperty.call(event, 'logo_path') ? { logo_path: event.logo_path || null } : {}),
            });

            if (event?.landing_page_config && typeof event.landing_page_config === 'object') {
                liveLandingPageConfig.value = { ...event.landing_page_config };
            }

            if (Object.prototype.hasOwnProperty.call(event, 'hero_title')) {
                liveHeroTitle.value = event.hero_title || `Welcome to ${tenant.value?.name}`;
            }

            if (Object.prototype.hasOwnProperty.call(event, 'hero_subtitle')) {
                liveHeroSubtitle.value = event.hero_subtitle || 'Providing high-quality dental care with a gentle touch and modern technology.';
            }

            if (Object.prototype.hasOwnProperty.call(event, 'about_us_description')) {
                liveAboutDescription.value = event.about_us_description || `${tenant.value?.name} is dedicated to providing the best dental care in the region. Our team of experienced professionals is here to ensure your smile remains healthy and beautiful.`;
            }

            if (event?.operating_hours && typeof event.operating_hours === 'object') {
                liveOperatingHours.value = { ...event.operating_hours };
            }

            if (!onlineBookingEnabled.value && showBookingModal.value) {
                showBookingModal.value = false;
                showBookingUnavailableModal.value = true;
            }
        });
});

onUnmounted(() => {
    if (window.Echo && tenant.value?.id) {
        window.Echo.leave(`tenant.${tenant.value.id}.booking`);
        window.Echo.leave(`tenant.${tenant.value.id}.branding`);
    }
    bookingChannel = null;
    brandingChannel = null;
});

const formatTime = (time) => {
    if (!time) return '';
    const [h, m] = time.split(':');
    const hour = parseInt(h);
    const ampm = hour >= 12 ? 'PM' : 'AM';
    const h12 = hour % 12 || 12;
    return `${h12}:${m} ${ampm}`;
};

const dayLabels = {
    monday: 'Mon', tuesday: 'Tue', wednesday: 'Wed', 
    thursday: 'Thu', friday: 'Fri', saturday: 'Sat', sunday: 'Sun'
};

const normalizeEnabled = (value, fallback = false) => {
    if (typeof value === 'boolean') return value;
    if (typeof value === 'number') return value === 1;
    if (typeof value === 'string') {
        const normalized = value.trim().toLowerCase();
        if (['1', 'true', 'yes', 'on'].includes(normalized)) return true;
        if (['0', 'false', 'no', 'off', ''].includes(normalized)) return false;
    }
    return fallback;
};

const operatingDayOrder = ['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday'];

const orderedOperatingHours = computed(() => {
    const source = liveOperatingHours.value && typeof liveOperatingHours.value === 'object'
        ? liveOperatingHours.value
        : {};

    return operatingDayOrder.map((day) => {
        const schedule = source[day] || {};
        const enabled = normalizeEnabled(schedule.enabled, !['saturday', 'sunday'].includes(day));

        return {
            day,
            enabled,
            open: schedule.open || '08:00',
            close: schedule.close || '17:00',
        };
    });
});

const operatingHoursDesign = computed(() => landingConfig.value.operating_hours_style || {});
</script>

<template>
    <Head :title="tenant?.name" />

    <div class="min-h-screen transition-colors duration-500" :class="fonts.general" :style="{ backgroundColor: backgroundColor, color: landingConfig.text_secondary }">
        <!-- Navigation -->
        <nav class="sticky top-0 z-50 bg-white/80 backdrop-blur-md border-b border-gray-100">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="flex justify-between h-20 items-center">
                    <div class="flex items-center gap-3">
                        <div v-if="tenant?.logo_path" class="flex-shrink-0">
                            <img :src="tenant.logo_path" :alt="tenant.name" class="h-10 w-auto object-contain">
                        </div>
                        <div class="text-2xl font-black tracking-tight" :style="{ color: brandingColor }" :class="fonts.header">
                            {{ tenant?.name }}
                        </div>
                    </div>
                    <div class="hidden md:flex items-center space-x-8">
                        <a v-if="isSectionActive('content')" href="#content" class="text-sm font-medium hover:text-gray-600 transition-colors">About</a>
                        <a v-if="isSectionActive('services')" href="#services" class="text-sm font-medium hover:text-gray-600 transition-colors">Services</a>
                        <a v-if="isSectionActive('team')" href="#team" class="text-sm font-medium hover:text-gray-600 transition-colors">Our team</a>
                        <a v-if="isSectionActive('contact')" href="#contact" class="text-sm font-medium hover:text-gray-600 transition-colors">Contact</a>
                        <button @click="showLoginModal = true" class="text-sm font-medium text-gray-500 hover:text-gray-900">Login</button>
                        <button @click="openBookingFlow" 
                           class="inline-flex items-center px-6 py-3 border border-transparent text-sm font-bold rounded-full shadow-sm text-white transition-all transform hover:scale-105 active:scale-95"
                           :style="{ backgroundColor: brandingColor }">
                            Book Appointment
                        </button>
                    </div>
                    <!-- Mobile Button (Simplified) -->
                    <div class="md:hidden">
                        <button @click="openBookingFlow" 
                           class="inline-flex items-center px-4 py-2 border border-transparent text-xs font-bold rounded-full text-white"
                           :style="{ backgroundColor: brandingColor }">
                            Book Now
                        </button>
                    </div>
                </div>
            </div>
        </nav>

        <!-- Hero Section -->
        <header v-if="isSectionActive('hero')" class="relative py-20 lg:py-32 overflow-hidden" :style="getSectionBackgroundStyle('hero', '#f9fafb')">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 relative z-10">
                <div class="lg:w-2/3">
                    <div class="flex flex-wrap items-center gap-3 mb-6">
                        <span class="inline-block px-4 py-1.5 text-xs font-bold uppercase tracking-widest text-white rounded-full bg-blue-600/10" :style="{ color: brandingColor, backgroundColor: brandingColor + '15' }">
                            {{ heroBadgeText }}
                        </span>
                        <span
                            class="inline-flex items-center gap-2 px-3 py-1.5 rounded-full text-[10px] font-black uppercase tracking-widest border"
                            :class="bookingEnabled ? 'text-emerald-700 bg-emerald-50 border-emerald-200' : 'text-red-700 bg-red-50 border-red-200'"
                        >
                            <span class="w-2 h-2 rounded-full" :class="bookingEnabled ? 'bg-emerald-500' : 'bg-red-500'"></span>
                            Online Booking {{ bookingEnabled ? 'Active' : 'Unavailable' }}
                        </span>
                    </div>
                    <h1 class="text-5xl lg:text-7xl font-black leading-tight mb-8" :class="fonts.header" :style="{ color: landingConfig.text_primary }">
                        {{ heroTitle }}
                    </h1>
                    <p class="text-xl mb-10 leading-relaxed max-w-2xl" :style="{ color: landingConfig.text_secondary }">
                        {{ heroSubtitle }}
                    </p>
                    <div class="flex flex-col sm:flex-row items-center gap-6">
                        <button @click="openBookingFlow" 
                           class="inline-flex justify-center items-center px-8 py-4 border border-transparent text-lg font-bold rounded-full shadow-xl text-white transition-all hover:shadow-2xl hover:scale-105 active:scale-95"
                           :style="{ backgroundColor: brandingColor }">
                            {{ heroCtaText }}
                        </button>
                        
                        <!-- QR Code Section (only when booking enabled) -->
                        <div v-if="bookingEnabled && tenant.qr_code_path" class="flex items-center gap-4 p-3 bg-white rounded-3xl shadow-sm border border-gray-100">
                            <div class="w-16 h-16 bg-gray-100 rounded-xl flex items-center justify-center overflow-hidden">
                                <img :src="'/tenant-storage/' + tenant.qr_code_path" class="w-full h-full object-cover">
                            </div>
                            <div class="text-left">
                                <p class="text-[10px] font-black text-gray-400 uppercase tracking-widest leading-none mb-1">Mobile Booking</p>
                                <p class="text-xs font-bold text-gray-700">Scan to Book</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <!-- Decorative Elements -->
            <div class="absolute top-0 right-0 w-1/3 h-full bg-blue-50/50 hidden lg:block -skew-x-12 transform translate-x-20"></div>
        </header>

        <!-- Services Section -->
        <section v-if="isSectionActive('services')" id="services" class="py-24" :style="getSectionBackgroundStyle('services', landingConfig.background_color)">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="flex flex-col lg:flex-row gap-16 items-center mb-16">
                    <div class="lg:w-1/2" v-if="getSectionImage('services')">
                        <img :src="getSectionImage('services')" class="w-full rounded-[3rem] shadow-2xl object-cover aspect-video lg:aspect-square" />
                    </div>
                    <div :class="getSectionImage('services') ? 'lg:w-1/2' : 'w-full text-center'">
                        <h2 class="text-3xl lg:text-4xl font-black mb-4" :class="fonts.header" :style="{ color: landingConfig.text_primary }">{{ getSectionTitle('services', 'Our Specialized Services') }}</h2>
                        <p class="max-w-2xl" :class="getSectionImage('services') ? '' : 'mx-auto'" :style="{ color: landingConfig.text_secondary }">{{ getSectionSubtitle('services', 'We offer a wide range of dental treatments to keep your clinic healthy and your smile glowing.') }}</p>
                    </div>
                </div>
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
                    <div v-for="service in services" :key="service.id" class="p-8 bg-white/50 backdrop-blur-sm rounded-3xl border border-transparent hover:border-primary/20 transition-all group shadow-sm">
                        <div class="w-14 h-14 rounded-2xl flex items-center justify-center mb-6 text-2xl group-hover:scale-110 transition-transform" :style="{ backgroundColor: brandingColor + '15', color: brandingColor }">
                            🦷
                        </div>
                        <h3 class="text-xl font-bold mb-3" :class="fonts.header" :style="{ color: landingConfig.text_primary }">{{ service.name }}</h3>
                        <p class="text-sm leading-relaxed mb-6" :style="{ color: landingConfig.text_secondary }">
                            {{ service.description || 'Professional dental care tailored to your needs.' }}
                        </p>
                        <p class="text-lg font-black" :style="{ color: brandingColor }">₱ {{ service.price }}</p>
                    </div>
                </div>
            </div>
        </section>

        <!-- About Us -->
        <section v-if="isSectionActive('content')" id="content" class="py-24" :style="getSectionBackgroundStyle('content', '#f9fafb')">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 flex flex-col lg:flex-row items-center gap-16">
                <div class="lg:w-1/2" v-if="getSectionImage('content')">
                    <div class="relative">
                        <img :src="getSectionImage('content')" class="w-full aspect-square object-cover rounded-[3rem] shadow-2xl" />
                    </div>
                </div>
                <div class="lg:w-1/2">
                    <h2 class="text-3xl lg:text-4xl font-black mb-8" :class="fonts.header" :style="{ color: landingConfig.text_primary }">{{ getSectionTitle('content', 'Committed to Excellence in Dental Care') }}</h2>
                    <p class="text-lg leading-relaxed mb-8" :style="{ color: landingConfig.text_secondary }">
                        {{ getSectionSubtitle('content', aboutDescription) }}
                    </p>
                    <ul class="space-y-4 mb-10">
                        <li v-for="(item, idx) in contentHighlights" :key="`content-highlight-${idx}`" class="flex items-center gap-3">
                            <span class="w-6 h-6 rounded-full flex items-center justify-center text-xs text-white" :style="{ backgroundColor: brandingColor }">✓</span>
                            <span class="font-medium">{{ item }}</span>
                        </li>
                    </ul>
                </div>
            </div>
        </section>

        <!-- Team Section -->
        <section v-if="isSectionActive('team')" id="team" class="py-24" :style="getSectionBackgroundStyle('team', landingConfig.background_color)">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="flex flex-col lg:flex-row-reverse gap-16 items-center mb-16">
                    <div class="lg:w-1/2" v-if="getSectionImage('team')">
                        <img :src="getSectionImage('team')" class="w-full rounded-[3rem] shadow-2xl object-cover aspect-video lg:aspect-square" />
                    </div>
                    <div :class="getSectionImage('team') ? 'lg:w-1/2' : 'w-full text-center'">
                        <h2 class="text-3xl lg:text-4xl font-black mb-4" :class="fonts.header" :style="{ color: landingConfig.text_primary }">{{ getSectionTitle('team', 'Meet Our Specialist Team') }}</h2>
                        <p class="max-w-2xl mx-auto" :style="{ color: landingConfig.text_secondary }">{{ getSectionSubtitle('team', 'Expert dentists dedicated to provide world-class dental treatments with care.') }}</p>
                    </div>
                </div>
                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-8">
                    <div v-for="member in resolvedTeamMembers" :key="member.id" class="text-center group">
                        <div class="w-full aspect-[4/5] bg-white rounded-3xl mb-6 overflow-hidden relative shadow-sm border border-base-200">
                             <img v-if="member.image_url" :src="member.image_url" :alt="member.name" class="w-full h-full object-cover" />
                             <div v-else class="w-full h-full flex items-center justify-center text-7xl opacity-20 group-hover:scale-110 transition-transform">👨‍⚕️</div>
                        </div>
                        <h3 class="text-xl font-bold" :class="fonts.names" :style="{ color: landingConfig.text_primary }">{{ member.name }}</h3>
                        <p class="text-sm" :style="{ color: landingConfig.text_secondary }">{{ member.role || 'Clinic Team Member' }}</p>
                        <p v-if="member.bio" class="text-xs mt-2 line-clamp-3" :style="{ color: landingConfig.text_secondary }">{{ member.bio }}</p>
                    </div>
                </div>
            </div>
        </section>

        <!-- Contact Us Section -->
        <section v-if="isSectionActive('contact')" id="contact-form" class="py-24 border-t border-gray-100" :style="getSectionBackgroundStyle('contact', landingConfig.background_color)">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="flex flex-col lg:flex-row gap-16 items-center">
                    <div class="lg:w-1/2">
                        <div v-if="getSectionImage('contact')" class="mb-8">
                            <img :src="getSectionImage('contact')" class="w-full rounded-[2rem] shadow-lg object-cover" />
                        </div>
                        <span class="text-xs font-bold uppercase tracking-widest" :style="{ color: brandingColor }">Get in touch</span>
                        <h2 class="text-3xl lg:text-4xl font-black mb-6 mt-2" :class="fonts.header" :style="{ color: landingConfig.text_primary }">{{ getSectionTitle('contact', "Have a Concern? We're Here to Help.") }}</h2>
                        <p class="text-lg mb-10 leading-relaxed" :style="{ color: landingConfig.text_secondary }">
                            {{ getSectionSubtitle('contact', "Whether you're looking for an appointment or have a general inquiry, feel free to send us a message. Our team will respond as quickly as possible.") }}
                        </p>
                        
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-8">
                            <div class="flex items-start gap-4">
                                <span class="text-2xl">📞</span>
                                <div>
                                    <p class="font-bold" :style="{ color: landingConfig.text_primary }">Call Us</p>
                                    <p class="text-sm" :style="{ color: landingConfig.text_secondary }">{{ tenant.phone || 'Phone not provided' }}</p>
                                </div>
                            </div>
                            <div class="flex items-start gap-4">
                                <span class="text-2xl">✉️</span>
                                <div>
                                    <p class="font-bold" :style="{ color: landingConfig.text_primary }">Email Us</p>
                                    <p class="text-sm" :style="{ color: landingConfig.text_secondary }">{{ tenant.email || 'Email not provided' }}</p>
                                </div>
                            </div>
                            <div class="flex items-start gap-4">
                                <span class="text-2xl">📍</span>
                                <div>
                                    <p class="font-bold" :style="{ color: landingConfig.text_primary }">Visit Us</p>
                                    <p class="text-sm" :style="{ color: landingConfig.text_secondary }">{{ formattedAddress || 'Address not provided' }}</p>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="lg:w-1/2 bg-white p-8 lg:p-12 rounded-[2.5rem] shadow-xl relative overflow-hidden border border-base-200">
                        <!-- Flash Message -->
                        <div v-if="$page.props.flash && $page.props.flash.success" class="mb-6 p-4 bg-green-100 border border-green-200 text-green-700 rounded-xl flex items-center gap-3">
                            <span>✅</span>
                            <span>{{ $page.props.flash.success }}</span>
                        </div>

                        <form @submit.prevent="submit" class="space-y-6 relative z-10">
                            <!-- ... Form Fields ... -->
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
                                <div>
                                    <label class="block text-sm font-bold text-gray-700 mb-1">Your Name</label>
                                    <input type="text" v-model="form.name" required class="w-full bg-base-100 border-none rounded-2xl shadow-inner focus:ring-2 p-4" :style="{ '--tw-ring-color': brandingColor }" placeholder="John Doe">
                                </div>
                                <div>
                                    <label class="block text-sm font-bold text-gray-700 mb-1">Email Address</label>
                                    <input type="email" v-model="form.email" required class="w-full bg-base-100 border-none rounded-2xl shadow-inner focus:ring-2 p-4" :style="{ '--tw-ring-color': brandingColor }" placeholder="john@example.com">
                                </div>
                            </div>
                            <div>
                                <label class="block text-sm font-bold text-gray-700 mb-1">Message</label>
                                <textarea v-model="form.message" required rows="4" class="w-full bg-base-100 border-none rounded-2xl shadow-inner focus:ring-2 p-4" :style="{ '--tw-ring-color': brandingColor }" placeholder="Type your message here..."></textarea>
                            </div>
                            <button type="submit" :disabled="form.processing" class="w-full py-4 text-white font-black text-lg rounded-2xl shadow-lg hover:shadow-xl transition-all disabled:opacity-50" :style="{ backgroundColor: brandingColor }">
                                {{ form.processing ? 'Sending...' : 'Send Message' }}
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </section>

        <!-- Footer / Contact -->
        <footer id="contact" class="py-20" :style="{ backgroundColor: operatingHoursDesign.section_background }">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="pt-8" :style="{ borderTop: `1px solid ${operatingHoursDesign.section_border_color}` }">
                    <!-- Operating Hours -->
                    <div class="mb-8">
                        <h4 class="text-lg font-bold mb-4" :style="{ color: operatingHoursDesign.section_title_color }">Operating Hours</h4>
                        <div class="grid grid-cols-2 sm:grid-cols-4 lg:grid-cols-7 gap-3">
                            <div 
                                v-for="schedule in orderedOperatingHours" 
                                :key="schedule.day"
                                class="text-center p-3 rounded-xl"
                                :style="{ backgroundColor: schedule.enabled ? operatingHoursDesign.card_open_background : operatingHoursDesign.card_closed_background }"
                            >
                                <p class="text-xs font-bold uppercase tracking-wider mb-1" :style="{ color: schedule.enabled ? operatingHoursDesign.card_open_day_color : operatingHoursDesign.card_closed_day_color }">{{ dayLabels[schedule.day] || schedule.day }}</p>
                                <p v-if="schedule.enabled" class="text-[10px]" :style="{ color: operatingHoursDesign.card_time_color }">
                                    {{ formatTime(schedule.open) }}<br>{{ formatTime(schedule.close) }}
                                </p>
                                <p v-else class="text-[10px] font-black uppercase tracking-wider" :style="{ color: operatingHoursDesign.closed_label_color }">Closed</p>
                            </div>
                        </div>
                    </div>

                    <div class="flex flex-col md:flex-row justify-between items-center text-sm" :style="{ color: operatingHoursDesign.copyright_color }">
                        <p>&copy; 2026 {{ tenant.name }}. All rights reserved.</p>
                    </div>
                </div>
            </div>
        </footer>

        <!-- Booking Modal -->
        <BookingModal 
            :show="showBookingModal" 
            :tenant="$page.props.tenant || tenant" 
            :services="services" 
            :dentists="dentists"
            :medical-records="medicalRecords"
            @close="showBookingModal = false" 
        />

        <div v-if="showBookingUnavailableModal" class="fixed inset-0 z-[120] flex items-center justify-center p-4">
            <div class="absolute inset-0 bg-gray-900/70 backdrop-blur-sm" @click="closeBookingUnavailableModal"></div>
            <div class="relative w-full max-w-lg rounded-3xl bg-white p-8 shadow-2xl border border-gray-100">
                <div class="w-16 h-16 rounded-2xl flex items-center justify-center mb-5" :style="{ backgroundColor: brandingColor + '15' }">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor" class="w-8 h-8" :style="{ color: brandingColor }">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 1 1-18 0 9 9 0 0 1 18 0Zm-9 3.75h.008v.008H12v-.008Z" />
                    </svg>
                </div>
                <h3 class="text-2xl font-black text-gray-900 mb-3">Online Booking Unavailable</h3>
                <p class="text-sm leading-relaxed text-gray-600 mb-7">
                    Online appointment booking is currently unavailable for this clinic. You may visit our clinic directly or send us a message and we will assist you.
                </p>
                <div class="flex flex-col sm:flex-row gap-3">
                    <button
                        type="button"
                        @click="goToSection('contact')"
                        class="flex-1 py-3 rounded-2xl text-white font-black"
                        :style="{ backgroundColor: brandingColor }"
                    >
                        Visit Clinic Info
                    </button>
                    <button
                        type="button"
                        @click="goToSection('contact-form')"
                        class="flex-1 py-3 rounded-2xl border border-gray-200 text-gray-700 font-bold hover:bg-gray-50"
                    >
                        Send Message
                    </button>
                </div>
            </div>
        </div>

        <!-- Login Modal -->
        <LoginModal
            :show="showLoginModal"
            :tenant="$page.props.tenant || tenant"
            :recaptchaSiteKey="recaptchaSiteKey"
            @close="showLoginModal = false"
        />
    </div>
</template>

<style scoped>
html {
    scroll-behavior: smooth;
}
</style>

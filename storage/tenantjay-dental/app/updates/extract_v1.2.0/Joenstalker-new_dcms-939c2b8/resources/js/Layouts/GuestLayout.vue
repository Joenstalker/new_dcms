<script setup>
import ApplicationLogo from '@/Components/ApplicationLogo.vue';
import ThemeSwitcher from '@/Components/ThemeSwitcher.vue';
import { Link, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

const page = usePage();
const branding = computed(() => page.props.branding || {});

// Platform info
const platformName = computed(() => branding.value.platform_name || 'DCMS');
const platformLogo = computed(() => {
    const logo = branding.value.platform_logo;
    if (!logo) return null;
    
    // Handle Base64 data URLs directly (Support for Database-Only Isolation)
    if (logo.startsWith('data:image/')) return logo;
    
    // If it's a full URL (from TenantBrandingService route), use as-is
    if (logo.startsWith('http://') || logo.startsWith('https://')) return logo;

    return '/tenant-storage/logos/' + logo;
});
const primaryColor = computed(() => branding.value.primary_color || '#0ea5e9');
const footerText = computed(() => branding.value.footer_text || '© 2026 DCMS. All rights reserved.');
</script>

<template>
    <div
        class="flex min-h-screen flex-col items-center bg-base-200 pt-6 sm:justify-center sm:pt-0 relative"
    >
        <!-- Theme switcher in top-right corner -->
        <div class="absolute top-4 right-4">
            <ThemeSwitcher />
        </div>

        <div>
            <Link href="/">
                <div class="h-20 w-20 rounded-lg border border-base-300 bg-base-100 p-2 flex items-center justify-center overflow-hidden">
                    <img v-if="platformLogo" :src="platformLogo" :alt="platformName" class="h-full w-full object-contain object-center" />
                    <ApplicationLogo v-else class="h-full w-full fill-current text-base-content/50" />
                </div>
            </Link>
        </div>

        <div
            class="mt-6 w-full overflow-hidden bg-base-100 px-6 py-4 shadow-md sm:max-w-md sm:rounded-lg"
        >
            <slot />
        </div>

        <!-- Footer -->
        <div class="mt-4 text-center">
            <p class="text-xs text-base-content/50">{{ footerText }}</p>
        </div>
    </div>
</template>


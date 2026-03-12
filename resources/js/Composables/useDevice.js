import { computed } from 'vue';
import { usePage } from '@inertiajs/vue3';

/**
 * Composable for accessing server-side device detection.
 *
 * Usage:
 *   const { isMobile, isTablet, isDesktop } = useDevice();
 *
 * The detection is powered by mobiledetect/mobiledetectlib on the backend
 * and shared via Inertia props on every page load.
 */
export function useDevice() {
    const page = usePage();
    const device = computed(() => page.props.device || {});

    return {
        isMobile: computed(() => !!device.value.isMobile),
        isTablet: computed(() => !!device.value.isTablet),
        isDesktop: computed(() => !!device.value.isDesktop),
    };
}

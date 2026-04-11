import { reactive } from 'vue';

export const brandingState = reactive({
    primary_color: '#0ea5e9',
    contrast_color: '#ffffff',
    portal_background_type: 'color',
    portal_background_color: null,
    portal_background_image: null,
    is_initialized: false,

    // Initialize state from Inertia page props
    initialize(props) {
        if (this.is_initialized) return;

        if (props.branding_computed) {
            this.primary_color = props.branding_computed.primary_color;
            this.contrast_color = props.branding_computed.contrast_color;
        }

        if (props.tenant) {
            this.portal_background_type = props.tenant.portal_background_type || 'color';
            this.portal_background_color = props.tenant.portal_background_color || null;
            this.portal_background_image = props.tenant.portal_background_image || null;
        }

        this.is_initialized = true;
    },

    // Set a new color (e.g. from a color picker live preview)
    setPrimaryColor(hexColor) {
        this.primary_color = hexColor;
        this.contrast_color = this.calculateContrast(hexColor);
    },

    setPortalBackgroundType(type) {
        this.portal_background_type = type || 'color';
    },

    setPortalBackgroundColor(color) {
        this.portal_background_color = color || null;
    },

    setPortalBackgroundImage(url) {
        this.portal_background_image = url || null;
    },

    // Calculate contrast text color based on luminance
    calculateContrast(hex) {
        if (!hex) return '#ffffff';
        const color = hex.replace('#', '');
        const r = parseInt(color.substr(0, 2), 16) || 0;
        const g = parseInt(color.substr(2, 2), 16) || 0;
        const b = parseInt(color.substr(4, 2), 16) || 0;
        const luminance = (0.299 * r + 0.587 * g + 0.114 * b) / 255;
        return luminance > 0.5 ? '#1f2937' : '#ffffff';
    }
});

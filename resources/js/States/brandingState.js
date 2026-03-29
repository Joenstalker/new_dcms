import { reactive } from 'vue';

export const brandingState = reactive({
    primary_color: '#0ea5e9',
    contrast_color: '#ffffff',
    is_initialized: false,

    // Initialize state from Inertia page props
    initialize(props) {
        if (!this.is_initialized && props.branding_computed) {
            this.primary_color = props.branding_computed.primary_color;
            this.contrast_color = props.branding_computed.contrast_color;
            this.is_initialized = true;
        }
    },

    // Set a new color (e.g. from a color picker live preview)
    setPrimaryColor(hexColor) {
        this.primary_color = hexColor;
        this.contrast_color = this.calculateContrast(hexColor);
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

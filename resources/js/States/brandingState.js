import { reactive } from 'vue';

export const brandingState = reactive({
    primary_color: '#0ea5e9',
    contrast_color: '#ffffff',
    portal_background_type: 'color',
    portal_background_color: null,
    portal_background_image: null,
    portal_background_overlay_opacity: 0,
    ui_sidebar_text_color: null,
    ui_sidebar_text_size: 12,
    ui_sidebar_background_color: null,
    ui_subnav_background_color: null,
    ui_header_title_color: null,
    ui_header_title_size: 20,
    ui_footer_text_color: null,
    ui_footer_text_size: 10,
    ui_footer_background_color: null,
    ui_main_text_color: null,
    ui_main_text_size: 14,
    ui_card_background_color: '#ffffff',
    ui_card_border_color: null,
    ui_card_text_color: null,
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
            this.portal_background_overlay_opacity = Number(props.tenant.portal_background_overlay_opacity ?? 0);
            this.ui_sidebar_text_color = props.tenant.ui_sidebar_text_color || null;
            this.ui_sidebar_text_size = Number(props.tenant.ui_sidebar_text_size ?? 12);
            this.ui_sidebar_background_color = props.tenant.ui_sidebar_background_color || null;
            this.ui_subnav_background_color = props.tenant.ui_subnav_background_color || null;
            this.ui_header_title_color = props.tenant.ui_header_title_color || null;
            this.ui_header_title_size = Number(props.tenant.ui_header_title_size ?? 20);
            this.ui_footer_text_color = props.tenant.ui_footer_text_color || null;
            this.ui_footer_text_size = Number(props.tenant.ui_footer_text_size ?? 10);
            this.ui_footer_background_color = props.tenant.ui_footer_background_color || null;
            this.ui_main_text_color = props.tenant.ui_main_text_color || null;
            this.ui_main_text_size = Number(props.tenant.ui_main_text_size ?? 14);
            this.ui_card_background_color = props.tenant.ui_card_background_color || '#ffffff';
            this.ui_card_border_color = props.tenant.ui_card_border_color || null;
            this.ui_card_text_color = props.tenant.ui_card_text_color || null;
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

    setPortalBackgroundOverlayOpacity(value) {
        this.portal_background_overlay_opacity = Number(value ?? 0);
    },

    setUiTokens(tokens = {}) {
        this.ui_sidebar_text_color = Object.prototype.hasOwnProperty.call(tokens, 'ui_sidebar_text_color')
            ? tokens.ui_sidebar_text_color
            : this.ui_sidebar_text_color;
        this.ui_sidebar_text_size = Number(Object.prototype.hasOwnProperty.call(tokens, 'ui_sidebar_text_size')
            ? tokens.ui_sidebar_text_size
            : (this.ui_sidebar_text_size ?? 12));
        this.ui_sidebar_background_color = Object.prototype.hasOwnProperty.call(tokens, 'ui_sidebar_background_color')
            ? tokens.ui_sidebar_background_color
            : this.ui_sidebar_background_color;
        this.ui_subnav_background_color = Object.prototype.hasOwnProperty.call(tokens, 'ui_subnav_background_color')
            ? tokens.ui_subnav_background_color
            : this.ui_subnav_background_color;

        this.ui_header_title_color = Object.prototype.hasOwnProperty.call(tokens, 'ui_header_title_color')
            ? tokens.ui_header_title_color
            : this.ui_header_title_color;
        this.ui_header_title_size = Number(Object.prototype.hasOwnProperty.call(tokens, 'ui_header_title_size')
            ? tokens.ui_header_title_size
            : (this.ui_header_title_size ?? 20));

        this.ui_footer_text_color = Object.prototype.hasOwnProperty.call(tokens, 'ui_footer_text_color')
            ? tokens.ui_footer_text_color
            : this.ui_footer_text_color;
        this.ui_footer_text_size = Number(Object.prototype.hasOwnProperty.call(tokens, 'ui_footer_text_size')
            ? tokens.ui_footer_text_size
            : (this.ui_footer_text_size ?? 10));
        this.ui_footer_background_color = Object.prototype.hasOwnProperty.call(tokens, 'ui_footer_background_color')
            ? tokens.ui_footer_background_color
            : this.ui_footer_background_color;

        this.ui_main_text_color = Object.prototype.hasOwnProperty.call(tokens, 'ui_main_text_color')
            ? tokens.ui_main_text_color
            : this.ui_main_text_color;
        this.ui_main_text_size = Number(Object.prototype.hasOwnProperty.call(tokens, 'ui_main_text_size')
            ? tokens.ui_main_text_size
            : (this.ui_main_text_size ?? 14));

        this.ui_card_background_color = Object.prototype.hasOwnProperty.call(tokens, 'ui_card_background_color')
            ? tokens.ui_card_background_color
            : this.ui_card_background_color;
        this.ui_card_border_color = Object.prototype.hasOwnProperty.call(tokens, 'ui_card_border_color')
            ? tokens.ui_card_border_color
            : this.ui_card_border_color;
        this.ui_card_text_color = Object.prototype.hasOwnProperty.call(tokens, 'ui_card_text_color')
            ? tokens.ui_card_text_color
            : this.ui_card_text_color;
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

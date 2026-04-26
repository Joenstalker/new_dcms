# Implementation Plan - Dynamic Landing Page Text & Colors

This plan outlines the steps to resolve text readability issues on the public Landing Page when changing the background color, and to make the hardcoded landing page texts editable within the Landing Designer.

## Goal
Make the text content (Hero, About Us) and text colors (Primary Text, Secondary Text) dynamically editable in the Landing Designer, and apply these settings to the public Tenant Landing Page.

## Proposed Changes

### [Frontend - Tenant Dashboard]

#### [MODIFY] [Index.vue](file:///d:/dentistmng/dcms_new/dcms/resources/js/Pages/Tenant/CustomBranding/Index.vue)
- Update the default `landing_page_config` in the `useForm` initialization to include default text colors:
  ```json
  landing_page_config: props.tenant?.landing_page_config || {
      background_color: '#ffffff',
      text_primary: '#111827',
      text_secondary: '#4b5563'
  }
  ```

#### [MODIFY] [LandingPageCustomizer.vue](file:///d:/dentistmng/dcms_new/dcms/resources/js/Pages/Tenant/CustomBranding/Partials/LandingPageCustomizer.vue)
- **Canvas Styling**: 
  - Add two new color pickers: "Heading Text Color (`text_primary`)" and "Paragraph Text Color (`text_secondary`)".
  - Update the "Background Live Preview" box to use these dynamic text colors so the user can immediately see if their text is readable against the chosen background color.
- **Section Designer - Hero & Content**: 
  - Add standard text inputs for `form.hero_title` and `form.hero_subtitle`.
  - Add a textarea for `form.about_us_description`.
  - (These fields are already supported by the backend but simply lack UI in the designer right now).

### [Frontend - Public View]

#### [MODIFY] [Landing.vue](file:///d:/dentistmng/dcms_new/dcms/resources/js/Pages/Tenant/Landing.vue)
- Extract the dynamic text colors from the passed `landingConfig`.
- Strip out Tailwind's hardcoded text color classes (e.g., `text-gray-900`, `text-gray-600`, `text-white`).
- Bind the header elements (`<h1>`, `<h2>`, `<h3>`) to `text_primary` via inline styling.
- Bind paragraph elements (`<p>`, `<li>`) to `text_secondary` via inline styling.

## Verification Plan

### Manual Verification
1. Navigate to **Custom Branding > Landing Designer**.
2. Change the **Landing Background Color** to black (`#000000`).
3. Change the **Heading Text Color** and **Paragraph Text Color** to white/light gray (`#ffffff`) and verify the preview updates correctly.
4. Edit the Hero Title and Subtitle.
5. Visit the public Tenant Landing Page and verify:
   - The background is black.
   - All text is brightly colored and completely readable.
   - The custom hero text displays correctly.

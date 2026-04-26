# Implementation Plan - Branding Package Unification

This plan outlines the steps to unlock all features within the Custom Branding section and enhance the user experience with defaults and cleaned-up UI.

## Goal
Make "Portal Design" and "Landing Designer" standard parts of the "Custom Branding" module by removing all `is_premium` restrictions and banners.

## Proposed Changes

### [Frontend]

#### [MODIFY] [Index.vue](file:///d:/dentistmng/dcms_new/dcms/resources/js/Pages/Tenant/CustomBranding/Index.vue)
- Simplify `isPremium` logic or ensure it always allows all tabs once the user has access to the page.

#### [MODIFY] [PortalCustomization.vue](file:///d:/dentistmng/dcms_new/dcms/resources/js/Pages/Tenant/CustomBranding/Partials/PortalCustomization.vue)
- Remove `:class="{ 'opacity-60 pointer-events-none grayscale': !is_premium }"` from the main container.
- Remove the "Unlock Staff Portal Customization" premium banner and upgrade button.
- Ensure `apply_to` defaults to `'all'` if not specified.

#### [MODIFY] [LandingPageCustomizer.vue](file:///d:/dentistmng/dcms_new/dcms/resources/js/Pages/Tenant/CustomBranding/Partials/LandingPageCustomizer.vue)
- Remove `:class="{ 'opacity-60 pointer-events-none grayscale': !is_premium }"` from the main container.
- Remove the "Premium Component" alert banner and upgrade button.
- Implement specialized default images for each section (Services, Team, Contact) if no image is uploaded.
    - Proposed Defaults:
        - **Services**: Modern dental clinic room image.
        - **Team**: A team of smiling dental professionals.
        - **Contact**: A friendly receptionist or a clean clinic entrance.

#### [MODIFY] [ClinicBranding.vue](file:///d:/dentistmng/dcms_new/dcms/resources/js/Pages/Tenant/CustomBranding/Partials/ClinicBranding.vue)
- Remove premium locks and grayscale opacity from "Visual Identity" (Colors & Typography).
- Remove the "Upgrade to Pro for Custom Colors & Fonts" badge.
- Unlock "Login Modal" and "Booking Modal" logo uploads.

## Verification Plan

### Automated Tests
- N/A (UI/Config change)

### Manual Verification
1. **Navigate to Custom Branding**.
2. **Verify all tabs (Features, Portal Design, Landing Designer)** are fully interactive and visually vibrant (no grayscale/opacity).
3. **Verify absence of banners**: No "Upgrade" or "Premium" alerts should be visible.
4. **Test Portal Logic**: Set branding to "Apply to All" and verify it saves.
5. **Test Landing Page**: Check the public landing page to see if default images appear correctly when no custom images are uploaded.

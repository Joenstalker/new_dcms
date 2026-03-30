# Custom Branding - Auto-Save and Image Optimization

## Objective
Implement auto-save functionality for the Custom Branding settings page, eliminating the need to manually click "Save Configuration". Concurrently, resolve a memory exhaustion issue where large Base64-encoded strings for landing page section images inside `landing_page_config` exceed PHP's memory limit.

## Proposed Changes

### Phase 1: Landing Page Image Optimization (Memory Exhaustion Fix)
- **Backend (`app/Http/Controllers/Tenant/SettingsController.php` & `routes/tenant.php`)**:
  - Add an endpoint `POST /settings/landing-images` to handle binary image uploads for landing page sections.
  - Implement a method to store these images as binary BLOBs using `TenantBrandingService::setStreamed()`.
  - Add a dedicated serve method (e.g., `serveLandingImage()`) to stream the binary data to the frontend, mirroring the existing `serveLogo()` method.
- **Backend (`app/Http/Controllers/Tenant/SettingsController.php`)**:
  - Remove `landing_page_config` from the duplicated `tenant->data` JSON column when updating the settings to prevent doubling the memory payload.
- **Frontend (`resources/js/Pages/tenant/CustomBranding/Partials/LandingPageCustomizer.vue`)**:
  - Update image upload handlers to:
    1. Resize images client-side to a max width (like logos currently do).
    2. Upload to the new endpoint `POST /settings/landing-images` using standard `FormData` file uploads (Multipart).
    3. Update `form.landing_page_config.sections[section].image` with the generated streaming URL (e.g., `/settings/landing-image/...`) instead of raw Base64 strings.

### Phase 2: Form Auto-Save
- **Frontend (`resources/js/Pages/tenant/CustomBranding/Index.vue`)**:
  - Replace the manual "Save Configuration" button with subtle "Saving..." / "All changes saved" UI indicators.
  - Add a deeply debounced `watch` (~1000-1500ms) on the Inertia `form` object.
  - Automatically fire an Inertia `post` or a manual Axios request (if avoiding full page lifecycle is preferred) on content change.
  - Ensure image uploads in child components (like `LandingPageCustomizer.vue` and `ClinicBranding.vue`) securely trigger saves or update states correctly without interfering with text uploads.

## Verification Plan

### Automated Tests
- Run `lint_runner.py` after writing the components.
- Run `security_scan.py` to ensure uploading processes are secured.

### Manual Verification
1. **Memory Limits Check**: Upload exceptionally large images (5MB+ per image) across the 3 landing page sections simultaneously and verify that PHP does not throw the 1GB memory exhaustion error.
2. **Database Integrity**: Inspect `tenant_brandings` and `tenants.data` to confirm the JSON columns no longer encapsulate massive Base64 strings, ensuring only paths/URLs are stored in JSON.
3. **Auto-Save Functionality**: Modify diverse inputs (text inputs, color pickers, and checkboxes like QR Booking) on the Custom Branding page. Check that a non-intrusive UI indicates successful saving and the page accurately reflects updates on refresh without manual saves action.
4. **Rendering**: Ensure the landing page and portal visually display the successfully streamed landing page images to guests/patients.

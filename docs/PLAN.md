# Plan: Public Branding Accessibility Fix

## Goal
Ensure custom branding logos (Header, Login Modal, Booking Modal) are visible to unauthenticated users on the tenant landing page.

## Proposed Changes

### 1. Research & Discovery (Explorer Agent) [DONE]
- Audited `routes/tenant.php`: `settings.logo` is public.
- Audited `HandleInertiaRequests.php`: Shares transformed `tenant` and `branding` props with guests.
- Audited `Landing.vue`: Header is missing the logo.
- Audited `LoginModal.vue` / `BookingModal.vue`: `logoUrl` logic incorrectly assumes `/tenant-storage/` for binary keys.

### 2. Implementation (Frontend Specialist)
- **Landing Header**: Add the logo element to the header in `Landing.vue`.
- **Global Props**: Update `Landing.vue` to pass `$page.props.tenant` to sub-modals to ensure they receive the transformed branding URLs.
- **Computed Logic**: Refine `logoUrl` in `LoginModal.vue` and `BookingModal.vue` to handle direct URLs (from global props) vs legacy paths.

### 3. Backend Verification (Backend Specialist)
- Ensure the `settings.logo` route remains stable and serves data correctly to guests.

## Verification Plan
- Access the tenant landing page as a Guest (Incognito).
- Verify Header logo is visible.
- Open Login Modal and verify logo.
- Open Booking Modal and verify logo.

# Tenant Feature Visibility Plan

## Goal
Implement feature gating in the tenant portal UI so that users only see sidebar links for features included in their active subscription plan, while simultaneously displaying all available platform features (locked and unlocked) inside their Subscription tab to encourage upgrades.

## Tasks
- [ ] Task 1: Update `resources/js/Layouts/AuthenticatedLayout.vue` top-level `filter` logic. → Verify: Top-level items (like Branches) correctly use `item.subscriptionFeature` to hide themselves if the tenant lacks the feature.
- [ ] Task 2: Add `subscriptionFeature: 'has_qr_booking'` to `QR Code Setup` subItem in `AuthenticatedLayout.vue`. → Verify: QR Code Setup is hidden for Basic plan tenants.
- [ ] Task 3: Ensure `resources/js/Pages/Tenant/Settings/Partials/Subscription.vue` fetches and displays all available system plans and dynamic features. → Verify: The tenant can see features that they do not currently have, with visual indicators (like lock icons or "Upgrade" badges) distinguishing them from their active features.

## Done When
- [ ] The tenant sidebar dynamically hides features they haven't paid for (e.g., QR Booking, Branding, Branches).
- [ ] The tenant can navigate to `Settings -> Subscription` and clearly see what features they could get by upgrading their plan.

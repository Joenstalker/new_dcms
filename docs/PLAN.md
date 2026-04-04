# PLAN.md: Resolving Dynamic Sidebar Reactivity

## Objective
Fix the critical bug where disabling a feature in `Tenant / Custom Branding / Module Management` successfully triggers a "Saved" notification but fails to reflect the removal of the feature in the sidebar.

## Domain Analysis
- **Frontend/UI (`frontend-specialist`)**: The Vue implementation correctly splices the `enabled_features` array using `toggleFeature(id)` and Inertia posts the Form. In `AuthenticatedLayout.vue` the sidebar correctly filters nodes based on `!page.props.tenant.enabled_features.includes(item.feature)`.
- **Backend/API (`backend-specialist`)**: The updated array validation intercepts correctly, returning HTTP 200/302. However, the database persistence step (specifically `TenantBrandingService::set` or `$tenant->update`) appears to gracefully silently fail or omit the array when writing to the database, leading Inertia to refresh the page with the stale database state.
- **Database/Storage (`database-architect`)**: Investigating the exact data-casting pipeline inside the `branding_settings` table vs the `tenants` table to ensure arrays are validly encoded as JSON.

## Phase 1: Deep Diagnosis (Sequential)
1. Inject detailed raw telemetry into `SettingsController` and `TenantBrandingService` to verify the precise shape of `enabled_features` when it reaches the database.
2. Confirm if `$tenant->update($validated)` is quietly stripping `enabled_features` because it might not be listed in `$fillable` on the `Tenant` model.

## Phase 2: Implementation (Parallel Agents)
1. **[backend-specialist]**: Permanently secure the backend database sink so that `enabled_features` updates are unconditionally synchronized between `branding_settings` and the Core `Tenant` model.
2. **[frontend-specialist]**: Audit the `AuthenticatedLayout` sidebar reactivity to guarantee `page.props.tenant.enabled_features` is correctly read over `page.props.subscription.features` for locally disabled modules.
3. **[test-engineer]**: Run verification to ensure toggling modules strictly hides their respective routes without leaking permissions.

## Verification Protocol
1. Open the UI, toggle off "Appointment Management" and "Staff Management".
2. Ensure the "ALL CHANGES SAVED" toast triggers.
3. Confirm the sidebar instantaneously removes those elements upon the Inertia page reload.
4. Refresh the page to verify the changes persist across sessions.

# Feature: Fix Subscription Plan Feature Visibility

## Goal
Ensure that the "Manage Plan" modal correctly reflects the "Live" status of features as defined in the seeders (e.g., `SubscriptionPlanSeeder` and `FeatureSeeder`). Specifically, features set to `false` for a plan (like SMS in the Basic plan) should appear as "Unchecked" in the management interface.

## Proposed Changes

### [Frontend] ManagePlanModal.vue
- **[MODIFY] [ManagePlanModal.vue](file:///d:/dentistmng/dcms_new/dcms/resources/js/Pages/Admin/Plans/Partials/ManagePlanModal.vue)**
    - Update the `form.features` initialization logic.
    - Change the `assigned` property calculation to respect the `value_boolean` for boolean features.
    - Logic: A feature is `assigned` (checked) if:
        - It's a `boolean` type and `pivot.value_boolean` is true.
        - It's a `numeric` or `tiered` type and the pivot record exists.

### [Backend] PlanController.php
- **[MODIFY] [PlanController.php](file:///d:/dentistmng/dcms_new/dcms/app/Http/Controllers/Admin/PlanController.php)**
    - Review the `update` method to ensure that when a feature is unchecked in the UI, it is properly detached from the plan (or its boolean value set to false, depending on the desired sync behavior). 
    - *Decision*: If the user says "if uncheck then its not part of the plan", we should probably **detach** it to keep the pivot table clean, or explicitly set it to false if we want to keep the record for some reason. The current `removeFeature` method already detaches.

## Verification Plan

### Automated Tests
- No existing automated tests specifically for this UI logic found.
- Will create a temporary test script `tests/Feature/PlanFeatureTest.php` to verify that `Basic` plan features are correctly returned via the API.

### Manual Verification
- Run `php artisan db:seed --class=FeatureSeeder` to ensure fresh data.
- Navigate to Admin -> Plans.
- Open "Manage" for the **Basic** plan.
- Verify that **SMS Notifications**, **Custom Branding**, etc., are **UNCHECKED**.
- Verify that **QR Code Online Booking** is **CHECKED**.
- Check **Ultimate** plan and verify all features are **CHECKED**.

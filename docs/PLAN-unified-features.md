# 🎯 Plan: Unified Feature Engine (Option A)

This plan outlines the migration from legacy hardcoded columns on the `subscription_plans` table to a fully dynamic, unified feature management system.

## 🔴 CRITICAL: SOCRATIC GATE (PHASE 0 - Clarifications Needed)

> [!IMPORTANT]
> To ensure a smooth transition, we need to decide on:
> 1.  **Unlimited Values**: Should we use `-1` or `null` for "Unlimited" numeric features? (Proposing `null` for consistency with legacy).
> 2.  **Legacy Column Cleanup**: Should we drop the columns immediately, or keep them with `@deprecated` tags during a 30-day grace period? (Proposing @deprecated for safe rollback).
> 3.  **Migration Automation**: Proposing a dedicated Artisan command `php artisan features:migrate-legacy` for one-time execution.

---

## 🏗️ Phase 1: Feature Schema & Seeds
- [ ] **[NEW]** Add missing `max_storage_mb` feature record to the `features` database seeder.
- [ ] **[UPDATE]** Ensure all legacy column keys have a corresponding record in the `features` table (verified: 10/11 exist).
- [ ] **[VERIFY]** Run `php artisan db:seed --class=FeatureSeeder` to ensure all foundation features are active.

## 🔄 Phase 2: Data Migration
- [ ] **[NEW]** Create `App\Console\Commands\Feature\SyncLegacyFeatures.php`.
    - Logic: Iterates through each `SubscriptionPlan` -> Reads `has_qr_booking`, `max_users`, etc. -> Updates the `plan_features` pivot table via the existing `addFeature` model method.
- [ ] **[EXECUTE]** Run the migration command and verify the `plan_features` table is populated for all 3 plans.

## 🛡️ Phase 3: Middleware & Core Enforcement
- [ ] **[MODIFY]** Update `app\Http\Middleware\CheckSubscription.php`.
    - Refactor logic to check dynamic features **exclusively**.
    - Remove fallback to legacy `has_qr_booking` columns.
- [ ] **[MODIFY]** Update `app\Models\SubscriptionPlan.php`.
    - Mark legacy columns as `@deprecated`.
    - Update `hasFeature()` and `getFeatureValue()` to prioritize the dynamic engine.

## 📊 Phase 4: UI & Admin Tools
- [ ] **[UPDATE]** Ensure `FeatureController.php` handles all migrated features correctly.
- [ ] **[VERIFY]** Manual check of the Admin Features Dashboard to ensure values match the previously hardcoded ones.

## 🏁 Phase 5: Final Cleanup (Optional/Post-Review)
- [ ] **[DELETE]** Remove legacy columns from `subscription_plans` migration and database.

---

## 🧪 Verification Plan

### Automated
- [ ] `php artisan features:migrate-legacy --dry-run` to see proposed changes.
- [ ] Create a temporary script to assert that `plan->getFeatureValue('max_users')` matches the old `plan->max_users`.

### Manual
1.  **Admin Panel**: Go to "Features" and verify the 3 plans show matching limits.
2.  **Tenant Side**: Log in as a tenant on a "Basic" plan and verify feature gate (e.g., if QR booking is disabled in the plan, verify it's blocked in the UI).
3.  **New Tenant Test**: Register a new tenant, approve them, and verify their features are automatically inherited from the selected plan.

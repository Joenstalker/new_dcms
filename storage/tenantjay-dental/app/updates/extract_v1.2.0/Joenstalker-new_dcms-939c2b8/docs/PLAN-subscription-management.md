# Project Plan: Subscription Management

## Overview
This plan implements robust management of subscription plans for the admin portal. It allows admins to modify dynamically seeded plans, introduces an intuitive "Manage" modal for plans, and implements real-time, event-driven file storage tracking. It also ensures those updates globally and immediately synchronize to all subscribed tenants.

## Project Type
**WEB** (Laravel Backend, Vue.js / Inertia Frontend)

## Success Criteria
- [ ] Admin can open a "Manage" modal for any subscription plan.
- [ ] Modal correctly displays and updates Base Details (Name, Prices).
- [ ] Modal correctly displays and updates Usage Limits, specifically dynamic storage limits.
- [ ] Modal displays available features from Feature Management with a check/uncheck toggle. 
- [ ] Changes to a plan immediately affect all tenants enrolled in that plan.
- [ ] Event-driven storage tracking increments/decrements tenant file storage usage in real-time.
- [ ] Tenant Dashboard correctly displays remaining storage capacity.
- [ ] Admin's Tenant Manage modal correctly displays current storage used by that specific tenant.

## Tech Stack
- **Backend:** Laravel (PHP)
- **Database:** MySQL
- **Frontend:** Vue.js 3 with Inertia.js and Tailwind CSS

## File Structure
```
├── app/Models/SubscriptionPlan.php                  # Add max_storage attribute if needed
├── app/Models/Tenant.php                            # Add storage_used attribute
├── app/Http/Controllers/Admin/PlanController.php    # Plan update logic
├── resources/js/Pages/Admin/Plans/                  # Vue components for Plans
├── resources/js/Pages/Admin/Plans/Partials/ManagePlanModal.vue # The new modal
├── resources/js/Pages/Admin/Tenants/Partials/       # Tenant management UI
├── resources/js/Pages/Tenant/Dashboard.vue          # Tenant dashboard
└── database/migrations/                             # Migrations for storage columns
```

## Task Breakdown

### 1. Database & Models (Foundation)
- **Agent:** `database-architect` / `backend-specialist`
- **Task:** Add `max_storage_bytes` (or MB) to `subscription_plans` table if not present. Add `storage_used_bytes` to the `tenants` table.
- **INPUT:** `SubscriptionPlan` and `Tenant` schema.
- **OUTPUT:** Migrations created and migrated. Models updated with cast and fillable properties.
- **VERIFY:** Database has the new columns.

### 2. Event-Driven Storage Calculation (Backend Core)
- **Agent:** `backend-specialist`
- **Task:** Implement observers or logic hooks on file uploads/deletions (e.g., Patient files) that increment or decrement the `storage_used_bytes` column on the active `Tenant`.
- **INPUT:** Current file upload controllers/services.
- **OUTPUT:** Storage counts accurately update when files are created or deleted.
- **VERIFY:** Uploading a file increases tenant storage count by exact file size.

### 3. Plan Management Backend (API Core)
- **Agent:** `backend-specialist`
- **Task:** Update the Admin Plan controller to handle updating Base details, Storage/Usage limits, and Features. Add validation. Ensure immediate relational sync (tenants fetch from plan directly).
- **INPUT:** `Admin/PlanController` or similar.
- **OUTPUT:** API endpoint that successfully updates a `SubscriptionPlan`.
- **VERIFY:** Sending a PUT request updates the plan securely.

### 4. Admin UI: Subscription Plans & Manage Modal (Frontend)
- **Agent:** `frontend-specialist`
- **Task:** In the Subscription Plans portal, change the edit icon to a "Manage" button. Inside the new `ManagePlanModal`, create 3 sections:
    1. Base Details (Name, Monthly, Yearly)
    2. Usage Limits (Storage limit input)
    3. Features (List of system features with check/uncheck toggles and a warning confirmation if unchecked)
- **INPUT:** Existing Subscription Plan page.
- **OUTPUT:** Working, aesthetic "Manage Plan" modal.
- **VERIFY:** Admin can click "Manage", see sections, toggle features, and save changes.

### 5. Tenant UI: Storage Dashboards (Frontend)
- **Agent:** `frontend-specialist`
- **Task:** 
    1. On the Tenant Dashboard, display a beautiful visual indicator of remaining storage (`max_storage_bytes` from Plan minus `storage_used_bytes` from Tenant).
    2. In the Admin -> Tenants table -> Manage Modal, link/display the real-time storage used.
- **INPUT:** Tenant Dashboard and Admin Tenant Modal.
- **OUTPUT:** Storage bars/text rendered correctly.
- **VERIFY:** Tenant sees "X GB used of Y GB".

## Phase X: Verification
- [ ] **Linting & Types:** Code passes linting rules. 
- [ ] **Security:** `security_scan.py` passes.
- [ ] **Build Check:** `npm run build` succeeds.
- [ ] **UX/Aesthetics Check:** Modals use current premium design system patterns.
- [ ] **Functionality:** End-to-end test of managing a plan and uploading a file.

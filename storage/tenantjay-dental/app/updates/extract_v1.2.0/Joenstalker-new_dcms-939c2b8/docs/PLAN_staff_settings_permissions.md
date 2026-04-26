# Implementation Plan - Staff Settings Permissions Fix

Fixing missing Spatie permissions for personal staff settings across all tenants.

## 🎼 Orchestration Details
| Agent | Domain | Focus Area |
|---|---|---|
| `project-planner` | Planning | Task breakdown, PLAN.md |
| `backend-specialist` | Server | Laravel, Permission Syncing |
| `test-engineer` | Testing | Verification of Permission assignment |

## 1. ANALYSIS
- **Symptom**: `Spatie\Permission\Exceptions\PermissionDoesNotExist`
- **Missing Permissions**: 
    - `manage own calendar`
    - `manage own notifications`
    - `manage own working hours`
- **Root Cause**: The permissions exist in `RolesAndPermissionsSeeder.php` but have not been synced into existing tenant databases. Tenants only run seeders on creation via `SeedTenantDatabase`.

## 2. PHASE 1: PLANNING (Current)
- [x] Analyze codebase for permission usage (`StaffSettingsController.php`).
- [x] Locate permission definitions (`RolesAndPermissionsSeeder.php`).
- [x] Draft `docs/PLAN.md`.
- [ ] Seek user approval for implementation.

## 3. PHASE 2: IMPLEMENTATION (Post-Approval)
- **Step 1 (Backend)**: Execute `tenants:seed` for all existing tenants to ensure the permissions table is up-to-date.
- **Step 2 (Backend)**: Verify that the `Owner` role is correctly updated for all tenants to include these new permissions (as per `RolesAndPermissionsSeeder`).
- **Step 3 (Test)**: Attempt to assign one of these permissions to a staff member through the UI/API and verify it no longer throws an exception.
- **Step 4 (Test)**: Run `lint_runner.py` and `security_scan.py` to ensure system integrity.

## 4. VERIFICATION
- [ ] Command `php artisan tenants:run db:seed --option="class=RolesAndPermissionsSeeder"` (or equivalent) completes successfully.
- [ ] No `PermissionDoesNotExist` when updating staff permissions.
- [ ] Staff can access "My Settings" if permitted.

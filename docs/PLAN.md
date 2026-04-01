# Implementation Plan: Tenant Activity Logs

**Goal:** Implement an Activity Logs module in the Tenant Sidebar that tracks logins (success/failed), actions by the clinic owner and staff, and unauthorized access attempts.

## Phase 2 Architecture

### Backend: Packages & Database
- **Install Package:** `composer require spatie/laravel-activitylog`
- Publish and run migrations for `activity_log`.

### Backend: Event Listeners (Logging)
- **Logins & Failures:** Create listeners for `Illuminate\Auth\Events\Login` and `Illuminate\Auth\Events\Failed`. Bind them in `EventServiceProvider`.
  - Log `Login successful` or `Failed login attempt`.
- **Model Actions:** Use `Spatie\Activitylog\Traits\LogsActivity` trait on key models (e.g., `Patient`, `Appointment`, `User`, `Treatment`) to automatically log create/update/delete actions by staff.
- **Unauthorized Access:** Create a middleware or hook into Exception rendering to log `403 Forbidden` / `UnauthorizedException` events from Spatie permissions.

### Backend: Controllers & Routing
- **Controller:** Create `App\Http\Controllers\ActivityLogController` with an `index` method to fetch logs from the `Spatie\Activitylog\Models\Activity` model, paginated, ordered by latest.
- **Routes:** Add `activity-logs.index` to `routes/tenant.php` under the `auth` middleware. Ensure only users with `view activity logs` permission or Clinic Owners can access it.
- **Seeder:** Add `view activity logs` permission to the database and assign it to the `Owner` role.

### Frontend: UI & Sidebar
- **Sidebar Link:** Update `resources/js/Layouts/AuthenticatedLayout.vue` to include an "Activity Logs" menu item under the "System" or "Management" category.
- **Page Component:** Create `resources/js/Pages/ActivityLogs/Index.vue`
  - Display a table/list of activities: `description`, `causer` (User), `subject` (Model), `created_at`.
  - Add filtering by Causer / Event Type.

## Verification Plan
1. **Tests / Automated:**
   - Run `security_scan.py` and `lint_runner.py` after implementation.
2. **Manual:**
   - Log out and log in with wrong password -> Verify failed login log entry.
   - Log in successfully -> Verify success log entry.
   - Navigate to restricted module as Staff -> Verify unauthorized access attempt log.
   - Navigate to Activity Logs as Clinic Owner -> Verify logs are visible.

# Resolve Staff Permission Error Implementation Plan

The objective is to fix the `Spatie\Permission\Exceptions\PermissionDoesNotExist` error that occurs when a clinic owner adds a new staff member. This error is caused by the missing `view dashboard` permission in the tenant database's permissions table.

## Proposed Changes

### [Backend] roles-and-permissions-seeder

#### [MODIFY] [RolesAndPermissionsSeeder.php](file:///d:/dentistmng/dcms_new/dcms/database/seeders/RolesAndPermissionsSeeder.php)
- Add `view dashboard` to the `$permissions` array to ensure it is seeded for all tenants.

### [Backend] staff-controller-verification

#### [VERIFY] [StaffController.php](file:///d:/dentistmng/dcms_new/dcms/app/Http/Controllers/StaffController.php)
- Ensure line 47: `$user->syncPermissions(['view dashboard']);` works correctly after seeding.

## Verification Plan

### Automated Tests
1. Run the `RolesAndPermissionsSeeder` via artisan command:
   ```bash
   php artisan tenants:run db:seed --option="class=Database\Seeders\RolesAndPermissionsSeeder"
   ```

### Manual Verification
1. Log in as a Clinic Owner.
2. Navigate to Staff Management.
3. Add a new staff member.
4. Verify that the staff is created successfully without a 500 error.

# Mandatory Password Change for New Tenants and Staff

## Objective
Enforce a mandatory password change for new tenants (Clinic Owners) and new staff (Dentists, Assistants) upon their first login. The users must be prompted with a modal to change their password before they can access their landing page. The new password must pass strength requirements (min 10 chars, uppercase, lowercase, numbers, special characters).

## Proposed Changes

### 1. Database & Model
- **Create Migration**: `add_requires_password_change_to_users_table.php` to add a boolean column `requires_password_change` (default `false`) to the `users` table.
- **Update Model**: Add `requires_password_change` to the `$fillable` and `$casts` arrays in `app/Models/User.php`.

### 2. Account Creation Flow
- **Tenant Approval (`Admin\PendingRegistrationController@approve`)**: When creating the `Owner` user inside the tenant context, set `requires_password_change => true`.
- **Staff Creation (`StaffController@store`)**: When creating a new staff member, set `requires_password_change => true`.

### 3. Backend Logic & Middleware
- **Middleware**: Create `EnsurePasswordIsChanged` middleware. If `auth()->check()` and `auth()->user()->requires_password_change` is true, redirect all requests (except `logout`, `password.update`, and frontend page loads if handled via modal) or share a prop.
- **Inertia Shared Props**: Update `HandleInertiaRequests.php` to share `requires_password_change` so the frontend knows when to trigger the modal globally.
- **Password Update Endpoint**: Use the existing `PasswordController` or create a new endpoint that:
  - Validates the new password with strong rules (`min:10`, `mixedCase`, `letters`, `numbers`, `symbols`).
  - Updates the password.
  - Sets `requires_password_change = false`.

### 4. Frontend Implementation
- **Component**: Create `resources/js/Components/MandatoryPasswordChangeModal.vue` based on `AccountSettingsModal.vue`.
  - Set it as uncloseable (no "X" button, no click-outside-to-close).
  - Include Current Password field (autofilled or required to type if not possible to autofill securely, though the prompt says "autofill the generated password" - we might need to pass the generated password via session flush on first login, or just leave it blank if they already logged in with it. Wait, the prompt says "autofill the generated password" - if they just logged in, they know it, but we can't retrieve the raw password from DB. If it's literally autofilled, we might just require New Password and Confirm Password, or accept the current password if they type it. *Note: We will clarify this in implementation, typically we just ask for New and Confirm if forced by admin, or ask for Current if standard.*)
  - Include Password Strength Indicator.
- **Global Layout**: Inject the modal into the main tenant layout (e.g., `TenantLayout.vue` or `AuthenticatedLayout.vue`). It will automatically show if `page.props.auth.requires_password_change` is true.

## Verification Plan
1. **Tenant Testing**: Register a new tenant, approve as admin. Log in as the new tenant. Verify the modal appears, cannot be dismissed, and requires a strong password. Upon success, verify the flag is cleared and the landing page is accessible.
2. **Staff Testing**: Log in as Owner, create a new Dentist. Log in as the Dentist. Verify the same modal behavior.
3. **Security Testing**: Attempt to navigate to other URLs or make API requests while `requires_password_change` is true to ensure the middleware blocks them.

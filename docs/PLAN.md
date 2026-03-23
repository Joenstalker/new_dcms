# [PLAN] Fix Tenant Logout Redirect & CSRF Error

The goal is to ensure that when a tenant staff member logs out, they are correctly redirected to their own clinic's landing page (subdomain) and do not encounter a 419 Page Expired error.

## Proposed Changes

### 🎨 Frontend - `frontend-specialist`

#### [MODIFY] [AuthenticatedLayout.vue](file:///d:/dentistmng/dcms_new/dcms/resources/js/Layouts/AuthenticatedLayout.vue)
- **Change**: In `handleLogout`, change the form action from a named route to a relative path.
- **Logic**: Use `form.action = '/logout'` instead of `route('logout')`. 
- **Reason**: This ensures the request is sent to the current domain (e.g., `rhodsmile.localhost:8080/logout`) rather than potentially resolving to the central domain (`localhost:8080/logout`), avoiding CSRF token mismatches (419 errors).

### ⚙️ Backend - `backend-specialist`

#### [MODIFY] [AuthenticatedSessionController.php](file:///d:/dentistmng/dcms_new/dcms/app/Http/Controllers/Auth/AuthenticatedSessionController.php)
- **Method**: `destroy`
- **Logic**: 
    - Explicitly check for an active tenant using `tenant()`.
    - If a tenant is present, redirect to the named route `tenant.landing`.
    - If no tenant (central admin), continue redirecting to `central.home`.
- **Reason**: Ensures the user lands on the clinic-specific landing page after logging out from their portal.

## Verification Plan

### Manual Verification
1.  Login to a tenant portal (e.g., `rhodsmile.localhost:8080/dashboard`).
2.  Click the **Logout** button.
3.  **Verify No Error**: Ensure the 419 Page Expired error no longer appears.
4.  **Verify Redirection**: Ensure you are redirected to the tenant's landing page (`rhodsmile.localhost:8080/`) and NOT the central homepage or login screen.

# Implementation Plan: SweetAlert on Admin Login

**Goal:** Provide a visual SweetAlert success message when an administrator logs into the central domain.

## Current Architecture
The application uses Laravel Inertia with Vue 3. 
- **Backend:** `App\Http\Controllers\Auth\AuthenticatedSessionController` handles both tenant and central admin logins. Admin logins are redirected to `admin.dashboard`.
- **Middleware:** `HandleInertiaRequests` automatically shares `$request->session()->get('success')` to Inertia frontend props (`page.props.flash.success`).
- **Frontend:** `AdminLayout.vue` contains a global SweetAlert watcher that detects `page.props.flash.success` and fires a `Swal.fire` success toast automatically.

## Proposed Changes

### `app/Http/Controllers/Auth/AuthenticatedSessionController.php`
- Modify the admin redirect on successful login (lines 54-56).
- Chain `->with('success', 'Admin login successful! Welcome back.')` to the `redirect()->intended(...)` response.
- This will inject the flash message into the session, which is then picked up by `HandleInertiaRequests` and passed to `AdminLayout.vue`.

## Verification Details
1. **Security:** No authentication logic is altered, only a flash message is added.
2. **UX:** The SweetAlert will be a toast notification in the top-end, auto-dismissing after 3 seconds, matching existing platform styles.
3. We will run `security_scan.py` and `lint_runner.py` after implementation as per the orchestration protocol.

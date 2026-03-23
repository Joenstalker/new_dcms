# [PLAN] Simplify Registration Configuration

The goal is to simplify the registration settings UI by removing the redundant "Auto-Approve Timing" field. The system will automatically trigger auto-approval (if enabled) exactly when the "Registration Timeout" ends.

## Proposed Changes

### 🎨 Frontend - `frontend-specialist`

#### [MODIFY] [RegistrationSettings.vue](file:///d:/dentistmng/dcms_new/dcms/resources/js/Pages/Admin/SystemSettings/Partials/RegistrationSettings.vue)
- Remove the "Auto-Approve Timing" input field and its associated description from `RegistrationSettings.vue`.
- Keep the "Enable Auto-Approve" toggle.
- Update the description for "Enable Auto-Approve" to clarify it triggers at the end of the timeout.

### ⚙️ Backend - `backend-specialist`

#### [MODIFY] [ProcessExpiredRegistrations.php](file:///d:/dentistmng/dcms_new/dcms/app/Console/Commands/ProcessExpiredRegistrations.php)
- (Technical Cleanup) Remove the unused `$autoApproveMinutes` variable to avoid confusion in the logs/logic.

## Verification Plan

### Manual Verification
1.  Navigate to **System Settings > Registrations**.
2.  Verify the "Auto-Approve Timing" field is gone.
3.  Set "Registration Timeout" to 2 minutes and "Enable Auto-Approve" to ON.
4.  Register a new tenant clinic.
5.  **Success Modal**: Shows 2 minutes countdown.
6.  **Auto-Approve**: Wait 2 minutes and run `php artisan registrations:process-expired`.
7.  Verify the tenant is automatically approved.

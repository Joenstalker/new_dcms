# Orchestration Plan: Staff Portal Login Fix

## Task
Restore the authentication logic in `TenantAuthController::store` to allow Staff (Dentists, Assistants) and Owners to login to the tenant portal.

## Agents Required
1. **project-planner**: Create task breakdown.
2. **explorer-agent**: Verify Role/Permission integration for staff users.
3. **backend-specialist**: Implement the `store` method with security checks.
4. **test-engineer**: Verify the login flow with mock credentials.

## Phase 1: Planning
- [ ] Research: Confirm the expected redirect URL for Staff (typically `/dashboard`).
- [ ] Strategy: Implement `Auth::attempt` with reCAPTCHA verification and tenant-status checking.
- [ ] Review: Ensure `IdentifyTenant` middleware is providing the correct database context.

## Phase 2: Implementation (after approval)
- [ ] Fix `TenantAuthController.php`: Implement `store` method.
- [ ] Security: Ensure reCAPTCHA token is validated before authentication attempt.
- [ ] Feedback: Return clear error messages if credentials or tenant status are invalid.

## Verification
- [ ] Security Scan: Ensure no exposed passwords or sensitive data in logs.
- [ ] Manual Check: Test login with an existing Staff email and password.

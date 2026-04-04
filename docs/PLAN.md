# Admin Earnings Management Plan

## Overview
This plan outlines the steps to investigate and properly manage Admin Earnings from approved tenant applications. Currently, the Admin Revenue is calculated via mathematical estimates (`count * avgPrice`) in `RevenueController`. To properly manage earnings, the system needs to accurately track actual Stripe payouts, application registration fees, and subscriptions via a dedicated `AdminEarning` tracking mechanism.

## Project Type
BACKEND & WEB

## Success Criteria
- Actual payments from Stripe embedded checkout are recorded precisely.
- Revenue Dashboard displays real collected earnings instead of estimates.
- `PendingRegistration` approvals cleanly mark earnings as realized.
- Refunded registrations deduct from realized earnings properly.

## Tech Stack
- Laravel (Backend)
- Inertia.js + Vue 3 (Frontend)
- Stripe PHP (Payments)

## File Structure
- `app/Models/AdminEarning.php`
- `database/migrations/xxxx_create_admin_earnings_table.php`
- `app/Http/Controllers/Admin/RevenueController.php` (Update)
- `app/Http/Controllers/RegistrationController.php` (Update)
- `resources/js/Pages/Admin/Revenue/Index.vue` (Update)

## Task Breakdown

### Task 1: Create AdminEarning Model and Migration
- **Agent**: `backend-specialist`
- **Skills**: `database-design`, `backend-specialist`
- **Priority**: P0
- **Dependencies**: None
- **INPUT→OUTPUT→VERIFY**: Input: Create schema -> Output: `AdminEarning` model and migration (fields: tenant_id, amount, source: 'registration'/'subscription', stripe_payment_id, status) -> Verify: `php artisan migrate` passes.

### Task 2: Implement Stripe Sync in RegistrationController
- **Agent**: `backend-specialist`
- **Skills**: `api-patterns`
- **Priority**: P1
- **Dependencies**: Task 1
- **INPUT→OUTPUT→VERIFY**: Input: Handle checkout success -> Output: Create `AdminEarning` record when tenant pays in `processSuccessfulRegistration`. -> Verify: Run local registration test.

### Task 3: Update RevenueController to Use Actual Earnings
- **Agent**: `backend-specialist`
- **Skills**: `clean-code`
- **Priority**: P1
- **Dependencies**: Task 1
- **INPUT→OUTPUT→VERIFY**: Input: Replace `count * avgPrice` logic -> Output: `RevenueController` sums actual `AdminEarning` records -> Verify: JSON response shows correct sums.

### Task 4: Update Revenue Dashboard UI
- **Agent**: `frontend-specialist`
- **Skills**: `frontend-design`
- **Priority**: P2
- **Dependencies**: Task 3
- **INPUT→OUTPUT→VERIFY**: Input: Render exact amounts -> Output: Vue component `Admin/Revenue/Index.vue` displays accurate historical and current earnings. -> Verify: Run `npm run lint` and view UI.

## Phase X: Verification
- [ ] Run Security Scan: `python .agent/skills/vulnerability-scanner/scripts/security_scan.py .`
- [ ] Linting & Types: `npm run lint && npx tsc --noEmit`
- [ ] UX Audit: `python .agent/skills/frontend-design/scripts/ux_audit.py .`

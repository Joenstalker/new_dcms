# Admin Revenue Bug & Track System Plan

## Overview
The Admin dashboard currently estimates "Revenue" by adding up monthly prices from active `Subscription` plans. However, when a tenant registers and pays via Stripe, their specific payment (e.g., initial charge, setup fees) is only recorded implicitly in the `PendingRegistration` and then abstracted into a `Subscription`. 
There is no dedicated "Payments" history table in the central Admin dashboard. Because of this, the new tenant's actual Stripe payment event is invisible in the Admin's "Revenue" view. 

This plan addresses this bug by implementing an explicit `AdminEarning` (or `SystemPayment`) ledger to track every Stripe dollar paid to the Admin, ensuring accurate financial reconciliation visibly on the dashboard.

## Project Type
BACKEND & WEB

## Success Criteria
- The Central application must maintain a `system_earnings` or `admin_payments` table logging specific checkout sessions and payments from Stripe.
- A new section in the Admin Revenue dashboard displays "Recent Actual Payments", reflecting the exact money transferred via Stripe.
- Existing MRR/ARR logic should be supplemented (or replaced if strictly needed) by real historical earnings data.

## Tech Stack
- Laravel (Backend / Database Migrations)
- Stripe PHP (Webhook/Checkout processing)
- Vue 3 / Tailwind / Inertia (Admin Revenue Dashboard UI)

## File Structure
- `app/Models/SystemEarning.php` [NEW]
- `database/migrations/xxxx_create_system_earnings_table.php` [NEW]
- `app/Http/Controllers/RegistrationController.php` [MODIFY]
- `app/Http/Controllers/Admin/RevenueController.php` [MODIFY]
- `resources/js/Pages/Admin/Revenue/Index.vue` [MODIFY]

## Task Breakdown

### Task 1: Create the System Earnings Transaction Ledger
- **Agent**: `backend-specialist`
- **Skills**: `database-design`, `backend-specialist`
- **Priority**: P0
- **Dependencies**: None
- **INPUT→OUTPUT→VERIFY**: Input: Create schema for actual payments -> Output: `SystemEarning` model and migration (fields: tenant_id, amount, currency, stripe_payment_intent_id, stripe_session_id, description, paid_at) -> Verify: `php artisan migrate` passes successfully.

### Task 2: Sync Stripe Payments during Registration Success
- **Agent**: `backend-specialist`
- **Skills**: `api-patterns`
- **Priority**: P1
- **Dependencies**: Task 1
- **INPUT→OUTPUT→VERIFY**: Input: RegistrationController webhook and success handler -> Output: Hook into `processSuccessfulRegistration` and webhook handlers to insert exactly what was paid into `SystemEarning`. -> Verify: Run manual payment test via Stripe checkout.

### Task 3: Load Actual Payments to Revenue Controller
- **Agent**: `backend-specialist`
- **Skills**: `clean-code`
- **Priority**: P1
- **Dependencies**: Task 1
- **INPUT→OUTPUT→VERIFY**: Input: Modify `Admin/RevenueController.php` -> Output: Inject `$recentPayments = SystemEarning::latest()->take(20)->get()` into the props passed to Inertia. -> Verify: Endpoint returns JSON with `recentPayments`.

### Task 4: Display "Actual Payments" on the Admin Dashboard
- **Agent**: `frontend-specialist`
- **Skills**: `frontend-design`
- **Priority**: P2
- **Dependencies**: Task 3
- **INPUT→OUTPUT→VERIFY**: Input: Update `Admin/Revenue/Index.vue` -> Output: A new data table under MRR charts explicitly listing chronological Stripe checkout payments. -> Verify: Compile UI with `npm run dev` and visually verify table rendering.

## Phase X: Verification
- [ ] Run Security Scan: `python .agent/skills/vulnerability-scanner/scripts/security_scan.py .`
- [ ] Linting & Types: `npm run lint && npx tsc --noEmit`
- [ ] Backend Test: Validate `SystemEarning` is stored upon manual mock tenant payment.
- [ ] UX Audit: `python .agent/skills/frontend-design/scripts/ux_audit.py .`

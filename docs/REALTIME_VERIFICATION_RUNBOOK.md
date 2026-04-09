# Realtime Verification Runbook (Tenant/Staff/Patients)

Date: 2026-04-09

## Local Runtime (confirmed)
- Laravel app: `http://localhost:8080`
- Reverb websocket server: `localhost:8081` (TCP reachable)
- Vite: `http://localhost:5174`
- Tenant URL (localhost-only): `http://junjunsmile.localhost:8080`

Note: Use localhost-based tenant subdomains only (for example `*.localhost`).

## Pre-Flight
1. Open two browser windows (A and B).
2. Use same tenant domain in both windows: `http://junjunsmile.localhost:8080`.
3. Log in with two different staff accounts if possible.
4. Keep DevTools Console open in at least one tab.

## Scenario 1: Online Booking -> Queue Realtime
1. In Window A, open public booking page: `/book` and submit booking.
2. In Window B, open Appointments page.
3. Expected:
- New booking row appears without refresh.
- Toast appears for new online booking.

## Scenario 2: Appointment Lifecycle Realtime
1. In Window A, approve/reject/update appointment.
2. In Window B, keep Appointments page open.
3. Expected:
- Status/list updates instantly.
- Deletions remove row without refresh.

## Scenario 3: Notification Bell Realtime
1. Trigger any action that creates in-app notifications (e.g., booking create/update).
2. Observe bell in the other window.
3. Expected:
- Unread badge increments immediately.
- Latest notification appears in dropdown list without manual fetch.

## Scenario 4: Patient Module Realtime
1. In Window A, create/update/delete patient.
2. In Window B, keep Patients page open.
3. Expected:
- Patient row inserts/updates/removes immediately.
- No full page reload required.

## Scenario 5: Services Module Realtime
1. In Window A, create/edit/approve/reject/delete service.
2. In Window B, keep Service & Pricing page open.
3. Expected:
- Service status/row syncs immediately across tabs.

## Scenario 6: Treatments Module Realtime
1. In Window A, add/edit/delete treatment.
2. In Window B, keep Treatments page open.
3. Expected:
- Treatment list updates in realtime.
- If a treatment currently opened in modal is deleted, modal is safely closed.

## Scenario 7: Billing Module Realtime
1. In Window A, create invoice then mark as paid.
2. In Window B, open Billing tabs (Cashier, Transactions, Receipts).
3. Expected:
- Invoice updates reflected in all billing tabs without refresh.

## Scenario 8: Staff + Access Realtime
1. In Window A (owner), update role/permissions of staff user currently logged in at Window B.
2. Expected in Window B:
- Access update toast appears.
- Auth payload reloads and menu permissions update.
3. Delete staff account from Window A.
4. Expected in Window B:
- Forced-logout alert appears.
- User is logged out automatically.

## Scenario 9: Dashboard Live Counters
1. Keep Dashboard open in Window B.
2. In Window A, perform actions changing counts:
- Create/approve/reject appointments
- Add patient
- Create/pay invoice
3. Expected:
- Dashboard cards refresh automatically (daily appointments, pending bookings, total patients, monthly revenue).

## Quick Troubleshooting
- If no realtime updates appear:
1. Confirm Reverb still running on `8081`.
2. Confirm app still running on `8080`.
3. Confirm Vite still running on `5174`.
4. Confirm browser loaded from tenant domain (`junjunsmile.localhost`) not central URL.
5. Hard refresh one tab once to reload Echo subscriptions.

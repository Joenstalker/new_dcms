# Remaining Features Implementation Plan (APPROVED)

## Goal
Implement 5 remaining features (11, 12, 14, 15, 16) for the Tenant App, fully aligned with the dynamic feature system.

> **#13 Priority Support — REMOVED by user decision. Not implementing.**

---

## Subscription Plan Alignment

| Feature | Key | Type | Basic | Pro | Ultimate |
|---------|-----|------|-------|-----|----------|
| Appointment Notifications | `sms_notifications` | boolean | ❌ | ✅ | ✅ |
| Custom Branding | `custom_branding` | boolean | ❌ | ✅ | ✅ |
| Multi-Branch | `multi_branch` | boolean | ❌ | ❌ | ✅ |
| Report Level | `report_level` | tiered | basic | enhanced | advanced |
| Advanced Analytics | `advanced_analytics` | boolean | ❌ | ❌ | ✅ |

---

## Tasks

### Feature 12: Custom Branding Gate (Quick Win)
> **Existing:** Branding settings UI exists in `Settings/Partials/BrandingSettings.vue`. Flag `custom_branding` already in DB.

- [ ] **12.1** In `SettingsController@index` — conditionally pass `has_branding` from subscription
- [ ] **12.2** In `Settings/Index.vue` — hide branding tab/section if `!subscription.has_branding`, show upgrade CTA instead
- [ ] Verify: Basic tenant → branding tab hidden with upgrade prompt. Pro tenant → branding tab visible

---

### Feature 11: Appointment Notifications (DB + Email, Zero Cost)
> **Approach:** Database notifications + email. No SMS/telecom cost. Uses existing `TenantNotificationService`.

- [ ] **11.1** Create `NotificationTriggerService.php` — centralized service for sending appointment notifications
  - Methods: `onBookingCreated()`, `onBookingApproved()`, `onAppointmentReminder()`
  - Channels: database (via existing `TenantNotificationService`) + email (via Laravel Mail)
- [ ] **11.2** Create `AppointmentReminder` Mailable — email template for 24h reminders
- [ ] **11.3** Create `AppointmentConfirmation` Mailable — email on booking creation
- [ ] **11.4** Wire triggers into `AppointmentController::store()` and `approve()`
- [ ] **11.5** Create artisan command `SendAppointmentReminders` — scheduled daily, sends reminders for tomorrow's appointments
- [ ] **11.6** Register command in `console.php` schedule
- [ ] **11.7** Add notification preferences in Tenant `Settings/Index.vue` — toggle per event type
- [ ] **11.8** Gate entire notification feature: check `subscription.has_sms` before sending
- [ ] Verify: Pro tenant → create appointment → notification appears in bell + email sent. Basic tenant → no notifications triggered.

---

### Feature 15: Report Level Tiers
> **Existing:** `ReportController` with basic `index()`. `report_level` tiered feature (basic/enhanced/advanced) in DB.

- [ ] **15.1** Expand `ReportController` — add methods for each tier:
  - `basic`: Patient count, appointment count, revenue total (simple cards)
  - `enhanced`: + trend charts (monthly revenue, patient growth, appointment fill rate)
  - `advanced`: + export to PDF/CSV, service breakdown, dentist utilization, period comparison
- [ ] **15.2** Update `Reports/Index.vue` — conditionally render sections based on `subscription.report_level`
- [ ] **15.3** Add export endpoints: `reports/export/pdf`, `reports/export/csv` (advanced only)
- [ ] Verify: Basic → sees simple cards. Pro (enhanced) → sees cards + charts. Ultimate (advanced) → sees everything + export buttons

---

### Feature 16: Advanced Tenant Analytics
> **Existing:** `advanced_analytics` flag in DB. No tenant-side analytics page exists.

- [ ] **16.1** Create `Tenant/AnalyticsController.php` — aggregate queries:
  - Revenue by period (daily/weekly/monthly)
  - Patient acquisition trend
  - Appointment fill rate & no-show rate
  - Top services by revenue
  - Dentist utilization (hours worked vs available)
- [ ] **16.2** Create `Tenant/Analytics/Index.vue` — dashboard with chart tabs:
  - Revenue Tab, Patients Tab, Appointments Tab, Services Tab
- [ ] **16.3** Add route: `Route::get('analytics', ...)` gated by `->middleware('check.subscription:advanced_analytics')`
- [ ] **16.4** Add sidebar link — visible only when `subscription.has_analytics === true`
- [ ] Verify: Ultimate tenant → `/analytics` renders charts. Pro/Basic → redirect with upgrade message

---

### Feature 14: Multi-Branch Support (Most Complex)
> **Existing:** `multi_branch` flag in DB. No branch model exists.

- [ ] **14.1** Create `Branch` model + tenant migration:
  - Fields: `name`, `address`, `phone`, `email`, `is_primary` (bool), `is_active` (bool)
- [ ] **14.2** Create `BranchController` — CRUD + set primary, toggle active
- [ ] **14.3** Add `branch_id` nullable FK to: `appointments`, `patients`, `users` (staff), `services`
  - Tenant migration: `add_branch_id_to_core_tables`
  - Default: assign all existing records to auto-created primary branch
- [ ] **14.4** Create `Branches/Index.vue` — branch list + manage modal
- [ ] **14.5** Create `BranchFormModal.vue` — add/edit branch
- [ ] **14.6** Add branch selector in tenant header — switch active branch context
- [ ] **14.7** Create `BranchScope` trait — auto-scope queries by active branch when multi-branch enabled
- [ ] **14.8** Gate route group: `->middleware('check.subscription:multi_branch')`
- [ ] Verify: Ultimate → create branch, assign staff, switch branch, data scoped. Non-Ultimate → branch menu hidden

---

## Cross-Cutting
- [ ] **CC.1** Update sidebar (`AdminLayout.vue`) — conditionally show Analytics, Branches links
- [ ] **CC.2** Ensure all feature gating uses `$page.props.subscription` (no hardcoded plan names)

## Done When
- [ ] All 5 features accessible with correct plan gating
- [ ] No hardcoded plan names — all dynamic via `hasFeature()` / `$page.props.subscription`
- [ ] Existing tests pass: `php artisan test`

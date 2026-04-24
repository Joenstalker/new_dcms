# PLAN: Stripe Embedded Checkout (No-Redirect Modal Flow)

## Overview

**Goal:** Replace the current Stripe redirect-based payment flow with Stripe Embedded Checkout. The user stays on the landing page throughout — the payment UI appears inside the existing `RegistrationModal.vue` as Step 4, and after payment is confirmed, the Stripe iframe disappears and a success screen (Step 5) is shown inline.

**Current behavior:**
- User clicks "Proceed to Payment" → `emit('openPayment')` → external handler → `POST /registration/checkout` → browser redirects to Stripe → after payment, redirects to `/registration/success` → renders `payment-received.blade.php` as a full page

**New behavior:**
- User clicks "Proceed to Payment" → `POST /registration/checkout` (now returns `clientSecret` instead of a redirect URL) → Stripe Embedded Checkout mounts inside the modal (Step 4) → `onComplete` fires → `POST /registration/complete` (JSON) → modal transitions to Step 5 (success screen) → user stays on landing page ✅

---

## Project Type
**WEB** — Laravel backend + Vue 3 (Inertia.js) frontend

---

## Success Criteria

- [ ] User can complete the full registration without the browser tab ever navigating away
- [ ] Stripe Embedded Checkout renders inside the modal at Step 4
- [ ] After payment, `onComplete` fires and the modal transitions to a success screen (Step 5)
- [ ] The success screen shows clinic name, amount paid, subdomain URL, and countdown timer
- [ ] `PendingRegistration` + `Tenant` are created correctly via the new JSON endpoint
- [ ] Emails (RegistrationPending + admin notification) still fire
- [ ] Old `/registration/success` blade page still works as a fallback (not removed)
- [ ] `npm run build` succeeds with no errors

---

## Tech Stack

| Layer | Technology |
|---|---|
| Payment SDK | `@stripe/stripe-js` (already in npm or to be installed) |
| Frontend | Vue 3 + Inertia.js |
| Backend | Laravel `RegistrationController` |
| Routing | `routes/web.php` — 1 new route added |

---

## File Structure (Changes Only)

```
app/Http/Controllers/
  └── RegistrationController.php        [MODIFY] — switch checkout + add completeRegistration()

routes/
  └── web.php                           [MODIFY] — add POST /registration/complete route

resources/js/Components/
  └── RegistrationModal.vue             [MODIFY] — add Step 4 (Stripe embed) + Step 5 (success)

resources/views/emails/registration/
  └── payment-received.blade.php        [NO CHANGE] — kept as fallback
```

---

## Task Breakdown

### T1 — Backend: Switch `createCheckoutSession` to Embedded Mode
**Agent:** `backend-specialist`
**Priority:** P0 (blocks all frontend work)
**Dependencies:** none

**What changes in `RegistrationController::createCheckoutSession()`:**
- Add `'ui_mode' => 'embedded'` to Stripe session params
- Replace `'success_url'` with `'return_url' => config('app.url') . '/?payment=success'` (required by Stripe even for embedded; used as fallback only)
- Change JSON response: instead of returning `{ url }`, return `{ clientSecret: $session->client_secret }`

```
INPUT:  Same POST /registration/checkout payload as today
OUTPUT: { success: true, clientSecret: "cs_test_..." }
VERIFY: POST /registration/checkout via Postman → response has clientSecret key, no url key
```

---

### T2 — Backend: New `completeRegistration()` JSON Endpoint
**Agent:** `backend-specialist`
**Priority:** P0
**Dependencies:** T1

**Why:** The current `handleSuccess()` method returns a Blade view (full page). We need a JSON version that the Vue `onComplete` callback can call without a page navigation.

**New method `completeRegistration(Request $request)`:**
- Accepts `session_id` as POST body
- Runs the same logic as `handleSuccess()`: verify Stripe session, find `PendingRegistration`, create `Tenant`, create `Domain`, create `Subscription`, update `PendingRegistration`, send `RegistrationPending` mail, notify admins
- Returns JSON: `{ success: true, registration: { clinic_name, first_name, last_name, amount_paid, expires_at, subdomain } }`
- On failure: returns JSON error with appropriate HTTP status

**New route in `web.php`:**
```php
Route::post('/complete', [RegistrationController::class, 'completeRegistration']);
// Named: registration.complete
```

```
INPUT:  POST /registration/complete  { session_id: "cs_xxx" }
OUTPUT: { success: true, registration: { clinic_name, amount_paid, expires_at, subdomain } }
VERIFY: After real Stripe test payment, endpoint returns registration data without page redirect
```

---

### T3 — Frontend: Install / Confirm `@stripe/stripe-js`
**Agent:** `frontend-specialist`
**Priority:** P1
**Dependencies:** none (parallel with T1/T2)

Check if `@stripe/stripe-js` is in `package.json`. If not, install it:
```bash
npm install @stripe/stripe-js
```

```
INPUT:  package.json
OUTPUT: @stripe/stripe-js present in dependencies
VERIFY: npm list @stripe/stripe-js → shows version number
```

---

### T4 — Frontend: Extend `RegistrationModal.vue` — Step 4 (Stripe Embed)
**Agent:** `frontend-specialist`
**Priority:** P1
**Dependencies:** T1, T3

**Current modal has 3 steps + emits `openPayment` to parent.**
New approach: payment happens INSIDE the modal as Step 4.

**Changes to `RegistrationModal.vue`:**

1. **Remove the `openPayment` emit** — payment flow stays inside this component
2. **Change `proceedToPayment()`:**
   - Instead of `emit('openPayment', ...)`, call `POST /registration/checkout` directly
   - On success, store `clientSecret` in a ref
   - Advance to `currentStep.value = 4`
3. **Add Step 4 template block:**
   ```html
   <div v-if="currentStep === 4">
     <div id="stripe-embedded-checkout"></div>
   </div>
   ```
4. **Mount Embedded Checkout after `currentStep` becomes 4:**
   - `loadStripe(import.meta.env.VITE_STRIPE_KEY)`
   - `stripe.initEmbeddedCheckout({ clientSecret, onComplete: handlePaymentComplete })`
   - `checkout.mount('#stripe-embedded-checkout')`
5. **`handlePaymentComplete()` callback:**
   - Call `checkout.unmount()` / `checkout.destroy()`
   - Call `POST /registration/complete` with `session_id`
   - Store response data in `paymentResult` ref
   - `currentStep.value = 5`
6. **Update progress bar:** Change `v-for="step in 3"` → `v-for="step in 4"` (Step 4 = Payment, not shown in stepper or hidden during step 4/5)
7. **Update header step counter:** Hide "Step X of Y" text during steps 4 and 5

```
INPUT:  RegistrationModal.vue with 3-step flow
OUTPUT: Modal supports 5 steps; Stripe iframe mounts at step 4; onComplete transitions to step 5
VERIFY: Browser test — go through steps 1-3, click Pay, see Stripe UI appear inside modal
```

---

### T5 — Frontend: Add Step 5 (Success Screen) Inside Modal
**Agent:** `frontend-specialist`
**Priority:** P2
**Dependencies:** T4

**Step 5 template** — mirrors the content from `payment-received.blade.php` as a Vue template:

```html
<div v-if="currentStep === 5" class="py-6 text-center space-y-4">
  <!-- Success icon -->
  <!-- "Payment Received!" heading -->
  <!-- Dear {first_name} message -->
  <!-- Countdown timer (JS setInterval, same logic as blade) -->
  <!-- Registration details card: clinic, url, amount, status -->
  <!-- "What's next?" text -->
  <!-- Close button → closeModal() -->
</div>
```

Key data comes from `paymentResult` ref (populated in T4's `handlePaymentComplete`).

Countdown timer: replicate the vanilla JS countdown from `payment-received.blade.php` using Vue's `onMounted` + `setInterval` inside a `watch` or lifecycle hook triggered when `currentStep === 5`.

```
INPUT:  paymentResult ref with { clinic_name, first_name, last_name, amount_paid, expires_at, subdomain }
OUTPUT: Beautiful success screen inside the modal
VERIFY: After test payment, step 4 disappears, step 5 shows with correct clinic name and countdown
```

---

### T6 — Frontend: Add `VITE_STRIPE_KEY` Environment Variable
**Agent:** `frontend-specialist`
**Priority:** P1
**Dependencies:** T3

Add to `.env`:
```
VITE_STRIPE_KEY=pk_test_xxx
```

This is the Stripe **publishable key** (safe for frontend). Verify it's already set (check existing `.env`/`config` for any existing Stripe key reference in the frontend). If it exists under a different name, use that instead.

```
INPUT:  .env file
OUTPUT: VITE_STRIPE_KEY present
VERIFY: console.log(import.meta.env.VITE_STRIPE_KEY) in browser → shows pk_test_...
```

---

### T7 — Cleanup: Update Landing.vue to Remove `openPayment` Handler
**Agent:** `frontend-specialist`
**Priority:** P3
**Dependencies:** T4

Since `RegistrationModal.vue` no longer emits `openPayment`, wherever `Landing.vue` (or the parent page) listens to `@openPayment` needs to be cleaned up to prevent dead code or errors.

```
INPUT:  Landing.vue / parent page component
OUTPUT: No orphaned @openPayment listener; no PaymentModal.vue open call (if one existed)
VERIFY: No console errors when clicking "Proceed to Payment"
```

---

## Risk & Mitigations

| Risk | Mitigation |
|---|---|
| `onComplete` not available in older Stripe.js versions | Verify `@stripe/stripe-js` is latest. `onComplete` was added in 2023 |
| CORS / CSP blocking Stripe iframe | Stripe embedded checkout uses `*.stripe.com` — ensure no strict CSP header blocks iframes |
| Stripe test card 3DS triggers redirect | 3DS challenge opens a popup by Stripe — this is expected; `onComplete` still fires after |
| User closes modal mid-payment | On `closeModal()`, call `checkout.destroy()` if instance exists; Stripe will cancel the session |
| Double-submit if user clicks Pay twice | Disable "Proceed to Payment" button after first click; reset if POST /checkout fails |

---

## Phase X: Verification Checklist

### Automated
```bash
# Build check
npm run build

# Security scan
python .agent/skills/vulnerability-scanner/scripts/security_scan.py .

# UX audit
python .agent/skills/frontend-design/scripts/ux_audit.py .
```

### Manual Browser Test
1. Open the landing page
2. Select a plan → click "Register" or "Get Started"
3. Complete Step 1 (account info) → Step 2 (subdomain) → Step 3 (review)
4. Click **"Proceed to Payment"** — verify the modal does NOT close, Stripe embed appears as Step 4
5. Use Stripe test card `4242 4242 4242 4242` / any future date / any CVC
6. Verify: Stripe UI closes inside the modal, Step 5 success screen appears with correct clinic name
7. Verify: Browser URL did NOT change at any point
8. Check admin panel → Pending Registrations → new entry should exist
9. Check email inbox → `RegistrationPending` mail received

### Keep Old Flow (Fallback)
- `GET /registration/success?session_id=xxx` still returns `payment-received.blade.php` (not removed)
- This can be used if the embedded flow fails for any reason

---

## Agent Assignments Summary

| Task | Agent | Priority |
|---|---|---|
| T1 — Switch createCheckoutSession to embedded | backend-specialist | P0 |
| T2 — New completeRegistration JSON endpoint | backend-specialist | P0 |
| T3 — Install @stripe/stripe-js | frontend-specialist | P1 |
| T4 — Step 4: Stripe Embed in Modal | frontend-specialist | P1 |
| T5 — Step 5: Success Screen in Modal | frontend-specialist | P2 |
| T6 — VITE_STRIPE_KEY env var | frontend-specialist | P1 |
| T7 — Clean up Landing.vue openPayment handler | frontend-specialist | P3 |

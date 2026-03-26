# Dynamic Subscription Plan Features

## Objective
Enforce subscription plan limits and feature access dynamically across the tenant portal. We want to ensure tenants only access what they've paid for, while actively upselling higher tiers to grow revenue.

## Architectural Decisions
Based on our discussion, we will implement the industry standard SaaS pattern:
1. **Feature Visibility UI**: Show unavailable features in the Sidebar with a **"Lock" icon** to encourage upgrades, rather than hiding them completely.
2. **Handling Downgrades/Limits**: If a tenant exceeds a numerical limit (e.g. 5 users on a 4 user plan), we apply a **"Soft Lock"**: preventing the creation of *new* resources until they upgrade or delete existing ones. We will never delete user data automatically.
3. **Enforcement Scope**: Strict validation at *both* the UI level (hiding/locking) and the **Backend level (Middleware)** for total security.

---

## 🛑 Phase 1: User Review
*Requires user approval before proceeding to Phase 2.*

---

## 🚀 Phase 2: Implementation (Parallel Orchestration)

Upon approval, the orchestrator will coordinate the following agents concurrently:

### 1. 🏗️ `database-architect` (Core Logic)
- **Task**: Enhance the `Tenant` / `SubscriptionPlan` models.
- **Details**: Add helper methods to quickly evaluate numerical limits (`canAddMoreUsers()`, `canAddMorePatients()`, `canAddMoreAppointments()`) and strict feature presence.

### 2. 🛡️ `backend-specialist` (Middleware & Infrastructure)
- **Task**: Secure the API and prepare data for the UI.
- **Details**: 
  - Create `CheckTenantFeature` and `CheckTenantLimit` middleware to protect routes.
  - Apply middleware to critical tenant routes.
  - Share active plan limits and locked features via Inertia shared props (`HandleInertiaRequests.php`).

### 3. 🎨 `frontend-specialist` (UI / UX)
- **Task**: Reflect limits and locked features in the UI.
- **Details**: 
  - Update `AuthenticatedLayout.vue` sidebar to display a 🔒 **Lock icon** and a tooltip/modal on restricted menu items.
  - Update `Index` views (e.g., Staff, Patients) to disable the "Create" buttons and show an "Upgrade Plan" banner if limits are reached.

### 4. 🧪 `test-engineer` (Verification)
- **Task**: Validate security and code quality.
- **Details**:
  - Run `.agent/skills/vulnerability-scanner/scripts/security_scan.py` (if available) or standard checks.
  - Run linting and verify that the app builds correctly.

---

## 🏁 Phase 3: Synthesis & Handoff
- Generate the final Orchestration Report detailing all agent findings and actions.

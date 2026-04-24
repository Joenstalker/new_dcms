# PHASE 0 — SYSTEM ANALYSIS

## 1. System Components Identification

### 1. Tenancy Package Used
**Stancl/Tenancy (^3.9)** is the underlying multi-tenant architecture providing multi-database management.

### 2. Landlord Database Configuration
The landlord (central) database operates under the default `central` database connection. The standard Laravel `database/migrations` directory applies exclusively to the landlord database, controlling `tenants`, `domains`, `subscription_plans`, and `features`.

### 3. Tenant Database Initialization Flow
When a tenant is created, Stancl creates a new database natively. 
- Connecting logic utilizes `Stancl\Tenancy\Bootstrappers\DatabaseTenancyBootstrapper`.
- Tenant DB names are hashed by default (`tenant_[16char_hash]_db`) as configured in `config/tenancy.php`.
- The `MySQLDatabaseManager` actively creates these databases at runtime during registration creation pipelines.

### 4. Migration Separation (Tenant vs Landlord)
Strict separation is enforced through directory structures:
- **Landlord:** `database/migrations/`
- **Tenant:** `database/migrations/tenant/`
The command `php artisan tenants:migrate` executes exactly only the files inside the `tenant/` subdirectory against each connected tenant database in sequence dynamically.

### 5. Existing Feature Management Implementation
Features are managed centrally via the Landlord database.
- Database tables: `features`, `plan_features`, and `tenant_feature_updates` exist in global space.
- A basic over-the-air (OTA) setup logic exists within `features` table parameters.
- There is NO advanced lifecycle capability ("Coming Soon", etc.) in the database out-of-the-box.

### 6. Subscription Plan Structure
Subscription architecture is fully integrated into the Landlord database.
- `subscription_plans`: Holds the available tiers (e.g., Basic, Pro, Ultimate) and caps (bandwidth, storage).
- `subscriptions`: Relates individual tenants to a plan via Stripe subscription endpoints, including payment methods.
- Plans map directly to features through `plan_features`.

### 7. Middleware Related to Tenancy
Tenant-scoped routing and checks happen here:
- `app/Http/Middleware/CheckSubscription.php` (`check.subscription` alias) controls plan tiers.
- `app/Http/Middleware/CheckTenantFeature.php` (`tenant.feature` alias) checks granular feature enablement per tenant limit configuration.
- `app/Http/Middleware/CheckTenantLimit.php` regulates resource constraints dynamically per plan.

### 8. Current Deployment/Version Handling
Currently managed via `AppVersionService`.
- Fetches and caches the official app version directly from **GitHub Releases**.
- **Issue:** Web application code (checked via GitHub) is decoupled entirely from schema migration boundaries within tenant databases.

---

## 2. Architecture Diagram (Text)

```text
[ Incoming Request (Inertia/Vue) ]
            |
            V
[ Stancl Tenancy Bootstrapper ] ---> Identify Tenant via Domain/Subdomain
            |
            +--> Landlord Scope (Connection: `central`)
            |    - Tenants (domain mapped)
            |    - Subscription Plans & Subscriptions
            |    - Feature Definitions & `plan_features` mapping
            |    - System Version Control
            |
            +--> Tenant Scope (Connection: `tenant`)
                 - Custom Database (`tenant_[hash]_db`)
                 - Patients, Appointments, Invoices
                 - Tenant-Specific App Features
```

---

## 3. Risks for Multi-DB Upgrades

1. **Catastrophic Schema Desynchronization:** If an upgrade script fails halfway, 50% of tenants are functionally on the new feature schema while 50% remain on the old one. Codebase updates via git affect ALL tenants instantly. Code expects new columns, but DBs lack them.
2. **Version Unawareness:** The system pulls version info from GitHub Releases for display but does not actually know if the tenant's individual database schema is in parity with the released codebase.
3. **Time-Out Issues in Production:** Looping over hundreds of tenant databases synchronously during an automated deployment will hit PHP/Nginx execution timeouts. Upgrades must be isolated background processes.

---

## 4. Missing Components Required for Update System

- **Database Version Metadata Table:** We need a way to track explicitly which DCMS App version is applied to a specific Landlord OR Tenant database.
- **Tenant Upgrade Queue Strategy:** Async, isolated Laravel Jobs per tenant (e.g., `UpdateTenantDatabaseJob`) that dispatch automatically avoiding memory limits.
- **"Coming Soon" Feature Extension:** Modification to `Feature` model and frontend resolvers to parse lifecycles like `is_preview`, `is_coming_soon`, instead of just `active`/`inactive`.
- **System Maintenance Mode / Pre-Flight Checker:** Capability to restrict UI access for a single tenant while their specific database is mid-migration.

---

## 5. Recommended Extension Points (Services/Events)

1. **`Stancl\Tenancy\Events\TenantCreated`:** Hook here to automatically stamp the newly created tenant DB with the **current** global DCMS version.
2. **Feature Middleware (`tenant.feature`):** Extend this to reject routes elegantly if a feature is marked "Coming Soon" or explicitly lock it to super-admin test accounts based on beta gating.
3. **New Upgrade Orchestration Service:** `DCMSUpgradeService` that creates a tracked upgrade batch, pushing `TenantUpgradeJob` executions to the `queue.php` worker gracefully.
4. **Tenant Header Component (`resources/js/...`):** Global Inertia prop inclusion via `HandleInertiaRequests` to supply "Feature Flags Array" to Vue without needing duplicate Axios calls contextually updating UI stubs.

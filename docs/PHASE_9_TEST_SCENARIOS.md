# Tenant Upgrade System Validation & Verification Scenarios

This suite outlines the exact test configurations needed to observe and confirm the absolute safety and data integrity of the new DCMS Version ORM system natively. 

## Scenario 1: Upgrade Success Sequence
**Test Context:** A clean upgrade trigger from version `v1.0.0` to `v1.1.0`. 
1. **Initiate:** Admin triggers `POST /api/system/update`. 
2. **Observe:** The controller responds with an immediate `200 OK` (queued state), leaving the UX completely unresponsive.
3. **Queue Monitor:** The `UpgradeTenantJob` captures the request.
4. **Validations:**
    - `tenant_maintenance_ID` cache is explicitly populated. 
    - Database `tenant_upgrade_logs` registers natively with status `running`.
    - `FeatureSyncService` parses the tenant's plan seamlessly and ensures baseline additions to `tenant_features`.
    - `$tenant->version` saves reliably to `v1.1.0`.
    - Output from `migrate --force` lands neatly inside the `log_output`.

---

## Scenario 2: Fatal Migration Disconnect (Failure Recovery)
**Test Context:** A migration inherently corrupts due to a syntax issue inside `database/migrations/tenant`. 
1. **Initiate:** Pushed an intentional bad migration schema script.
2. **Action Trigger:** The Job fires the request directly to `$tenant->run(migrate)`.
3. **Validations (Recovery Protocol Executed):**
    - Migration fails fatally.
    - Tenant Object `$tenant->version` rollback directly triggers `catch()` resolving back to the `oldVersion`.
    - The transaction natively reverses Landlord DB states. 
    - `tenant_upgrade_logs` marks the exact trace payload definitively under status `failed`.
    - Maintenance mode un-locks actively (`Cache::forget(...)`).

---

## Scenario 3: Graceful Feature Manual Overrides 
**Test Context:** Features mismatched natively. Admin gave a specific user explicit access to `Advanced Analytics`, but the tenant’s tier (`Basic Plan`) does not have this capability natively. An update drops pushing plan syncs.
1. **Action Trigger:** Syncing is run passively.
2. **Validations:**
    - `FeatureSyncService` utilizes `firstOrCreate()`. 
    - The Tenant's configuration for `Advanced Analytics` skips mutation. 
    - All *other* baseline updates are successfully generated.
    - Manual UI Beta selections remain untouched implicitly.

---

## Scenario 4: Concurrent Upgrade Safety Locks
**Test Context:** A rogue script or user spam-clicks the `/update` payload dynamically 50 times in 1 second.
1. **Initiate:** Mass fire array logic dynamically creating identical Jobs.
2. **Action Trigger:** Priority 1 arrives at the `TenantUpgradeService`. It evaluates `Cache::lock(...)` natively and assumes authority. 
3. **Validations (Lock Sequence Safety):**
    - Payload 2->50 arrive milliseconds later. The `!$lock->get()` logic hard halts the subsequent operations. 
    - Log explicitly fires the string `"Abort: Tenant [ID] database upgrade is already running."` natively to `storage/logs/laravel.log`.
    - Memory/Database connections are safeguarded against mass concurrency schema duplication effectively natively. 

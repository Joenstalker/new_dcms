# PLAN: GitHub-Driven System Updates & Notifications

## Objective
Automate the synchronization of GitHub Releases with the Tenant "System Updates" dashboard. Tenants will be notified of new versions, can view changelogs, and "apply" updates to stay current with the latest platform features and **database migrations**.

## Proposed Architecture

### 0. Schema Changes (`database-architect`)
-   **Add `system_release_id` to `features`**: Link `Feature` records to `SystemRelease` records to enable version tracking.

### 1. GitHub Integration Service (`backend-specialist`)
-   **Expansion of `AppVersionService`**: Add a method to fetch all recent releases from the GitHub API.
-   **Flagging Migrations**: The `SystemRelease` model already has `requires_db_update`. We will set this to `true` if the GitHub release body contains a specific keyword (e.g., `[MIGRATION]`) or if an admin manually flags it in the future.

### 2. Automated Sync Logic (`backend-specialist`)
-   **Enhance `CheckSystemUpdates.php` command**:
    -   Create `SystemRelease` and `Feature` (link them).
    -   If `requires_db_update` is detected, set the flag in the release record.
    -   Broadcast `TenantFeatureUpdate` (pending) to all active tenants.

### 3. Manual Version & Migration Application (`backend-specialist`)
-   **Update `FeatureOTAUpdateService`**:
    -   When a tenant applies a feature of type `system_version`:
        1.  **Version Update**: Set `tenant.version` to match the release.
        2.  **Migration Trigger**: If the release `requires_db_update` is TRUE, the system will execute `Artisan::call('tenants:migrate', ['--tenants' => [$tenant->id]])`.
    -   This guarantees that **schema changes** only touch the tenant's database when they choose to update.

### 4. Tenant Dashboard UI (`frontend-specialist`)
-   **Updates Page (`Updates.vue`)**: 
    -   Show a "Database Update Required" warning on version cards that involve migrations.
    -   Render Markdown/Changelogs.

## Phase 1: Preparation
- [ ] Create migration for `features.system_release_id`.
- [ ] Update `AppVersionService` for full release fetching.
- [ ] Update `CheckSystemUpdates` command with migration detection logic.

## Phase 2: Core Logic
- [ ] Implement manual `tenants:migrate` execution in `FeatureOTAUpdateService`.
- [ ] Ensure version data is persisted correctly.

## Phase 3: User Interface
- [ ] Update `Updates.vue` layout for changelogs and "Migration Needed" indicators.
- [ ] Implement Markdown rendering for release notes.

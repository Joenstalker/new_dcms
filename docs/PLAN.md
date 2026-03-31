# Multi-Agent Orchestration Plan: App Versioning Display

**Context:**
- **User Request:** Display the main application's current version to all tenants in the sidebar.
- **Decisions:** 
  - **What:** The DCMS application's GitHub version/status displayed in the UI.
  - **How:** Use the GitHub API (or local `.git` state) to fetch the latest version securely and cache it.
  - **Auto-Increment:** The version will auto-increment on every push/change (either via Commit Count or a GitHub Action pipeline).
  - **UI Location:** Sidebar bottom, below the user profile.

## Phase 1: Planning (Completed)
- Clarified that this is for displaying the global app version to tenants, not backing up tenant data.

## Phase 2: Implementation (Parallel Execution)

### Agent 1: `backend-specialist` (Core Version Service)
- Create a `AppVersionService` class in Laravel.
- Implement logic to fetch the current version from the GitHub repository:
  - *If Commit-Based:* Fetch the total commit count to dynamically generate the version (e.g., version `1.0.{commits}`).
  - *If Release-Based:* Fetch the `releases/latest` API endpoint.
- Cache the result (using `Cache::remember('app_version', 3600, ...)` for 1 hour) to ensure we do not hit GitHub API rate limits and keep the UI blazing fast.
- Bind the version string globally via Inertia middleware (e.g., `HandleInertiaRequests`) or a View Composer so the frontend always has access to it.

### Agent 2: `frontend-specialist` (Sidebar UI)
- Locate the main Sidebar layout component for the tenant UI.
- Add a subtle design element at the bottom of the sidebar, right below the profile section.
- Render the dynamic version string (e.g., `<span class="text-xs text-gray-400">Version: v1.0.61</span>`).

### Agent 3: `test-engineer` (Verification)
- Write tests to ensure `AppVersionService` degrades gracefully if the GitHub API is down or rate-limited (it should fallback to a default or old cached value so the UI doesn't crash).
- Run mandatory verification scripts (`security_scan.py` and `lint_runner.py`).

## Exit Criteria
- Tenants can see the current system version seamlessly at the bottom of their sidebar.
- The system automatically increments or updates this version based on new commits/releases in GitHub.
- The GitHub API usage is safely cached and fully optimized.

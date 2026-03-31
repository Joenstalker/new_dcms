# Multi-Agent Orchestration Plan: App Versioning Display (API Edition)

**Context:**
- **User Request:** Display the main application's current **GitHub Release Version** to all tenants in the sidebar.
- **Decisions:** 
  - **What:** The DCMS application's GitHub Release tag.
  - **How:** Use Laravel's `Http` facade to GET `api.github.com/repos/Joenstalker/new_dcms/releases/latest`.
  - **Fallback:** Graceful Degradation (`v1.0.0-dev`) if API limits are hit.
  - **Cache:** 1 Hour cache to prevent rapid API consumption.

## Phase 1: Planning (Completed)
- Transitioned from local `git rev-list` to professional Semantic Releases.

## Phase 2: Implementation (Parallel Execution)

### Agent 1: `backend-specialist` (Core Version Service)
- Rewrite `AppVersionService.php` to use the GitHub API.
- Catch HTTP exceptions gracefully to ensure the app never crashes.

### Agent 2: `test-engineer` (Verification)
- Validate safety and performance.
- Clear cache to execute tests.

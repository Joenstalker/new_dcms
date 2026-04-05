# Custom Branding Analysis & Orchestration Plan

## 🎼 Orchestration Goal
Analyze the custom branding flow on both frontend and backend to ensure functionality, security, and completeness.
Additionally, completely remove the "ALL CHANGES SAVED" toast from `Index.vue` as specifically requested by the user.

## Agents Required (3+)
1. `project-planner`: Handled planning (currently).
2. `frontend-specialist`: Edit `Index.vue` to remove the save toast UI, and review frontend data flow.
3. `backend-specialist`: Review `TenantController` (or SettingsController) endpoints handling form submissions to ensure logo and branding settings are saved properly.
4. `test-engineer`: Verify system integrity post-changes via available static checks and test commands (`lint_runner.py` / `test_runner.py`).

## Proposed Implementation Steps
1. **Frontend**: In `resources/js/Pages/Tenant/CustomBranding/Index.vue`, find the Auto-Save Status Indicator `<div>` (lines 242-253) and either completely remove it or `v-if="false"` it. Also verify that `branding_color` correctly updates `brandingState.primary_color` (via the `watch` effect).
2. **Backend**: Verify `routes` for `/settings/logo` and `/settings`. Ensure it has proper validation, updates `tenant->enabled_features`, `font_family`, `branding_color`, `logo_path`, etc., and accurately handles file uploading for logos.
3. **Verification**: 
   - Execute `python .agent/skills/lint-and-validate/scripts/lint_runner.py .`
   - Test custom branding endpoint and file upload mechanism if unit tests exist, or manually verify.

## User Action Required
Please approve this plan before we proceed to Phase 2 (Implementation).

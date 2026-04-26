# Orchestration Plan: Tenant Custom Branding Validation Persistent Fix

## Objective
Identify and resolve the persistent array validation failure for `enabled_features` inside the Tenant Custom Branding page, despite previous backend casting attempts.

## Phase 1: Reconnaissance (Debugger & Backend Specialist)
1. **Payload Interception**: Inject a direct `File::put` dumping mechanism into `SettingsController@update` immediately before validation. This will capture the raw HTTP payload (`gettype`, actual value, and full `$request->all()`) dispatched by Inertia's `forceFormData: true`.
2. **Structural Analysis**: Determine precisely what Javascript structure is bypassing the `is_string` and `is_array` checks (e.g. nested objects, Vue Proxies, missing keys).

## Phase 2: Implementation (Frontend & Backend Specialists)
1. **Backend Payload Coercion**: If the payload is identifiable (e.g., an associative array `{0: "null"}` instead of an empty array or flat string), refine the middleware in `SettingsController` to intercept and safely flatten it.
2. **Frontend Sanitization (Fallback)**: If `forceFormData` fundamentally destroys the payload structure before transmission, intercept `form.enabled_features` inside `Index.vue`'s `autoSave()` method, ensuring it is strictly converted to `[]` or a valid literal Array before `form.post()` engages.

## Phase 3: Verification (Test Engineer)
- Watch the output log to confirm array traversal.
- Confirm that Custom Branding forms can successfully auto-save color, feature, and string updates.

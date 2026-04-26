# PLAN: OTA Manual Push (Option B)

## Phase -1: Context Check
**Goal**: Implement a manual "Push Update to Tenants" action (Option B) that allows admins to safely configure new features and assign them to plans before explicitly broadcasting the OTA update to eligible tenants.

## Phase 0: Socratic Gate (Clarification Required)
Before finalizing the technical implementation steps, the following strategic questions need to be answered to ensure the system behaves exactly as expected:

1. **Trigger Location**: Should the "Push OTA Update" button be located on the **Feature** management page (easiest to push one feature to all its plans), or on the **Subscription Plan** page (easiest to push multiple new features for a specific plan)? Answer: on the **Subscription Plan** page (I want that when I push update in the premium all tenants including those who did not avail the said premium will be notified because it gives advertisment to think them avail the said plan.)
2. **Notification Batching**: If an admin stages 3 new features over a week and then clicks "Push", should the system consolidate them into **one digest email**, or fire **three separate emails**? Answer: one digest email
3. **Admin Visibility**: Do we need to add a visual indicator (like a "Staged" vs "Pushed" badge) in the Admin panel so the admin knows which features are currently waiting to be pushed? Answer: yes

## Phase 1: Proposed Architecture (Draft)

### Database Changes
*   Add a `pushed_at` timestamp or `is_pushed` boolean to the `feature_subscription_plan` pivot table, OR handle tracking dynamically via the `tenant_feature_updates` existence.

### UI/UX Updates (Admin Panel)
*   Add a "Push Updates" action button mapped to an Inertia controller method.
*   Add visual indicators showing staged/unpushed features.

### Backend Logic
*   Create a new controller method `FeatureOTAController@push` to handle the manual trigger.
*   Update `FeatureOTAUpdateService` to calculate delta (features added to plan but not yet pushed) and execute the records creation and mailing.

## Phase 2: Implementation (Pending Socratic Gate)
*(This section will be detailed with exact files to create/modify once the questions in Phase 0 are answered.)*

## Phase 3: Verification
*   Tests to ensure features are NOT pushed automatically on save.
*   Tests to verify the manual push mechanism creates `tenant_feature_updates` and triggers targeted emails.

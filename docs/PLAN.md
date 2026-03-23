# Orchestration Plan: Tenant Schema and Profile Display Fixes

This plan outlines the steps to resolve the missing `profile_picture` column in tenant databases and the display issues in the admin area.

## Proposed Changes

### 1. Database Schema (Tenant)
- **Goal**: Add `profile_picture` column to all tenant `users` tables.
- **Action**: Create a new migration in `database/migrations/tenant/`.
- **Details**: Column type `longtext`, nullable, positioned after `email`.

### 2. Storage Infrastructure
- **Goal**: Fix the broken image links.
- **Action**: Run `php artisan storage:link` to create the missing public symlink.
- **Verification**: Ensure `public/storage` points to `storage/app/public`.

### 3. Profile Display Debugging
- **Goal**: Ensure the uploaded photo displays immediately in the header and sidebar.
- **Action**: 
    - Verify `User` model accessor `profile_picture_url` handles storage paths correctly with the new symlink.
    - Ensure `ProfileDropdown.vue` and layouts reactively update after a successful Inertia visit.
    - Check if the `user` object in `page.props.auth.user` is being refreshed after the upload.

### 4. Verification
- Run `php artisan tenants:migrate` to update all tenant databases.
- Test uploading a new photo in the admin area.
- Verify the sidebar and header update immediately without a full page refresh.

## Orchestration Agents
- `project-planner`: Managing this plan.
- `database-architect`: Implementing the tenant migration.
- `backend-specialist`: Handling the storage link and controller logic.
- `frontend-specialist`: Verifying and polishing the UI reactivity.
- `test-engineer`: Final verification of the entire flow.

---
**Do you approve this plan? (Y/N)**

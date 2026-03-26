## Branding Decoupling
- **Principle**: Decouple tenant colors from central admin branding.
- **Logic**: Use `tenant.branding_color` -> fallback to `branding.primary_color`.

## Proposed Changes

### Backend
- **Routes**: Add `show`, `update`, and `destroy` routes with granular permissions.
- **Controller**: Implement `show`, `update`, and `destroy` methods.
- **Seeder**: Add `edit treatments` and `delete treatments` to `RolesAndPermissionsSeeder.php`.

### Frontend
- **PermissionsTab.vue**: Add dedicated "Treatment Module" group.
- **Index.vue**: Add modal states and permission-gated actions.
- **Modals**: Create `Create`, `Edit`, `Show`, and `Delete` treatment modals.

## Verification
- Manual browser testing of all CRUD operations.
- Security and linting checks.

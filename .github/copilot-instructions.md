# DCMS Project Guidelines

## Code Style
Follow PSR-12 for PHP. Use Laravel Pint for code formatting. For JavaScript/Vue, follow standard ESLint rules.

## Architecture
Multi-tenant SaaS on Laravel 11 using Stancl/Tenancy v3.9 with isolated per-tenant databases. Subdomain-based tenant identification (e.g., `clinic.dcms.test`).

See [FINAL_ARCHITECTURE_REPORT.md](docs/FINAL_ARCHITECTURE_REPORT.md) for detailed architecture and scalability considerations.

## Build and Test
- `php artisan serve --port=8080` (dev server)
- `npm run dev` (Vite assets)
- `php artisan reverb:start` (WebSocket server)
- `composer test` or `php artisan test` (unit/feature tests)
- `composer quality` (phpstan + pint + coverage, min 80%)

Use `npm run dev:all` for concurrent development stack.

## Conventions
- **Testing**: Use SQLite with `TEST_TOKEN` prefix for parallel execution. Always call `$this->deleteTenantSqliteFiles()` in tearDown(). Provision tenant DB with `provisionTenantSqliteAndMigrate($tenant)`.
- **Tenancy Context**: Explicit initialization `tenancy()->initialize($tenant)` and cleanup `tenancy()->end()`.
- **Authentication**: Manual session binding for tenant users: `withSession(['tenant_authenticated' => true, 'tenant_authenticated_tenant_id' => $tenant->id, 'tenant_authenticated_user_id' => $user->id])`.
- **Responses**: Use `ApiResponse` trait for standardized JSON: `respondSuccess()` / `respondError()`.
- **Assets**: Use `tenant_asset()` helper for tenant-specific files.

See [PHASE_0_ANALYSIS.md](docs/PHASE_0_ANALYSIS.md) for system analysis and extension points.

## Pitfalls
- Route model binding queries central DB before tenancy bootstrap—verify tenant manually.
- Avoid auth context leakage; session middleware enforces tenant boundaries.
- Handle Stripe webhooks for both `published` and `released` actions.
- Clear permission cache after seeding with `forgetCachedPermissions()`.
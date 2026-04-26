# PLAN: Session Configuration Overrides

## Context
Make the "Session Configuration" settings in the Admin portal functional by injecting them into the Laravel runtime configuration.

## Task Breakdown

### Phase 1: Core Implementation
- [ ] Create `App\Providers\SystemSettingsServiceProvider` <!-- id: 1 -->
- [ ] Implement config injection logic with caching <!-- id: 2 -->
- [ ] Register the provider in `bootstrap/providers.php` <!-- id: 3 -->

### Phase 2: Auth Logic Updates
- [ ] Update `LoginRequest.php` to use dynamic rate limiting <!-- id: 4 -->
- [ ] Update `AuthenticatedSessionController.php` if needed for "Remember Me" <!-- id: 5 -->

### Phase 3: Controller Updates
- [ ] Update `SystemSettingsController.php` to clear cache on save <!-- id: 6 -->

### Phase 4: Verification
- [ ] Verify config values via Artisan/Tinker <!-- id: 7 -->
- [ ] Test rate limiting behavior <!-- id: 8 -->

## Verification Checklist
- [ ] `config('session.lifetime')` matches database value <!-- id: 9 -->
- [ ] Login lockout triggers after `max_login_attempts` <!-- id: 10 -->
- [ ] Cache is cleared when settings are updated <!-- id: 11 -->

# Project Plan: GitHub Webhook for Real-time Updates

Enable real-time update detection by allowing GitHub to push release events directly to the application.

## Phase 1: Environment Configuration
- Add `GITHUB_WEBHOOK_SECRET` to `.env`.
- Update `config/services.php` to include:
  ```php
  'github' => [
      'webhook_secret' => env('GITHUB_WEBHOOK_SECRET'),
  ],
  ```

## Phase 2: Core Implementation
### GitHubWebhookController
- **Path**: `app/Http/Controllers/GitHubWebhookController.php`
- **Logic**:
    - Validate `X-Hub-Signature-256` using `hash_hmac('sha256', $payload, $secret)`.
    - Verify event type is `release` and action is `published`.
    - On success:
        - Call `AppVersionService::clearCache()`.
        - Trigger `Artisan::call('system:check-updates')`.
    - On failure (Invalid Signature):
        - Log in `AuditLog` with event `github_webhook_failed`.
        - Return 401 Unauthorized.

## Phase 3: Routing & Infrastructure
- **Route**: `POST /github/webhook` (Central domain).
- **CSRF**: Add to `except` array in `bootstrap/app.php`.

## Phase 4: Verification & Testing
### Scripts
- Create `tests/Feature/GitHubWebhookTest.php` or a temporary test script to simulate the HMAC signature.
### Live Test
1. Start `ngrok http 8080`.
2. Configure Webhook in GitHub Repo with the ngrok URL and the secret from `.env`.
3. Publish a test release and monitor `storage/logs/laravel.log` and the Admin Dashboard Audit Logs.

---

## Agent Assignments
| Agent | Task |
| :--- | :--- |
| **backend-specialist** | Controller logic, Routing, and Security. |
| **test-engineer** | Automated verification scripts. |
| **project-planner** | Orchestration and Plan maintenance. |

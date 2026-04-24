# DCMS (Dental Clinic Management System)

A robust, multi-tenant SaaS application built on Laravel 11, specifically designed for dental clinic management. It features secure tenant data isolation, dynamic subdomains, robust role-based access control, and an automated Stripe subscription billing engine.

---

## 🚀 Prerequisites

Before you begin, ensure you have the following installed on your machine:
- **PHP** >= 8.2
- **Composer** (latest version)
- **Node.js** & **npm** (latest LTS)
- **MySQL** / MariaDB
- **Stripe CLI** (Required for local webhook testing)

---

## ⚙️ Local Setup Guide

Follow these steps to get the project running on your local machine:

### 1. Clone & Install Dependencies
Clone the repository and install both PHP and Node.js dependencies:
```bash
git clone <repository-url>
cd dcms
composer install
npm install
```

### 2. Configure Environment Variables
Copy the example environment file and generate your application key:
```bash
cp .env.example .env
php artisan key:generate
```

Open your `.env` file and set the minimum required values below.

This block is the most important setup for team onboarding because it controls:
- central admin login credentials,
- Laravel Reverb real-time broadcasting, and
- local database connection.

```env
APP_URL=http://localhost:8080

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=new_dcms_db
DB_USERNAME=root
DB_PASSWORD=

# Central admin login seed (used by AdminUserSeeder)
ADMIN_SEED_NAME="DCMS Admin"
ADMIN_SEED_EMAIL=admin@example.test
ADMIN_SEED_PASSWORD=ChangeMe_123!

# Reverb / broadcasting (local)
BROADCAST_CONNECTION=reverb
REVERB_APP_ID=dcms-local
REVERB_APP_KEY=dcms-local-key
REVERB_APP_SECRET=dcms-local-secret
REVERB_HOST=127.0.0.1
REVERB_PORT=8081
REVERB_SCHEME=http
REVERB_SERVER_HOST=0.0.0.0
REVERB_SERVER_PORT=8081

# Vite Echo client (must match Reverb host/port/scheme)
VITE_REVERB_APP_KEY="${REVERB_APP_KEY}"
VITE_REVERB_HOST="${REVERB_HOST}"
VITE_REVERB_PORT="${REVERB_PORT}"
VITE_REVERB_SCHEME="${REVERB_SCHEME}"

# GitHub webhook secret (for release sync)
GITHUB_WEBHOOK_SECRET=replace-with-a-long-random-secret
```

Important notes:
1. Do not commit real credentials to git.
2. `REVERB_SERVER_PORT` and `REVERB_PORT` should be the same port locally (recommended: `8081`).
3. If you change `.env` values, run `php artisan optimize:clear`.

### 3. Run Database Migrations & Seeders
Because this is a multi-tenant application using `stancl/tenancy`, you must migrate the central database first, then seed the initial admin data and subscription plans:
```bash
php artisan migrate:fresh --seed
```

After seeding, central login is available at:
- `http://localhost:8080/login`

Use the same values you set in `.env`:
- `ADMIN_SEED_EMAIL`
- `ADMIN_SEED_PASSWORD`

### 4. Start the Application
You will need three terminals running simultaneously for local development.

**Terminal 1 (Laravel Server):**
```bash
php artisan serve --port=8080
```

**Terminal 2 (Vite Assets):**
```bash
npm run dev
```

**Terminal 3 (Reverb WebSocket Server):**
```bash
php artisan reverb:start
```

You can now access the central landing page at `http://localhost:8080`.
The websocket server will independently run via Reverb gracefully on port `8081`.

---

## 🔌 Laravel Reverb (Real-time) Checklist

Use this quick checklist when notifications/live UI are not updating:

1. Ensure `BROADCAST_CONNECTION=reverb` in `.env`.
2. Ensure Reverb server/client ports match (recommended `8081`):
    - `REVERB_SERVER_PORT`
    - `REVERB_PORT`
    - `VITE_REVERB_PORT`
3. Ensure host/scheme are aligned:
    - local host: `127.0.0.1`
    - local scheme: `http`
4. Keep both processes running:
    - `php artisan reverb:start`
    - `npm run dev`
5. After changing Reverb env values:
    - `php artisan optimize:clear`
    - restart Laravel, Vite, and Reverb terminals.

---

## 💳 Stripe & Webhook Configuration

This project uses Stripe Embedded Checkout for tenant registration and the Stripe Customer Portal for subscription management (upgrades, downgrades, and renewals). 

To ensure that the local application database automatically updates when a tenant changes their plan on Stripe, you **must** configure Stripe Webhooks.

### 1. Get your Stripe API Keys
In your `.env` file, ensure your Stripe keys are set:
```env
STRIPE_KEY=pk_test_...
STRIPE_SECRET=sk_test_...
```

### 2. Install the Stripe CLI
If you are on Windows:
1. Download the executable from [Stripe CLI Releases](https://github.com/stripe/stripe-cli/releases/latest).
2. Extract the `stripe.exe` file.
3. Open your terminal in that folder and run:
   ```bash
   .\stripe login
   ```

### 3. Forward Webhooks Locally
Because Stripe cannot natively send POST requests to a private `localhost` URL, you must use the Stripe CLI to bridge the connection.

Open a terminal and run the following command to forward Stripe events to the local webhook handler:
```bash
.\stripe listen --forward-to http://localhost:8080/registration/webhook
```

### 4. Update your Webhook Secret
When you run the `listen` command, the terminal will output a webhook signing secret:
> `Your webhook signing secret is whsec_...`

Copy this value, paste it into your `.env` file, and restart your `php artisan serve` server:
```env
STRIPE_WEBHOOK_SECRET=whsec_...
```

Your local application is now fully configured to receive and process real-time subscription updates from Stripe!

---

## 🏢 Multi-Tenancy Architecture

This application uses database-separated multi-tenancy.
- **Central Database (`new_dcms_db`)**: Stores overarching data like the tenant list, domains, users (admins), and subscription plans.
- **Tenant Databases (`tenant_{id}_db`)**: Automatically created when a clinic registers. Each clinic gets its own isolated database for maximum security and data privacy.

Tenant access is routed dynamically via subdomains (e.g., `http://smile.localhost:8080`).

---

## 🧪 Preview Sandbox Tenant (Development Staging for Tenant Features)

This project includes a dedicated **Preview Sandbox Tenant** used to validate tenant-facing features before rolling them out to live clinics.

### Why this exists
1. Safely test tenant UI/flows without using production tenant accounts.
2. Validate feature behavior end-to-end before release.
3. Keep rollout controlled: live tenants receive changes only when updates are released and applied.

### How it is different from live tenants
1. It is identified by a dedicated tenant id/subdomain from environment variables.
2. It is treated as a development sandbox and can be reset.
3. It is intended for preview/testing, not day-to-day clinic operations.

### Required `.env` settings
```env
TENANT_PREVIEW_ID=preview-sandbox
TENANT_PREVIEW_SUBDOMAIN=tenantpreview
TENANT_PREVIEW_LOGIN_NAME="Preview Owner"
TENANT_PREVIEW_LOGIN_EMAIL=preview-owner@local.test
TENANT_PREVIEW_LOGIN_PASSWORD=preview-owner-password
```

After changes, clear caches:
```bash
php artisan optimize:clear
```

### Preview Tools dropdown (Admin top bar)
The **Preview Tools** dropdown is the control center for sandbox operations:
1. **Open Preview Sandbox**: Opens the preview tenant in a new tab (`tenantpreview.localhost:8080`).
2. **Reset Preview Sandbox**: Rebuilds sandbox data for clean re-testing.
3. **Clear Preview State**: Clears preview session flags in the current admin context.
4. **Preview Credentials**: Quick copy for preview login email/password.

### Recommended release flow (Preview -> Live)
1. Build and verify tenant changes in the Preview Sandbox Tenant.
2. Commit and push changes to GitHub.
3. Deploy updated code.
4. Register/publish the feature update in Central Admin.
5. Tenants apply the update per your OTA/update workflow.

### Important rollout contract (must-gate rule)
To ensure preview work does **not** auto-apply to live tenants:
1. All tenant-facing changes must be guarded by a feature gate key.
2. The gate must validate:
    - plan entitlement, and
    - tenant update status (`applied`) for that feature.
3. Deploying code alone must not expose the behavior unless the tenant applies the update.

If a change is shipped without gating, it becomes global immediately after deployment.

This preserves safe validation in preview while keeping live tenant rollout explicit and controlled.

## 🛡️ Support
If you encounter any issues during setup, feel free to open an issue or consult the Laravel and Stancl/Tenancy documentation.

## ⚓ GitHub Webhook Configuration (Real-time Updates)

To enable real-time detection of new releases and notify tenants immediately, you must configure a GitHub Webhook.

### 1. Set the Webhook Secret
In your `.env` file, set a unique secret:
```env
GITHUB_WEBHOOK_SECRET=replace-with-a-long-random-secret
```

### 2. Configure GitHub Settings
1.  Navigate to your repository **Settings** > **Webhooks** > **Add webhook**.
2.  **Payload URL**: `https://your-domain.com/github/webhook`
    - *For local testing, use ngrok: `https://abcd-123.ngrok-free.app/github/webhook`*
3.  **Content type**: Select `application/json`.
4.  **Secret**: Paste your `GITHUB_WEBHOOK_SECRET`.
5.  **Events**: Select **"Let me select individual events"** and check **Releases**.
6.  Click **Add webhook**.

### 3. Verification
Check the **Audit Logs** in the Admin Dashboard to verify successful incoming deliveries (`github_webhook_success`) or validation failures (`github_webhook_failed`).

### 4. Local Release Publish Checklist (Important)
When testing GitHub Releases locally, start your tunnel first before creating/publishing a release.

1. Start Laravel app on port `8080`.
2. Start ngrok first: `ngrok http 8080`.
3. Copy the HTTPS forwarding URL from ngrok, e.g. `https://<ngrok-id>.ngrok-free.app`.
4. Set `APP_URL` to that ngrok URL in `.env`, then run `php artisan optimize:clear`.
   - This ensures central domain routing recognizes the ngrok host.
5. Update GitHub Webhook **Payload URL** to: `https://<ngrok-id>.ngrok-free.app/github/webhook`.
6. Validate endpoint availability before publishing:
    - `GET /github/webhook` should return `405 Method Not Allowed` (not `404`).
7. Publish the GitHub release (or click **Redeliver** on release events).
8. Confirm delivery status is `2xx` in GitHub and verify sync output in Admin Audit Logs.
9. Supported release actions for sync are `published` and `released`.

If ngrok is not running before release publish/redelivery, GitHub deliveries can fail with `404` and release sync will be skipped.

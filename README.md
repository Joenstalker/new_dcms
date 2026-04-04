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

Open your `.env` file and configure your primary database credentials:
```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=new_dcms_db
DB_USERNAME=root
DB_PASSWORD=
```

### 3. Run Database Migrations & Seeders
Because this is a multi-tenant application using `stancl/tenancy`, you must migrate the central database first, then seed the initial admin data and subscription plans:
```bash
php artisan migrate:fresh --seed
```

### 4. Start the Application
You will need two terminals running simultaneously for local development.

**Terminal 1 (Laravel Server):**
```bash
php artisan serve --port=8080
```

**Terminal 2 (Vite Assets):**
```bash
npm run dev
```

You can now access the central landing page at `http://localhost:8080`.

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

Tenant access is routed dynamically via subdomains (e.g., `http://rhodsmile.localhost:8080`).

## 🛡️ Support
If you encounter any issues during setup, feel free to open an issue or consult the Laravel and Stancl/Tenancy documentation.

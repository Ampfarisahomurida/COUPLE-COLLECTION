# Couple Collection

A responsive storefront built with HTML/CSS/JavaScript, a PHP API, and MySQL. Customers create an account or sign in before entering the storefront, adding products to their basket, or checking out.

## Run with Docker

1. Copy `.env.example` to `.env` and set database and admin credentials. Keep `.env` private; it is ignored by Git. Set the configured MySQL connection details for the environment where the app runs. Add Stripe keys when card payments are ready.
2. From the repository root, run:

   ```powershell
   docker compose up --build -d
   docker compose exec php php backend/php/seed.php
   ```

3. Open `http://localhost:8080` and create a customer account or sign in. The owner portal is at `/backend/php/admin/dashboard.php`.

The seed command creates the initial catalog and only creates an administrator when valid `ADMIN_EMAIL` and `ADMIN_PASSWORD` values are set. Keep `.env` private.

The owner portal uses the configured administrator account and includes confirmed sales totals, order status and EFT payment counts, registered-customer totals, recent customer registrations, and product management. Customer registration is separate from owner access; new customers start by creating an account on the sign-in page.

## Run locally with PHP

Install PHP with PDO MySQL enabled and Composer, start MySQL, and copy `.env.example` to `.env`. Set `DB_HOST`, `DB_NAME`, `DB_USER`, and `DB_PASS` to a MySQL account that can create and use the store database. Set `ADMIN_EMAIL` to your owner login and choose a unique `ADMIN_PASSWORD` with at least 12 characters. The local PHP server and seed command load values from `.env`; operating-system environment variables take precedence. From the repository root:

```powershell
Set-Location backend\php
composer install
Set-Location ..\..
php backend\php\seed.php
php -S 127.0.0.1:8000 router.php
```

Open `http://127.0.0.1:8000`. The seed command creates the tables and the configured owner account. If MySQL reports access denied, correct the credentials in `.env` for the MySQL instance you started; do not put database credentials in PHP source files.

## Payments and launch configuration

Card checkout uses Stripe PaymentIntents. Set `STRIPE_SECRET`, `STRIPE_PUB`, and `STRIPE_WEBHOOK_SECRET` to the keys for the correct environment; do not commit live keys. The server calculates product prices and the configured flat `DELIVERY_FEE` (R99.99 by default), verifies successful Stripe payments, checks stock, and saves orders and order items in the database. EFT orders reserve stock, remain awaiting payment, and are not marked paid; the store must confirm payment before dispatch. Mobile wallet checkout is not offered because no wallet provider is integrated.

The age gate records a customer’s self-confirmation in the session; it is not third-party age assurance and may not meet the legal requirements in your market. Before accepting customers, the store owner still needs to configure compliant age assurance where required, live payment credentials, shipping and return terms, a customer support contact, privacy/terms disclosures, and production HTTPS/hosting. These depend on real business details and should not be replaced with invented information.

## Tests

With the local PHP server running, execute:

```powershell
php tests\run_tests.php
```

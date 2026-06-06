Couple Collection — E-Commerce Prototype
=====================================

This repository is an initial scaffold for the Couple Collection Online Shop.

Tech stack (initial):
- Frontend: static HTML/CSS/JS in frontend/public
- Backend: PHP (simple API) in backend/php
- Database: MySQL / MariaDB (sql/schema.sql)

Quick start (PHP built-in server):

```bash
cd "c:\Users\murid\OneDrive\Desktop\couple-collection-shop\backend\php"
php -S localhost:8000
```

Open frontend/public/index.html in a browser for the static frontend.

Next steps: implement product pages, admin dashboard, payment integration, and age verification.

Database setup
--------------
The project uses MySQL / MariaDB. Either import the provided SQL schema or run the PHP seed script which will create the database, tables, and sample products.

Option 1 — import SQL:

```bash
mysql -u root -p < sql/schema.sql
```

Option 2 — run the PHP seed script (simpler when using local PHP + DB credentials configured in `backend/php/config.php`):

```bash
php "c:\Users\murid\OneDrive\Desktop\couple-collection-shop\backend\php\seed.php"
```

After setting up the database, the backend API at `http://localhost:8000/api/products` will return products from the DB.

Admin access
------------
The seed script creates a default admin user when no users exist:

- Email: admin@example.com
- Password: ChangeMe123!

To access the admin dashboard, open:

```
file://c:/Users/murid/OneDrive/Desktop/couple-collection-shop/backend/php/admin/dashboard.php
```

Admin API endpoints (session-based):
- `POST /api/admin/login.php` — body: `{ "email": "...", "password": "..." }`
- `GET /api/admin/logout.php` — destroys session
- `GET /api/admin/products.php` — list products
- `POST /api/admin/products.php` — create product (admin only)
- `PUT /api/admin/products.php` — update product (admin only)
- `DELETE /api/admin/products.php` — delete product (admin only)

Security & password reset
-------------------------
- CSRF protection: admin write endpoints require a valid CSRF token supplied in the `X-CSRF-Token` header or as `csrf_token` in the body. The admin dashboard includes the token in forms.

Password reset (development flow):
- `POST /api/admin/request_reset.php` — body `{ "email": "..." }` returns a `reset_token` (in production this token should be emailed)
- `POST /api/admin/reset_password.php` — body `{ "token": "...", "password": "..." }` to complete the reset

Cart & Checkout (scaffold)
-------------------------
- Frontend: `frontend/public/cart.html`, `frontend/public/checkout.html` (uses localStorage for cart)
- API: `backend/php/api/checkout.php` — placeholder that records an order and returns a placeholder payment status

Payment, cart sync, uploads, tests & deploy
------------------------------------------
- Stripe scaffolding endpoints: `backend/php/api/payment/create_intent.php` and `backend/php/api/payment/webhook.php` (webhook logs to `backend/php/logs/payment_webhook.log`). Configure keys in `backend/php/config.php` or environment variables `STRIPE_SECRET` and `STRIPE_PUB`.
- Server-side cart API: `backend/php/api/cart.php` (session-backed). Frontend Add-to-cart buttons sync to this API and also persist to `localStorage`.
- Admin image uploads: `backend/php/api/admin/upload_image.php` saves uploaded images to `backend/php/uploads/`.
- Admin edit UI now uses a modal with image upload support.
- Basic test runner: `tests/run_tests.php` and deploy scripts `deploy.sh` and `deploy.ps1`.

Run tests and deploy (local)
```bash
# Run tests (ensure backend server running on localhost:8000)
php tests/run_tests.php

# Quick deploy (runs seed and prepares uploads)
bash deploy.sh
# or on Windows PowerShell
.\\deploy.ps1

Stripe setup
------------
This project now includes Stripe SDK integration. To use Stripe locally:

1. Install PHP dependencies (Composer) in the `backend/php` folder:

```bash
cd backend/php
composer install
```

2. Set environment variables (or edit `backend/php/config.php`) with your Stripe keys:

```bash
export STRIPE_SECRET=sk_live_...
export STRIPE_PUB=pk_live_...
export STRIPE_WEBHOOK_SECRET=whsec_...
```

3. Expose your local webhook endpoint to Stripe (during development) using a tool like `stripe listen` or `ngrok`, and configure the webhook secret.

4. The client checkout page uses Stripe.js; the server endpoint `backend/php/api/payment/create_intent.php` creates PaymentIntents using the secret key, and `backend/php/api/payment/webhook.php` verifies webhook signatures when `STRIPE_WEBHOOK_SECRET` is set.
```


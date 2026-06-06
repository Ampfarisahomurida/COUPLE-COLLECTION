<?php
// Database configuration (adjust for your environment)
define('DB_HOST', getenv('DB_HOST') ?: '127.0.0.1');
define('DB_NAME', getenv('DB_NAME') ?: 'couple_collection');
define('DB_USER', getenv('DB_USER') ?: 'root');
define('DB_PASS', getenv('DB_PASS') ?: '');

// Site settings
define('SITE_NAME', getenv('SITE_NAME') ?: 'Couple Collection');

// Payment provider placeholders (set your real keys in production)
define('STRIPE_SECRET_KEY', getenv('STRIPE_SECRET') ?: 'sk_test_replace_me');
define('STRIPE_PUBLISHABLE_KEY', getenv('STRIPE_PUB') ?: 'pk_test_replace_me');
define('STRIPE_WEBHOOK_SECRET', getenv('STRIPE_WEBHOOK_SECRET') ?: '');


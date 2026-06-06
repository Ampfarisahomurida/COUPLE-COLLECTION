<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../../config.php';
// Return publishable key for client-side Stripe.js
echo json_encode(['publishableKey'=>STRIPE_PUBLISHABLE_KEY]);

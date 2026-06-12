<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../../config.php';
// Return publishable key for client-side Stripe.js
$key = STRIPE_PUBLISHABLE_KEY;
if(!$key || $key === 'pk_test_replace_me' || strpos($key, 'pk_') !== 0){
  echo json_encode(['publishableKey'=>null,'cardEnabled'=>false]);
  exit;
}
echo json_encode(['publishableKey'=>$key,'cardEnabled'=>true]);

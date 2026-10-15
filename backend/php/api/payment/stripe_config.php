<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../../config.php';
// Return publishable key for client-side Stripe.js
$key = STRIPE_PUBLISHABLE_KEY;
if(!$key || $key === 'pk_test_replace_me' || strpos($key, 'pk_') !== 0 || strpos(STRIPE_SECRET_KEY, 'sk_') !== 0 || strpos(STRIPE_SECRET_KEY, 'replace_me') !== false){
  echo json_encode(['publishableKey'=>null,'cardEnabled'=>false,'deliveryFee'=>DELIVERY_FEE]);
  exit;
}
echo json_encode(['publishableKey'=>$key,'cardEnabled'=>true,'deliveryFee'=>DELIVERY_FEE]);

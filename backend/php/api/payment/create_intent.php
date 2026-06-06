<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../../config.php';

if($_SERVER['REQUEST_METHOD'] !== 'POST'){
  http_response_code(405);
  echo json_encode(['error'=>'Method not allowed']);
  exit;
}

$data = json_decode(file_get_contents('php://input'), true) ?: $_POST;
$amount = isset($data['amount']) ? (int)$data['amount'] : 0;
$currency = $data['currency'] ?? 'zar';

if($amount <= 0){ http_response_code(400); echo json_encode(['error'=>'Invalid amount']); exit; }

// Use Stripe PHP SDK
require_once __DIR__ . '/../../vendor/autoload.php';
\Stripe\Stripe::setApiKey(STRIPE_SECRET_KEY);

try{
  $pi = \Stripe\PaymentIntent::create([
    'amount' => $amount,
    'currency' => $currency,
    'payment_method_types' => ['card'],
    'description' => $data['description'] ?? null,
    'metadata' => $data['metadata'] ?? []
  ]);
  echo json_encode($pi);
}catch(Exception $e){
  http_response_code(500);
  echo json_encode(['error'=>'Stripe error','detail'=>$e->getMessage()]);
}

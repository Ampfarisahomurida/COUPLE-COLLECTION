<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../db.php';

// Simple placeholder checkout handler. In production integrate with payment provider SDKs.
if($_SERVER['REQUEST_METHOD'] !== 'POST'){
  http_response_code(405);
  echo json_encode(['error'=>'Method not allowed']);
  exit;
}

$data = json_decode(file_get_contents('php://input'), true);
if(empty($data['cart']) || empty($data['name']) || empty($data['email'])){
  http_response_code(400);
  echo json_encode(['error'=>'Missing fields']);
  exit;
}

$pdo = get_db();
// Create an order record (simplified)
$total = 0;
foreach($data['cart'] as $item) $total += ($item['price'] ?? 0) * ($item['qty'] ?? 1);
$ins = $pdo->prepare('INSERT INTO orders (user_id,total,status,shipping_address,discreet_shipping) VALUES (NULL,:total,:status,NULL,1)');
$ins->execute([':total'=>$total,':status'=>'pending']);
$orderId = $pdo->lastInsertId();

// Placeholder: simulate payment processing depending on method
switch($data['method'] ?? 'card'){
  case 'eft':
    $payment_status = 'awaiting_payment_eft';
    break;
  case 'wallet':
    $payment_status = 'pending_wallet';
    break;
  default:
    $payment_status = 'processing_card_placeholder';
}

echo json_encode(['ok'=>true,'order_id'=>$orderId,'payment_status'=>$payment_status]);

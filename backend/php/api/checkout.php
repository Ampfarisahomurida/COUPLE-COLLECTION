<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/../session.php';

if($_SERVER['REQUEST_METHOD'] !== 'POST'){
  http_response_code(405);
  echo json_encode(['error'=>'Method not allowed']);
  exit;
}

$data = json_decode(file_get_contents('php://input'), true) ?: $_POST;
if(empty($data['cart']) || empty($data['name']) || empty($data['email'])){
  http_response_code(400);
  echo json_encode(['error'=>'Missing fields']);
  exit;
}

if(!is_array($data['cart'])){
  http_response_code(400);
  echo json_encode(['error'=>'Invalid cart format']);
  exit;
}

$total = 0.0;
foreach($data['cart'] as $item){
  $price = isset($item['price']) ? floatval($item['price']) : 0.0;
  $qty = isset($item['qty']) ? intval($item['qty']) : 1;
  $total += $price * $qty;
}

$method = strtolower(trim($data['method'] ?? 'card'));
$status = 'pending';
if($method === 'eft'){
  $status = 'awaiting_payment_eft';
} elseif($method === 'wallet'){
  $status = 'pending_wallet';
} elseif($method === 'card'){
  $status = !empty($data['payment_intent_id']) ? 'paid' : 'processing_card';
}

$pdo = get_db();
$user_id = !empty($_SESSION['user_id']) ? (int)$_SESSION['user_id'] : null;
$shipping = $data['shipping_address'] ?? null;
$discreet = isset($data['discreet_shipping']) ? (int)$data['discreet_shipping'] : 1;

$ins = $pdo->prepare('INSERT INTO orders (user_id,total,status,shipping_address,discreet_shipping) VALUES (:user_id,:total,:status,:shipping,:discreet)');
$ins->execute([
  ':user_id' => $user_id,
  ':total' => $total,
  ':status' => $status,
  ':shipping' => $shipping,
  ':discreet' => $discreet,
]);
$orderId = $pdo->lastInsertId();

echo json_encode(['ok'=>true,'order_id'=>$orderId,'status'=>$status]);

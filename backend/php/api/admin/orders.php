<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../../db.php';
require_once __DIR__ . '/../../session.php';

if(empty($_SESSION['is_admin'])){
  http_response_code(401);
  echo json_encode(['error'=>'Authentication required']);
  exit;
}

$method = $_SERVER['REQUEST_METHOD'];
$pdo = get_db();

if($method === 'GET'){
  $stmt = $pdo->query("SELECT o.id, o.customer_name, o.customer_email, o.total, o.status, o.payment_method, o.shipping_address, o.created_at,
      (SELECT GROUP_CONCAT(CONCAT(oi.product_name, ' × ', oi.quantity) SEPARATOR ', ') FROM order_items oi WHERE oi.order_id = o.id) AS items
    FROM orders o ORDER BY o.created_at DESC LIMIT 100");
  echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
  exit;
}

if($method !== 'PATCH'){
  http_response_code(405);
  echo json_encode(['error'=>'Method not allowed']);
  exit;
}

$data = json_decode(file_get_contents('php://input'), true);
$token = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? ($data['csrf_token'] ?? null);
if(!verify_csrf_token($token)){
  http_response_code(403);
  echo json_encode(['error'=>'Invalid CSRF token']);
  exit;
}

$orderId = filter_var($data['id'] ?? null, FILTER_VALIDATE_INT);
$nextStatus = $data['status'] ?? '';
if(!$orderId){
  http_response_code(400);
  echo json_encode(['error'=>'Order ID is required']);
  exit;
}

$transitions = [
  'awaiting_payment_eft'=>['paid','cancelled'],
  'paid'=>['processing'],
  'processing'=>['shipped'],
  'shipped'=>['delivered'],
  'delivered'=>[],
  'cancelled'=>[]
];

try{
  $pdo->beginTransaction();
  $find = $pdo->prepare('SELECT status FROM orders WHERE id = :id FOR UPDATE');
  $find->execute([':id'=>$orderId]);
  $currentStatus = $find->fetchColumn();
  if($currentStatus === false){
    $pdo->rollBack();
    http_response_code(404);
    echo json_encode(['error'=>'Order not found']);
    exit;
  }
  if(!in_array($nextStatus, $transitions[$currentStatus] ?? [], true)){
    $pdo->rollBack();
    http_response_code(409);
    echo json_encode(['error'=>'That order status change is not allowed']);
    exit;
  }

  if($nextStatus === 'cancelled'){
    $items = $pdo->prepare('SELECT product_id, quantity FROM order_items WHERE order_id = :id');
    $items->execute([':id'=>$orderId]);
    $restoreStock = $pdo->prepare('UPDATE products SET stock = stock + :quantity WHERE id = :id');
    foreach($items->fetchAll(PDO::FETCH_ASSOC) as $item){
      $restoreStock->execute([':quantity'=>(int)$item['quantity'],':id'=>(int)$item['product_id']]);
    }
  }

  $update = $pdo->prepare('UPDATE orders SET status = :status WHERE id = :id');
  $update->execute([':status'=>$nextStatus,':id'=>$orderId]);
  $pdo->commit();
  echo json_encode(['ok'=>true,'status'=>$nextStatus]);
}catch(Throwable $e){
  if($pdo->inTransaction()) $pdo->rollBack();
  error_log('Admin order update failed: ' . $e->getMessage());
  http_response_code(500);
  echo json_encode(['error'=>'Could not update the order']);
}

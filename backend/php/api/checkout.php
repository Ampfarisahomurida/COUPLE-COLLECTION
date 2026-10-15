<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/../session.php';
require_once __DIR__ . '/../config.php';

if($_SERVER['REQUEST_METHOD'] !== 'POST'){
  http_response_code(405);
  echo json_encode(['error'=>'Method not allowed']);
  exit;
}
if(empty($_SESSION['user_id'])){
  http_response_code(401);
  echo json_encode(['error'=>'Sign in or create an account before placing an order']);
  exit;
}

$data = json_decode(file_get_contents('php://input'), true);
$items = $data['cart'] ?? null;
$name = trim((string)($data['name'] ?? ''));
$email = trim((string)($data['email'] ?? ''));
$shipping = trim((string)($data['shipping_address'] ?? ''));
$method = strtolower(trim((string)($data['method'] ?? '')));

if($name === '' || strlen($name) > 160 || !filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($email) > 255 || $shipping === '' || strlen($shipping) > 2000){
  http_response_code(400);
  echo json_encode(['error'=>'Enter a valid name, email and delivery address']);
  exit;
}
if(!is_array($items) || !$items || count($items) > 50){
  http_response_code(400);
  echo json_encode(['error'=>'Your cart is empty or invalid']);
  exit;
}
if(!in_array($method, ['card','eft'], true)){
  http_response_code(400);
  echo json_encode(['error'=>'Select a supported payment method']);
  exit;
}

$cart = [];
foreach($items as $item){
  $id = filter_var($item['id'] ?? null, FILTER_VALIDATE_INT);
  $qty = filter_var($item['qty'] ?? null, FILTER_VALIDATE_INT);
  if(!$id || !$qty || $qty > 10){
    http_response_code(400);
    echo json_encode(['error'=>'Invalid product or quantity']);
    exit;
  }
  $cart[$id] = ($cart[$id] ?? 0) + $qty;
  if($cart[$id] > 10){
    http_response_code(400);
    echo json_encode(['error'=>'A maximum of 10 of each product can be ordered']);
    exit;
  }
}

if($method === 'card' && empty($data['payment_intent_id'])){
  http_response_code(400);
  echo json_encode(['error'=>'Card payment has not been confirmed']);
  exit;
}
if($method === 'card' && !preg_match('/^pi_[A-Za-z0-9]+$/', (string)$data['payment_intent_id'])){
  http_response_code(400);
  echo json_encode(['error'=>'Invalid payment reference']);
  exit;
}

try{
  $pdo = get_db(false);
  $pdo->beginTransaction();
  $paymentReference = $method === 'card' ? trim((string)$data['payment_intent_id']) : null;
  if($paymentReference !== null){
    $previous = $pdo->prepare('SELECT id,status FROM orders WHERE payment_reference = :reference LIMIT 1');
    $previous->execute([':reference'=>$paymentReference]);
    $existingOrder = $previous->fetch(PDO::FETCH_ASSOC);
    if($existingOrder){
      $pdo->commit();
      echo json_encode(['ok'=>true,'order_id'=>$existingOrder['id'],'status'=>$existingOrder['status']]);
      exit;
    }
  }

  ksort($cart, SORT_NUMERIC);
  $select = $pdo->prepare('SELECT id, name, price, stock, age_restricted FROM products WHERE id = :id FOR UPDATE');
  $orderItems = [];
  $subtotalCents = 0;
  $canonicalCart = [];

  foreach($cart as $id=>$qty){
    $select->execute([':id'=>$id]);
    $product = $select->fetch(PDO::FETCH_ASSOC);
    if(!$product){
      $pdo->rollBack();
      http_response_code(409);
      echo json_encode(['error'=>'A product in your cart is no longer available']);
      exit;
    }
    if((int)$product['stock'] < $qty){
      $pdo->rollBack();
      http_response_code(409);
      echo json_encode(['error'=>$product['name'] . ' does not have enough stock']);
      exit;
    }
    if((int)$product['age_restricted'] === 1 && empty($_SESSION['age_verified'])){
      $pdo->rollBack();
      http_response_code(403);
      echo json_encode(['error'=>'Confirm that you are 18 or older before purchasing this item']);
      exit;
    }
    $unitCents = (int)round((float)$product['price'] * 100);
    $subtotalCents += $unitCents * $qty;
    $orderItems[] = [
      'id'=>(int)$product['id'],
      'name'=>$product['name'],
      'price'=>$unitCents / 100,
      'qty'=>$qty
    ];
    $canonicalCart[] = ['id'=>(int)$id,'qty'=>$qty];
  }

  $deliveryCents = (int)round(DELIVERY_FEE * 100);
  $totalCents = $subtotalCents + $deliveryCents;
  if($totalCents <= 0 || $totalCents > 99999999){
    $pdo->rollBack();
    http_response_code(400);
    echo json_encode(['error'=>'Order total is invalid']);
    exit;
  }

  $status = 'awaiting_payment_eft';
  if($method === 'card'){
    if(!is_file(__DIR__ . '/../vendor/autoload.php')){
      $pdo->rollBack();
      http_response_code(503);
      echo json_encode(['error'=>'Payment service is not installed']);
      exit;
    }
    require_once __DIR__ . '/../vendor/autoload.php';
    require_once __DIR__ . '/../config.php';
    if(strpos(STRIPE_SECRET_KEY, 'sk_') !== 0 || strpos(STRIPE_SECRET_KEY, 'replace_me') !== false){
      $pdo->rollBack();
      http_response_code(503);
      echo json_encode(['error'=>'Card payments are not configured']);
      exit;
    }

    \Stripe\Stripe::setApiKey(STRIPE_SECRET_KEY);
    $intent = \Stripe\PaymentIntent::retrieve($paymentReference);
    $intentCartHash = (string)($intent->metadata->cart_sha256 ?? '');
    $cartHash = hash('sha256', json_encode($canonicalCart));
    if($intent->status !== 'succeeded' || (int)$intent->amount !== $totalCents || strtolower($intent->currency) !== 'zar' || !hash_equals($cartHash, $intentCartHash)){
      $pdo->rollBack();
      http_response_code(402);
      echo json_encode(['error'=>'Payment could not be verified for this order']);
      exit;
    }
    $status = 'paid';
  }

  $userId = (int)$_SESSION['user_id'];
  $insertOrder = $pdo->prepare('INSERT INTO orders (user_id,total,status,shipping_address,discreet_shipping,customer_name,customer_email,payment_method,payment_reference,delivery_fee) VALUES (:user_id,:total,:status,:shipping,1,:name,:email,:method,:reference,:delivery_fee)');
  $insertOrder->execute([
    ':user_id'=>$userId,
    ':total'=>$totalCents / 100,
    ':status'=>$status,
    ':shipping'=>$shipping,
    ':name'=>$name,
    ':email'=>$email,
    ':method'=>$method,
    ':reference'=>$paymentReference,
    ':delivery_fee'=>$deliveryCents / 100
  ]);
  $orderId = $pdo->lastInsertId();

  $insertItem = $pdo->prepare('INSERT INTO order_items (order_id,product_id,product_name,unit_price,quantity) VALUES (:order_id,:product_id,:name,:price,:qty)');
  $decreaseStock = $pdo->prepare('UPDATE products SET stock = stock - :qty WHERE id = :id AND stock >= :qty');
  foreach($orderItems as $item){
    $insertItem->execute([
      ':order_id'=>$orderId,
      ':product_id'=>$item['id'],
      ':name'=>$item['name'],
      ':price'=>$item['price'],
      ':qty'=>$item['qty']
    ]);
    $decreaseStock->execute([':qty'=>$item['qty'],':id'=>$item['id']]);
    if($decreaseStock->rowCount() !== 1){
      throw new RuntimeException('Inventory changed while completing the order');
    }
  }

  if($userId !== null){
    $findCart = $pdo->prepare('SELECT id FROM carts WHERE user_id = :user_id LIMIT 1');
    $findCart->execute([':user_id'=>$userId]);
    $cartId = $findCart->fetchColumn();
    if($cartId !== false){
      $clearCart = $pdo->prepare('DELETE FROM cart_items WHERE cart_id = :cart_id');
      $clearCart->execute([':cart_id'=>$cartId]);
    }
  }

  $pdo->commit();
  unset($_SESSION['cart']);
  echo json_encode(['ok'=>true,'order_id'=>$orderId,'status'=>$status,'total'=>$totalCents / 100]);
}catch(\Stripe\Exception\ApiErrorException $e){
  if(isset($pdo) && $pdo->inTransaction()) $pdo->rollBack();
  error_log('Stripe payment verification failed: ' . $e->getMessage());
  http_response_code(502);
  echo json_encode(['error'=>'Payment verification is temporarily unavailable']);
}catch(Throwable $e){
  if(isset($pdo) && $pdo->inTransaction()) $pdo->rollBack();
  error_log('Checkout failed: ' . $e->getMessage());
  http_response_code(503);
  echo json_encode(['error'=>'Checkout is temporarily unavailable. Please try again; your cart has been kept.']);
}

<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../../config.php';
require_once __DIR__ . '/../../db.php';
require_once __DIR__ . '/../../session.php';

if($_SERVER['REQUEST_METHOD'] !== 'POST'){
  http_response_code(405);
  echo json_encode(['error'=>'Method not allowed']);
  exit;
}
if(empty($_SESSION['user_id'])){
  http_response_code(401);
  echo json_encode(['error'=>'Sign in or create an account before paying']);
  exit;
}

$data = json_decode(file_get_contents('php://input'), true);
$items = $data['cart'] ?? null;
if(!is_array($items) || !$items || count($items) > 50){
  http_response_code(400);
  echo json_encode(['error'=>'Your cart is empty or invalid']);
  exit;
}

if(!defined('STRIPE_SECRET_KEY') || strpos(STRIPE_SECRET_KEY, 'sk_') !== 0 || strpos(STRIPE_SECRET_KEY, 'replace_me') !== false){
  http_response_code(503);
  echo json_encode(['error'=>'Card payments are not configured']);
  exit;
}

if(!is_file(__DIR__ . '/../../vendor/autoload.php')){
  http_response_code(503);
  echo json_encode(['error'=>'Payment service is not installed']);
  exit;
}

try{
  $pdo = get_db(false);
  $subtotalCents = 0;
  $normalizedItems = [];
  $stmt = $pdo->prepare('SELECT id, name, price, stock, age_restricted FROM products WHERE id = :id');
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

  ksort($cart, SORT_NUMERIC);
  foreach($cart as $id=>$qty){
    $stmt->execute([':id'=>$id]);
    $product = $stmt->fetch(PDO::FETCH_ASSOC);
    if(!$product){
      http_response_code(400);
      echo json_encode(['error'=>'A product in your cart is no longer available']);
      exit;
    }
    if((int)$product['stock'] < $qty){
      http_response_code(409);
      echo json_encode(['error'=>$product['name'] . ' does not have enough stock']);
      exit;
    }
    if((int)$product['age_restricted'] === 1 && empty($_SESSION['age_verified'])){
      http_response_code(403);
      echo json_encode(['error'=>'Confirm that you are 18 or older before purchasing this item']);
      exit;
    }

    $unitCents = (int)round((float)$product['price'] * 100);
    $subtotalCents += $unitCents * $qty;
    $normalizedItems[] = ['id'=>(int)$id,'qty'=>$qty];
  }

  $deliveryCents = (int)round(DELIVERY_FEE * 100);
  $totalCents = $subtotalCents + $deliveryCents;
  if($totalCents <= 0 || $totalCents > 99999999){
    http_response_code(400);
    echo json_encode(['error'=>'Order total is invalid']);
    exit;
  }

  require_once __DIR__ . '/../../vendor/autoload.php';
  \Stripe\Stripe::setApiKey(STRIPE_SECRET_KEY);
  $intent = \Stripe\PaymentIntent::create([
    'amount'=>$totalCents,
    'currency'=>'zar',
    'payment_method_types'=>['card'],
    'description'=>'Order from Couple Collection',
    'metadata'=>[
      'cart_sha256'=>hash('sha256', json_encode($normalizedItems)),
      'delivery_fee_cents'=>(string)$deliveryCents
    ]
  ]);
  echo json_encode([
    'client_secret'=>$intent->client_secret,
    'amount'=>$totalCents,
    'currency'=>'zar'
  ]);
}catch(\Stripe\Exception\ApiErrorException $e){
  error_log('Stripe PaymentIntent creation failed: ' . $e->getMessage());
  http_response_code(502);
  echo json_encode(['error'=>'Payment provider could not start the payment']);
}catch(Throwable $e){
  error_log('PaymentIntent creation failed: ' . $e->getMessage());
  http_response_code(503);
  echo json_encode(['error'=>'Checkout is temporarily unavailable']);
}

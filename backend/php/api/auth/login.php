<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../../db.php';
require_once __DIR__ . '/../../session.php';

if($_SERVER['REQUEST_METHOD'] !== 'POST'){
  http_response_code(405); echo json_encode(['error'=>'Method not allowed']); exit;
}

$data = json_decode(file_get_contents('php://input'), true) ?: $_POST;
$email = strtolower(trim((string)($data['email'] ?? '')));
$password = (string)($data['password'] ?? '');
if(!verify_csrf_token($_SERVER['HTTP_X_CSRF_TOKEN'] ?? ($data['csrf_token'] ?? null))){
  http_response_code(403); echo json_encode(['error'=>'Please refresh the page and try again']); exit;
}
if(!filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($email) > 255 || $password === ''){
  http_response_code(400); echo json_encode(['error'=>'Enter a valid email address and password']); exit;
}

$pdo = get_db();
$stmt = $pdo->prepare('SELECT id,email,password_hash,is_admin FROM users WHERE email = :email LIMIT 1');
$stmt->execute([':email'=>$email]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);
if(!$user || !password_verify($password, $user['password_hash'])){ http_response_code(401); echo json_encode(['error'=>'Invalid credentials']); exit; }

// Set session for normal user
session_regenerate_id(true);
$_SESSION['user_id'] = (int)$user['id'];
$_SESSION['email'] = $user['email'];
if((int)$user['is_admin']){
  $_SESSION['is_admin'] = 1;
  $_SESSION['admin_id'] = (int)$user['id'];
  $_SESSION['admin_email'] = $user['email'];
}else{
  unset($_SESSION['is_admin'], $_SESSION['admin_id'], $_SESSION['admin_email']);
}

// Merge session cart into DB cart if present
if(!empty($_SESSION['cart'])){
  $cart = $_SESSION['cart'];
  // find or create cart for user
  $cstmt = $pdo->prepare('SELECT id FROM carts WHERE user_id = :uid LIMIT 1');
  $cstmt->execute([':uid'=>$user['id']]);
  $crow = $cstmt->fetch(PDO::FETCH_ASSOC);
  if($crow) $cart_id = $crow['id'];
  else{ $ins = $pdo->prepare('INSERT INTO carts (user_id) VALUES (:uid)'); $ins->execute([':uid'=>$user['id']]); $cart_id = $pdo->lastInsertId(); }

  // merge items: if product exists, increase qty
  foreach($cart as $it){
    $pid = $it['id']; $name = $it['name']; $price = $it['price']; $qty = $it['qty'];
    $is = $pdo->prepare('SELECT id,qty FROM cart_items WHERE cart_id = :cid AND product_id = :pid LIMIT 1');
    $is->execute([':cid'=>$cart_id,':pid'=>$pid]);
    $existing = $is->fetch(PDO::FETCH_ASSOC);
    if($existing){
      $u = $pdo->prepare('UPDATE cart_items SET qty = qty + :q WHERE id = :id');
      $u->execute([':q'=>$qty,':id'=>$existing['id']]);
    }else{
      $ins = $pdo->prepare('INSERT INTO cart_items (cart_id,product_id,name,price,qty) VALUES (:cid,:pid,:name,:price,:qty)');
      $ins->execute([':cid'=>$cart_id,':pid'=>$pid,':name'=>$name,':price'=>$price,':qty'=>$qty]);
    }
  }
  // clear session cart
  unset($_SESSION['cart']);
}

echo json_encode(['ok'=>true,'user_id'=>$_SESSION['user_id'],'email'=>$_SESSION['email'],'is_admin'=>!empty($_SESSION['is_admin'])]);

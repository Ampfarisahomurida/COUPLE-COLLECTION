<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../session.php';
require_once __DIR__ . '/../db.php';

$pdo = null;
try{ $pdo = get_db(false); }catch(Throwable $e){ $pdo = null; }

$data = json_decode(file_get_contents('php://input'), true) ?: $_POST;
$method = $_SERVER['REQUEST_METHOD'];

function session_cart_list(){ return $_SESSION['cart'] ?? []; }

// If user is authenticated, use DB-backed cart
if(!empty($_SESSION['user_id']) && $pdo){
  $user_id = (int)$_SESSION['user_id'];
  // find or create cart
  $cstmt = $pdo->prepare('SELECT id FROM carts WHERE user_id = :uid LIMIT 1');
  $cstmt->execute([':uid'=>$user_id]);
  $crow = $cstmt->fetch(PDO::FETCH_ASSOC);
  if($crow) $cart_id = $crow['id'];
  else{ $ins = $pdo->prepare('INSERT INTO carts (user_id) VALUES (:uid)'); $ins->execute([':uid'=>$user_id]); $cart_id = $pdo->lastInsertId(); }

  if($method === 'GET'){
    $it = $pdo->prepare('SELECT id,product_id,name,price,qty,options FROM cart_items WHERE cart_id = :cid');
    $it->execute([':cid'=>$cart_id]);
    $rows = $it->fetchAll(PDO::FETCH_ASSOC);
    echo json_encode($rows);
    exit;
  }

  if($method === 'POST'){
    if(empty($data['id']) || empty($data['name'])){ http_response_code(400); echo json_encode(['error'=>'id and name required']); exit; }
    $pid = $data['id']; $name = $data['name']; $price = $data['price'] ?? 0; $qty = isset($data['qty']) ? (int)$data['qty'] : 1;
    $is = $pdo->prepare('SELECT id,qty FROM cart_items WHERE cart_id = :cid AND product_id = :pid LIMIT 1');
    $is->execute([':cid'=>$cart_id,':pid'=>$pid]);
    $existing = $is->fetch(PDO::FETCH_ASSOC);
    if($existing){ $u = $pdo->prepare('UPDATE cart_items SET qty = qty + :q WHERE id = :id'); $u->execute([':q'=>$qty,':id'=>$existing['id']]); }
    else{ $ins = $pdo->prepare('INSERT INTO cart_items (cart_id,product_id,name,price,qty) VALUES (:cid,:pid,:name,:price,:qty)'); $ins->execute([':cid'=>$cart_id,':pid'=>$pid,':name'=>$name,':price'=>$price,':qty'=>$qty]); }
    // return current items
    $it = $pdo->prepare('SELECT id,product_id,name,price,qty FROM cart_items WHERE cart_id = :cid'); $it->execute([':cid'=>$cart_id]);
    echo json_encode($it->fetchAll(PDO::FETCH_ASSOC)); exit;
  }

  if($method === 'PUT'){
    if(empty($data['id'])){ http_response_code(400); echo json_encode(['error'=>'id required']); exit; }
    $pid = $data['id']; $qty = isset($data['qty']) ? (int)$data['qty'] : null;
    if($qty === null){ http_response_code(400); echo json_encode(['error'=>'qty required']); exit; }
    $u = $pdo->prepare('UPDATE cart_items SET qty = :qty WHERE cart_id = :cid AND product_id = :pid');
    $u->execute([':qty'=>$qty,':cid'=>$cart_id,':pid'=>$pid]);
    $it = $pdo->prepare('SELECT id,product_id,name,price,qty FROM cart_items WHERE cart_id = :cid'); $it->execute([':cid'=>$cart_id]);
    echo json_encode($it->fetchAll(PDO::FETCH_ASSOC)); exit;
  }

  if($method === 'DELETE'){
    if(empty($data['id'])){ http_response_code(400); echo json_encode(['error'=>'id required']); exit; }
    $pid = $data['id'];
    $d = $pdo->prepare('DELETE FROM cart_items WHERE cart_id = :cid AND product_id = :pid');
    $d->execute([':cid'=>$cart_id,':pid'=>$pid]);
    $it = $pdo->prepare('SELECT id,product_id,name,price,qty FROM cart_items WHERE cart_id = :cid'); $it->execute([':cid'=>$cart_id]);
    echo json_encode($it->fetchAll(PDO::FETCH_ASSOC)); exit;
  }

  http_response_code(405); echo json_encode(['error'=>'Method not allowed']); exit;
}

// Fallback: anonymous session-backed cart
if($_SERVER['REQUEST_METHOD'] === 'GET'){
  echo json_encode(session_cart_list()); exit;
}

$data = $data;
$method = $_SERVER['REQUEST_METHOD'];

if($method === 'POST'){
  if(empty($data['id']) || empty($data['name'])){ http_response_code(400); echo json_encode(['error'=>'id and name required']); exit; }
  if(empty($_SESSION['cart']) || !is_array($_SESSION['cart'])) $_SESSION['cart'] = [];
  $cart = &$_SESSION['cart'];
  $found = false;
  foreach($cart as &$it){ if($it['id']==$data['id']){ $it['qty'] = (int)($it['qty'] ?? 0) + (isset($data['qty']) ? (int)$data['qty'] : 1); $found=true; break; } }
  if(!$found){ $cart[] = ['id'=>$data['id'],'name'=>$data['name'],'price'=>floatval($data['price'] ?? 0),'qty'=>isset($data['qty'])?(int)$data['qty']:1]; }
  echo json_encode($cart); exit;
}

if($method === 'PUT'){
  if(empty($data['id'])){ http_response_code(400); echo json_encode(['error'=>'id required']); exit; }
  if(empty($_SESSION['cart']) || !is_array($_SESSION['cart'])) $_SESSION['cart'] = [];
  $cart = &$_SESSION['cart'];
  foreach($cart as &$it){ if($it['id']==$data['id']){ $it['qty'] = isset($data['qty']) ? (int)$data['qty'] : $it['qty']; break; } }
  $cart = array_values(array_filter($cart, function($it){ return (int)($it['qty'] ?? 0) > 0; }));
  $_SESSION['cart'] = $cart;
  echo json_encode($cart); exit;
}

if($method === 'DELETE'){
  if(empty($data['id'])){ http_response_code(400); echo json_encode(['error'=>'id required']); exit; }
  if(empty($_SESSION['cart']) || !is_array($_SESSION['cart'])) $_SESSION['cart'] = [];
  $cart = &$_SESSION['cart'];
  $cart = array_filter($cart, function($it) use($data){ return $it['id'] != $data['id']; });
  $_SESSION['cart'] = array_values($cart);
  echo json_encode($_SESSION['cart']); exit;
}

http_response_code(405);
echo json_encode(['error'=>'Method not allowed']);

<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../../db.php';
require_once __DIR__ . '/../../session.php';

$method = $_SERVER['REQUEST_METHOD'];
$pdo = get_db();

// Helper: admin-only actions
function require_admin(){
  if(empty($_SESSION['is_admin'])){
    http_response_code(401);
    echo json_encode(['error'=>'Authentication required']);
    exit;
  }
}

if($method === 'GET'){
  // list or single product
  if(isset($_GET['id'])){
    $id = (int)$_GET['id'];
    $stmt = $pdo->prepare('SELECT id, sku, name, short_description, description, category, price, stock, age_restricted FROM products WHERE id = :id');
    $stmt->execute([':id'=>$id]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    if($row) echo json_encode($row);
    else{ http_response_code(404); echo json_encode(['error'=>'Product not found']); }
    exit;
  }
  $stmt = $pdo->query('SELECT id, sku, name, short_description, category, price, stock, age_restricted FROM products ORDER BY id DESC');
  $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
  echo json_encode($rows);
  exit;
}

if($method === 'POST'){
  require_admin();
  $data = json_decode(file_get_contents('php://input'), true) ?: $_POST;
  // CSRF protection
  $token = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? ($data['csrf_token'] ?? null);
  if(!verify_csrf_token($token)){
    http_response_code(403);
    echo json_encode(['error'=>'Invalid CSRF token']);
    exit;
  }
  $stmt = $pdo->prepare('INSERT INTO products (sku,name,short_description,description,category,price,stock,age_restricted) VALUES (:sku,:name,:short,:desc,:cat,:price,:stock,:age)');
  $stmt->execute([
    ':sku'=>$data['sku'] ?? null,
    ':name'=>$data['name'] ?? 'Untitled',
    ':short'=>$data['short_description'] ?? null,
    ':desc'=>$data['description'] ?? null,
    ':cat'=>$data['category'] ?? null,
    ':price'=>isset($data['price']) ? $data['price'] : 0.00,
    ':stock'=>isset($data['stock']) ? $data['stock'] : 0,
    ':age'=>isset($data['age_restricted']) ? $data['age_restricted'] : 0,
  ]);
  echo json_encode(['ok'=>true,'id'=>$pdo->lastInsertId()]);
  exit;
}

if($method === 'PUT'){
  require_admin();
  $data = json_decode(file_get_contents('php://input'), true);
  $token = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? ($data['csrf_token'] ?? null);
  if(!verify_csrf_token($token)){
    http_response_code(403);
    echo json_encode(['error'=>'Invalid CSRF token']);
    exit;
  }
  if(empty($data['id'])){ http_response_code(400); echo json_encode(['error'=>'id required']); exit; }
  $stmt = $pdo->prepare('UPDATE products SET sku=:sku,name=:name,short_description=:short,description=:desc,category=:cat,price=:price,stock=:stock,age_restricted=:age WHERE id=:id');
  $stmt->execute([
    ':sku'=>$data['sku'] ?? null,
    ':name'=>$data['name'] ?? 'Untitled',
    ':short'=>$data['short_description'] ?? null,
    ':desc'=>$data['description'] ?? null,
    ':cat'=>$data['category'] ?? null,
    ':price'=>isset($data['price']) ? $data['price'] : 0.00,
    ':stock'=>isset($data['stock']) ? $data['stock'] : 0,
    ':age'=>isset($data['age_restricted']) ? $data['age_restricted'] : 0,
    ':id'=>$data['id']
  ]);
  echo json_encode(['ok'=>true]);
  exit;
}

if($method === 'DELETE'){
  require_admin();
  $data = json_decode(file_get_contents('php://input'), true) ?: $_POST;
  $token = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? ($data['csrf_token'] ?? null);
  if(!verify_csrf_token($token)){
    http_response_code(403);
    echo json_encode(['error'=>'Invalid CSRF token']);
    exit;
  }
  if(empty($data['id'])){ http_response_code(400); echo json_encode(['error'=>'id required']); exit; }
  $stmt = $pdo->prepare('DELETE FROM products WHERE id = :id');
  $stmt->execute([':id'=>$data['id']]);
  echo json_encode(['ok'=>true]);
  exit;
}

http_response_code(405);
echo json_encode(['error'=>'Method not allowed']);

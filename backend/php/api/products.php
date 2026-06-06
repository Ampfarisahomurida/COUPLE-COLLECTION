<?php
header('Content-Type: application/json');

// Try to use the database; if unavailable, fall back to static data.
$useDb = false;
try{
  require_once __DIR__ . '/../db.php';
  $pdo = get_db();
  $useDb = true;
}catch(Throwable $e){
  $useDb = false;
}

if($useDb){
  try{
    if(isset($_GET['id'])){
      $id = (int)$_GET['id'];
      $stmt = $pdo->prepare('SELECT id, sku, name, description, category, price, stock, age_restricted FROM products WHERE id = :id');
      $stmt->execute([':id'=>$id]);
      $row = $stmt->fetch(PDO::FETCH_ASSOC);
      if($row) echo json_encode($row);
      else{ http_response_code(404); echo json_encode(['error'=>'Product not found']); }
      exit;
    }

    $stmt = $pdo->query('SELECT id, sku, name, short_description, description, category, price, stock, age_restricted FROM products ORDER BY id ASC');
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo json_encode($rows);
    exit;
  }catch(Throwable $e){
    // fall through to static fallback
  }
}

// Static fallback data
$products = [
  ['id'=>1,'name'=>'Sensual Massage Oil','short_description'=>'Lightly scented massage oil for couples.','description'=>'A light, non-greasy massage oil with natural ingredients.','category'=>'Category B','age_restricted'=>false,'price'=>129.99],
  ['id'=>2,'name'=>'Discreet Intimacy Kit','short_description'=>'Tasteful kit with instructions and accessories.','description'=>'Curated intimacy kit designed for discretion and enjoyment.','category'=>'Category A','age_restricted'=>true,'price'=>499.00],
  ['id'=>3,'name'=>'Couples Game Card Pack','short_description'=>'Fun prompts to connect and laugh together.','description'=>'A deck of conversation and activity prompts for couples.','category'=>'Category B','age_restricted'=>false,'price'=>199.50]
];

// If an id is provided, return single product from fallback
if(isset($_GET['id'])){
  $id = (int)$_GET['id'];
  foreach($products as $p){
    if($p['id'] === $id){
      echo json_encode($p);
      exit;
    }
  }
  http_response_code(404);
  echo json_encode(['error'=>'Product not found']);
  exit;
}

echo json_encode($products);

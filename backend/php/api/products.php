<?php
header('Content-Type: application/json');

// Try to use the database; if unavailable, fall back to static data.
$useDb = false;
try{
  require_once __DIR__ . '/../db.php';
  $pdo = get_db(false);
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
  ['id'=>'date-night-kit','name'=>'Date Night Kit','short_description'=>'Everything needed for a memorable evening together.','description'=>'Everything needed for a memorable evening together.','category'=>'Date Night','age_restricted'=>false,'price'=>499.99,'image'=>'images/1348f53552fdf085e6f5a9f8c6bf1669.jpg','collections'=>['Date Night Kits','New Arrivals','Most Loved']],
  ['id'=>'massage-oil-set','name'=>'Massage Oil Set','short_description'=>'Relaxing aromatherapy oils for couples.','description'=>'Relaxing aromatherapy oils for couples.','category'=>'Wellness','age_restricted'=>false,'price'=>249.99,'image'=>'images/491e5580dbe04ce2e66f7f54a55563fa.jpg','collections'=>['Self-Care Sets','Most Loved']],
  ['id'=>'luxury-candle-set','name'=>'Luxury Candle Set','short_description'=>'Premium scented candles for romantic moments.','description'=>'Premium scented candles for romantic moments.','category'=>'Home','age_restricted'=>false,'price'=>199.99,'image'=>'images/candle.jpg','collections'=>['Date Night Kits','New Arrivals']],
  ['id'=>'chocolate-gift-box','name'=>'Chocolate Gift Box','short_description'=>'Delicious gourmet chocolates for sharing.','description'=>'Delicious gourmet chocolates for sharing.','category'=>'Gifts','age_restricted'=>false,'price'=>299.99,'image'=>'images/chocolate gift box.jpg','collections'=>['Date Night Kits','Most Loved']],
  ['id'=>'couples-wellness-box','name'=>'Couples Wellness Box','short_description'=>'Self-care essentials for relaxation and comfort.','description'=>'Self-care essentials for relaxation and comfort.','category'=>'Wellness','age_restricted'=>false,'price'=>599.99,'image'=>'images/Couples Wellness Box.jpg','collections'=>['Self-Care Sets','New Arrivals','Most Loved']],
  ['id'=>'anniversary-gift-collection','name'=>'Anniversary Gift Collection','short_description'=>'Elegant gifts designed for special celebrations.','description'=>'Elegant gifts designed for special celebrations.','category'=>'Gifts','age_restricted'=>false,'price'=>799.99,'image'=>'images/Anniversary Gift Collection.jpg','collections'=>['Most Loved','New Arrivals']],
  ['id'=>'matching-couple-mugs','name'=>'Matching Couple Mugs','short_description'=>'Stylish matching mugs for everyday moments.','description'=>'Stylish matching mugs for everyday moments.','category'=>'Home','age_restricted'=>false,'price'=>179.99,'image'=>'images/Matching Couple Mugs.jpg','collections'=>['New Arrivals']],
  ['id'=>'personalized-photo-frame','name'=>'Personalized Photo Frame','short_description'=>'Display your favorite memories together.','description'=>'Display your favorite memories together.','category'=>'Gifts','age_restricted'=>false,'price'=>349.99,'image'=>'images/Personalized Photo Frame.jpg','collections'=>['Most Loved']]
];

// If an id is provided, return single product from fallback
if(isset($_GET['id'])){
  $id = $_GET['id'];
  foreach($products as $p){
    if((string)$p['id'] === (string)$id){
      echo json_encode($p);
      exit;
    }
  }
  http_response_code(404);
  echo json_encode(['error'=>'Product not found']);
  exit;
}

echo json_encode($products);

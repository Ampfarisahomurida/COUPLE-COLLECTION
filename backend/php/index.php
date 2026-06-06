<?php
// Minimal router for API endpoints
$uri = $_SERVER['REQUEST_URI'];
if(strpos($uri, '/api/products') !== false){
  require __DIR__ . '/api/products.php';
  exit;
}

// fallback: simple message
header('Content-Type: text/plain');
echo "Couple Collection backend running. Use /api/products for sample data.";

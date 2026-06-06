<?php
// Basic test runner using curl to verify endpoints are reachable.
function http($url, $method='GET', $data=null){
  $ch = curl_init($url);
  curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
  curl_setopt($ch, CURLOPT_HEADER, true);
  if($method === 'POST') curl_setopt($ch, CURLOPT_POST, true);
  if($data && is_array($data)) curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
  if($data) curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
  $res = curl_exec($ch);
  $info = curl_getinfo($ch);
  curl_close($ch);
  return [$info['http_code'], $res];
}

$base = 'http://localhost:8000';
$endpoints = [
  '/api/products.php',
  '/api/cart.php',
  '/api/checkout.php'
];
foreach($endpoints as $e){
  list($code,$res) = http($base.$e);
  echo $e . ' -> ' . $code . "\n";
}

// Attempt to call admin login with seed admin (will only work if seed ran)
list($c,$r) = http($base.'/api/admin/login.php','POST', ['email'=>'admin@example.com','password'=>'ChangeMe123!']);
echo '/api/admin/login.php -> ' . $c . "\n";

echo "Tests completed.\n";

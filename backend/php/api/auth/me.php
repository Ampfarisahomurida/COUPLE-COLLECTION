<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../../session.php';

if($_SERVER['REQUEST_METHOD'] !== 'GET'){
  http_response_code(405);
  echo json_encode(['error'=>'Method not allowed']);
  exit;
}

$isCustomer = !empty($_SESSION['user_id']);
echo json_encode([
  'authenticated'=>$isCustomer,
  'email'=>$isCustomer ? ($_SESSION['email'] ?? '') : null,
  'is_admin'=>$isCustomer && !empty($_SESSION['is_admin']),
  'csrf_token'=>get_csrf_token()
]);

<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../../session.php';

if($_SERVER['REQUEST_METHOD'] !== 'POST'){
  http_response_code(405);
  echo json_encode(['error'=>'Method not allowed']);
  exit;
}

$data = json_decode(file_get_contents('php://input'), true) ?: [];
$token = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? ($data['csrf_token'] ?? null);
if(!verify_csrf_token($token)){
  http_response_code(403);
  echo json_encode(['error'=>'Please refresh the page and try again']);
  exit;
}

$_SESSION = [];
if(ini_get('session.use_cookies')){
  $params = session_get_cookie_params();
  setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'], $params['secure'], $params['httponly']);
}
session_destroy();
echo json_encode(['ok'=>true]);

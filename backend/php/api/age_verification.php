<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../session.php';

if($_SERVER['REQUEST_METHOD'] === 'GET'){
  echo json_encode(['verified'=>!empty($_SESSION['age_verified'])]);
  exit;
}

if($_SERVER['REQUEST_METHOD'] !== 'POST'){
  http_response_code(405);
  echo json_encode(['error'=>'Method not allowed']);
  exit;
}

$data = json_decode(file_get_contents('php://input'), true);
if(($data['confirm'] ?? false) !== true){
  http_response_code(400);
  echo json_encode(['error'=>'Age confirmation is required']);
  exit;
}

$_SESSION['age_verified'] = time();
echo json_encode(['verified'=>true]);

<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../../db.php';
require_once __DIR__ . '/../../session.php';

if($_SERVER['REQUEST_METHOD'] !== 'POST'){
  http_response_code(405);
  echo json_encode(['error'=>'Method not allowed']);
  exit;
}

$input = json_decode(file_get_contents('php://input'), true) ?: $_POST;
$email = isset($input['email']) ? trim($input['email']) : '';
$password = isset($input['password']) ? $input['password'] : '';

if(!$email || !$password){
  http_response_code(400);
  echo json_encode(['error'=>'Email and password required']);
  exit;
}

$pdo = get_db();
$stmt = $pdo->prepare('SELECT id, email, password_hash, is_admin FROM users WHERE email = :email LIMIT 1');
$stmt->execute([':email'=>$email]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if(!$user || !password_verify($password, $user['password_hash'])){
  http_response_code(401);
  echo json_encode(['error'=>'Invalid credentials']);
  exit;
}

// Only allow admin users
if(!(int)$user['is_admin']){
  http_response_code(403);
  echo json_encode(['error'=>'Not an admin']);
  exit;
}

// Set session
$_SESSION['admin_id'] = (int)$user['id'];
$_SESSION['admin_email'] = $user['email'];
$_SESSION['is_admin'] = 1;

echo json_encode(['ok'=>true,'email'=>$user['email']]);
